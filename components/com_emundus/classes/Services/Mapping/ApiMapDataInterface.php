<?php

namespace Tchooz\Services\Mapping;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Enums\Api\ApiMethodEnum;

interface ApiMapDataInterface
{

	/**
	 * @param   MappingEntity       $mappingEntity
	 * @param   ActionTargetEntity  $context
	 *
	 * @return bool
	 */
	public function mapRequest(MappingEntity $mappingEntity, ActionTargetEntity $context, ApiMethodEnum $method): bool;

	/**
	 * @return array<SynchronizerMappingObjectDefinition>
	 */
	public function getMappingObjectsDefinitions(): array;
}