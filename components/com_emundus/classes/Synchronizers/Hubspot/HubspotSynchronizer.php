<?php

namespace Tchooz\Synchronizers\Hubspot;

use Joomla\CMS\Log\Log;
use Tchooz\api\Api;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\ExternalReference\ExternalReferenceEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Repositories\ExternalReference\ExternalReferenceRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Mapping\ApiMapDataInterface;
use Tchooz\Services\Mapping\MappingService;

class HubspotSynchronizer extends Api implements ApiMapDataInterface
{
	public function __construct()
	{
		parent::__construct();

		Log::addLogger(['text_file' => 'com_emundus.hubspot.php',], Log::ALL, ['com_emundus.hubspot']);

		try
		{
			$auth = $this->getAuthenticationInfos();

			$this->setBaseUrl($auth['base_url']);
			$headers = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $auth['token'],
				'Accept'        => 'application/json'
			);
			$this->setHeaders($headers);
			$this->setClient();
			$this->setAuth($auth['token']);
		}
		catch (\Exception $e)
		{
			Log::add('Error on Hubspot api connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.hubspot');
		}
	}

	private function getAuthenticationInfos(): array
	{
		$auth = [];

		$syncRepository   = new SynchronizerRepository();
		$syncEntity       = $syncRepository->getByType('hubspot');
		$params           = $syncEntity->getConfig() ?? [];
		$auth['token']    = !empty($params['authentication']['token']) ? \EmundusHelperFabrik::decryptDatas($params['authentication']['token']) : '';
		$auth['base_url'] = 'https://api.hubapi.com';

		return $auth;
	}

	/**
	 * @param   MappingEntity       $mappingEntity
	 * @param   ActionTargetEntity  $context
	 * @param   ApiMethodEnum       $method
	 *
	 * @return bool
	 */
	public function mapRequest(MappingEntity $mappingEntity, ActionTargetEntity $context, ApiMethodEnum $method): bool
	{
		$mapped = false;

		if (!empty($mappingEntity->getTargetObject()))
		{
			$mappingObjectDefinition = $this->getMappingObjectDefinition($mappingEntity->getTargetObject());

			if (!empty($mappingObjectDefinition))
			{
				$mappedData = MappingService::getJsonFromMapping($mappingEntity, $context);

				$objectId = null;
				if (!empty($mappingObjectDefinition->getExternalReference()))
				{
					$externalReferenceRepository = new ExternalReferenceRepository();
					$foundReferences = $externalReferenceRepository->getAll([
						'sync_id' => $mappingEntity->getSynchronizerId(),
						'reference_object' => $mappingObjectDefinition->getExternalReference()->getReferenceObject(),
						'reference_attribute' => $mappingObjectDefinition->getExternalReference()->getReferenceAttribute(),
						'intern_id' => $context->getUserId() // todo: make dynamic finding of internId for other entities
					]);

					if (!empty($foundReferences))
					{
						$method = ApiMethodEnum::PATCH;
						$objectId = $foundReferences[0]->getReference();
					}
				}

				switch ($mappingObjectDefinition->getName())
				{
					case 'contact':
						$mappedData = ['properties' => $mappedData];
						break;
					default:
						// No specific wrapping needed
						break;
				}

				switch($method) {
					case ApiMethodEnum::PATCH:
						if (!empty($objectId))
						{
							$response = $this->patch($this->getBaseUrl() . $mappingObjectDefinition->getRoute() . '/' . $objectId, json_encode($mappedData));
							if (!empty($response) && in_array($response['status'], [200, 201]))
							{
								$mapped = true;
							}
							else
							{
								Log::add('Error on Hubspot mapping PATCH request : ' . json_encode($response), Log::ERROR, 'com_emundus.hubspot');
							}
						}
						else
						{
							Log::add('No object ID found for PATCH request on target object : ' . $mappingEntity->getTargetObject(), Log::ERROR, 'com_emundus.hubspot');
						}
						break;
					default:
						// Default to POST
						$response = $this->post($this->getBaseUrl() . $mappingObjectDefinition->getRoute(), json_encode($mappedData), ['Content-Type' => 'application/json', 'Accept' => 'application/json']);
						if (!empty($response) && in_array($response['status'], [200, 201]))
						{
							if (!empty($response['data']->properties->{$mappingObjectDefinition->getExternalReference()->getReferenceAttribute()}))
							{
								$externalReference = new ExternalReferenceEntity(
									0,
									$mappingObjectDefinition->getExternalReference()->getColumn(),
									(string) $context->getUserId(), // todo: make dynamic finding of internId for other entities
									(string) $response['data']->properties->{$mappingObjectDefinition->getExternalReference()->getReferenceAttribute()},
									$mappingEntity->getSynchronizerId(),
									$mappingObjectDefinition->getExternalReference()->getReferenceObject(),
									$mappingObjectDefinition->getExternalReference()->getReferenceAttribute()
								);
								$externalReferenceRepository = new ExternalReferenceRepository();
								$externalReferenceRepository->flush($externalReference);
							}


							$mapped = true;
						}
						else
						{
							Log::add('Error on Hubspot mapping request : ' . json_encode($response), Log::ERROR, 'com_emundus.hubspot');
						}
						break;
				}
			}
			else
			{
				Log::add('No mapping object definition found for target object : ' . $mappingEntity->getTargetObject(), Log::ERROR, 'com_emundus.hubspot');
			}
		}

		return $mapped;
	}

	private function getMappingObjectDefinition(string $targetObject): ?SynchronizerMappingObjectDefinition
	{
		$definition = null;

		$definitions = $this->getMappingObjectsDefinitions();

		foreach ($definitions as $def)
		{
			if ($def->getName() === $targetObject)
			{
				$definition = $def;
				break;
			}
		}

		return $definition;
	}

	public function getMappingObjectsDefinitions(): array
	{
		return [
			new SynchronizerMappingObjectDefinition('contact', '/crm/v3/objects/contacts', new ExternalReferenceEntity(0, 'jos_emundus_users.user_id', '', '', null, 'contacts', 'hs_object_id'), [ApiMethodEnum::GET, ApiMethodEnum::POST]),
		];
	}
}