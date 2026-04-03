<?php

namespace Unit\Component\Emundus\Repositories\Automation;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitions\ApplicantCurrentFilePredefinition;
use Tchooz\Entities\Automation\TargetPredefinitions\UsersAssociatedToFilePredefinition;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionsAndorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Automation\AutomationRepository;
use Tchooz\Repositories\Automation\EventsRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Automation
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Automation\AutomationRepository
 * @covers      \Tchooz\Repositories\Automation\ConditionRepository
 * @covers      \Tchooz\Repositories\Automation\ActionRepository
 * @covers      \Tchooz\Repositories\Automation\TargetRepository
 */
class AutomationRepositoryTest extends UnitTestCase
{
	private ?AutomationRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new AutomationRepository();
	}


	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::__construct
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::flush
	 * @return void
	 */
	public function testSave()
	{
		$newStatus      = 1;
		$condition      = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$action         = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$target         = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);

		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation       = new AutomationEntity(0, 'Test Automation', 'This is a test automation');
		$automation->addConditionGroup($conditionGroup);
		$automation->addAction($action);
		$automation->setEvent($event);

		$saved = $this->repository->flush($automation);
		$this->assertTrue($saved);
		$this->assertGreaterThan(0, $automation->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::flush
	 * @return void
	 */
	public function testUpdate()
	{
		$automation       = new AutomationEntity(0, 'Test Automation to update', 'This is a test automation to update');
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation->setEvent($event);
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);

		$automation->addAction($action);
		$condition      = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
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
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::delete
	 * @return void
	 */
	public function testDelete()
	{
		$automation       = new AutomationEntity(0, 'Test Automation to delete', 'This is a test automation to delete');
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation->setEvent($event);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$action->addTarget($target);
		$automation->addAction($action);
		$condition      = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$automation->addConditionGroup($conditionGroup);

		$saved = $this->repository->flush($automation);
		$this->assertTrue($saved);
		$this->assertGreaterThan(0, $automation->getId());

		$deleted = $this->repository->delete($automation->getId());
		$this->assertTrue($deleted);

		$retrievedAutomation = $this->repository->getById($automation->getId());
		$this->assertNull($retrievedAutomation);
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::getById
	 * @return void
	 */
	public function testGetAutomationById()
	{
		$newStatus      = 1;
		$condition      = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$action         = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$target         = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation       = new AutomationEntity(0, 'Test Automation', 'This is a test automation');
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
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::getAutomationsByEventName
	 * @return void
	 */
	public function testGetAutomationByEventName(): void
	{
		$newStatus      = 1;
		$condition      = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$action         = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$target         = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);

		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation       = new AutomationEntity(0, 'Test Automation', 'This is a test automation');
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
		foreach ($automations as $a)
		{
			if ($a->getId() === $automation->getId())
			{
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

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::duplicateAutomation
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::cloneConditionGroupRecursive
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::flush
	 * @covers \Tchooz\Repositories\Automation\ConditionRepository::saveGroupCondition
	 * @covers \Tchooz\Repositories\Automation\ConditionRepository::saveCondition
	 * @return void
	 */
	public function testDuplicateAutomation(): void
	{
		$automationToDuplicate = new AutomationEntity(0, 'Automation to duplicate', 'This automation will be duplicated');
		$eventsRepository      = new EventsRepository();
		$event                 = $eventsRepository->getEventByName('onAfterStatusChange');
		$automationToDuplicate->setEvent($event);
		$target = new TargetEntity(0, TargetTypeEnum::USER, new UsersAssociatedToFilePredefinition());

		$targetCondition = new ConditionEntity(0, 0, ConditionTargetTypeEnum::USERDATA, 'group', ConditionOperatorEnum::EQUALS, [1]);
		$target->setConditions([$targetCondition]);

		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$action->addTarget($target);
		$automationToDuplicate->addAction($action);
		$condition	  = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);

		$subCondition = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'campaign_id', ConditionOperatorEnum::EQUALS, 5);
		$subGroup = new ConditionGroupEntity(0, [$subCondition], ConditionsAndorEnum::OR);
		$conditionGroup->addSubGroup($subGroup);

		$automationToDuplicate->addConditionGroup($conditionGroup);

		$saved = $this->repository->flush($automationToDuplicate);
		$this->assertTrue($saved);
		$this->h_dataset->addToSamples('automations', $automationToDuplicate->getId());

		$this->assertGreaterThan(0, $automationToDuplicate->getId(), 'Condition group ID should not be empty after save');
		$this->assertGreaterThan(0, $conditionGroup->getId(), 'Condition group ID should not be empty after save');
		$this->assertGreaterThan(0, $condition->getId(), 'Condition ID should not be empty after save');
		$this->assertGreaterThan(0, $subGroup->getId(), 'Sub group ID should not be empty after save');
		$this->assertGreaterThan(0, $subCondition->getId(), 'Sub condition ID should not be empty after save');

		$duplicatedAutomation = $this->repository->duplicateAutomation($automationToDuplicate);
		$this->assertNotNull($duplicatedAutomation);
		$this->h_dataset->addToSamples('automations', $duplicatedAutomation->getId());

		// Verify that the duplicated automation has a different ID but the same properties
		$this->assertNotEquals($automationToDuplicate->getId(), $duplicatedAutomation->getId(), 'IDs should be different');
		$this->assertNotEquals($automationToDuplicate->getName(), $duplicatedAutomation->getName(), 'Name should be the same');
		$this->assertEquals($automationToDuplicate->getDescription(), $duplicatedAutomation->getDescription(), 'Description should be the same');
		$this->assertEquals($automationToDuplicate->getEvent()->getId(), $duplicatedAutomation->getEvent()->getId(), 'Event should be the same');
		$this->assertCount(1, $duplicatedAutomation->getActions(), 'There should be one action in the duplicated automation');
		$this->assertEquals($automationToDuplicate->getActions()[0]->getParameterValue(ActionUpdateStatus::STATUS_PARAMETER), $duplicatedAutomation->getActions()[0]->getParameterValue(ActionUpdateStatus::STATUS_PARAMETER), 'Action parameters should be the same');
		$this->assertNotEquals($automationToDuplicate->getActions()[0]->getId(), $duplicatedAutomation->getActions()[0]->getId(), 'Action IDs should be different');

		// Verify that the conditions and conditions groups are duplicated, but have different IDs
		$this->assertCount(count($automationToDuplicate->getConditionsGroups()), $duplicatedAutomation->getConditionsGroups(), 'There should be the same number of condition groups');
		$originalGroup = $automationToDuplicate->getConditionsGroups()[0];
		$duplicatedGroup = $duplicatedAutomation->getConditionsGroups()[0];
		$this->assertNotEquals($originalGroup->getId(), $duplicatedGroup->getId(), 'Condition group IDs should be different');
		$this->assertCount(count($originalGroup->getConditions()), $duplicatedGroup->getConditions(), 'There should be the same number of conditions in the group');
		$this->assertNotEquals($originalGroup->getConditions()[0]->getId(), $duplicatedGroup->getConditions()[0]->getId(), 'Condition IDs should be different');
		$this->assertEquals($duplicatedGroup->getId(), $duplicatedGroup->getConditions()[0]->getGroupId(), 'Duplicated condition should be linked to the duplicated group');
		$this->assertEquals($originalGroup->getConditions()[0]->getField(), $duplicatedGroup->getConditions()[0]->getField(), 'Condition fields should be the same');
		$this->assertEquals($originalGroup->getConditions()[0]->getOperator(), $duplicatedGroup->getConditions()[0]->getOperator(), 'Condition operators should be the same');
		$this->assertEquals($originalGroup->getConditions()[0]->getValue(), $duplicatedGroup->getConditions()[0]->getValue(), 'Condition values should be the same');

		// Verify that subgroups are duplicated recursively with different IDs and correct links
		$this->assertCount(count($originalGroup->getSubGroups()), $duplicatedGroup->getSubGroups(), 'There should be the same number of subgroups');
		$originalSubGroup = $originalGroup->getSubGroups()[0];
		$duplicatedSubGroup = $duplicatedGroup->getSubGroups()[0];
		$this->assertNotEquals($originalSubGroup->getId(), $duplicatedSubGroup->getId(), 'Subgroup IDs should be different');
		$this->assertGreaterThan(0, $duplicatedSubGroup->getId(), 'Duplicated subgroup ID should not be empty');
		$this->assertEquals($originalSubGroup->getOperator(), $duplicatedSubGroup->getOperator(), 'Subgroup operators should be the same');
		$this->assertEquals($duplicatedGroup->getId(), $duplicatedSubGroup->getParentId(), 'Duplicated subgroup parent_id should reference the duplicated parent group');
		$this->assertCount(count($originalSubGroup->getConditions()), $duplicatedSubGroup->getConditions(), 'There should be the same number of conditions in the subgroup');
		$this->assertNotEquals($originalSubGroup->getConditions()[0]->getId(), $duplicatedSubGroup->getConditions()[0]->getId(), 'Subgroup condition IDs should be different');
		$this->assertGreaterThan(0, $duplicatedSubGroup->getConditions()[0]->getId(), 'Duplicated subgroup condition ID should not be empty');
		$this->assertEquals($duplicatedSubGroup->getId(), $duplicatedSubGroup->getConditions()[0]->getGroupId(), 'Duplicated subgroup condition should be linked to the duplicated subgroup');
		$this->assertEquals($originalSubGroup->getConditions()[0]->getField(), $duplicatedSubGroup->getConditions()[0]->getField(), 'Subgroup condition fields should be the same');
		$this->assertEquals($originalSubGroup->getConditions()[0]->getOperator(), $duplicatedSubGroup->getConditions()[0]->getOperator(), 'Subgroup condition operators should be the same');
		$this->assertEquals($originalSubGroup->getConditions()[0]->getValue(), $duplicatedSubGroup->getConditions()[0]->getValue(), 'Subgroup condition values should be the same');

		// Verify that the target of the action is also duplicated
		$this->assertCount(1, $duplicatedAutomation->getActions()[0]->getTargets(), 'There should be one target in the action of the duplicated automation');
		$this->assertEquals($automationToDuplicate->getActions()[0]->getTargets()[0]->getType(), $duplicatedAutomation->getActions()[0]->getTargets()[0]->getType(), 'Target types should be the same');
		$this->assertNotEquals($automationToDuplicate->getActions()[0]->getTargets()[0]->getId(), $duplicatedAutomation->getActions()[0]->getTargets()[0]->getId(), 'Target IDs should be different');

		$targetToDuplicate = $automationToDuplicate->getActions()[0]->getTargets()[0];
		$duplicatedTarget  = $duplicatedAutomation->getActions()[0]->getTargets()[0];
		$this->assertCount(1, $duplicatedTarget->getConditions(), 'There should be one condition in the target of the duplicated automation');
		$this->assertEquals($targetToDuplicate->getConditions()[0]->getField(), $duplicatedTarget->getConditions()[0]->getField(), 'Condition fields should be the same');
		$this->assertEquals($targetToDuplicate->getConditions()[0]->getOperator(), $duplicatedTarget->getConditions()[0]->getOperator(), 'Condition operators should be the same');
		$this->assertEquals($targetToDuplicate->getConditions()[0]->getValue(), $duplicatedTarget->getConditions()[0]->getValue(), 'Condition values should be the same');
		$this->assertNotEquals($targetToDuplicate->getConditions()[0]->getId(), $duplicatedTarget->getConditions()[0]->getId(), 'Condition IDs should be different');
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::getAutomations
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::applyFilters
	 * @return void
	 */
	public function testGetAutomations(): void
	{
		$automationEntity = new AutomationEntity(0, 'Test Automation for getAutomations', 'This is a test automation for getAutomations');
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automationEntity->setEvent($event);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$action->addTarget($target);
		$automationEntity->addAction($action);
		$condition      = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$automationEntity->addConditionGroup($conditionGroup);
		$saved = $this->repository->flush($automationEntity);
		$this->assertTrue($saved);
		$this->h_dataset->addToSamples('automations', $automationEntity->getId());

		$automations = $this->repository->getAutomations();
		$this->assertIsArray($automations);
		$this->assertGreaterThan(0, count($automations));
		$this->assertInstanceOf(AutomationEntity::class, $automations[0]);

		$filteredAutomations = $this->repository->getAutomations(['search' => 'Test Automation for getAutomations']);
		$this->assertIsArray($filteredAutomations);
		$this->assertCount(1, $filteredAutomations);
		$this->assertEquals($automationEntity->getId(), $filteredAutomations[0]->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::getAutomationsCount
	 * @return void
	 */
	public function testGetAutomationsCount(): void
	{
		$initialCount = $this->repository->getAutomationsCount();

		$automationEntity = new AutomationEntity(0, 'Test Automation for getAutomationsCount', 'This is a test automation for getAutomationsCount');
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automationEntity->setEvent($event);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$action->addTarget($target);
		$automationEntity->addAction($action);
		$condition      = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$automationEntity->addConditionGroup($conditionGroup);
		$saved = $this->repository->flush($automationEntity);
		$this->assertTrue($saved);
		$this->h_dataset->addToSamples('automations', $automationEntity->getId());

		$countAfterAdding = $this->repository->getAutomationsCount();
		$this->assertEquals($initialCount + 1, $countAfterAdding);
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::togglePublishedAutomations
	 * @return void
	 */
	public function testTogglePublishedAutomations(): void
	{

		$automationEntity = new AutomationEntity(0, 'Test Automation for getAutomationsCount', 'This is a test automation for getAutomationsCount');
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automationEntity->setEvent($event);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$action->addTarget($target);
		$automationEntity->addAction($action);
		$condition      = new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$automationEntity->addConditionGroup($conditionGroup);
		$saved = $this->repository->flush($automationEntity);

		$this->assertTrue($saved);
		$this->h_dataset->addToSamples('automations', $automationEntity->getId());

		$toggled = $this->repository->togglePublishedAutomations([$automationEntity->getId()], false);
		$this->assertTrue($toggled);

		$retrievedAutomation = $this->repository->getById($automationEntity->getId());
		$this->assertNotNull($retrievedAutomation);
		$this->assertFalse($retrievedAutomation->isPublished(), 'Automation should be unpublished');

		$toggledBack = $this->repository->togglePublishedAutomations([$automationEntity->getId()], true);
		$this->assertTrue($toggledBack);

		$retrievedAutomation = $this->repository->getById($automationEntity->getId());
		$this->assertNotNull($retrievedAutomation);
		$this->assertTrue($retrievedAutomation->isPublished(), 'Automation should be published');
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::validateAutomation
	 * @return void
	 */
	public function testValidateAutomationEmptyName(): void
	{
		$invalidNameAutomation = new AutomationEntity(0, '');

		$this->expectException(\InvalidArgumentException::class);
		$this->repository->flush($invalidNameAutomation);
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::validateAutomation
	 * @return void
	 */
	public function testValidateAutomationInvalidName(): void
	{
		$moreThan255CharsName  = str_repeat('a', 256);
		$invalidNameAutomation = new AutomationEntity(0, $moreThan255CharsName);

		$this->expectException(\InvalidArgumentException::class);
		$this->repository->flush($invalidNameAutomation);
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::validateAutomation
	 * @return void
	 */
	public function testValidateAutomationInvalidEvent(): void
	{
		$automation = new AutomationEntity(0, 'Automation with invalid event');
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->flush($automation);
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::validateAutomation
	 * @return void
	 */
	public function testValidateAutomationNoActions(): void
	{
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation       = new AutomationEntity(0, 'Automation with no actions', 'This automation has no actions');
		$automation->setEvent($event);

		$this->expectException(\InvalidArgumentException::class);
		$this->repository->flush($automation);
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::validateAutomation
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::flush
	 * @return void
	 */
	public function testValidateAutomationActionsButMissingRequiredParameters(): void
	{
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation       = new AutomationEntity(0, 'Automation with no actions', 'This automation has no actions');
		$automation->setEvent($event);

		$action = new ActionUpdateStatus([]);
		$automation->addAction($action);

		$this->expectException(\InvalidArgumentException::class);
		$this->repository->flush($automation);
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::flush
	 * @covers \Tchooz\Repositories\Automation\AutomationRepository::getById
	 * @covers \Tchooz\Repositories\Automation\ConditionRepository::saveCondition
	 * @covers \Tchooz\Repositories\Automation\ConditionRepository::saveGroupCondition
	 * @covers \Tchooz\Repositories\Automation\ConditionRepository::getConditionsGroupsByAutomationId
	 * @covers \Tchooz\Repositories\Automation\ConditionRepository::getChildrenGroupsByParentId
	 * @covers \Tchooz\Repositories\Automation\ConditionRepository::getConditionsByGroupId
	 * @return void
	 */
	public function testGetAutomationByIdWithParentGroupHavingNoConditionAndOnlySubGroups(): void
	{
		$automation       = new AutomationEntity(0, 'Test Automation with parent group having no condition and only subgroups', 'This is a test automation');
		$eventsRepository = new EventsRepository();
		$event            = $eventsRepository->getEventByName('onAfterStatusChange');
		$automation->setEvent($event);

		$conditionGroup1 = new ConditionGroupEntity(0, [], ConditionsAndorEnum::AND, 0, [
			new ConditionGroupEntity(0, [new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 0)], ConditionsAndorEnum::AND, 0),
			new ConditionGroupEntity(0, [new ConditionEntity(0, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::NOT_EQUALS, 1)], ConditionsAndorEnum::AND, 0)
		]);

		$automation->setConditionsGroups([$conditionGroup1]);
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$target = new TargetEntity(0, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action->addTarget($target);
		$automation->addAction($action);

		$saved = $this->repository->flush($automation);
		$this->assertTrue($saved, 'Automation should be saved successfully');
		$this->assertGreaterThan(0, $automation->getId(), 'Automation ID should be greater than 0 after saving');

		$retrievedAutomation = $this->repository->getById($automation->getId());

		$this->assertNotNull($retrievedAutomation);
		$this->assertCount(1, $retrievedAutomation->getConditionsGroups(), 'There should be one parent condition group');
		$this->assertCount(2, $retrievedAutomation->getConditionsGroups()[0]->getSubGroups(), 'Parent group should have two subgroups');
		$this->assertCount(0, $retrievedAutomation->getConditionsGroups()[0]->getConditions(), 'Parent group should have no conditions');
	}
}