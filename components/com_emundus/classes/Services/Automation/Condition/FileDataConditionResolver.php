<?php

namespace Tchooz\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TableJoin;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Entities\Fields\DateField;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Traits\TraitAutomatedTask;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_campaign_candidature', alias: 'ecc')]
class FileDataConditionResolver implements ConditionTargetResolverInterface
{
	use TraitTable;
	use TraitAutomatedTask;

	private array $statusChoices = [];

	private array $tagsChoices = [];

	/**
	 * @inheritDoc
	 */
	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::FILEDATA->value;
	}

	/**
	 * @inheritDoc
	 */
	public static function getAllowedActionTargetTypes(): array
	{
		return [
			TargetTypeEnum::FILE,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getAvailableFields(array $contextFilters): array
	{
		$parameters =  [
			new StringField('fnum', Text::_('COM_EMUNDUS_FNUM'), false),
			new StringField('short_reference', Text::_('COM_EMUNDUS_SHORT_REFERENCE'), false),
			new ChoiceField('status', Text::_('COM_EMUNDUS_ACCESS_STATUS'), $this->getStatusChoices(), false, true),
			new ChoiceField('id_tag', Text::_('COM_EMUNDUS_TAGS'), $this->getTagsChoices(), false, true),
			new DateField('date_time', Text::_('APPLICATION_CREATION_DATE'), false)
		];

		$addonRepository = new AddonRepository();
		$publicAddon = $addonRepository->getByName(AddonEnum::PUBLIC_SESSION->value);
		if ($publicAddon->isActivated())
		{
			$parameters[] = new YesnoField('public', Text::_('COM_EMUNDUS_IS_PUBLIC'));
		}

		$anonymousAddon = $addonRepository->getByName(AddonEnum::ANONYMOUS->value);
		if ($anonymousAddon->isActivated())
		{
			$parameters[] = new YesnoField('anonymous', Text::_('COM_EMUNDUS_IS_ANONYMOUS'));
		}

		$customReferenceAddon = $addonRepository->getByName(AddonEnum::CUSTOM_REFERENCE_FORMAT->value);
		if ($customReferenceAddon->isActivated())
		{
			$parameters[] = new StringField('custom_reference', Text::_('COM_EMUNDUS_REFERENCE_CUSTOM'), false);
		}

		return $parameters;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveValue(ActionTargetEntity $context, string $fieldName, ValueFormatEnum $format = ValueFormatEnum::RAW): mixed
	{
		$value = null;

		$fnum = $context->getFile();

		if (!empty($fnum))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			switch ($fieldName)
			{
				case 'id_tag':
					$query->select('DISTINCT ' . $db->quoteName('id_tag'))
						->from($db->quoteName('#__emundus_tag_assoc'))
						->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
					$db->setQuery($query);
					$value = $db->loadColumn();
					break;
				case 'custom_reference':
					$query->select($db->quoteName('eir.reference'))
						->from($db->quoteName('#__emundus_internal_reference', 'eir'))
						->innerJoin($db->quoteName($this->getTableName(self::class), 'ecc') . ' ON ' . $db->quoteName('ecc.id') . ' = ' . $db->quoteName('eir.ccid'))
						->where($db->quoteName('ecc.fnum') . ' = ' . $db->quote($fnum))
						->where($db->quoteName('eir.active') . ' = 1');
					$db->setQuery($query);
					$value = $db->loadResult();
					break;
				default:
					$query->select($db->quoteName($fieldName))
						->from($db->quoteName($this->getTableName(self::class)))
						->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
					$db->setQuery($query);
					$value = $db->loadResult();
					break;
			}
		}

		return $value;
	}

	public function getColumnsForField(string $field): array
	{
		$columns = [];

		switch ($field)
		{
			case 'id_tag':
				$columns[] =  'eta.id_tag';
				break;
			case 'custom_reference':
				$columns[] = 'eir.reference';
				break;
			default:
				$columns[] = $this->getTableAlias(self::class) . '.' . $field;
				break;
		}

		return $columns;
	}

	/**
	 * @inheritDoc
	 */
	public function getJoins(string $field): array
	{
		$joins = [];

		switch ($field)
		{
			case 'id_tag':
				$joins[] = new TableJoin(
					'#__emundus_tag_assoc',
					'eta',
					'fnum',
					'fnum',
					$this->getTableAlias(self::class),
					'INNER'
				);
				break;
			case 'custom_reference':
				// LEFT so that files without a custom reference still match "is empty" conditions
				$joins[] = new TableJoin(
					'#__emundus_internal_reference',
					'eir',
					'ccid',
					'id',
					$this->getTableAlias(self::class),
					'LEFT',
					'eir.active = 1'
				);
				break;
			default:
				// No joins needed
				break;
		}

		return $joins;
	}

	/**
	 * @inheritDoc
	 */
	public function getJoinsToTable(TargetTypeEnum $targetType): array
	{
		return [];
	}

	public function searchable(): bool
	{
		return false;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getStatusChoices(): array
	{
		if (!empty($this->statusChoices))
		{
			return $this->statusChoices;
		} else {
			if (!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files = new \EmundusModelFiles();
			$states  = $m_files->getAllStatus($this->getAutomatedTaskUserId());

			$choices = [];
			foreach ($states as $state)
			{
				$choices[] = new ChoiceFieldValue($state['step'], $state['value']);
			}

			$this->statusChoices = $choices;
		}

		return $this->statusChoices;
	}

	/**
	 *
	 * @return ChoiceFieldValue[]
	 */
	private function getTagsChoices(): array
	{
		if (empty($this->tagsChoices)) {
			if (!class_exists('EmundusModelSettings'))
			{
				require_once JPATH_ROOT.'/components/com_emundus/models/settings.php';
			}
			$m_settings = new \EmundusModelSettings();
			$tags = $m_settings->getTags();

			foreach ($tags as $tag) {
				$this->tagsChoices[] = new ChoiceFieldValue($tag->id, $tag->label);
			}
		}

		return $this->tagsChoices;
	}
}