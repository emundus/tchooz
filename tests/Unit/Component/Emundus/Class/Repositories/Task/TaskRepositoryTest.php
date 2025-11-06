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
		$this->assertContains($task->getStatus(), [TaskStatusEnum::COMPLETED], 'Task status should be either COMPLETED after execution.');
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
}