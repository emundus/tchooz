<?php

namespace Tchooz\Synchronizers\Hubspot\Objects;

use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingResolution;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\ObjectSearchInterface;

/**
 * HubSpot contact object: mapped one-to-one to an eMundus user (jos_emundus_users.user_id).
 *
 * Existence is resolved by the stored external reference first, then by a HubSpot search on email
 * (to avoid creating duplicates); a match switches the request to PATCH and yields a reference the
 * executor will persist.
 */
class ContactObject extends AbstractHubspotObject
{
	public function getName(): string
	{
		return 'contact';
	}

	protected function buildDefinition(): SynchronizerMappingObjectDefinition
	{
		return new SynchronizerMappingObjectDefinition(
			'contact',
			'COM_EMUNDUS_HUBSPOT_CONTACT_OBJECT_LABEL',
			'/crm/v3/objects/contacts',
			new ExternalReferenceEntity(0, 'jos_emundus_users.user_id', '', '', null, 'contacts', 'hs_object_id'),
			[ApiMethodEnum::GET, ApiMethodEnum::POST]
		);
	}

	public function resolveExistence(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): MappingResolution
	{
		$reference  = $this->getDefinition()->getExternalReference();
		$internalId = $context->getUserIdFromFile();

		$resolution = new MappingResolution(ApiMethodEnum::POST, $internalId);

		$found = $this->externalReferenceRepository->get([
			'sync_id'             => $mapping->getSynchronizerId(),
			'reference_object'    => $reference->getReferenceObject(),
			'reference_attribute' => $reference->getReferenceAttribute(),
			'intern_id'           => $internalId,
		]);

		if (!empty($found))
		{
			$resolution->method   = ApiMethodEnum::PATCH;
			$resolution->objectId = $found[0]->getReference();

			return $resolution;
		}

		// Not tracked yet: look the contact up on HubSpot by email to avoid a duplicate.
		if (!($transport instanceof ObjectSearchInterface))
		{
			return $resolution;
		}

		$emundusUser = $this->emundusUserRepository->getByUserId($internalId);

		if (empty($emundusUser))
		{
			return $resolution;
		}

		$hsContacts = $transport->searchObjects($reference->getReferenceObject(), ['email' => $emundusUser->getUser()->email]);

		if (!empty($hsContacts))
		{
			$resolution->method              = ApiMethodEnum::PATCH;
			$resolution->objectId            = $hsContacts[0]->id;
			$resolution->discoveredReference = new ExternalReferenceEntity(
				0,
				$reference->getColumn(),
				(string) $internalId,
				(string) $hsContacts[0]->id,
				$mapping->getSynchronizerId(),
				$reference->getReferenceObject(),
				$reference->getReferenceAttribute()
			);

			Log::add('Contact found in Hubspot with email ' . $emundusUser->getUser()->email . ' and Hubspot ID : ' . $hsContacts[0]->id, Log::INFO, 'com_emundus.hubspot');
		}

		return $resolution;
	}

	public function buildPayload(array $mappedData, MappingEntity $mapping, ActionTargetEntity $context): array
	{
		return $this->wrapProperties($mappedData);
	}
}
