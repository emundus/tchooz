<?php

namespace Unit\Component\Emundus\Repositories\Automation;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitions\ApplicantCurrentFilePredefinition;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Automation\AutomationRepository;
use Tchooz\Repositories\Automation\EventsRepository;

class AutomationRepositoryTest extends UnitTestCase
{
	private ?AutomationRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new AutomationRepository();
	}


	/**
	 * @covers AutomationRepository::flush
	 * @return void
	 */
	public function testSave()
	{
		$newStatus = 1;
		$condition = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);

		$eventsRepository = new EventsRepository();
		$event = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation = new AutomationEntity(0, 'Test Automation', 'This is a test automation');
		$automation->addConditionGroup($conditionGroup);
		$automation->addAction($action);
		$automation->setEvent($event);

		$saved = $this->repository->flush($automation);
		$this->assertTrue($saved);
		$this->assertGreaterThan(0, $automation->getId());
	}

	/**
	 * @covers AutomationRepository::flush
	 * @return void
	 */
	public function testUpdate()
	{
		$automation = new AutomationEntity(0, 'Test Automation to update', 'This is a test automation to update');
		$eventsRepository = new EventsRepository();
		$event = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation->setEvent($event);
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);

		$automation->addAction($action);
		$condition = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$automation->addConditionGroup($conditionGroup);

		$saved = $this->repository->flush($automation);
		$this->assertTrue($saved);
		$this->assertGreaterThan(0, $automation->getId());

		// Update the automation
		$automation->setName('Updated Test Automation');
		$automation->setDescription('This is an updated test automation');
		$action->setParameterValues(ActionUpdateStatus::STATUS_PARAMETER, 2);

		$condition->setValue(0);
		$conditionGroup->setConditions([$condition]);
		$automation->removeConditionsGroups();
		$automation->addConditionGroup($conditionGroup);

		$updated = $this->repository->flush($automation);
		$this->assertTrue($updated);

		// Retrieve and verify the update
		$retrievedAutomation = $this->repository->getById($automation->getId());
		$this->assertNotNull($retrievedAutomation);
		$this->assertEquals('Updated Test Automation', $retrievedAutomation->getName());
		$this->assertEquals('This is an updated test automation', $retrievedAutomation->getDescription());

		$this->assertCount(1, $retrievedAutomation->getActions(), 'There should be one action');
		$this->assertEquals(2, $retrievedAutomation->getActions()[0]->getParameterValue(ActionUpdateStatus::STATUS_PARAMETER), 'Action parameter should be updated');
		$this->assertCount(1, $retrievedAutomation->getConditionsGroups(), 'There should be one condition group');
		$this->assertCount(1, $retrievedAutomation->getConditionsGroups()[0]->getConditions(), 'There should be one condition in the group');
		$this->assertEquals(0, $retrievedAutomation->getConditionsGroups()[0]->getConditions()[0]->getValue(), 'Condition value should be updated');
		$this->assertEquals($event->getId(), $retrievedAutomation->getEvent()->getId(), 'Event should remain unchanged');
	}

	/**
	 * @covers AutomationRepository::delete
	 * @return void
	 */
	public function testDelete()
	{
		$automation = new AutomationEntity(0, 'Test Automation to delete', 'This is a test automation to delete');
		$eventsRepository = new EventsRepository();
		$event = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation->setEvent($event);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$action->addTarget($target);
		$automation->addAction($action);

		$saved = $this->repository->flush($automation);
		$this->assertTrue($saved);
		$this->assertGreaterThan(0, $automation->getId());

		$deleted = $this->repository->delete($automation->getId());
		$this->assertTrue($deleted);

		$retrievedAutomation = $this->repository->getById($automation->getId());
		$this->assertNull($retrievedAutomation);
	}

	/**
	 * @covers AutomationRepository::getById
	 * @return void
	 */
	public function testGetAutomationById()
	{
		$newStatus = 1;
		$condition = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);
		$eventsRepository = new EventsRepository();
		$event = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation = new AutomationEntity(0, 'Test Automation', 'This is a test automation');
		$automation->addConditionGroup($conditionGroup);
		$automation->addAction($action);
		$automation->setEvent($event);

		$saved = $this->repository->flush($automation);
		$this->assertTrue($saved);
		$this->assertGreaterThan(0, $automation->getId());

		$retrievedAutomation = $this->repository->getById($automation->getId());
		$this->assertNotNull($retrievedAutomation);
		$this->assertEquals($automation->getId(), $retrievedAutomation->getId());
		$this->assertEquals($automation->getName(), $retrievedAutomation->getName());
		$this->assertEquals($automation->getDescription(), $retrievedAutomation->getDescription());
		$this->assertEquals($automation->getEvent()->getId(), $retrievedAutomation->getEvent()->getId());

		$this->assertCount(1, $retrievedAutomation->getConditionsGroups());
		$this->assertCount(1, $retrievedAutomation->getConditionsGroups()[0]->getConditions());
		$this->assertCount(1, $retrievedAutomation->getActions());

		$this->assertEquals($automation->getConditionsGroups()[0]->getConditions()[0]->getField(), $retrievedAutomation->getConditionsGroups()[0]->getConditions()[0]->getField());
		$this->assertEquals($newStatus, $retrievedAutomation->getActions()[0]->getParameterValue(ActionUpdateStatus::STATUS_PARAMETER));
	}

	/**
	 * @covers AutomationRepository::getAutomationByEventName
	 * @return void
	 */
	public function testGetAutomationByEventName(): void
	{
		$newStatus = 1;
		$condition = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);

		$eventsRepository = new EventsRepository();
		$event = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation = new AutomationEntity(0, 'Test Automation', 'This is a test automation');
		$automation->addConditionGroup($conditionGroup);
		$automation->addAction($action);
		$automation->setEvent($event);
		$saved = $this->repository->flush($automation);

		$this->assertTrue($saved);
		$this->assertGreaterThan(0, $automation->getId());

		$automations = $this->repository->getAutomationsByEventName('onAfterStatusChange');
		$this->assertNotEmpty($automations);
		$this->assertIsArray($automations);
		$this->assertGreaterThan(0, count($automations));
		$this->assertInstanceOf(AutomationEntity::class, $automations[0]);

		$foundAutomation = null;
		foreach ($automations as $a) {
			if ($a->getId() === $automation->getId()) {
				$foundAutomation = $a;
				break;
			}
		}
		$this->assertNotNull($foundAutomation);
		$this->assertEquals($automation->getName(), $foundAutomation->getName());
		$this->assertEquals($automation->getDescription(), $foundAutomation->getDescription());
		$this->assertEquals($automation->getEvent()->getId(), $foundAutomation->getEvent()->getId());
		$this->assertCount(1, $foundAutomation->getConditionsGroups());
		$this->assertCount(1, $foundAutomation->getConditionsGroups()[0]->getConditions());
		$this->assertCount(1, $foundAutomation->getActions());
	}

	public function tearDown(): void
	{
		$this->h_dataset->resetAutomations();
	}
}