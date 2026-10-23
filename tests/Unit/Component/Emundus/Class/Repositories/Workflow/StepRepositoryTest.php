<?php

namespace Unit\Component\Emundus\Class\Repositories\Workflow;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Workflow\StepRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Workflow
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Workflow\StepRepository
 */
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
	 * @covers \Tchooz\Repositories\Workflow\StepRepository::save
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
	 * @covers \Tchooz\Repositories\Workflow\StepRepository::save
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

	/**
	 * @covers \Tchooz\Repositories\Workflow\StepRepository::getEvaluationStepsByCampaign
	 * @return void
	 */
	public function testGetEvaluationStepsByCampaign()
	{
		$this->refreshDataset();
		$workflow = new WorkflowEntity(0, 'Test Workflow evaluation steps', 1, [], [$this->dataset['program']['programme_id']]);
		(new WorkflowRepository())->save($workflow);

		if (!class_exists('EmundusModelForm'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/form.php';
		}
		$evalFormId = (new \EmundusModelForm())->createFormEval(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$this->assertNotEmpty($evalFormId, 'Evaluation form should be created');

		$applicantStep = new StepEntity(0, $workflow->getId(), 'Applicant step', $this->applicantStepType, 1000, null, [0], 1, 0, 1, 1);
		$this->repository->save($applicantStep);

		$evaluationStep = new StepEntity(0, $workflow->getId(), 'Evaluation step', $this->stepTypeRepository->getStepTypeById(2), null, $evalFormId, [1], 0, 0, 1, 2);
		$this->repository->save($evaluationStep);

		$stepsByTable = $this->repository->getEvaluationStepsByCampaign($this->dataset['campaign']);

		$this->assertCount(1, $stepsByTable, 'Only the evaluation step table should be returned');
		$table = array_key_first($stepsByTable);
		$this->assertNotEmpty($table, 'Steps should be indexed by their form table');
		$this->assertCount(1, $stepsByTable[$table]);
		$this->assertSame($evaluationStep->getId(), $stepsByTable[$table][0]->getId());

		$this->assertEmpty($this->repository->getEvaluationStepsByCampaign(0), 'No campaign, no steps');
	}
}