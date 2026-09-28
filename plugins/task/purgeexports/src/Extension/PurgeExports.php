<?php

namespace Joomla\Plugin\Task\PurgeExports\Extension;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Services\Export\ExportStorageCleaner;

class PurgeExports extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	protected const TASKS_MAP = [
		'plg_task_purgeexports_task_get' => [
			'langConstPrefix' => 'PLG_TASK_PURGEEXPORTS',
			'method'          => 'purgeExports',
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

	protected function purgeExports(ExecuteTaskEvent $event): int
	{
		$failed = false;

		$expiredDate = new \DateTime();
		$expiredDate->modify('-7 days');

		$exportRepository = new ExportRepository();
		$expiredExports = $exportRepository->getExpiredExports($expiredDate);

		foreach ($expiredExports as $exportEntity) {
			// Delete the export
			if(!$exportRepository->delete($exportEntity->getId()))
			{
				$failed = true;
				Log::add('Failed to delete export with ID: ' . $exportEntity->getId(), Log::ERROR, 'task_purgeexports');
			}
		}

		Log::addLogger(['text_file' => 'task_purgeexports.log.php'], Log::ALL, ['task_purgeexports']);

		try
		{
			$removed = (new ExportStorageCleaner($exportRepository))->deleteOrphans();
			Log::add('Removed ' . $removed . ' orphan export leftovers.', Log::INFO, 'task_purgeexports');
		}
		catch (\Throwable $e)
		{
			$failed = true;
			Log::add('Failed to remove orphan export leftovers: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), Log::ERROR, 'task_purgeexports');
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}
}