<?php

namespace Joomla\Plugin\Task\ExecuteEmundusActions\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\DI\Container;
use Joomla\Event\SubscriberInterface;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Factories\Language\DbLanguageFactory;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\Task\TaskRepository;

class ExecuteEmundusActions extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	private const TIMEOUT_SECONDS = 30;

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
			// load front languages
			if (Factory::getApplication()->isClient('cli'))
			{
				if (!class_exists('DbLanguageFactory'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Factories/Language/DbLanguageFactory.php';
				}
				if (!class_exists('DbLanguage'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Services/Language/DbLanguage.php';
				}

				$defaultLangCode = LanguageFactory::getDefaultLanguageCode();

				$container = Factory::getContainer();
				$container->alias('language.factory', DbLanguageFactory::class)
					->share(
						DbLanguageFactory::class,
						function (Container $container) {
							return new DbLanguageFactory();
						},
						true
					);

				$lang = $container->get(DbLanguageFactory::class)->createLanguage($defaultLangCode, false);

				// Register the language object with Factory
				Factory::$language = $lang;
			}

			Log::add('Found ' . count($tasks) . ' pending tasks to execute.', Log::DEBUG, 'task_executeemundusactions');

			$startTime = microtime(true);
			$timeoutReached = false;
			foreach ($tasks as $i => $task) {
				if ((microtime(true) - $startTime) >= self::TIMEOUT_SECONDS) {
					Log::add('Execution stopped: timeout of ' . self::TIMEOUT_SECONDS . ' seconds reached. Executed ' . $i . ' out of ' . count($tasks) . ' tasks.', Log::INFO, 'task_executeemundusactions');
					$timeoutReached = true;
					break;
				}

				try {
					$repository->executeTask($task);
					if ($task->getStatus() === TaskStatusEnum::FAILED)
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

			if ($timeoutReached)
			{
				// If timeout reached, check health of in progress tasks to ensure no tasks are stuck
				$repository->checkInProgressTasksHealth();
				Log::add('Checked health of in progress tasks after timeout.', Log::DEBUG, 'task_executeemundusactions');
			}
		} else {
			Log::add('No pending tasks found to execute.', Log::DEBUG, 'task_executeemundusactions');

			// No tasks to process, consider it a success and check health of in progress tasks to ensure no tasks are stuck
			// check only here, because when there are tasks to process, they are prioritized
			$repository->checkInProgressTasksHealth();
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}
}