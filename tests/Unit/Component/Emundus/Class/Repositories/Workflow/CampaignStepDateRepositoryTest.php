<?php

namespace Unit\Component\Emundus\Class\Repositories\Workflow;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Workflow\CampaignStepDateEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Workflow\CampaignStepDateRepository;
use Tchooz\Repositories\Workflow\StepRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

class CampaignStepDateRepositoryTest extends UnitTestCase
{
	private WorkflowEntity $workflow;

	private StepTypeRepository $stepTypeRepository;

	private WorkflowRepository $workflowRepository;

	private StepRepository $stepRepository;

	private CampaignStepDateRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new CampaignStepDateRepository();

		$this->stepRepository = new StepRepository();
		$this->stepTypeRepository = new StepTypeRepository();
		$step = new StepEntity(
			id: 0,
			workflow_id: 0,
			label: 'Test Step',
			type: $this->stepTypeRepository->getStepTypeById(1),
			profile_id: 1000,
			form_id: null,
			entry_status: [0],
			output_status: 1,
			multiple: 0,
			state: 1,
			ordering: 1
		);

		$workflow = new WorkflowEntity(0, 'Test Workflow', 1, [$step], [ $this->dataset['program']['programme_id'] ]);
		$this->workflowRepository = new WorkflowRepository();
		$this->workflowRepository->save($workflow);
		$this->workflow = $workflow;
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\CampaignStepDateRepository::save
	 * @return void
	 */
	public function testSave()
	{
		$step = $this->workflow->getSteps()[0];

		$newDate = new CampaignStepDateEntity(
			0,
			(int)$this->dataset['campaign'],
			$step->getId(),
			new \DateTimeImmutable('2024-01-01 00:00:00'),
			new \DateTimeImmutable('2024-12-31 23:59:59'),
		);

		$saved = $this->repository->save($newDate);
		$this->assertTrue($saved, 'Campaign step date should be saved successfully.');
		$this->assertGreaterThan(0, $newDate->getId(), 'Campaign step date ID should be set after saving.');

		$foundDates = $this->repository->getCampaignsDatesByStepId($step->getId());
		$this->assertNotEmpty($foundDates, 'Should retrieve campaign step dates after saving.');
		$this->assertEquals($newDate->getCampaignId(), $foundDates[0]->getCampaignId(), 'Campaign ID should match.');
		$this->assertEquals($newDate->getStartDate()->format('Y-m-d H:i:s'), $foundDates[0]->getStartDate()->format('Y-m-d H:i:s'), 'Start date should match.');
		$this->assertEquals($newDate->getEndDate()->format('Y-m-d H:i:s'), $foundDates[0]->getEndDate()->format('Y-m-d H:i:s'), 'End date should match.');
	}

	/**
	 * @covers \Tchooz\Repositories\Workflow\CampaignStepDateRepository::getCampaignsDatesByStepId
	 * @return void
	 */
	public function testGetCampaignsDatesByStepId(): void
	{
		$newProgram = $this->h_dataset->createSampleProgram('Program Bis');
		$newCampaign = $this->h_dataset->createSampleCampaign($newProgram);
		$programIds = $this->workflow->getProgramIds();
		$programIds[] = $newProgram['programme_id'];
		$this->workflow->setProgramIds($programIds);

		$step = $this->workflow->getSteps()[0];
		$dates = $this->repository->getCampaignsDatesByStepId($step->getId());
		$this->assertIsArray($dates, 'Expected an array of campaign step dates.');
		$this->assertEmpty($dates, 'Expected an array of campaign step dates.');

		$newDate = new CampaignStepDateEntity(
			0,
			$this->dataset['campaign'],
			$step->getId(),
			new \DateTimeImmutable('2024-01-01 00:00:00'),
			new \DateTimeImmutable('2024-12-31 23:59:59'),
		);
		$newDateBis = new CampaignStepDateEntity(
			0,
			$newCampaign,
			$step->getId(),
			new \DateTimeImmutable('2024-06-01 00:00:00'),
			new \DateTimeImmutable('2024-11-30 23:59:59'),
		);

		$step->setCampaignsDates([$newDate, $newDateBis]);
		$this->stepRepository->save($step);

		$dates = $this->repository->getCampaignsDatesByStepId($step->getId());
		$this->assertIsArray($dates, 'Expected an array of campaign step dates after saving.');
		$this->assertNotEmpty($dates, 'Expected an array of campaign step dates after saving.');
		$this->assertCount(2, $dates, 'Expected one campaign step date after saving.');

		// Verify the details of the retrieved dates
		$this->assertEquals($newDate->getCampaignId(), $dates[0]->getCampaignId(), 'First campaign ID should match.');
		$this->assertEquals($newDate->getStartDate()->format('Y-m-d H:i:s'), $dates[0]->getStartDate()->format('Y-m-d H:i:s'), 'First start date should match.');
		$this->assertEquals($newDateBis->getCampaignId(), $dates[1]->getCampaignId(), 'Second campaign ID should match.');
		$this->assertEquals($newDateBis->getStartDate()->format('Y-m-d H:i:s'), $dates[1]->getStartDate()->format('Y-m-d H:i:s'), 'Second start date should match.');

	}
}