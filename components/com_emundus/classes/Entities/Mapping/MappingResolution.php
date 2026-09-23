<?php

namespace Tchooz\Entities\Mapping;

use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Enums\Api\ApiMethodEnum;

/**
 * Result of a mapping object's existence resolution: tells the executor which HTTP method to use
 * (POST to create vs PATCH to update), the remote object id to patch, the resolved internal id used
 * for the external reference, and any external reference discovered during resolution that the
 * executor must persist.
 */
final class MappingResolution
{
	public function __construct(
		public ApiMethodEnum $method,
		public mixed $internalId = null,
		public ?string $objectId = null,
		public ?ExternalReferenceEntity $discoveredReference = null
	) {}
}
