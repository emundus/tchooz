<?php

namespace Tchooz\Synchronizers\Hubspot\Objects;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Mapping\AssociationDefinition;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingResolution;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Factories\Payment\TransactionFactory;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\TransactionRepository;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Services\Mapping\AssociationReoslvers\UserIdResolver;
use Tchooz\Synchronizers\Mapping\SupportsAssociationsInterface;
use Tchooz\Synchronizers\MappingTransportInterface;

/**
 * HubSpot deal object: mapped to a payment transaction (jos_emundus_payment_transaction.id).
 *
 * The deal requires a payment stage type (`step_type_id`); the target transaction is the latest one
 * attached to a step of that type in the file's workflow. Its amount and title feed the payload,
 * and the deal is associated with the applicant's contact.
 */
class DealObject extends AbstractHubspotObject implements SupportsAssociationsInterface
{
	protected WorkflowRepository $workflowRepository;

	protected TransactionRepository $transactionRepository;

	private ?TransactionEntity $resolvedTransaction = null;

	private bool $transactionResolved = false;

	public function __construct(
		?ExternalReferenceRepository $externalReferenceRepository = null,
		?EmundusUserRepository $emundusUserRepository = null,
		?WorkflowRepository $workflowRepository = null,
		?TransactionRepository $transactionRepository = null
	) {
		parent::__construct($externalReferenceRepository, $emundusUserRepository);

		$this->workflowRepository    = $workflowRepository ?? new WorkflowRepository();
		$this->transactionRepository = $transactionRepository ?? new TransactionRepository();
	}

	public function getName(): string
	{
		return 'deal';
	}

	protected function buildDefinition(): SynchronizerMappingObjectDefinition
	{
		$paymentRepository       = new PaymentRepository();
		$stepTypesOptionProvider = new FieldOptionProvider('workflow', 'getsteptypes', [], new StepTypeRepository(), 'get', ['filters' => ['action_id' => $paymentRepository->getActionId()]]);

		$dealRequiredFields = [
			(new ChoiceField('step_type_id', Text::_('COM_EMUNDUS_HUBSPOT_DEAL_REQUIRED_STEP_ID'), [], true, false))
				->setOptionsProvider($stepTypesOptionProvider)
				->provideOptions(),
		];

		return new SynchronizerMappingObjectDefinition(
			'deal',
			'COM_EMUNDUS_HUBSPOT_DEAL_OBJECT_LABEL',
			'/crm/v3/objects/deals',
			new ExternalReferenceEntity(0, 'jos_emundus_payment_transaction.id', '', '', null, 'deals', 'hs_object_id'),
			[ApiMethodEnum::GET, ApiMethodEnum::POST],
			$dealRequiredFields,
			[],
			[new AssociationDefinition('contact', new UserIdResolver())]
		);
	}

	public function validate(MappingEntity $mapping): void
	{
		$params = $mapping->getParams() ?? [];

		if (empty($params['step_type_id']))
		{
			throw new \DomainException(Text::sprintf('COM_EMUNDUS_MAPPING_REQUIRED_FIELD_MISSING', Text::_('COM_EMUNDUS_HUBSPOT_DEAL_REQUIRED_STEP_ID'), $this->getName()));
		}
	}

	public function resolveExistence(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): MappingResolution
	{
		$transaction = $this->resolveTransaction($mapping, $context);
		$reference   = $this->getDefinition()->getExternalReference();
		$internalId  = $transaction->getId();

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
		}

		return $resolution;
	}

	public function buildPayload(array $mappedData, MappingEntity $mapping, ActionTargetEntity $context): array
	{
		$transaction = $this->resolveTransaction($mapping, $context);

		$mappedData['amount']   = $transaction->getAmount();
		$mappedData['dealname'] = TransactionFactory::getTransactionTitle($transaction);

		return $this->wrapProperties($mappedData);
	}

	/**
	 * Resolve (and memoize) the transaction that backs this deal: the latest transaction attached to
	 * a step of the configured payment stage type in the file's workflow.
	 *
	 * @throws \DomainException when no matching step or transaction exists.
	 */
	private function resolveTransaction(MappingEntity $mapping, ActionTargetEntity $context): TransactionEntity
	{
		if ($this->transactionResolved)
		{
			return $this->resolvedTransaction;
		}

		$stepTypeId = (int) ($mapping->getParams()['step_type_id'] ?? 0);
		$workflow   = $this->workflowRepository->getWorkflowByFnum($context->getFile());

		$stepIds = [];

		if (!empty($workflow))
		{
			foreach ($workflow->getSteps() as $step)
			{
				if ($step->getType()->getId() === $stepTypeId)
				{
					$stepIds[] = $step->getId();
				}
			}
		}

		if (empty($stepIds))
		{
			throw new \DomainException(Text::sprintf('COM_EMUNDUS_HUBSPOT_DEAL_NO_STEP', $stepTypeId));
		}

		$transactions = $this->transactionRepository->get(['step_id' => $stepIds, 'fnum' => $context->getFile()], 1, 1, '*', 'id DESC');

		if (empty($transactions))
		{
			throw new \DomainException(Text::sprintf('COM_EMUNDUS_HUBSPOT_DEAL_NO_TRANSACTION', $context->getUserIdFromFile()));
		}

		$this->resolvedTransaction = $transactions[0];
		$this->transactionResolved = true;

		return $this->resolvedTransaction;
	}
}
