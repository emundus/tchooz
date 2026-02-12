<?php

namespace Unit\Component\Emundus\Class\Entities\Task;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Task\TaskPriorityEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

class TaskEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Task\TaskEntity::execute
	 * @return void
	 */
	public function testTaskExecute(): void
	{
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$task = new TaskEntity(1, TaskStatusEnum::PENDING, $action, 1, [], new \DateTimeImmutable(), new \DateTimeImmutable(), null, null, 0, TaskPriorityEnum::MEDIUM);
		$task->setMetadata(['fnums' => [$this->dataset['fnum']]]);

		$task->execute();
		$this->assertEquals(1, $task->getAttempts(), "Task attempts should be incremented after execution.");
		$this->assertEquals(TaskStatusEnum::COMPLETED, $task->getStatus(), "Task status should be updated to COMPLETED after successful execution.");

		$repository = new ApplicationFileRepository();
		$applicationFile = $repository->getByFnum($this->dataset['fnum']);
		$this->assertEquals(1, $applicationFile->getStatus()->getStep(), "Application file status should be updated to 1 after action execution.");
	}

	/**
	 * @covers \Tchooz\Entities\Task\TaskEntity::execute
	 * @return void
	 */
	public function testTaskExecuteNoAction(): void
	{
		$task = new TaskEntity(1, TaskStatusEnum::PENDING, null, 1, [], new \DateTimeImmutable(), new \DateTimeImmutable(), null, null, 0, TaskPriorityEnum::MEDIUM);
		$task->setMetadata([
			'fnums' => [$this->dataset['fnum']]
		]);

		$task->execute();
		$this->assertEquals(1, $task->getAttempts(), "Task attempts should be incremented after execution.");
		$this->assertEquals(TaskStatusEnum::FAILED, $task->getStatus(), 'Task status should be updated to FAILED when there is no action to execute.');
	}

	/**
	 * @covers \Tchooz\Entities\Task\TaskEntity::execute
	 * @return void
	 */
	public function testTaskExecuteNoTargetEntities(): void
	{
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$task = new TaskEntity(1, TaskStatusEnum::PENDING, $action, 1, [], new \DateTimeImmutable(), new \DateTimeImmutable(), null, null, 0, TaskPriorityEnum::MEDIUM);
		$task->setMetadata([
			'fnums' => []
		]);

		$task->execute();
		$this->assertEquals(1, $task->getAttempts(), "Task attempts should be incremented after execution.");
		$this->assertEquals(TaskStatusEnum::FAILED, $task->getStatus(), 'Task status should be updated to FAILED when there are no target entities to execute the action on.');
	}
}