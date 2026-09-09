<?php

namespace Unit\Component\Emundus\Class\Repositories\Workflow;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Payment\CurrencyRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Workflow
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Workflow\WorkflowRepository
 */
class WorkflowRepositoryTest extends UnitTestCase
{
	private WorkflowRepository $repository;

	private StepTypeRepository $stepTypeRepository;

	private int $programId;

	private StepTypeEntity $applicantStepType;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new WorkflowRepository();
		$this->programId = $this->dataset['program']['programme_id'];

		$this->stepTypeRepository = new StepTypeRepository();
		$this->applicantStepType = $this->stepTypeRepository->getStepTypeById(1);
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::save
	 * @return void
	 */
	public function testSave()
	{
		$workflow = new WorkflowEntity(0, 'Test Workflow', 1, [], [$this->programId]);

		$saved = $this->repository->save($workflow);
		$this->assertTrue($saved, 'Workflow should be saved successfully.');
		$this->assertGreaterThan(0, $workflow->getId(), 'Workflow ID should be set after saving.');
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::save
	 * @return void
	 */
	public function testSaveWithSteps()
	{
		$steps = [
			new StepEntity(0, 0, 'Step 1', $this->applicantStepType, 1000, null, [0], 1, 0, 1, 1),
			new StepEntity(0, 0, 'Step 2', $this->applicantStepType, 1000, null, [1], 2, 0, 1, 2),
		];

		$workflow = new WorkflowEntity(0, 'Test Workflow with Steps', 1, $steps, [$this->programId]);
		$saved = $this->repository->save($workflow);
		$this->assertTrue($saved, 'Workflow with steps should be saved successfully.');
		$this->assertGreaterThan(0, $workflow->getId(), 'Workflow ID should be set after saving.');

		foreach ($workflow->getSteps() as $step) {
			$this->assertGreaterThan(0, $step->getId(), 'Step ID should be set after saving the workflow.');
		}

		$foundWorkflow = $this->repository->getWorkflowById($workflow->getId());

		$this->assertNotNull($foundWorkflow, 'Saved workflow should be retrievable.');
		$this->assertCount(2, $foundWorkflow->getSteps(), 'Saved workflow should have 2 steps.');
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::save
	 * @return void
	 */
	public function testSaveWithStepsOnSameEntryStatus()
	{
		$steps = [
			new StepEntity(0, 0, 'Step 1', $this->applicantStepType, 1000, null, [0], 1, 0, 1, 1),
			new StepEntity(0, 0, 'Step 2', $this->applicantStepType, 1000, null, [0], 2, 0, 1, 2),
		];

		$workflow = new WorkflowEntity(0, 'Test Workflow with Conflicting Steps', 1, $steps, [$this->programId]);
		// Expecting an exception due to conflicting entry statuses
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->save($workflow);
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::getWorkflowById
	 * @return void
	 */
	public function testGetWorkflowById()
	{
		$foundWorkflow = $this->repository->getWorkflowById(999999); // Non-existing ID
		$this->assertNull($foundWorkflow, 'Should return null for non-existing workflow ID.');
		
		$workflow = new WorkflowEntity(0, 'Test GetById', 1, [], [$this->programId]);
		$this->repository->save($workflow);

		$foundWorkflow = $this->repository->getWorkflowById($workflow->getId());
		$this->assertInstanceOf(WorkflowEntity::class, $foundWorkflow, 'Should return a WorkflowEntity instance.');
		$this->assertEquals($workflow->getId(), $foundWorkflow->getId(), 'Workflow ID should match the requested ID.');
		$this->assertEquals($workflow->getLabel(), $foundWorkflow->getLabel(), 'Workflow label should match the saved label.');
		$this->assertContains($this->programId, $foundWorkflow->getProgramIds(), 'Program ID should be associated with the workflow.');
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::getWorkflowByProgramId
	 * @return void
	 */
	public function testGetWorkflowByProgramId()
	{
		$foundWorkflow = $this->repository->getWorkflowByProgramId(999999); // Non-existing program ID
		$this->assertNull($foundWorkflow, 'Should return null for non-existing program ID.');

		$workflow = new WorkflowEntity(0, 'Test GetByProgramId', 1, [], [$this->programId]);
		$this->repository->save($workflow);

		$foundWorkflow = $this->repository->getWorkflowByProgramId($this->programId);
		$this->assertInstanceOf(WorkflowEntity::class, $foundWorkflow, 'Should return a WorkflowEntity instance.');
		$this->assertEquals($workflow->getId(), $foundWorkflow->getId(), 'Workflow ID should match the saved workflow ID.');
		$this->assertEquals($workflow->getLabel(), $foundWorkflow->getLabel(), 'Workflow label should match the saved label.');
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::delete
	 * @return void
	 */
	public function testDelete()
	{
		$workflow = new WorkflowEntity(0, 'Test Delete', 1, [], [$this->programId]);
		$this->repository->save($workflow);

		$deleted = $this->repository->delete($workflow);
		$this->assertTrue($deleted, 'Workflow should be deleted successfully.');

		$foundWorkflow = $this->repository->getWorkflowById($workflow->getId());
		$this->assertNull($foundWorkflow, 'Deleted workflow should not be found.');
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::getWorkflowByFnum
	 * @return void
	 */
	public function testGetWorkflowWithChildrenLoaded(): void
	{
		$campaign1 = $this->dataset['campaign'];
		$program1 = $this->dataset['program'];

		$program2 = $this->h_dataset->createSampleProgram();
		$campaign2 = $this->h_dataset->createSampleCampaign($program2);

		$campaignRepository = new CampaignRepository();
		$campaignEntity1 = $campaignRepository->getById($campaign1);
		$campaignEntity2 = $campaignRepository->getById($campaign2);

		$campaignEntity2->setParent($campaignEntity1);
		$flushed = $campaignRepository->flush($campaignEntity2);
		$this->assertTrue($flushed, 'Campaign parent relationship should be saved successfully.');

		$workflowParent = new WorkflowEntity(0, 'Parent Workflow', 1, [], [$program1['programme_id']]);
		$saved = $this->repository->save($workflowParent);
		$this->assertTrue($saved, 'Parent workflow should be saved successfully.');

		$workflowChild = new WorkflowEntity(0, 'Child Workflow', 1, [], [$program2['programme_id']]);
		$saved = $this->repository->save($workflowChild);
		$this->assertTrue($saved, 'Child workflow should be saved successfully.');

		$retrievedWorkflow = $this->repository->getWorkflowByFnum($this->dataset['fnum'], true);

		$this->assertNotNull($retrievedWorkflow, 'Workflow should be retrieved successfully.');
		$this->assertEquals($workflowParent->getId(), $retrievedWorkflow->getId(), 'Workflow ID should match the saved workflow ID.');
		$this->assertNotEmpty($retrievedWorkflow->getChildWorkflows(), 'Child workflows should be loaded.');
		$this->assertCount(1, $retrievedWorkflow->getChildWorkflows(), 'There should be exactly one child workflow.');
		$retrievedChildWorkflow = current($retrievedWorkflow->getChildWorkflows());
		$this->assertEquals($workflowChild->getId(), $retrievedChildWorkflow->getId(), 'Child workflow ID should match the saved child workflow ID.');

		$retrievedWorkflow = $this->repository->getWorkflowByFnum($this->dataset['fnum'], false);
		$this->assertEmpty($retrievedWorkflow->getChildWorkflows(), 'Child workflows should not be loaded when not requested.');
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::getWorkflowsByFnums
	 * @return void
	 */
	public function testGetWorkflowsByFnums(): void
	{
		try {
			$fnum1 = $this->dataset['fnum'];
			$program2 = $this->h_dataset->createSampleProgram();
			$campaign2 = $this->h_dataset->createSampleCampaign($program2);
			$fnum2 = $this->h_dataset->createSampleFile($campaign2, $this->dataset['applicant']);

			$workflow1 = new WorkflowEntity(0, 'Workflow 1', 1, [], [$this->programId]);
			$saved = $this->repository->save($workflow1);
			$this->assertTrue($saved, 'Workflow 1 should be saved successfully.');

			$workflow2 = new WorkflowEntity(0, 'Workflow 2', 1, [], [$program2['programme_id']]);
			$saved = $this->repository->save($workflow2);
			$this->assertTrue($saved, 'Workflow 2 should be saved successfully.');

			$workflows = $this->repository->getWorkflowsByFnums([$fnum1, $fnum2]);

			$this->assertCount(2, $workflows, 'Should retrieve two workflows for the given fnums.');
			$workflowIds = array_map(fn($wf) => $wf->getId(), $workflows);
			$this->assertContains($workflow1->getId(), $workflowIds, 'Workflow 1 should be in the retrieved workflows.');
			$this->assertContains($workflow2->getId(), $workflowIds, 'Workflow 2 should be in the retrieved workflows.');
		} catch (\Exception $e) {
			$this->fail('Exception occurred during testGetWorkflowsByFnums: ' . $e->getMessage());
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\WorkflowRepository::duplicate
	 * @return void
	 */
	public function testDuplicateWithPaymentSteps(): void
	{
		$paymentStepType = $this->stepTypeRepository->getStepTypeByCode('payment');
		$this->assertNotNull($paymentStepType, 'Payment step type should exist.');

		$this->db->setQuery(
			$this->db->createQuery()
				->update('#__emundus_setup_sync')
				->set('published = 1')
				->set('enabled = 1')
				->where('type = ' . $this->db->quote('sogecommerce'))
		);
		$this->db->execute();

		$steps = [
			new StepEntity(0, 0, 'Application', $this->applicantStepType, 1000, null, [0], 1, 0, 1, 1),
			new StepEntity(0, 0, 'First payment', $paymentStepType, null, null, [1], 2, 0, 1, 2),
			new StepEntity(0, 0, 'Balance payment', $paymentStepType, null, null, [2], 3, 0, 1, 3),
		];

		$workflow = new WorkflowEntity(0, 'Workflow to duplicate', 1, $steps, [$this->programId]);
		$this->assertTrue($this->repository->save($workflow), 'Workflow should be saved successfully.');

		$paymentRepository = new PaymentRepository();
		$services = $paymentRepository->getPaymentServices();
		$this->assertNotEmpty($services, 'At least one payment service should be available.');

		$currencyRepository = new CurrencyRepository();
		$currency = $currencyRepository->getCurrencyById(1);

		$productRepository = new ProductRepository();
		$mandatoryProduct = new ProductEntity();
		$mandatoryProduct->setLabel('Mandatory product');
		$mandatoryProduct->setCurrency($currency);
		$mandatoryProduct->setPrice(500.0);
		$mandatoryProduct->setMandatory(1);
		$productRepository->flush($mandatoryProduct);

		$optionalProduct = new ProductEntity();
		$optionalProduct->setLabel('Optional product');
		$optionalProduct->setCurrency($currency);
		$optionalProduct->setPrice(20.0);
		$optionalProduct->setMandatory(0);
		$productRepository->flush($optionalProduct);

		$firstPaymentStepId = $workflow->getSteps()[1]->getId();
		$balancePaymentStepId = $workflow->getSteps()[2]->getId();

		$firstPaymentStep = $paymentRepository->getPaymentStepById($firstPaymentStepId);
		$firstPaymentStep->setDescription('<p>Pay your registration fees</p>');
		$firstPaymentStep->setSynchronizerId($services[0]->id);
		$firstPaymentStep->setPaymentMethods($paymentRepository->getPaymentMethods());
		$firstPaymentStep->setProducts([$mandatoryProduct, $optionalProduct]);
		$firstPaymentStep->setAdvanceType(1);
		$firstPaymentStep->setAdvanceAmount(100);
		$firstPaymentStep->setInstallmentMonthday(5);
		$rule = new \stdClass();
		$rule->from_amount = 200;
		$rule->to_amount = 400;
		$rule->min_installments = 1;
		$rule->max_installments = 3;
		$firstPaymentStep->setInstallmentRules([$rule]);
		$this->assertTrue($paymentRepository->flushPaymentStep($firstPaymentStep), 'Payment configuration should be saved.');

		$balancePaymentStep = $paymentRepository->getPaymentStepById($balancePaymentStepId);
		$balancePaymentStep->setSynchronizerId($services[0]->id);
		$balancePaymentStep->setPaymentMethods($paymentRepository->getPaymentMethods());
		$balancePaymentStep->setAdjustBalance(1);
		$balancePaymentStep->setAdjustBalanceStepId($firstPaymentStepId);
		$this->assertTrue($paymentRepository->flushPaymentStep($balancePaymentStep), 'Balance payment configuration should be saved.');

		$duplicatedWorkflow = $this->repository->duplicate($this->repository->getWorkflowById($workflow->getId()));
		$this->assertNotNull($duplicatedWorkflow, 'Workflow should be duplicated successfully.');
		$this->assertCount(3, $duplicatedWorkflow->getSteps(), 'Duplicated workflow should hold the three steps.');

		$duplicatedFirstPaymentStepId = $duplicatedWorkflow->getSteps()[1]->getId();
		$duplicatedBalancePaymentStepId = $duplicatedWorkflow->getSteps()[2]->getId();
		$this->assertNotEquals($firstPaymentStepId, $duplicatedFirstPaymentStepId, 'Duplicated payment step should be a new step.');

		$duplicatedFirstPaymentStep = $paymentRepository->getPaymentStepById($duplicatedFirstPaymentStepId);
		$this->assertEquals($firstPaymentStep->getDescription(), $duplicatedFirstPaymentStep->getDescription(), 'Description should be duplicated.');
		$this->assertEquals($firstPaymentStep->getSynchronizerId(), $duplicatedFirstPaymentStep->getSynchronizerId(), 'Synchronizer should be duplicated.');
		$this->assertEquals($firstPaymentStep->getAdvanceType(), $duplicatedFirstPaymentStep->getAdvanceType(), 'Advance type should be duplicated.');
		$this->assertEquals($firstPaymentStep->getAdvanceAmount(), $duplicatedFirstPaymentStep->getAdvanceAmount(), 'Advance amount should be duplicated.');
		$this->assertEquals($firstPaymentStep->getInstallmentMonthday(), $duplicatedFirstPaymentStep->getInstallmentMonthday(), 'Installment monthday should be duplicated.');
		$this->assertCount(count($firstPaymentStep->getPaymentMethods()), $duplicatedFirstPaymentStep->getPaymentMethods(), 'Payment methods should be duplicated.');
		$this->assertCount(count($firstPaymentStep->getInstallmentRules()), $duplicatedFirstPaymentStep->getInstallmentRules(), 'Installment rules should be duplicated.');

		$duplicatedProductIds = array_map(fn($product) => $product->getId(), $duplicatedFirstPaymentStep->getProducts());
		$this->assertContains($mandatoryProduct->getId(), $duplicatedProductIds, 'Mandatory product should be duplicated.');
		$this->assertContains($optionalProduct->getId(), $duplicatedProductIds, 'Optional product should be duplicated.');

		$duplicatedBalancePaymentStep = $paymentRepository->getPaymentStepById($duplicatedBalancePaymentStepId);
		$this->assertEquals(1, $duplicatedBalancePaymentStep->getAdjustBalance(), 'Adjust balance should be duplicated.');
		$this->assertEquals($duplicatedFirstPaymentStepId, $duplicatedBalancePaymentStep->getAdjustBalanceStepId(), 'Adjusted step should point to the duplicated step.');
	}
}
