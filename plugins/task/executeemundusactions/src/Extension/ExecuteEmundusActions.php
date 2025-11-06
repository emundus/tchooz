<?php

namespace Joomla\Plugin\Task\ExecuteEmundusActions\Extension;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Repositories\Task\TaskRepository;

class ExecuteEmundusActions extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	protected const TASKS_MAP = [
		'plg_task_executeemundusactions_task_get' => [
			'langConstPrefix' => 'PLG_TASK_EXECUTEEMUNDUSACTIONS',
			'method'          => 'executePendingActions',
		],
	];

	/**
	 * @var boolean
	 * @since 5.0.0
	 */
	protected $autoloadLanguage = true;


	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	protected function executePendingActions(ExecuteTaskEvent $event): int
	{
		$failed = false;
		$repository = new TaskRepository();
		$tasks = $repository->getPendingTasks();
		Log::addLogger(['text_file' => 'task_executeemundusactions.log.php'], Log::ALL, ['task_executeemundusactions']);

		if (!empty($tasks))
		{
			Log::add('Found ' . count($tasks) . ' pending tasks to execute.', Log::DEBUG, 'task_executeemundusactions');
			foreach ($tasks as $task) {
				try {
					$repository->executeTask($task);
					if ($task->getStatus() !== TaskStatusEnum::COMPLETED)
					{
						$failed = true;
					} else {
						Log::add('Successfully executed task ID ' . $task->getId() . '.', Log::DEBUG, 'task_executeemundusactions');
					}
				} catch (\Exception $e) {
					Log::add('Error executing task ID ' . $task->getId() . ': ' . $e->getMessage(), Log::ERROR, 'task_executeemundusactions');

					if ($task->getStatus() !== TaskStatusEnum::FAILED) {
						$task->setStatus(TaskStatusEnum::FAILED);
						$repository->saveTask($task);
					}
					$failed = true;
				}
			}
		} else {
			Log::add('No pending tasks found to execute.', Log::DEBUG, 'task_executeemundusactions');
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}
}