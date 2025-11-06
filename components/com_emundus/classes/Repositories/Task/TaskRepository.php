<?php

namespace Tchooz\Repositories\Task;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Factories\Task\TaskFactory;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: 'jos_emundus_task')]
class TaskRepository
{
	use TraitTable;

	private CONST MAX_PENDING_TASKS = 100;


	private DatabaseDriver $db;

	public function __construct(?DatabaseDriver $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
		Log::addLogger(['text_file' => 'com_emundus.task.repository.log.php'], Log::ALL, 'com_emundus.task.repository');
	}

	public function getTaskById(int $taskId): ?TaskEntity
	{
		$task = null;

		if (!empty($taskId))
		{
			$query = $this->db->createQuery()
				->select('*')
				->from($this->getTableName(self::class))
				->where($this->db->quoteName('id') . ' = ' . $taskId);

			$this->db->setQuery($query);
			$dbObject = $this->db->loadObject();

			if (!empty($dbObject))
			{
				$tasks = TaskFactory::fromDbObjects([$dbObject], $this->db);
				$task = $tasks[0] ?? null;
			}
		}

		return $task;
	}

	public function getPendingTasks(int $limit = 100): array
	{
		$pendingTasks = [];

		if ($limit > self::MAX_PENDING_TASKS) {
			$limit = self::MAX_PENDING_TASKS;
		}

		$query = $this->db->createQuery();
		$query->select('*')
			->from($this->getTableName(self::class))
			->where($this->db->quoteName('status') . ' = ' . $this->db->quote(TaskStatusEnum::PENDING->value))
			->order($this->db->quoteName('created_at') . ' ASC')
			->setLimit($limit);

		$this->db->setQuery($query);
		$dbObjects = $this->db->loadObjectList();

		if (!empty($dbObjects)) {
			$pendingTasks = TaskFactory::fromDbObjects($dbObjects, $this->db);
		}

		return $pendingTasks;
	}

	/**
	 * @param   array  $filters
	 *
	 * @return int
	 */
	public function getTasksCount(array $filters = []): int
	{
		$count = 0;

		try {
			$query = $this->db->createQuery();
			$query->select('COUNT(id)')
				->from($this->getTableName(self::class));

			$this->applyFilters($filters, $query);

			$this->db->setQuery($query);
			$count = (int) $this->db->loadResult();
		} catch (\Exception $e) {
			Log::add('Error getting tasks count: ' . $e->getMessage(), Log::ERROR, 'com_emundus.task.repository');
		}

		return $count;
	}

