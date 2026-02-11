<?php

namespace Unit\Component\Emundus\Class\Repositories\Task;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Task\TaskPriorityEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Repositories\Task\TaskRepository;

class TaskRepositoryTest extends UnitTestCase
{

	private TaskRepository $repository;

	private User $coordinatorUser;

	private AutomationEntity $automationEntity;

	public function setUp(): void
	{
		parent::setUp();
		$this->h_dataset->resetAutomations();
		$this->repository = new TaskRepository();
		$this->coordinatorUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$this->automationEntity = $this->h_dataset->createSampleAutomation();
	}

	/**
	 * @covers TaskRepository::saveTask
	 * @return void
	 */
	public function testSaveTask(): void
	{
		$actionTargetEntity = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);
		$metadata = ['actionTargetEntity' => $actionTargetEntity->serialize()];
		$task = new TaskEntity(0,TaskStatusEnum::PENDING, $this->automationEntity->getActions()[0], $this->dataset['coordinator'], $metadata);

		$saved = $this->repository->saveTask($task);
		$this->assertTrue($saved, 'Task should be saved successfully.');
		$this->assertGreaterThan(0, $task->getId(), 'Task ID should be set after saving.');
		$this->h_dataset->addToSamples('tasks', $task->getId());

		$task->setPriority(TaskPriorityEnum::HIGH);
		$updated = $this->repository->saveTask($task);
		$this->assertTrue($updated, 'Task should be updated successfully.');

