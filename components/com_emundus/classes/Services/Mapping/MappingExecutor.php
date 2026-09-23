<?php

namespace Tchooz\Services\Mapping;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingResolution;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Factories\Mapping\MappingObjectFactory;
use Tchooz\Factories\Synchronizer\SynchronizerFactory;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Synchronizers\FileUploadInterface;
use Tchooz\Synchronizers\Mapping\MappingObjectInterface;
use Tchooz\Synchronizers\Mapping\SelfExecutingMappingObject;
use Tchooz\Synchronizers\Mapping\SupportsAssociationsInterface;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\ObjectSearchInterface;

/**
 * Orchestrates an outgoing mapping: resolves the transport (synchronizer) and the mapping object,
 * converts the source data, replaces uploaded files, decides create vs update, transports the
 * request, persists external references and drives associations.
 *
 * The business knowledge lives in the mapping object; the transport carries requests; this service
 * only wires them together. It replaces the former HubspotSynchronizer::mapRequest() loop.
 */
class MappingExecutor
{
	private SynchronizerRepository $synchronizerRepository;

	private SynchronizerFactory $synchronizerFactory;

	private MappingObjectFactory $mappingObjectFactory;

	private ExternalReferenceRepository $externalReferenceRepository;

	public function __construct(
		?SynchronizerRepository $synchronizerRepository = null,
		?SynchronizerFactory $synchronizerFactory = null,
		?MappingObjectFactory $mappingObjectFactory = null,
		?ExternalReferenceRepository $externalReferenceRepository = null
	) {
		$this->synchronizerRepository      = $synchronizerRepository ?? new SynchronizerRepository();
		$this->synchronizerFactory         = $synchronizerFactory ?? new SynchronizerFactory();
		$this->mappingObjectFactory        = $mappingObjectFactory ?? new MappingObjectFactory();
		$this->externalReferenceRepository = $externalReferenceRepository ?? new ExternalReferenceRepository();

		Log::addLogger(['text_file' => 'com_emundus.mapping.php'], Log::ALL, ['com_emundus.mapping']);
	}

	/**
	 * @throws \Exception
	 */
	public function execute(MappingEntity $mapping, ActionTargetEntity $context, ApiMethodEnum $method = ApiMethodEnum::POST): bool
	{
		if (empty($mapping->getTargetObject()))
		{
			return false;
		}

		$synchronizer = $this->synchronizerRepository->getById($mapping->getSynchronizerId());

		if (empty($synchronizer))
		{
			throw new \DomainException(Text::sprintf('COM_EMUNDUS_MAPPING_SYNCHRONIZER_NOT_FOUND', $mapping->getSynchronizerId()));
		}

		$transport = $this->synchronizerFactory->getApiInstance($synchronizer);

		if (!($transport instanceof MappingTransportInterface))
		{
			throw new \DomainException(Text::sprintf('COM_EMUNDUS_MAPPING_TRANSPORT_UNSUPPORTED', $synchronizer->getName()));
		}

		$object = $this->mappingObjectFactory->make($synchronizer->getType(), $mapping->getTargetObject());

		// Objects that own a multi-step choreography (e.g. Sofis vendor) drive their own execution.
		if ($object instanceof SelfExecutingMappingObject)
		{
			return $object->execute($mapping, $context, $transport);
		}

		$definition = $object->getDefinition();

		$object->validate($mapping);

		$mappedData = MappingService::getJsonFromMapping($mapping, $context);
		$mappedData = $this->resolveUploads($mappedData, $transport, $mapping);

		$resolution = $object->resolveExistence($mapping, $context, $transport);

		if ($resolution->discoveredReference !== null && !$this->externalReferenceRepository->flush($resolution->discoveredReference))
		{
			Log::add('Error saving discovered external reference for object : ' . $mapping->getTargetObject(), Log::ERROR, 'com_emundus.mapping');

			throw new \RuntimeException(Text::_('COM_EMUNDUS_MAPPING_REFERENCE_SAVE_FAILED'));
		}

		$payload = $object->buildPayload($mappedData, $mapping, $context);

		if ($resolution->method === ApiMethodEnum::PATCH)
		{
			return $this->update($transport, $definition->getRoute(), $resolution->objectId, $payload, $mapping);
		}

		return $this->create($transport, $object, $definition->getRoute(), $payload, $resolution, $context, $mapping);
	}