	/**
	 * @param   array  $filters
	 * @param   int    $limit
	 * @param   int    $page
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getTasks(array $filters = [], int $limit = 100, int $page = 1): array
	{
		$tasks = [];

		$query = $this->db->createQuery();
		$query->select('*')
			->from($this->getTableName(self::class))
			->order($this->db->quoteName('created_at') . ' DESC');

		$this->applyFilters($filters, $query);

		if ($page < 1) {
			$page = 1;
		}

		$query->setLimit($limit, ($page - 1) * $limit);

		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();

		if (!empty($results))
		{
			$tasks = TaskFactory::fromDbObjects($results, $this->db);
		}

		return $tasks;
	}

	/**
	 * @param   array   $filters
	 * @param   object  $query
	 *
	 * @return void
	 */
	public function applyFilters(array $filters, object $query): void
	{
		if (!empty($filters))
		{
			foreach ($filters as $field => $value)
			{
				if (!in_array($field, ['status', 'action_id', 'user_id', 'id'], true)) {
					continue;
				}

				if (str_contains($value, ',')) {
					$values = explode(',', $value);
					$query->where($this->db->quoteName($field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $values)) . ')');
				} else {
					$query->where($this->db->quoteName($field) . ' = ' . $this->db->quote($value));
				}
			}
		}
	}

	/**
	 * @param   TaskEntity  $task
	 *
	 * @return bool
	 */
	public function saveTask(TaskEntity $task): bool
	{
		$saved = false;

		if (empty($task->getUserId()) || empty($task->getAction()) || empty($task->getAction()->getId()))
		{
			throw new \InvalidArgumentException('Task must have a valid user ID and action before saving.');
		}

		try {
			$query = $this->db->createQuery();
			if (!empty($task->getId()))
			{
				$query->clear()
					->update($this->getTableName(self::class))
					->set($this->db->quoteName('status') . ' = ' . $this->db->quote($task->getStatus()->value))
					->set($this->db->quoteName('action_id') . ' = ' . $this->db->quote($task->getAction()->getId()))
					->set($this->db->quoteName('user_id') . ' = ' . $this->db->quote($task->getUserId()))
					->set($this->db->quoteName('updated_at') . ' = ' . $this->db->quote((new \DateTimeImmutable())->format('Y-m-d H:i:s')))
					->set($this->db->quoteName('started_at') . ' = ' . ($task->getStartedAt() ? $this->db->quote($task->getStartedAt()->format('Y-m-d H:i:s')) : 'NULL'))
					->set($this->db->quoteName('finished_at') . ' = ' . ($task->getFinishedAt() ? $this->db->quote($task->getFinishedAt()->format('Y-m-d H:i:s')) : 'NULL'))
					->set($this->db->quoteName('metadata') . ' = ' . $this->db->quote(json_encode($task->getMetadata())))
					->set($this->db->quoteName('attempts') . ' = ' . $this->db->quote($task->getAttempts()))
					->where($this->db->quoteName('id') . ' = ' . $task->getId());

				$this->db->setQuery($query);
				$saved = $this->db->execute();
			}
			else
			{
				$query->clear()
					->insert($this->getTableName(self::class))
					->columns([
						$this->db->quoteName('status'),
						$this->db->quoteName('action_id'),
						$this->db->quoteName('user_id'),
						$this->db->quoteName('metadata'),
						$this->db->quoteName('created_at'),
						$this->db->quoteName('updated_at'),
						$this->db->quoteName('started_at'),
						$this->db->quoteName('finished_at'),
						$this->db->quoteName('attempts'),
					])
					->values(
						$this->db->quote($task->getStatus()->value) . ', ' .
						$this->db->quote($task->getAction()->getId()) . ', ' .
						$this->db->quote($task->getUserId()) . ', ' .
						$this->db->quote(json_encode($task->getMetadata())) . ', ' .
						$this->db->quote((new \DateTimeImmutable())->format('Y-m-d H:i:s')) . ', ' .
						$this->db->quote((new \DateTimeImmutable())->format('Y-m-d H:i:s')) . ', ' .
						($task->getStartedAt() ? $this->db->quote($task->getStartedAt()->format('Y-m-d H:i:s')) : 'NULL') . ', ' .
						($task->getFinishedAt() ? $this->db->quote($task->getFinishedAt()->format('Y-m-d H:i:s')) : 'NULL') . ', ' .
						$this->db->quote($task->getAttempts())
					);

				$this->db->setQuery($query);
				$saved = $this->db->execute();
				if ($saved)
				{
					$task->setId((int) $this->db->insertid());
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error saving task: ' . $e->getMessage(), Log::ERROR, 'com_emundus.task.repository');
			$saved = false;
		}

		return $saved;
	}

	/**
	 * @param int  $id
	 *
	 * @return bool
	 */
	public function deleteTaskById(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			try {
				$query = $this->db->createQuery()
					->delete($this->getTableName(self::class))
					->where($this->db->quoteName('id') . ' = ' . $id);

				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (\Exception $e) {
				Log::add('Error deleting task: ' . $e->getMessage(), Log::ERROR, 'com_emundus.task.repository');
			}
		}

		return $deleted;
	}

	/**
	 * @param   array<TaskEntity>  $tasks
	 *
	 * @return bool
	 */
	public function deleteTasks(array $tasks): bool
	{
		$deleted = false;

		try {
			$query = $this->db->createQuery();
			$query->delete($this->getTableName(self::class))
				->where($this->db->quoteName('id') . ' IN (' . implode(',', array_map(fn($task) => (int) $task->getId(), $tasks)) . ')');
			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		} catch (\Exception $e) {
			Log::add('Error deleting tasks: ' . $e->getMessage(), Log::ERROR, 'com_emundus.task.repository');
		}

		return $deleted;
	}

	/**
	 * @param   ActionEntity        $action
	 * @param   ActionTargetEntity  $actionTargetEntity
	 * @param   int                 $userId
	 *
	 * @return TaskEntity|null
	 */
	public function addActionToQueue(ActionEntity $action, ActionTargetEntity $actionTargetEntity, int $userId): ?TaskEntity
	{
		$task = null;

		if (!empty($action->getId())) {
			$task = new TaskEntity(0, TaskStatusEnum::PENDING, $action, $userId, ['actionTargetEntity' => $actionTargetEntity->serialize()]);
			$saved = $this->saveTask($task);

			if (!$saved) {
				$task = null;
			}
		}

		return $task;
	}

	/**
	 * @param   TaskEntity  $task
	 *
	 * @return TaskEntity
	 */
	public function executeTask(TaskEntity $task): TaskEntity
	{
		$task->setStatus(TaskStatusEnum::IN_PROGRESS);
		$task->setStartedAt(new \DateTimeImmutable());
		$task->setUpdatedAt(new \DateTimeImmutable());
		$this->saveTask($task);

		try {
			$task->execute();
		} catch (\Exception $e) {
			Log::add('Error executing task ID ' . $task->getId() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.task.repository');
			$task->setStatus(TaskStatusEnum::FAILED);
		}

		$task->setFinishedAt(new \DateTimeImmutable());
		$task->setUpdatedAt(new \DateTimeImmutable());
		$this->saveTask($task);

		return $task;
	}
}