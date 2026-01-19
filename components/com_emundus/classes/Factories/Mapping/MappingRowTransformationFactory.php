<?php

namespace Tchooz\Factories\Mapping;

use Tchooz\Entities\Mapping\MappingTransformEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;

class MappingRowTransformationFactory
{
	/**
	 * @return array<MappingTransformEntity>
	 */
	public function fromDbObjects(array $dbObjects): array
	{
		$transformations = [];

		if (!empty($dbObjects))
		{
			foreach ($dbObjects as $dbObject) {
				$transformations[] = new MappingTransformEntity(
					(int) $dbObject->id,
					(int) $dbObject->mapping_row_id,
					(int) $dbObject->order,
					MappingTransformersEnum::from($dbObject->type),
					json_decode($dbObject->parameters, true)
				);
			}
		}

		return $transformations;
	}

	/**
	 * @param   string|array  $json
	 *
	 * @return MappingTransformEntity|null
	 */
	public static function fromJson(string|array $json): ?MappingTransformEntity
	{
		$entity = null;
		if (is_string($json))
		{
			$json = json_decode($json, true);
		}

		if (!empty($json))
		{
			if (!isset($json['type']))
			{
				return null;
			}

			$entity = new MappingTransformEntity(
				isset($json['id']) ? (int) $json['id'] : 0,
				isset($json['mapping_row_id']) ? (int) $json['mapping_row_id'] : 0,
				isset($json['order']) ? (int) $json['order'] : 0,
				MappingTransformersEnum::from($json['type']),
				isset($json['parameters']) ? (array) $json['parameters'] : []
			);
		}

		return $entity;
	}
}