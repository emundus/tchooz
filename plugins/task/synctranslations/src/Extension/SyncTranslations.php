<?php

namespace Joomla\Plugin\Task\SyncTranslations\Extension;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Tchooz\Services\Language\DbLanguage;

class SyncTranslations extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	protected const TASKS_MAP = [
		'plg_task_synctranslations' => [
			'langConstPrefix' => 'PLG_TASK_SYNCTRANSLATIONS',
			'form'            => 'synctranslationsForm',
			'method'          => 'syncTranslations',
		],
	];

	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	public function __construct($config = [])
	{
		parent::__construct($config);
		Log::addLogger(['text_file' => 'plg_emundus_sync_translations.log'], Log::ALL, ['plg_emundus_sync_translations']);
	}

	/**
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return int
	 * @throws Exception
	 */
	public function syncTranslations(ExecuteTaskEvent $event): int
	{
		$params = $event->getArgument('params');

		$dbLanguage = new DbLanguage();

		if(!$dbLanguage->databaseToFiles())
		{
			Log::add('Error syncing translations from database to files', Log::ERROR, 'plg_emundus_sync_translations');
			return Status::INVALID_EXIT;
		}

		if(!$dbLanguage->filesToDatabase())
		{
			Log::add('Error syncing translations from files to database', Log::ERROR, 'plg_emundus_sync_translations');
			return Status::INVALID_EXIT;
		}

		return Status::OK;
	}
}