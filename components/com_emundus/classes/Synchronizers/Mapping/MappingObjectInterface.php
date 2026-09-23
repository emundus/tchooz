<?php

namespace Tchooz\Synchronizers\Mapping;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingResolution;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Synchronizers\MappingTransportInterface;

/**
 * Business knowledge of a single remote object of an interconnection (e.g. a HubSpot contact or
 * deal). Each implementation owns its own rules — existence, required fields, payload shaping,
 * external reference resolution, associations — so that adding a new object never requires touching
 * the transport (synchronizer). The transport carries requests; the object decides what to send.
 */
interface MappingObjectInterface
{
	/**
	 * Machine name of the object, matching MappingEntity::getTargetObject() (e.g. 'contact').
	 */
	public function getName(): string;

	/**
	 * Descriptive definition (label, route, methods, external reference, required fields,
	 * associations) consumed by the configuration UI and serialization.
	 */
	public function getDefinition(): SynchronizerMappingObjectDefinition;

	/**
	 * Ensure the mapping carries everything this object requires before execution.
	 *
	 * @throws \DomainException when a required field is missing.
	 */
	public function validate(MappingEntity $mapping): void;

	/**
	 * Decide whether the remote object already exists (PATCH) or must be created (POST), resolving
	 * the internal id and any external reference discovered during the lookup.
	 */
	public function resolveExistence(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): MappingResolution;

	/**
	 * Shape the mapped data into the payload expected by the remote API.
	 *
	 * @param   array  $mappedData  Data already converted by MappingService::getJsonFromMapping().
	 */
	public function buildPayload(array $mappedData, MappingEntity $mapping, ActionTargetEntity $context): array;

	/**
	 * Build the external reference to persist after a successful creation (POST), or null if this
	 * object does not track an external reference.
	 *
	 * @param   mixed  $response    Transport response of the creation request.
	 * @param   mixed  $internalId  Internal id resolved during existence resolution.
	 */
	public function buildCreatedReference(mixed $response, mixed $internalId, MappingEntity $mapping): ?ExternalReferenceEntity;

	/**
	 * @return array<\Tchooz\Entities\Mapping\AssociationDefinition>
	 */
	public function getAssociations(): array;
}
