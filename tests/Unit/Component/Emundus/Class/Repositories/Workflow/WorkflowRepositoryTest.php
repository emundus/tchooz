<?php

namespace Unit\Component\Emundus\Class\Repositories\Workflow;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
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
	 * @covers WorkflowRepository::save
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
	 * @covers WorkflowRepository::save
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
	 * @covers WorkflowRepository::save
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
	 * @covers WorkflowRepository::getWorkflowById
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
	 * @covers WorkflowRepository::getWorkflowByProgramId
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
	 * @covers WorkflowRepository::delete
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
}