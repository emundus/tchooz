<?php

namespace Tchooz\Synchronizers\Mapping;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Synchronizers\MappingTransportInterface;

/**
 * Optional capability for mapping objects whose synchronization is a multi-step choreography that
 * does not fit the generic executor flow (single existence-check → POST/PATCH).
 *
 * When a resolved object implements this, the MappingExecutor delegates the whole run to it instead
 * of driving the generic flow. The object owns its own sequence of transport calls (search, create,
 * update…), while the transport stays ignorant of objects and routes.
 *
 * Example: the Sofis vendor (INT-01) — search by SIRET, then conditionally create/update the vendor
 * and its bank account.
 */
interface SelfExecutingMappingObject
{
	/**
	 * Run the full synchronization for this object.
	 *
	 * @return bool True on success.
	 * @throws \Exception on an unrecoverable error (surfaced to the caller / action log).
	 */
	public function execute(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): bool;
}
