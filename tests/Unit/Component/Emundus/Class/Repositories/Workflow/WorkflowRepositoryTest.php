<?php

namespace Unit\Component\Emundus\Class\Repositories\Workflow;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

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
}