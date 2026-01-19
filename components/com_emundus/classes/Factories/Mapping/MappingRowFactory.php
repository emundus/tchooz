<?php

namespace Tchooz\Factories\Mapping;

use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Repositories\Mapping\MappingRowTransformationRepository;

class MappingRowFactory
{
	public function fromDbObjects(array $dbObjects): array
	{
		$rows = [];

		if (!empty($dbObjects))
		{
			$transformationsRepository = new MappingRowTransformationRepository();

			foreach ($dbObjects as $dbObject) {
				$rows[] = new MappingRowEntity(
					(int) $dbObject->id,
					(int) $dbObject->mapping_id,
					(int) $dbObject->order,
					ConditionTargetTypeEnum::from((string) $dbObject->source_type),
					(string) $dbObject->source_field,
					(string) $dbObject->target_field,
					$transformationsRepository->getByMappingRowId((int) $dbObject->id),
				);
			}
		}

		return $rows;
	}

	/**
	 * @param   string|array  $json
	 *
	 * @return MappingRowEntity|null
	 */
	public static function fromJson(string|array $json): ?MappingRowEntity
	{
		$entity = null;
		if (is_string($json))
		{
			$json = json_decode($json, true);
		}

		if (!empty($json))
		{
			$transformations = [];
			if (!empty($json['transformations']))
			{
				foreach ($json['transformations'] as $transformationJson)
				{
					$transformationEntity = MappingRowTransformationFactory::fromJson($transformationJson);
					if ($transformationEntity !== null)
					{
						$transformations[] = $transformationEntity;
					}
				}
			}

			$entity = new MappingRowEntity(
				isset($json['id']) ? (int) $json['id'] : 0,
				isset($json['mapping_id']) ? (int) $json['mapping_id'] : 0,
				isset($json['order']) ? (int) $json['order'] : 0,
				isset($json['source_type']) ? ConditionTargetTypeEnum::from((string) $json['source_type']) : ConditionTargetTypeEnum::FIELD,
				isset($json['source_field']) ? (string) $json['source_field'] : '',
				isset($json['target_field']) ? (string) $json['target_field'] : '',
				$transformations,
			);
		}

		return $entity;
	}
}