	/**
	 * Replace UploadEntity values in the mapped data with their remote URL. Requires the transport
	 * to support file upload.
	 *
	 * @throws \Exception
	 */
	private function resolveUploads(array $mappedData, MappingTransportInterface $transport, MappingEntity $mapping): array
	{
		$hasUploads = false;

		foreach ($mappedData as $value)
		{
			if ($value instanceof UploadEntity || (is_array($value) && !empty($value) && $value[0] instanceof UploadEntity))
			{
				$hasUploads = true;
				break;
			}
		}

		if (!$hasUploads)
		{
			return $mappedData;
		}

		if (!($transport instanceof FileUploadInterface))
		{
			throw new \DomainException(Text::_('COM_EMUNDUS_MAPPING_UPLOAD_UNSUPPORTED'));
		}

		foreach ($mappedData as $key => $value)
		{
			if ($value instanceof UploadEntity)
			{
				$mappedData[$key] = $this->uploadOrFail($transport, $value, $mapping, $key);
			}
			elseif (is_array($value) && !empty($value) && $value[0] instanceof UploadEntity)
			{
				$urls = [];

				foreach ($value as $upload)
				{
					$urls[] = $this->uploadOrFail($transport, $upload, $mapping, $key);
				}

				$mappedData[$key] = implode(';', $urls);
			}
		}

		return $mappedData;
	}

	/**
	 * @throws \Exception
	 */
	private function uploadOrFail(FileUploadInterface $transport, UploadEntity $upload, MappingEntity $mapping, int|string $key): string
	{
		$url = $transport->uploadFile($upload, $mapping->getSynchronizerId());

		if (empty($url))
		{
			Log::add('Error uploading file for key : ' . $key, Log::ERROR, 'com_emundus.mapping');

			throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_MAPPING_UPLOAD_FAILED', $upload->getFilename()));
		}

		return $url;
	}

	/**
	 * Send an update (PATCH) for an already-known remote object.
	 *
	 * @throws \Exception
	 */
	private function update(MappingTransportInterface $transport, string $route, ?string $objectId, array $payload, MappingEntity $mapping): bool
	{
		if (empty($objectId))
		{
			Log::add('No object ID found for PATCH request on target object : ' . $mapping->getTargetObject(), Log::ERROR, 'com_emundus.mapping');

			return false;
		}

		$response = $transport->patch($transport->getBaseUrl() . $route . '/' . $objectId, json_encode($payload));

		if (!empty($response) && in_array($response['status'], [200, 201]))
		{
			return true;
		}

		Log::add('Error on mapping PATCH request : ' . json_encode($response), Log::ERROR, 'com_emundus.mapping');
		$this->throwRemoteError($response);

		return false;
	}

	/**
	 * Send a creation (POST), persist the created external reference and drive associations.
	 *
	 * @throws \Exception
	 */
	private function create(MappingTransportInterface $transport, MappingObjectInterface $object, string $route, array $payload, MappingResolution $resolution, ActionTargetEntity $context, MappingEntity $mapping): bool
	{
		$response = $transport->post($transport->getBaseUrl() . $route, json_encode($payload), ['Content-Type' => 'application/json', 'Accept' => 'application/json']);

		if (empty($response) || !in_array($response['status'], [200, 201]))
		{
			Log::add('Error on mapping request : ' . json_encode($response), Log::ERROR, 'com_emundus.mapping');
			$this->throwRemoteError($response);

			return false;
		}

		$createdReference = $object->buildCreatedReference($response, $resolution->internalId, $mapping);

		if ($createdReference === null)
		{
			return true;
		}

		if (!$this->externalReferenceRepository->flush($createdReference))
		{
			Log::add('Error saving external reference for object : ' . $mapping->getTargetObject(), Log::ERROR, 'com_emundus.mapping');

			throw new \RuntimeException(Text::_('COM_EMUNDUS_MAPPING_REFERENCE_SAVE_FAILED'));
		}

		if ($object instanceof SupportsAssociationsInterface && !empty($object->getAssociations()))
		{
			if (!($transport instanceof ObjectSearchInterface))
			{
				throw new \DomainException(Text::_('COM_EMUNDUS_MAPPING_SEARCH_UNSUPPORTED'));
			}

			if (!$object->applyAssociations($createdReference, $resolution->internalId, $context, $transport))
			{
				Log::add('Error on associating objects for target object : ' . $mapping->getTargetObject(), Log::ERROR, 'com_emundus.mapping');

				throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_MAPPING_ASSOCIATION_FAILED', $mapping->getTargetObject()));
			}
		}

		return true;
	}

	/**
	 * Rethrow the remote API's error message when present, so it surfaces to the caller.
	 */
	private function throwRemoteError(array $response): void
	{
		if (!empty($response['error']))
		{
			$decoded = json_decode($response['error'], true);

			if (!empty($decoded['message']))
			{
				throw new \RuntimeException($decoded['message']);
			}
		}
	}
}