		$retrievedTask = $this->repository->getTaskById($task->getId());
		$this->assertEquals(TaskPriorityEnum::HIGH, $retrievedTask->getPriority(), 'Task priority should be updated to HIGH.');
	}

	/**
	 * @covers TaskRepository::getTaskById
	 * @return void
	 */
	public function testGetTaskById(): void
	{
		$actionTargetEntity = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);
		$metadata = ['actionTargetEntity' => $actionTargetEntity->serialize()];
		$task = new TaskEntity(0,TaskStatusEnum::PENDING, $this->automationEntity->getActions()[0], $this->dataset['coordinator'], $metadata);
		$saved = $this->repository->saveTask($task);

		$this->assertTrue($saved, 'Task should be saved successfully.');
		$this->assertGreaterThan(0, $task->getId(), 'Task ID should be set after saving.');
		$this->h_dataset->addToSamples('tasks', $task->getId());

		$retrievedTask = $this->repository->getTaskById($task->getId());
		$this->assertInstanceOf(TaskEntity::class, $retrievedTask, 'Retrieved task should be an instance of TaskEntity.');
		$this->assertEquals($task->getId(), $retrievedTask->getId(), 'Retrieved task ID should match the saved task ID.');
		$this->assertEquals(TaskStatusEnum::PENDING, $retrievedTask->getStatus(), 'Retrieved task status should be PENDING.');
		$this->assertEquals($task->getUserId(), $retrievedTask->getUserId(), 'Retrieved task user ID should match the saved task user ID.');
		$this->assertNotEmpty($retrievedTask->getMetadata());
		$this->assertNotEmpty($retrievedTask->getCreatedAt());
		$this->assertEmpty($retrievedTask->getStartedAt());
		$this->assertEmpty($retrievedTask->getFinishedAt());
	}

	/**
	 * @covers TaskRepository::getPendingTasks
	 * @return void
	 */
	public function testGetPendingTasks(): void
	{
		$actionTargetEntity = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);
		// create some tasks with various status for testing
		for ($i = 0; $i < 15; $i++) {
			$status = ($i % 2 == 0) ? TaskStatusEnum::PENDING : TaskStatusEnum::COMPLETED;
			$metadata = ['actionTargetEntity' => $actionTargetEntity->serialize()];
			$task = new TaskEntity(0, $status, $this->automationEntity->getActions()[0], $this->dataset['coordinator'], $metadata);
			$this->repository->saveTask($task);
			$this->h_dataset->addToSamples('tasks', $task->getId());
		}

		$pendingTasks = $this->repository->getPendingTasks(10);
		$this->assertIsArray($pendingTasks, 'Pending tasks should be returned as an array.');
		$this->assertLessThanOrEqual(10, count($pendingTasks), 'Number of pending tasks should not exceed the limit.');

		foreach ($pendingTasks as $task) {
			$this->assertInstanceOf(TaskEntity::class, $task, 'Each pending task should be an instance of TaskEntity.');
			$this->assertEquals(TaskStatusEnum::PENDING, $task->getStatus(), 'Each pending task should have status PENDING.');
			$this->assertNotEmpty($task->getUserId());
		}
	}

	/**
	 * @covers TaskRepository::getPendingTasks
	 * @return void
	 */
	public function testGetPendingTasksThatFailed(): void
	{
		$actionTargetEntity = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);

		// a task that failed is retried and should be returned by getPendingTasks if nb attempts is under 3, and it was done more than 5 minutes ago
		$moreThan5MinutesAgo = new \DateTimeImmutable('-10 minutes');
		$failedTaskToBeRetried = new TaskEntity(
			0,
			TaskStatusEnum::FAILED,
			$this->automationEntity->getActions()[0],
			$this->dataset['coordinator'],
			['actionTargetEntity' => $actionTargetEntity->serialize()],
			$moreThan5MinutesAgo,
			$moreThan5MinutesAgo,
			$moreThan5MinutesAgo,
			$moreThan5MinutesAgo,
			1
		);
		$this->repository->saveTask($failedTaskToBeRetried);
		$this->h_dataset->addToSamples('tasks', $failedTaskToBeRetried->getId());

		$failedTaskNotToBeRetriedBecauseTooManyAttempts = new TaskEntity(
			0,
			TaskStatusEnum::FAILED,
			$this->automationEntity->getActions()[0],
			$this->dataset['coordinator'],
			['actionTargetEntity' => $actionTargetEntity->serialize()],
			$moreThan5MinutesAgo,
			$moreThan5MinutesAgo,
			$moreThan5MinutesAgo,
			$moreThan5MinutesAgo,
			4
		);
		$this->repository->saveTask($failedTaskNotToBeRetriedBecauseTooManyAttempts);
		$this->h_dataset->addToSamples('tasks', $failedTaskNotToBeRetriedBecauseTooManyAttempts->getId());

		$failedTaskNotToBeRetriedBecauseTooRecent = new TaskEntity(
			0,
			TaskStatusEnum::FAILED,
			$this->automationEntity->getActions()[0],
			$this->dataset['coordinator'],
			['actionTargetEntity' => $actionTargetEntity->serialize()],
			new \DateTimeImmutable('-2 minutes'),
			new \DateTimeImmutable('-2 minutes'),
			new \DateTimeImmutable('-2 minutes'),
			new \DateTimeImmutable('-2 minutes'),
			2
		);
		$this->repository->saveTask($failedTaskNotToBeRetriedBecauseTooRecent);
		$this->h_dataset->addToSamples('tasks', $failedTaskNotToBeRetriedBecauseTooRecent->getId());

		$pendingTasks = $this->repository->getPendingTasks();
		$this->assertIsArray($pendingTasks, 'Pending tasks should be returned as an array.');
		$foundFailedTaskToBeRetried = false;
		$foundFailedTaskNotToBeRetried = false;

		foreach ($pendingTasks as $task) {
			$this->assertInstanceOf(TaskEntity::class, $task, 'Each pending task should be an instance of TaskEntity.');
			// assert status is either PENDING or FAILED
			$this->assertTrue($task->getStatus() === TaskStatusEnum::PENDING || $task->getStatus() === TaskStatusEnum::FAILED, 'Each pending task should have status PENDING or FAILED.');
			$this->assertNotEmpty($task->getUserId());
			if ($task->getId() === $failedTaskToBeRetried->getId()) {
				$foundFailedTaskToBeRetried = true;
			}

			if ($task->getId() === $failedTaskNotToBeRetriedBecauseTooManyAttempts->getId()) {
				$foundFailedTaskNotToBeRetried = true;
			}
			if ($task->getId() === $failedTaskNotToBeRetriedBecauseTooRecent->getId())
			{
				$foundFailedTaskNotToBeRetried = true;
			}
		}
		$this->assertTrue($foundFailedTaskToBeRetried, 'The failed task that should be retried was not found in pending tasks.');
		$this->assertFalse($foundFailedTaskNotToBeRetried, 'The failed task that should not be retried was found in pending tasks.');
	}

	/**
	 * @covers TaskRepository::executeTask
	 * @return void
	 */
	public function testExecuteTask()
	{
		$action = $this->automationEntity->getActions()[0];
		assert($action instanceof ActionUpdateStatus);
		$actionTargetEntity = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);
		$metadata = ['actionTargetEntity' => $actionTargetEntity->serialize()];
		$task = new TaskEntity(0,TaskStatusEnum::PENDING, $action, $this->dataset['coordinator'], $metadata);
		$saved = $this->repository->saveTask($task);

		$this->assertTrue($saved, 'Task should be saved successfully.');
		$this->assertGreaterThan(0, $task->getId(), 'Task ID should be set after saving.');
		$this->h_dataset->addToSamples('tasks', $task->getId());

		$this->repository->executeTask($task);
		$this->assertEquals(TaskStatusEnum::COMPLETED, $task->getStatus(), 'Task status should be completed.');
		$this->assertNotNull($task->getStartedAt(), 'Task started at should not be null.');
		$this->assertNotNull($task->getFinishedAt(), 'Task finished at should not be null.');

		// sample automation action is designed to update state of file to 1, so we can check that
		$query = $this->db->createQuery()
			->select($this->db->quoteName('status'))
			->from($this->db->quoteName('#__emundus_campaign_candidature'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($this->dataset['fnum']));

		$this->db->setQuery($query);
		$status = $this->db->loadResult();

		$this->assertEquals($action->getParameterValue($action::STATUS_PARAMETER), $status, 'Candidature status should be updated to 1 after task execution.');
	}

	/**
	 * @covers TaskRepository::checkInProgressTasksHealth
	 * @return void
	 */
	public function testCheckInProgressTasksHealth(): void
	{
		$actionTargetEntity = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);
		$metadata = ['actionTargetEntity' => $actionTargetEntity->serialize()];

		$stuckTask = new TaskEntity(
			0,
			TaskStatusEnum::IN_PROGRESS,
			$this->automationEntity->getActions()[0],
			$this->dataset['coordinator'],
			$metadata,
			new \DateTimeImmutable('-2 hour'),
			new \DateTimeImmutable('-2 hour'),
			null,
			null,
			1
		);
		$this->repository->saveTask($stuckTask);
		$this->h_dataset->addToSamples('tasks', $stuckTask->getId());

		$notStuckTask = new TaskEntity(
			0,
			TaskStatusEnum::IN_PROGRESS,
			$this->automationEntity->getActions()[0],
			$this->dataset['coordinator'],
			$metadata,
			new \DateTimeImmutable('-10 minutes'),
			new \DateTimeImmutable('-10 minutes'),
			null,
			null,
			1
		);
		$this->repository->saveTask($notStuckTask);
		$this->h_dataset->addToSamples('tasks', $notStuckTask->getId());

		$this->repository->checkInProgressTasksHealth();

		$retrievedTask = $this->repository->getTaskById($stuckTask->getId());
		$this->assertInstanceOf(TaskEntity::class, $retrievedTask, 'Retrieved task should be an instance of TaskEntity.');
		$this->assertEquals(TaskStatusEnum::FAILED, $retrievedTask->getStatus(), 'Stuck task status should be set to FAILED after health check.');

		$retrievedTask = $this->repository->getTaskById($notStuckTask->getId());
		$this->assertInstanceOf(TaskEntity::class, $retrievedTask, 'Retrieved task should be an instance of TaskEntity.');
		$this->assertEquals(TaskStatusEnum::IN_PROGRESS, $retrievedTask->getStatus(), 'Not stuck task status should remain IN_PROGRESS after health check.');
	}

	/**
	 * @covers \Tchooz\Repositories\Task\TaskRepository::getPendingTasks
	 * Vérifie que les tâches sont retournées par ordre de priorité croissante
	 */
	public function testGetPendingTasksReturnsTasksOrderedByPriority(): void
	{
		$actionTargetEntity = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);
		$metadata = ['actionTargetEntity' => $actionTargetEntity->serialize()];

		$taskLow = new TaskEntity(0, TaskStatusEnum::PENDING, $this->automationEntity->getActions()[0], $this->dataset['coordinator'], $metadata, null, null, null, null, 0, TaskPriorityEnum::LOW);
		$this->repository->saveTask($taskLow);
		$this->h_dataset->addToSamples('tasks', $taskLow->getId());

		$taskHigh = new TaskEntity(0, TaskStatusEnum::PENDING, $this->automationEntity->getActions()[0], $this->dataset['coordinator'], $metadata, null, null, null, null, 0, TaskPriorityEnum::HIGH);
		$this->repository->saveTask($taskHigh);
		$this->h_dataset->addToSamples('tasks', $taskHigh->getId());

		$taskMedium = new TaskEntity(0, TaskStatusEnum::PENDING, $this->automationEntity->getActions()[0], $this->dataset['coordinator'], $metadata);
		$this->repository->saveTask($taskMedium);
		$this->h_dataset->addToSamples('tasks', $taskMedium->getId());

		$pendingTasks = $this->repository->getPendingTasks(10);

		$foundHighIndex = null;
		$foundMediumIndex = null;
		$foundLowIndex = null;

		foreach ($pendingTasks as $index => $task) {
			if ($task->getPriority() === TaskPriorityEnum::HIGH && $foundHighIndex === null) {
				$foundHighIndex = $index;
			} elseif ($task->getPriority() === TaskPriorityEnum::MEDIUM && $foundMediumIndex === null) {
				$foundMediumIndex = $index;
			} elseif ($task->getPriority() === TaskPriorityEnum::LOW && $foundLowIndex === null) {
				$foundLowIndex = $index;
			}

			if ($foundHighIndex !== null && $foundMediumIndex !== null && $foundLowIndex !== null) {
				break; // Found all priorities, no need to continue loop
			}
		}

		$this->assertNotNull($foundHighIndex, 'High priority task should be found in pending tasks.');
		$this->assertNotNull($foundMediumIndex, 'Medium priority task should be found in pending tasks.');
		$this->assertNotNull($foundLowIndex, 'Low priority task should be found in pending tasks.');
		$this->assertTrue($foundHighIndex < $foundMediumIndex, 'High priority task should be returned before medium priority task.');
		$this->assertTrue($foundMediumIndex < $foundLowIndex, 'Medium priority task should be returned before low priority task.');
	}
}