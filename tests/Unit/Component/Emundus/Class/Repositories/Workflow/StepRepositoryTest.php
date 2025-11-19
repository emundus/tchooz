<?php

namespace Unit\Component\Emundus\Class\Repositories\Workflow;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Workflow\StepRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

class StepRepositoryTest extends UnitTestCase
{
	private StepRepository $repository;

	private StepTypeRepository $stepTypeRepository;

	private WorkflowEntity $workflow;

	private StepTypeEntity $applicantStepType;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new StepRepository();

		$workflow = new WorkflowEntity(0, 'Test Workflow', 1, [], [ $this->dataset['program']['programme_id'] ]);
		$workflowRepository = new WorkflowRepository();
		$workflowRepository->save($workflow);
		$this->workflow = $workflow;

		$this->stepTypeRepository = new StepTypeRepository();
		$this->applicantStepType = $this->stepTypeRepository->getStepTypeById(1);
	}

	/**
	 * @covers StepRepository::save
	 * @return void
	 */
	public function testSave()
	{
		$step = new StepEntity(
			id: 0,
			workflow_id: $this->workflow->getId(),
			label: 'Test Step',
			type: $this->applicantStepType,
			profile_id: 1000,
			form_id: null,
			entry_status: [0],
			output_status: 1,
			multiple: 0,
			state: 1,
			ordering: 1
		);

		$saved = $this->repository->save($step);
		$this->assertTrue($saved, 'Step should be saved successfully.');
		$this->assertGreaterThan(0, $step->getId(), 'Step ID should be set after saving.');
	}

	/**
	 * @covers StepRepository::save
	 * @return void
	 */
	public function testSaveWithoutWorkflow()
	{
		$step = new StepEntity(
			id: 0,
			workflow_id: 0,
			label: 'Test Step Without Workflow',
			type: $this->applicantStepType,
			profile_id: 1000,
			form_id: null,
			entry_status: [0],
			output_status: 1,
			multiple: 0,
			state: 1,
			ordering: 1
		);

		// Expect an exception when saving a step without a workflow ID
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->save($step);
	}
}