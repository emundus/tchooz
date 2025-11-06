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
use Tchooz\Entities\Fields\DateField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Services\Automation\FieldTransformer;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_campaigns', alias: 'esc')]
class CampaignDataConditionResolver implements ConditionTargetResolverInterface
{
	use TraitTable;

	public const DEFAULT_TABLE_FIELDS = [
		'id',
		'label',
		'description',
		'short_description',
		'year',
		'start_date',
		'end_date',
		'published',
		'training'
	];

	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::CAMPAIGNDATA->value;
	}

	public function getAvailableFields(array $contextFilters): array
	{
		$fields = [
			new ChoiceField('id', Text::_('COM_EMUNDUS_CAMPAIGN_FIELD'), $this->getCampaignsList(), false, true),
			new StringField('label', Text::_('COM_EMUNDUS_CAMPAIGN_FIELD_LABEL'), false),
			new StringField('description', Text::_('COM_EMUNDUS_CAMPAIGN_FIELD_DESCRIPTION'), false),
			new StringField('short_description', Text::_('COM_EMUNDUS_CAMPAIGN_FIELD_SHORT_DESCRIPTION'), false),
			new ChoiceField('year', Text::_('COM_EMUNDUS_CAMPAIGN_FIELD_YEAR'), $this->getYearsList(), false, true),
			new DateField('start_date', Text::_('COM_EMUNDUS_CAMPAIGN_FIELD_START_DATE'), false),
			new DateField('end_date', Text::_('COM_EMUNDUS_CAMPAIGN_FIELD_END_DATE'), false),
			new YesnoField('published', Text::_('COM_EMUNDUS_CAMPAIGN_FIELD_PUBLISHED')),
		];

		if (!class_exists('EmundusModelCampaign')) {
			require_once JPATH_ROOT . '/components/com_emundus/models/campaign.php';
		}
		$campaignModel = new \EmundusModelCampaign();
		$campaignMoreForm = $campaignModel->getCampaignMoreForm();
		if (!empty($campaignMoreForm) && !empty($campaignMoreForm['elements']))
		{
			foreach ($campaignMoreForm['elements'] as $element)
			{
				$formElements = \EmundusHelperEvents::getFormElements($campaignMoreForm['form_id'], $element['id']);
				$formElement = $formElements[0] ?? null;

				$field = FieldTransformer::transformFabrikElementIntoField($formElement);
				if ($field !== null)
				{
					$field->setName($element['name']);
					$fields[] = $field;
				}
			}
		}

		return $fields;
	}

	/**
	 * @param   ActionTargetEntity  $context  Context only has one file when triggered by runAutomations
	 * @param   string              $fieldName
	 *
	 * @return mixed
	 */
	public function resolveValue(ActionTargetEntity $context, string $fieldName): mixed
	{
		$value = null;

		if (!empty($context->getFile()))
		{
			$fnum = $context->getFile();

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			if (in_array($fieldName, self::DEFAULT_TABLE_FIELDS))
			{
				try {
					$query->select($db->quoteName('esc.' . $fieldName))
						->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
						->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $db->quoteName('ecc.campaign_id') . ' = ' . $db->quoteName('esc.id'))
						->where($db->quoteName('ecc.fnum') . ' = ' . $db->quote($fnum));
					$db->setQuery($query);
					$value = $db->loadResult();
				}
				catch (\Exception $e) {
					Log::add('Error fetching campaign data for fnum ' . $fnum . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.condition.resolver');
				}
			}
			else
			{
				try {
					$query->select($db->quoteName('escm.' . $fieldName))
						->from($db->quoteName('#__emundus_setup_campaigns_more', 'escm'))
						->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $db->quoteName('ecc.campaign_id') . ' = ' . $db->quoteName('escm.campaign_id'))
						->where($db->quoteName('ecc.fnum') . ' = ' . $db->quote($fnum));
					$db->setQuery($query);
					$value = $db->loadResult();
				}
				catch (\Exception $e)
				{
					Log::add('Error fetching campaign custom field data for fnum ' . $fnum . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.condition.resolver');
				}
			}
		}

		return $value;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getCampaignsList(): array
	{
		$options = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();
		$query->select('id, label, year')
			->from($db->quoteName('#__emundus_setup_campaigns'))
			->order('year DESC, label ASC');

		$db->setQuery($query);
		$campaigns = $db->loadObjectList();

		foreach ($campaigns as $campaign)
		{
			$options[] = new ChoiceFieldValue($campaign->id, $campaign->label . ' (' . $campaign->year . ')');
		}

		return $options;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getYearsList(): array
	{
		$options = [];

		try {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('DISTINCT year')
				->from($db->quoteName('#__emundus_setup_campaigns'))
				->where('year IS NOT NULL')
				->andWhere('published = 1')
				->order('year DESC');
			$db->setQuery($query);
			$years = $db->loadColumn();

			foreach ($years as $year) {
				$options[] = new ChoiceFieldValue($year, $year);
			}
		} catch (\Exception $e) {
			// TODO: Log the error or handle it as needed
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

	/**
	 * @return array<TableJoin>
	 */
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

		switch ($targetType) {
			case TargetTypeEnum::FILE:
				$joins = [
					new TableJoin($this->getTableName(self::class), $this->getTableAlias(self::class), 'id', 'campaign_id', $targetType->getTableAlias(), 'INNER'),
				];
				break;
			case TargetTypeEnum::USER:
				$joins = [
					new TableJoin(TargetTypeEnum::FILE->getTable(), TargetTypeEnum::FILE->getTableAlias(), 'applicant_id', 'id', $targetType->getTableAlias(), 'INNER'),
					new TableJoin($this->getTableName(self::class), $this->getTableAlias(self::class), 'id', 'campaign_id', TargetTypeEnum::FILE->getTableAlias(), 'INNER'),
				];
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