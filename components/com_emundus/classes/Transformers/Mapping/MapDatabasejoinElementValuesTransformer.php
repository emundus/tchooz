<?php

namespace Tchooz\Transformers\Mapping;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Field\FieldEventsEnum;
use Tchooz\Enums\Field\FieldWatcherActionEnum;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Services\Field\FieldWatcher;

class MapDatabasejoinElementValuesTransformer extends MappingTranformer
{
	private ?int $formId = null;
	private ?int $elementId = null;

	public function __construct()
	{
		$columnValueField = (new ChoiceField('column', Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_DATABASEJOIN_ELEMENT_VALUES_PARAMETER_COLUMN_VALUE_LABEL'), [], true, false))->setOptionsProvider(
			new FieldOptionProvider('mapping', 'getDatabaseJoinElementColumnsOptions', ['source_type', 'source_field'])
		)->addWatcher(new FieldWatcher('source_field', [FieldEventsEnum::ON_CHANGE], FieldWatcherActionEnum::RELOAD));

		$parameters = [$columnValueField];

		parent::__construct(MappingTransformersEnum::MAP_DATABASEJOIN_ELEMENT_VALUES, $parameters);
	}

	/**
	 * @inheritDoc
	 */
	public function transform(mixed $value): mixed
	{
		if (empty($this->getFormId()) || empty($this->getElementId()))
		{
			if (!$this->isExecutedWith(MappingRowEntity::class))
			{
				return $value;
			}

			$mappingRows = $this->getWithOfType(MappingRowEntity::class);
			$mappingRow  = $mappingRows[0];

			if (empty($mappingRow) || $mappingRow->getSourceType() !== ConditionTargetTypeEnum::FORMDATA)
			{
				if ($mappingRow->getSourceType() === ConditionTargetTypeEnum::ALIASDATA)
				{
					if (!class_exists('\EmundusHelperFabrik'))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
					}
					$elements = \EmundusHelperFabrik::getElementsByAlias($mappingRow->getSourceField());

					if (!empty($elements))
					{
						$element = $elements[0];
						$formId = $element->form_id;
						$elementId = $element->id;
					}
					else
					{
						return $value;
					}
				}
			}
			else
			{
				list($formId, $elementId) = explode('.', $mappingRow->getSourceField() ?? '');
			}
		}
		else
		{
			$formId    = $this->getFormId();
			$elementId = $this->getElementId();
		}

		if (!empty($formId) && !empty($elementId))
		{
			$elements = \EmundusHelperEvents::getFormElements($formId, $elementId);
			$element  = $elements[0] ?? null;
			if (!empty($element) && $element->plugin === ElementPluginEnum::DATABASEJOIN->value)
			{
				// get the stored value column, the table and try to select the column
				$column           = $this->getParameterValue('column');
				$params           = json_decode($element->params, true);
				$table            = $params['join_db_name'] ?? '';
				$identifierColumn = $params['join_key_column'] ?? '';

				if (!empty($column) && !empty($table) && !empty($identifierColumn))
				{
					$db    = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->createQuery()
						->select($db->quoteName($column))
						->from($db->quoteName($table))
						->where($db->quoteName($identifierColumn) . ' = ' . $db->quote($value));

					try {
						$db->setQuery($query);
						$result = $db->loadResult();

						if ($result !== null)
						{
							$value = $result;
						}
					}
					catch (\Exception $e)
					{
						Log::add('Error executing database query in MapDatabasejoinElementValuesTransformer: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
					}
				}
			}
		}


		return $value;
	}

	public function getFormId(): ?int
	{
		return $this->formId;
	}

	public function setFormId(int $formId): self
	{
		$this->formId = $formId;

		return $this;
	}

	public function getElementId(): ?int
	{
		return $this->elementId;
	}

	public function setElementId(int $elementId): self
	{
		$this->elementId = $elementId;

		return $this;
	}

	public function reset(): self
	{
		parent::reset();
		$this->formId = null;
		$this->elementId = null;

		return $this;
	}
}