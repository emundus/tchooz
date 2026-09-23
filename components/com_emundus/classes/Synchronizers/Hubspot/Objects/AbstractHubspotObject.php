<?php

namespace Tchooz\Synchronizers\Hubspot\Objects;

use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Factories\Mapping\MappingObjectFactory;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Synchronizers\Mapping\AbstractMappingObject;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\ObjectSearchInterface;

/**
 * Shared HubSpot object behaviour: payload wrapping (`{ "properties": … }`), building the external
 * reference from a creation response, and applying object associations through the HubSpot
 * associations API. Concrete HubSpot objects (Contact, Deal) provide their definition, existence
 * rules and payload content.
 *
 * Repositories are injected with sensible defaults so objects stay testable while matching the
 * codebase's inline-instantiation convention.
 */
abstract class AbstractHubspotObject extends AbstractMappingObject
{
	protected ExternalReferenceRepository $externalReferenceRepository;

	protected EmundusUserRepository $emundusUserRepository;

	public function __construct(
		?ExternalReferenceRepository $externalReferenceRepository = null,
		?EmundusUserRepository $emundusUserRepository = null
	) {
		$this->externalReferenceRepository = $externalReferenceRepository ?? new ExternalReferenceRepository();
		$this->emundusUserRepository       = $emundusUserRepository ?? new EmundusUserRepository();

		Log::addLogger(['text_file' => 'com_emundus.hubspot.php'], Log::ALL, ['com_emundus.hubspot']);
	}

	/**
	 * Wrap mapped data in the HubSpot `properties` envelope expected by the CRM objects API.
	 */
	protected function wrapProperties(array $data): array
	{
		return ['properties' => $data];
	}

	/**
	 * Build the external reference to persist after a HubSpot object is created, reading the
	 * reference attribute (e.g. hs_object_id) from the creation response properties.
	 */
	public function buildCreatedReference(mixed $response, mixed $internalId, MappingEntity $mapping): ?ExternalReferenceEntity
	{
		$reference = $this->getDefinition()->getExternalReference();
		$attribute = $reference->getReferenceAttribute();

		if (empty($response['data']->properties->{$attribute}))
		{
			return null;
		}

		return new ExternalReferenceEntity(
			0,
			$reference->getColumn(),
			(string) $internalId,
			(string) $response['data']->properties->{$attribute},
			$mapping->getSynchronizerId(),
			$reference->getReferenceObject(),
			$reference->getReferenceAttribute()
		);
	}

	/**
	 * Associate the freshly created object with the related objects declared in its definition,
	 * using the HubSpot associations API. Related references are resolved from the store, or —
	 * for contacts — looked up on HubSpot by email and persisted before associating.
	 */
	public function applyAssociations(
		ExternalReferenceEntity $createdReference,
		mixed $internalId,
		ActionTargetEntity $context,
		MappingTransportInterface&ObjectSearchInterface $transport
	): bool {
		$associated = false;
		$factory    = new MappingObjectFactory();

		foreach ($this->getAssociations() as $association)
		{
			try
			{
				$associatedObject = $factory->make('hubspot', $association->targetObject);
			}
			catch (\DomainException $e)
			{
				Log::add('No mapping object definition found for associated object : ' . $association->targetObject, Log::ERROR, 'com_emundus.hubspot');
				continue;
			}

			$associatedReference   = $associatedObject->getDefinition()->getExternalReference();
			$associationInternalId = $association->resolver->resolve($context, $internalId);

			$found = $this->externalReferenceRepository->get([
				'sync_id'             => $createdReference->getSynchronizerId(),
				'reference_object'    => $associatedReference->getReferenceObject(),
				'reference_attribute' => $associatedReference->getReferenceAttribute(),
				'intern_id'           => $associationInternalId,
			], 1);

			if (empty($found) && $association->targetObject === 'contact')
			{
				$emundusUser = $this->emundusUserRepository->getByUserId($context->getUserIdFromFile());

				if (!empty($emundusUser))
				{
					$hsContacts = $transport->searchObjects($associatedReference->getReferenceObject(), ['email' => $emundusUser->getUser()->email]);

					if (!empty($hsContacts))
					{
						$newReference = new ExternalReferenceEntity(
							0,
							$associatedReference->getColumn(),
							(string) $associationInternalId,
							(string) $hsContacts[0]->id,
							$createdReference->getSynchronizerId(),
							$associatedReference->getReferenceObject(),
							$associatedReference->getReferenceAttribute()
						);

						if ($this->externalReferenceRepository->flush($newReference))
						{
							$found = [$newReference];
						}
						else
						{
							Log::add('Error saving external reference for contact association with user ID : ' . $context->getUserIdFromFile() . ' Hubspot Id : ' . $hsContacts[0]->id, Log::ERROR, 'com_emundus.hubspot');
						}
					}
				}
			}

			if (empty($found))
			{
				continue;
			}

			$targetReference = $found[0];
			$url             = $transport->getBaseUrl() . '/crm/v3/associations/' . $associatedObject->getName() . '/' . $this->getName() . '/batch/create';
			Log::add('Try associate URL : ' . $url, Log::INFO, 'com_emundus.hubspot');

			try
			{
				$response = $transport->post(
					$url,
					json_encode([
						'inputs' => [
							[
								'from' => ['id' => $targetReference->getReference()],
								'to'   => ['id' => $createdReference->getReference()],
								'type' => $associatedObject->getName() . '_to_' . $this->getName(),
							],
						],
					]),
					['Content-Type' => 'application/json', 'Accept' => 'application/json']
				);

				if (!empty($response) && in_array($response['status'], [200, 201]))
				{
					$associated = true;
				}
				else
				{
					Log::add('Error on Hubspot association request : ' . json_encode($response), Log::ERROR, 'com_emundus.hubspot');
				}
			}
			catch (\Exception $e)
			{
				Log::add('Exception on Hubspot association request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.hubspot');
			}
		}

		return $associated;
	}
}
