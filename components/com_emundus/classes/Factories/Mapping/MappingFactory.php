<?php

namespace Tchooz\Factories\Mapping;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Factories\Field\ChoiceFieldFactory;
use Tchooz\Repositories\Mapping\MappingRowRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Services\Field\FieldWatcher;

class MappingFactory
{
	public function fromDbObjects(array $dbObjects): array
	{
		$entities = [];

		if (!empty($dbObjects))
		{
			$mappingRowRepository = new MappingRowRepository();
			foreach ($dbObjects as $dbObject) {
				$entities[] = new MappingEntity(
					(int) $dbObject->id,
					(string) $dbObject->label,
					(int) $dbObject->synchronizer_id,
					$dbObject->target_object ?? '',
					$mappingRowRepository->getByMappingId((int) $dbObject->id),
				);
			}
		}

		return $entities;
	}

	/**
	 * @param   string|array  $json
	 *
	 * @return MappingEntity|null
	 */
	public static function fromJson(string|array $json): ?MappingEntity
	{
		$entity = null;

		if (is_string($json))
		{
			$json = json_decode($json, true);
		}

		if (!empty($json))
		{
			$rows = [];

			if (isset($json['rows']) && is_array($json['rows']))
			{
				foreach ($json['rows'] as $rowJson)
				{
					$rowEntity = MappingRowFactory::fromJson($rowJson);
					if ($rowEntity !== null)
					{
						$rows[] = $rowEntity;
					}
				}
			}


			$entity = new MappingEntity(
				isset($json['id']) ? (int) $json['id'] : 0,
				isset($json['label']) ? (string) $json['label'] : '',
				isset($json['synchronizer_id']) ? (int) $json['synchronizer_id'] : 0,
				isset($json['target_object']) ? (string) $json['target_object'] : '',
				$rows
			);
		}

		return $entity;
	}

	/**
	 * @return array<Field>
	 */
	public function getFormFields(): array
	{
		$fields = [];

		$fields[] = new StringField('label', Text::_('COM_EMUNDUS_MAPPING_FIELD_LABEL_LABEL'), true);
		$fields[] = new ChoiceField('synchronizer_id', Text::_('COM_EMUNDUS_MAPPING_FIELD_SYNCHRONIZER_ID_LABEL'), ChoiceFieldFactory::makeOptions(new SynchronizerRepository(), 'getAll', ['filters' => [], 'limit' => 0]), true, false);

		try
		{
			$optionsProvider = new FieldOptionProvider('mapping', 'getMappingObjectsOptions', ['synchronizer_id']);
			$watcher = new FieldWatcher('synchronizer_id');
			$fields[] = (new ChoiceField('target_object', Text::_('COM_EMUNDUS_MAPPING_FIELD_TARGET_OBJECT_LABEL'), [], true, false))->setOptionsProvider($optionsProvider)->addWatcher($watcher);
		} catch (\Exception $e)
		{
			$fields[] = new StringField('target_object', Text::_('COM_EMUNDUS_MAPPING_FIELD_TARGET_OBJECT_LABEL'), true);
		}

		return $fields;
	}
}