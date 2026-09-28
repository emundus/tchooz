<?php

namespace Unit\Component\Emundus\Class\Synchronizers\Hubspot\Objects;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Payment\TransactionRepository;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;
use Tchooz\Synchronizers\Hubspot\Objects\DealObject;

/**
 * @package     Unit\Component\Emundus\Class\Synchronizers\Hubspot\Objects
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Synchronizers\Hubspot\Objects\DealObject
 */
class DealObjectTest extends UnitTestCase
{
	private ExternalReferenceRepository $externalReferenceRepository;

	private EmundusUserRepository $emundusUserRepository;

	private WorkflowRepository $workflowRepository;

	private TransactionRepository $transactionRepository;

	protected function setUp(): void
	{
		$this->externalReferenceRepository = $this->createMock(ExternalReferenceRepository::class);
		$this->emundusUserRepository       = $this->createMock(EmundusUserRepository::class);
		$this->workflowRepository          = $this->createMock(WorkflowRepository::class);
		$this->transactionRepository       = $this->createMock(TransactionRepository::class);
	}

	protected function tearDown(): void
	{
		// No dataset created — nothing to clean up.
	}

	private function makeObject(): DealObject
	{
		return new DealObject(
			$this->externalReferenceRepository,
			$this->emundusUserRepository,
			$this->workflowRepository,
			$this->transactionRepository
		);
	}

	private function makeContext(): ActionTargetEntity
	{
		$context = $this->createMock(ActionTargetEntity::class);
		$context->method('getFile')->willReturn('fnum_test');
		$context->method('getUserIdFromFile')->willReturn(5);

		return $context;
	}

	// -------------------------------------------------------------------------
	// validate()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\DealObject::validate
	 * @return void
	 */
	public function testValidateWhenStepTypeIdMissingThrowsDomainException(): void
	{
		$this->expectException(\DomainException::class);

		$this->makeObject()->validate(new MappingEntity(1, 'Deal Mapping', 7, 'deal', [], []));
	}

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\DealObject::validate
	 * @return void
	 */
	public function testValidateWhenStepTypeIdPresentDoesNotThrow(): void
	{
		$this->makeObject()->validate(new MappingEntity(1, 'Deal Mapping', 7, 'deal', ['step_type_id' => 3], []));

		$this->addToAssertionCount(1); // reaching here means no exception was thrown
	}

	// -------------------------------------------------------------------------
	// buildPayload() — transaction resolution
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\DealObject::buildPayload
	 * @return void
	 */
	public function testBuildPayloadWhenTransactionResolvedAddsAmountAndDealname(): void
	{
		$step     = new StepEntity(10, 1, 'Payment step', new StepTypeEntity(3));
		$workflow = new WorkflowEntity(1, 'Workflow', 1, [$step]);
		$this->workflowRepository->method('getWorkflowByFnum')->willReturn($workflow);

		$transaction = new TransactionEntity(42);
		$transaction->setAmount(150.0);
		$transaction->setData('');
		$this->transactionRepository->method('get')->willReturn([$transaction]);

		$mapping = new MappingEntity(1, 'Deal Mapping', 7, 'deal', ['step_type_id' => 3], []);

		$payload = $this->makeObject()->buildPayload(['custom' => 'value'], $mapping, $this->makeContext());

		$this->assertSame(
			['properties' => ['custom' => 'value', 'amount' => 150.0, 'dealname' => '']],
			$payload,
			'Deal payload must wrap mapped data plus the transaction amount and title in a "properties" envelope.'
		);
	}

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\DealObject::buildPayload
	 * @return void
	 */
	public function testBuildPayloadWhenNoMatchingStepThrowsDomainException(): void
	{
		// No workflow (or no step of the configured type) means no step id to attach a transaction to.
		$this->workflowRepository->method('getWorkflowByFnum')->willReturn(null);

		$mapping = new MappingEntity(1, 'Deal Mapping', 7, 'deal', ['step_type_id' => 3], []);

		$this->expectException(\DomainException::class);

		$this->makeObject()->buildPayload([], $mapping, $this->makeContext());
	}

	/**
	 * @covers \Tchooz\Synchronizers\Hubspot\Objects\DealObject::buildPayload
	 * @return void
	 */
	public function testBuildPayloadWhenNoTransactionThrowsDomainException(): void
	{
		$step     = new StepEntity(10, 1, 'Payment step', new StepTypeEntity(3));
		$workflow = new WorkflowEntity(1, 'Workflow', 1, [$step]);
		$this->workflowRepository->method('getWorkflowByFnum')->willReturn($workflow);

		$this->transactionRepository->method('get')->willReturn([]);

		$mapping = new MappingEntity(1, 'Deal Mapping', 7, 'deal', ['step_type_id' => 3], []);

		$this->expectException(\DomainException::class);

		$this->makeObject()->buildPayload([], $mapping, $this->makeContext());
	}
}
