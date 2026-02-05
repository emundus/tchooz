<?php

namespace Tchooz\Services\Automation\Condition;

use EmundusHelperFabrik;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Entities\Automation\TableJoin;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\Fabrik\ElementDatabaseJoinDisplayTypeEnum;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Repositories\Automation\AutomationRepository;
use Tchooz\Services\Automation\FieldTransformer;
use Tchooz\Traits\TraitTable;

require_once (JPATH_ROOT . '/components/com_emundus/helpers/events.php');

#[TableAttribute(table: '#__emundus_campaign_candidature', alias: 'ecc')]
class FormDataConditionResolver implements ConditionTargetResolverInterface
{
	use TraitTable;

	private array $formIds = [];

	/**
	 * @inheritDoc
	 */
	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::FORMDATA->value;
	}

	public function getFormIds(): array
	{
		$this->initializeFormIds();

		return $this->formIds;
	}

	/**
	 * @inheritDoc
	 */
	public function getAvailableFields(array $contextFilters): array
	{
		$fields = [];

		$this->initializeFormIds();

		if (empty($contextFilters['search']))
		{
			// todo: replace this to use "storedValues" only and remove automationId usage, to make it generic
			if (!empty($contextFilters['automationId'])) {
				// get the automation conditions on this target type to load by default already selected fields
				$automationId = (int)$contextFilters['automationId'];
				$automationRepository = new AutomationRepository();
				$automation = $automationRepository->getById($automationId);

				foreach ($automation->getConditionsGroups() as $conditionGroup) {
					assert($conditionGroup instanceof ConditionGroupEntity);

					foreach ($conditionGroup->getConditions() as $condition) {
						assert($condition instanceof ConditionEntity);

						if ($condition->getTargetType() === ConditionTargetTypeEnum::FORMDATA) {
							$fieldName = $condition->getField();
							if (!empty($fieldName)) {
								list($formId, $elementId) = explode('.', $fieldName);
								$elements = \EmundusHelperEvents::getFormElements((int)$formId, (int)$elementId, true, [], []);
								$element = $elements[0] ?? null;
								if (!empty($element)) {
									$field = FieldTransformer::transformFabrikElementIntoField($element);

									// When research is on, it means that not all options are loaded
									// But in case of loading condition, we need to get back the condition values stored
									// so we look for it if missing
									if ($field instanceof ChoiceField && !empty($field->getResearch()))
									{
										$missingOpts = [];
										$loadedOptIds = array_map(function ($choiceFieldValue) {
											return $choiceFieldValue->getValue();
										}, $field->getChoices());
										foreach ($condition->getValue() as $value)
										{
											if (!in_array($value, $loadedOptIds)) {
												$missingOpts[] = $value;
											}
										}

										if (!empty($missingOpts))
										{
											$newOptions = FieldTransformer::getElementOptions($element, '', $missingOpts);
											foreach ($newOptions as $newOption)
											{
												$field->addChoice($newOption);
											}
										}
									}

									$fields[] = $field;
								}
							}
						}
					}
				}
			}

			if (!empty($contextFilters['storedValues']))
			{
				$storedValues = $contextFilters['storedValues'];
				foreach ($storedValues as $storedValue) {
					list($formId, $elementId) = explode('.', $storedValue);
					$elements = \EmundusHelperEvents::getFormElements((int)$formId, (int)$elementId, true, [], []);
					$element = $elements[0] ?? null;
					if (!empty($element)) {
						$field = FieldTransformer::transformFabrikElementIntoField($element);

						$fields[] = $field;
					}
				}
			}

			$chunks = array_chunk($this->formIds, 5);
			$elements = \EmundusHelperFabrik::searchFabrikElements('', $chunks[0]);
		} else {
			$elements = \EmundusHelperFabrik::searchFabrikElements($contextFilters['search'], $this->formIds);
		}
		foreach ($elements as $element)
		{
			$fields[] = FieldTransformer::transformFabrikElementIntoField($element);
		}

		// make sure there are no duplicates
		$uniqueFields = [];
		foreach ($fields as $key => $field) {
			if (!in_array($field->getName(), $uniqueFields))
			{
				$uniqueFields[] = $field->getName();
			} else {
				unset($fields[$key]);
			}
		}

		return array_values($fields);
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	public function getAvailableElementsOptions(string $search = ''): array
	{
		$options = [];

		if (!empty($this->getFormIds()))
		{
			$elements = \EmundusHelperFabrik::searchFabrikElements($search, $this->formIds);

			foreach ($elements as $element)
			{
				$options[] = new ChoiceFieldValue(
					$element->form_id . '.' . $element->id,
					Text::_($element->label) . ' (' . Text::_($element->form_label) . ')'
				);
			}
		}

		return $options;
	}

	/**
	 * @param   string  $fieldName
	 * @param   string  $search
	 *
	 * @return array<ChoiceFieldValue>
	 */
	public function searchFieldValues(string $fieldName, string $search)
	{
		$values = [];

		if (!empty($fieldName) && !empty($search))
		{
			list($formId, $elementId) = explode('.', $fieldName);

			if (!empty($elementId) && !empty($formId))
			{
				$elements = \EmundusHelperEvents::getFormElements((int)$formId, (int)$elementId, true, [], []);
				$element = $elements[0] ?? null;

				if (!empty($element))
				{
					$values = FieldTransformer::getElementOptions($element, $search);
				}
			}
		}

		return $values;
	}

	private function initializeFormIds(): void
	{
		if (empty($this->formIds))
		{
			$this->formIds = \EmundusHelperFabrik::getFabrikFormsListIntendedToFiles();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function resolveValue(ActionTargetEntity $context, string $fieldName, ValueFormatEnum $format = ValueFormatEnum::RAW): mixed
	{
		$foundValue = null;

		if (empty($fieldName)) {
			throw new \Exception('Field cannot be empty.');
		}
		$fnum = $context->getFile();

		if (!empty($context->getFile()) || !empty($context->getUserId()))
		{
			list($formId, $elementId) = explode('.', $fieldName);

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$elements = \EmundusHelperEvents::getFormElements((int)$formId, (int)$elementId, true, [], []);
			$element = $elements[0] ?? null;

			if (!empty($element))
			{
				if (!class_exists('EmundusHelperFabrik')) {
					require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');
				}
				$helper = new EmundusHelperFabrik();

				if (!empty($context->getFile()))
				{
					$elementValue = $helper->getFabrikElementValue((array)$element, $context->getFile(), 0, $format);

					if (isset($elementValue[$element->id][$context->getFile()]['val'])) {
						$foundValue = $elementValue[$element->id][$context->getFile()]['val'];
					}
				}
				else
				{
					$elementValue = $helper->getFabrikElementValue((array)$element, null, 0, $format, $context->getUserId());

					if (isset($elementValue[$element->id][$context->getUserId()]['val'])) {
						$foundValue = $elementValue[$element->id][$context->getUserId()]['val'];
					}
				}

				if (!empty($foundValue))
				{
					switch($element->plugin)
					{
						case ElementPluginEnum::CHECKBOX->value:
						case ElementPluginEnum::DATABASEJOIN->value:
						case ElementPluginEnum::DROPDOWN->value:
							$params = json_decode($element->params, true);
							if ($element->plugin === ElementPluginEnum::CHECKBOX->value ||  (!empty($params['multiple']) && $params['multiple'] == 1))
							{
								$foundValue = json_decode($foundValue, true) ?? [];
							}

							if (is_string($foundValue) && str_contains($foundValue, ',')) {
								$foundValue = explode(',',$foundValue);
							}

							if (is_array($foundValue) && sizeof($foundValue) < 2) {
								$foundValue = $foundValue[0];
							}
							break;
						default:
							// no transformation needed
					}
				}
			} else {
				throw new \Exception('Field not found: ' . $fieldName);
			}
		} else {
			throw new \Exception('Cannot resolve form data value without file or user context.');
		}

		return $foundValue;
	}

	public function searchable(): bool
	{
		return true;
	}

	public function getColumnsForField(string $field): array
	{
		$columns = [];
		$element = $this->getElementFromFieldName($field);

		if (!empty($element)) {
			switch ($element->plugin) {
				case ElementPluginEnum::DATABASEJOIN->value:
					$elementParams = json_decode($element->params);

					if (ElementDatabaseJoinDisplayTypeEnum::isMultiSelect($elementParams->database_join_display_type)) {
						if (!empty($element->table_join)) {
							$columns[] = $element->table_join . '.' . $element->name;
						}
					} else {
						$columns[] = $element->db_table_name . '.' . $element->name;
					}
					break;
				default:
					$columns[] = $element->db_table_name . '.' . $element->name;

			}
		}

		return $columns;
	}

	public function getJoins(string $field): array
	{
		$joins = [];

		if (!empty($field))
		{
			$element = $this->getElementFromFieldName($field);
			if (!empty($element))
			{
				$elementParams = json_decode($element->params);
				if ($element->plugin === ElementPluginEnum::DATABASEJOIN->value && ElementDatabaseJoinDisplayTypeEnum::isMultiSelect($elementParams->database_join_display_type))
				{
					$joins[] = new TableJoin($element->db_table_name, $element->db_table_name, 'fnum', 'fnum', $this->getTableAlias(self::class), 'INNER');

					if (!empty($element->table_join))
					{
						$joins[] = new TableJoin($element->table_join, $element->table_join, 'parent_id', 'id', $element->db_table_name, 'LEFT');
					}
				}
				else
				{
					$joins[] = new TableJoin($element->db_table_name, $element->db_table_name, 'fnum', 'fnum', $this->getTableAlias(self::class), 'INNER');
				}
			}
		}

		return $joins;
	}

	public function getJoinsToTable(TargetTypeEnum $targetType): array
	{
		return [];
	}

	private function getElementFromFieldName(string $fieldName): ?object
	{
		$element = null;

		if (!empty($fieldName))
		{
			list($formId, $elementId) = explode('.', $fieldName);
			$elements = \EmundusHelperEvents::getFormElements((int)$formId, (int)$elementId, true, [], []);
			$element = $elements[0] ?? null;
		}

		return $element;
	}

	public static function getAllowedActionTargetTypes(): array
	{
		return [
			TargetTypeEnum::FILE,
		];
	}
}