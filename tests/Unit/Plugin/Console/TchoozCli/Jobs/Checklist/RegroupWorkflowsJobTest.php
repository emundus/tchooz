<?php

namespace Unit\Plugin\Console\TchoozCli\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Jobs\Checklist\RegroupWorkflowsJob;
use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Workflow\CampaignStepDateEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Workflow\StepRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

class RegroupWorkflowsJobTest extends UnitTestCase
{
	private WorkflowRepository $workflowRepository;

	private StepRepository $stepRepository;

	/**
	 * @var array<WorkflowEntity>
	 */
	private array $workflows;

	public function setUp(): void
	{
		parent::setUp();

		$this->h_dataset->resetWorkflows();

		$db = Factory::getContainer()->get('DatabaseDriver');

		$this->workflowRepository = new WorkflowRepository($db);
		$this->stepRepository     = new StepRepository($db);

		$program1 = $this->h_dataset->createSampleProgram('Program 1');
		$program2 = $this->h_dataset->createSampleProgram('Program 2');
		$program3 = $this->h_dataset->createSampleProgram('Program 3');

		$campaignProgram1 = $this->h_dataset->createSampleCampaign($program1);
		$campaignProgram2 = $this->h_dataset->createSampleCampaign($program2);
		$campaignProgram3 = $this->h_dataset->createSampleCampaign($program3);

		$campaign1StepDate1 = new CampaignStepDateEntity(0, $campaignProgram1, 0, new \DateTimeImmutable('2024-11-30'));
		$campaign1StepDate2 = new CampaignStepDateEntity(0, $campaignProgram1, 0, new \DateTimeImmutable('2024-12-25'));
		$campaign2StepDate1 = new CampaignStepDateEntity(0, $campaignProgram2, 0, new \DateTimeImmutable('2024-12-30'));
		$campaign3StepDate1 = new CampaignStepDateEntity(0, $campaignProgram3, 0, new \DateTimeImmutable('2024-10-15'));

		$program1 = $program1['programme_id'];
		$program2 = $program2['programme_id'];
		$program3 = $program3['programme_id'];

		$similarSteps   = [
			new StepEntity(0, 0, 'Step 1', new StepTypeEntity(1), 1000, 0, [0], 1, 0, 1, 0, '', 0, [$campaign1StepDate1]),
			new StepEntity(0, 0, 'Step 2', new StepTypeEntity(1), 1000, 0, [1], 2, 0, 1, 0, '', 0, [$campaign1StepDate2]),
		];
		$similarSteps2  = [
			new StepEntity(0, 0, 'Step 1', new StepTypeEntity(1), 1000, 0, [0], 1, 0, 1, 0, '', 0, [$campaign2StepDate1]),
			new StepEntity(0, 0, 'Step 2', new StepTypeEntity(1), 1000, 0, [1], 2),
		];
		$differentSteps = [
			new StepEntity(0, 0, 'Step A', new StepTypeEntity(1), 1000, 0, [1], 2, 0, 1, 0, '', 0, [$campaign3StepDate1]),
			new StepEntity(0, 0, 'Step B', new StepTypeEntity(1), 1000, 0, [2], 2),
		];

		$workflow1 = new WorkflowEntity(0, 'Workflow 1', 1, $similarSteps, [$program1]);
		$workflow2 = new WorkflowEntity(0, 'Workflow 2', 1, $similarSteps2, [$program2]);
		$workflow3 = new WorkflowEntity(0, 'Workflow 3', 1, $differentSteps, [$program3]);
		$this->workflowRepository->save($workflow1);
		$this->workflowRepository->save($workflow2);
		$this->workflowRepository->save($workflow3);

		$this->workflows = [$workflow1, $workflow2, $workflow3];
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\Jobs\Checklist\RegroupWorkflowsJob::getWorkflowsGroupedBySameSignature
	 * @return void
	 */
	public function testGetWorkflowsGroupedBySameSignature(): void
	{
		$groupedWorkflows = RegroupWorkflowsJob::getWorkflowsGroupedBySameSignature();

		$this->assertCount(2, $groupedWorkflows, 'There should be 2 groups of workflows');

		foreach ($groupedWorkflows as $signature => $workflows)
		{
			if (count($workflows) > 1)
			{
				// This group should contain the two similar workflows
				$this->assertCount(2, $workflows, 'This group should contain 2 similar workflows');
				$this->assertEquals('Workflow 1', $workflows[0]->getLabel());
				$this->assertEquals('Workflow 2', $workflows[1]->getLabel());
			}
			else
			{
				// This group should contain the different workflow
				$this->assertCount(1, $workflows, 'This group should contain 1 different workflow');
				$this->assertEquals('Workflow 3', $workflows[0]->getLabel());
			}
		}
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\Jobs\Checklist\RegroupWorkflowsJob::regroupWorkflows
	 * @return void
	 */
	public function testRegroupWorkflows(): void
	{
		$groupedWorkflows = RegroupWorkflowsJob::getWorkflowsGroupedBySameSignature();

		// Before regrouping, verify that there are two similar workflows
		$this->assertCount(2, $groupedWorkflows[array_key_first($groupedWorkflows)], 'There should be 2 similar workflows before regrouping');
		// Perform regrouping
		RegroupWorkflowsJob::regroupWorkflows($groupedWorkflows[array_key_first($groupedWorkflows)]);

		$workflows = $this->workflowRepository->getWorkflows();
		$this->assertCount(2, $workflows, 'After regrouping, there should be only 2 workflows left');

		// Verify that the first workflow now has programs from both original workflows
		$remainingWorkflow = $this->workflowRepository->getWorkflowById($this->workflows[0]->getId());
		$this->assertCount(2, $remainingWorkflow->getProgramIds(), 'The remaining workflow should have 2 program IDs assigned');
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\Jobs\Checklist\RegroupWorkflowsJob::regroupWorkflows
	 * @return void
	 * @throws \Exception
	 */
	public function testRegroupWorkflowKeepCampaignsStepsDates(): void
	{

		$groupedWorkflows = RegroupWorkflowsJob::getWorkflowsGroupedBySameSignature();
		RegroupWorkflowsJob::regroupWorkflows($groupedWorkflows[array_key_first($groupedWorkflows)]);

		$remainingWorkflow = $this->workflowRepository->getWorkflowById($this->workflows[0]->getId());
		$steps             = $this->stepRepository->getStepsByWorkflowId($remainingWorkflow->getId());

		$this->assertCount(2, $steps, 'The remaining workflow should have 2 steps');

		$allCampaignDates = [];
		foreach ($steps as $step)
		{
			foreach ($step->campaignsDates as $campaignDate)
			{
				$allCampaignDates[] = $campaignDate;
			}
		}

		$this->assertCount(3, $allCampaignDates, 'There should be 3 campaign step dates in total after regrouping');
		// verify the dates are the expected ones
		$dateStrings = array_map(fn($date) => $date->getStartDate()?->format('Y-m-d'), $allCampaignDates);
		$this->assertContains('2024-11-30', $dateStrings);
		$this->assertContains('2024-12-25', $dateStrings);
		$this->assertContains('2024-12-30', $dateStrings);
		$this->assertNotContains('2024-10-15', $dateStrings);
	}
}