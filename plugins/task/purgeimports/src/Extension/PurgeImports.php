<?php

namespace Joomla\Plugin\Task\PurgeImports\Extension;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Tchooz\Repositories\Import\ImportRepository;
use Tchooz\Repositories\Task\TaskRepository;

/**
 * Scheduled cleanup of finished imports: removes the durable source file and
 * the tracking row (plus the associated task) of imports completed or failed
 * more than N days ago. Twin of the PurgeExports task.
 */
// TODO migrate this class to the new global purge system
class PurgeImports extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * Default retention, in days, before a finished import is purged.
	 * @since 5.0.0
	 */
	public const DEFAULT_RETENTION_DAYS = 365;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	protected const TASKS_MAP = [
		'plg_task_purgeimports_task_get' => [
			'langConstPrefix' => 'PLG_TASK_PURGEIMPORTS',
			'form'            => 'purgeimports',
			'method'          => 'purgeImports',
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

	protected function purgeImports(ExecuteTaskEvent $event): int
	{
		Log::addLogger(['text_file' => 'task_purgeimports.log.php'], Log::ALL, ['task_purgeimports']);

		$failed = false;

		$params        = $event->getArgument('params');
		$retentionDays = (int) ($params->retention_days ?? self::DEFAULT_RETENTION_DAYS);
		if ($retentionDays < 1)
		{
			$retentionDays = self::DEFAULT_RETENTION_DAYS;
		}

		$expiredDate = new \DateTime();
		$expiredDate->modify('-' . $retentionDays . ' days');

		$importRepository = new ImportRepository();
		$taskRepository   = new TaskRepository();
		$expiredImports   = $importRepository->getExpiredImports($expiredDate);

		foreach ($expiredImports as $importEntity)
		{
			$task = $importEntity->getTask();

			// delete() also removes the durable source file under tmp/imports/.
			if (!$importRepository->delete($importEntity->getId()))
			{
				$failed = true;
				Log::add('Failed to delete import with ID: ' . $importEntity->getId(), Log::ERROR, 'task_purgeimports');
				continue;
			}

			if (!empty($task))
			{
				$taskRepository->deleteTaskById($task->getId());
			}
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}
}
