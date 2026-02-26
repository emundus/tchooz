<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Entities\Automation\ActionEntity;

class ActionEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Automation\ActionEntity::with()
	 */
	public function testWith(): void
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$newStatus = 1;

		$context = new ActionTargetEntity($coord, $fnum, 0, []);
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$taskEntity = new TaskEntity(1, TaskStatusEnum::PENDING);
		$action->with($taskEntity);

		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result, 'Adding TaskEntity via with() should not affect execution.');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ActionEntity::isExecutedWith()
	 * @covers \Tchooz\Entities\Automation\ActionEntity::getWithOfType()
	 */
	public function testRetrieveWithEntity(): void
	{
		$newStatus = 1;
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$taskEntity = new TaskEntity(1, TaskStatusEnum::PENDING);
		$action->with($taskEntity);
		$this->assertTrue($action->isExecutedWith(TaskEntity::class), 'isExecutedWith() should return true for TaskEntity added via with().');
		$this->assertNotEmpty($action->getWithOfType(TaskEntity::class), 'Should retrieve the TaskEntity added via with().');
	}
}