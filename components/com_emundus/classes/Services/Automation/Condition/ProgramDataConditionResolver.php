<?php

namespace Tchooz\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TableJoin;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_programmes', alias: 'esp')]
class ProgramDataConditionResolver implements ConditionTargetResolverInterface
{
	use TraitTable;

	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::PROGRAMDATA->value;
	}

	public function getAvailableFields(array $contextFilters): array
	{
		return [
			new ChoiceField('id', Text::_('COM_EMUNDUS_PROGRAM_FIELD'), $this->getProgramsList(), false, true),
			new StringField('label', Text::_('COM_EMUNDUS_PROGRAM_FIELD_LABEL'), false),
			new StringField('category', Text::_('COM_EMUNDUS_PROGRAM_FIELD_CATEGORY'), false),
			new StringField('code', Text::_('COM_EMUNDUS_PROGRAM_FIELD_CODE'), false),
		];
	}

	public function resolveValue(ActionTargetEntity $context, string $fieldName): mixed
	{
		$value = null;

		if (!empty($context->getFile()))
		{
			try {
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->createQuery();

				$query->select($db->quoteName('esp.' . $fieldName))
					->from($db->quoteName('#__emundus_setup_programmes', 'esp'))
					->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $db->quoteName('esc.training') . ' = ' . $db->quoteName('esp.code'))
					->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $db->quoteName('ecc.campaign_id') . ' = ' . $db->quoteName('esc.id'))
					->where($db->quoteName('ecc.fnum') . ' = ' . $db->quote($context->getFile()));
				$db->setQuery($query);
				$value = $db->loadResult();
			} catch (\Exception $e) {
				Log::add('Error fetching program data for fnum ' . $context->getFile() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.condition.resolver');
			}
		}

		return $value;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getProgramsList(): array
	{
		$options = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->select($db->quoteName(['id', 'label']))
			->from($db->quoteName('#__emundus_setup_programmes'))
			->order($db->quoteName('label') . ' ASC');

		$db->setQuery($query);
		$programs = $db->loadObjectList();

		foreach ($programs as $program)
		{
			$options[] = new ChoiceFieldValue($program->id, $program->label);
		}

		return $options;
	}

	public function searchable(): bool
	{
		return false;
	}

	public function getColumnsForField(string $field): array
	{
		return [$this->getTableAlias(self::class) . '.' .$field];
	}

	public function getJoins(string $field): array
	{
		return [];
	}

	/**
	 * @param   TargetTypeEnum  $targetType
	 *
	 * @return array<TableJoin>
	 */
	public function getJoinsToTable(TargetTypeEnum $targetType): array
	{
		$joins = [];

		switch($targetType)
		{
			case TargetTypeEnum::FILE:
				$campaignResolver = new CampaignDataConditionResolver();
				$joins = $campaignResolver->getJoinsToTable($targetType);

				$joins[] = new TableJoin(
					$this->getTableName(self::class),
					$this->getTableAlias(self::class),
					'code',
					'training',
					$campaignResolver->getTableAlias($campaignResolver::class),
					'INNER'
				);
				break;
			case TargetTypeEnum::USER:
				break;
		}

		return $joins;
	}

	public static function getAllowedActionTargetTypes(): array
	{
		return [
			TargetTypeEnum::FILE,
		];
	}
}