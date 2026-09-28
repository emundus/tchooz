<?php

namespace Tchooz\Synchronizers\Mapping;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\ObjectSearchInterface;

/**
 * Optional mapping-object capability: after a remote object is created, associate it with other
 * remote objects (e.g. link a HubSpot deal to its contact).
 *
 * The association mechanics (routes, payload) are connector-specific, so they live on the mapping
 * object rather than the transport. The executor drives the loop and calls this only when the
 * object implements it and declares associations. Associations may need to look up related objects,
 * hence the transport must also provide the search capability.
 */
interface SupportsAssociationsInterface
{
	public function applyAssociations(
		ExternalReferenceEntity $createdReference,
		mixed $internalId,
		ActionTargetEntity $context,
		MappingTransportInterface&ObjectSearchInterface $transport
	): bool;
}
