<?php

namespace Tchooz\Synchronizers\Hubspot;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\api\Api;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\ExternalReference\ExternalReferenceEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Mapping\AssociationDefinition;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Factories\Payment\TransactionFactory;
use Tchooz\Repositories\ExternalReference\ExternalReferenceRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\TransactionRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Services\Mapping\ApiMapDataInterface;
use Tchooz\Services\Mapping\AssociationReoslvers\UserIdResolver;
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
	 * @throws \Exception
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

				$externalReferenceRepository = new ExternalReferenceRepository();

				switch ($mappingObjectDefinition->getName())
				{
					case 'contact':
						$internalId = $context->getUserId();

						if (!empty($mappingObjectDefinition->getExternalReference()))
						{
							$foundReferences = $externalReferenceRepository->get([
								'sync_id'             => $mappingEntity->getSynchronizerId(),
								'reference_object'    => $mappingObjectDefinition->getExternalReference()->getReferenceObject(),
								'reference_attribute' => $mappingObjectDefinition->getExternalReference()->getReferenceAttribute(),
								'intern_id'           => $internalId
							]);

							if (!empty($foundReferences))
							{
								$method   = ApiMethodEnum::PATCH;
								$objectId = $foundReferences[0]->getReference();
							}
						}

						$mappedData = ['properties' => $mappedData];
						break;
					case 'deal':
						if (empty($mappingEntity->getParams()['step_type_id']))
						{
							throw new \Exception('Step type ID is required to create a deal in Hubspot.');
						}

						$worfklowRepository = new WorkflowRepository();
						$worfklow           = $worfklowRepository->getWorkflowByFnum($context->getFile());

						// get steps with this step type
						$stepIds = [];
						foreach ($worfklow->getSteps() as $step)
						{
							if ($step->getType()->getId() === (int) $mappingEntity->getParams()['step_type_id'])
							{
								$stepIds[] = $step->getId();
							}
						}

						if (!empty($stepIds))
						{
							// find last transaction attached to this user with this step type
							$transactionRepository = new TransactionRepository();
							$transactions          = $transactionRepository->get(['step_id' => $stepIds], 1, 1, '*', 'id DESC');

							if (!empty($transactions))
							{
								$transaction = $transactions[0];
								assert($transaction instanceof TransactionEntity);
								$internalId = $transaction->getId();

								$mappedData['amount'] = $transaction->getAmount();
								$mappedData['dealname'] = TransactionFactory::getTransactionTitle($transaction);
								$mappedData           = ['properties' => $mappedData];

								$transactionReferences = $externalReferenceRepository->get([
									'sync_id'             => $mappingEntity->getSynchronizerId(),
									'reference_object'    => $mappingObjectDefinition->getExternalReference()->getReferenceObject(),
									'reference_attribute' => $mappingObjectDefinition->getExternalReference()->getReferenceAttribute(),
									'intern_id'           => $internalId
								]);

								if (!empty($transactionReferences))
								{
									$method   = ApiMethodEnum::PATCH;
									$objectId = $transactionReferences[0]->getReference();
								}
							}
							else
							{
								throw new \Exception('No transaction found for user ID ' . $context->getUserId() . ' to create deal in Hubspot.');
							}
						}
						else
						{
							throw new \Exception('No step found for step type ID ' . $mappingEntity->getParams()['step_type_id'] . ' to create deal in Hubspot.');
						}
						break;
					default:
						throw new \Exception('Mapping for target object ' . $mappingObjectDefinition->getName() . ' not implemented yet.');
						break;
				}

				switch ($method)
				{
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
								$externalReference           = new ExternalReferenceEntity(
									0,
									$mappingObjectDefinition->getExternalReference()->getColumn(),
									(string) $internalId, // todo: make dynamic finding of internId for other entities
									(string) $response['data']->properties->{$mappingObjectDefinition->getExternalReference()->getReferenceAttribute()},
									$mappingEntity->getSynchronizerId(),
									$mappingObjectDefinition->getExternalReference()->getReferenceObject(),
									$mappingObjectDefinition->getExternalReference()->getReferenceAttribute()
								);
								$externalReferenceRepository = new ExternalReferenceRepository();
								$externalReferenceRepository->flush($externalReference);

								if (!empty($mappingObjectDefinition->getAssociations()))
								{
									if ($this->associateObjects($mappingObjectDefinition, $externalReference, $internalId, $mappingEntity->getSynchronizerId(), $context))
									{
										$mapped = true;
									} else {
										Log::add('Error on associating objects for target object : ' . $mappingEntity->getTargetObject(), Log::ERROR, 'com_emundus.hubspot');
									}
								} else {
									$mapped = true;
								}
							}
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
		$contactDefinition = new SynchronizerMappingObjectDefinition('contact', 'COM_EMUNDUS_HUBSPOT_CONTACT_OBJECT_LABEL', '/crm/v3/objects/contacts', new ExternalReferenceEntity(0, 'jos_emundus_users.user_id', '', '', null, 'contacts', 'hs_object_id'), [ApiMethodEnum::GET, ApiMethodEnum::POST]);
		$paymentRepository = new PaymentRepository();

		$stepTypesOptionProvider = new FieldOptionProvider('workflow', 'getsteptypes', [], new StepTypeRepository(), 'get', ['filters' => ['action_id' => $paymentRepository->getActionId()]]);
		$dealRequiredFields      = [
			(new ChoiceField('step_type_id', Text::_('COM_EMUNDUS_HUBSPOT_DEAL_REQUIRED_STEP_ID'), [], true, false))
				->setOptionsProvider($stepTypesOptionProvider)
				->provideOptions(),
		];
		$dealDefinition          = new SynchronizerMappingObjectDefinition('deal', 'COM_EMUNDUS_HUBSPOT_DEAL_OBJECT_LABEL', '/crm/v3/objects/deals', new ExternalReferenceEntity(0, 'jos_emundus_payment_transaction.id', '', '', null, 'deals', 'hs_object_id'), [ApiMethodEnum::GET, ApiMethodEnum::POST], $dealRequiredFields, [], [
			new AssociationDefinition('contact', new UserIdResolver())
		]);

		return [
			$contactDefinition,
			$dealDefinition
		];
	}

	/**
	 * @param   SynchronizerMappingObjectDefinition  $mappingObjectDefinition
	 * @param   ExternalReferenceEntity              $sourceReference
	 * @param   mixed                                $internalId
	 * @param   int                                  $synchronizerId
	 * @param   ActionTargetEntity                   $context
	 *
	 * @return bool
	 */
	private function associateObjects(SynchronizerMappingObjectDefinition $mappingObjectDefinition, ExternalReferenceEntity $sourceReference, mixed $internalId, int $synchronizerId, ActionTargetEntity $context): bool
	{
		$associated = false;

		$externalReferenceRepository = new ExternalReferenceRepository();

		foreach ($mappingObjectDefinition->getAssociations() as $association)
		{
			$associatedMappingObjectDefinition = $this->getMappingObjectDefinition($association->targetObject);

			if (!empty($associatedMappingObjectDefinition))
			{
				$associationInternalId = $association->resolver->resolve($context, $internalId);
				$foundReferences = $externalReferenceRepository->get([
					'sync_id'             => $synchronizerId,
					'reference_object'    => $associatedMappingObjectDefinition->getExternalReference()->getReferenceObject(),
					'reference_attribute' => $associatedMappingObjectDefinition->getExternalReference()->getReferenceAttribute(),
					'intern_id'           => $associationInternalId
				], 1);

				if (!empty($foundReferences))
				{
					$targetReference = $foundReferences[0];

					$url = $this->getBaseUrl() . '/crm/v3/associations/' . $associatedMappingObjectDefinition->getName() . '/' . $mappingObjectDefinition->getName() . '/batch/create';
					Log::add('Try associate URL : ' . $url, Log::INFO, 'com_emundus.hubspot');

					try {
						// Make association request
						$response = $this->post(
							$url,
							json_encode([
								"inputs" => [
									[
										"from" => [
											"id" => $targetReference->getReference(),
										],
										"to"   => [
											"id" => $sourceReference->getReference(),
										],
										"type" => $associatedMappingObjectDefinition->getName() . '_to_' . $mappingObjectDefinition->getName(),
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
					} catch
					(\Exception $e)
					{
						Log::add('Exception on Hubspot association request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.hubspot');
					}
				}
				else
				{
					Log::add('No external reference found for associated object : ' . $association->targetObject . ' and internal ID : ' . $associationInternalId, Log::ERROR, 'com_emundus.hubspot');
				}
			}
			else
			{
				Log::add('No mapping object definition found for associated object : ' . $association->targetObject, Log::ERROR, 'com_emundus.hubspot');
			}
		}

		return $associated;
	}
}