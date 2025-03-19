<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Task.deleteactionlogs
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Ammon\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Task\Ammon\Repository\AmmonRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * A task plugin. For Delete Action Logs after x days
 * {@see ExecuteTaskEvent}.
 *
 * @since 5.0.0
 */
final class Ammon extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	private const TASKS_MAP = [
		'plg_task_ammon' => [
			'langConstPrefix' => 'PLG_TASK_AMMON',
			'form'            => 'cron',
			'method'          => 'registerFilesInAmmon',
		],
	];

	private const PENDING = 'pending';
	private const FAILED = 'failed';
	private const SENT = 'sent';

	private int $limit = 100;

	private int $max_attempts = 3;

	private DatabaseDriver $db;

	/**
	 * @var boolean
	 * @since 5.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
	 *
	 * @return integer  The routine exit code.
	 *
	 * @throws \Exception
	 * @since  5.0.0
	 */
	private function registerFilesInAmmon(ExecuteTaskEvent $event): int
	{
		$sent             = false;

		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$params           = $event->getArgument('params');
		$this->limit            = (int)$params->limit ?? 100;
		$this->max_attempts = (int)$params->max_attempts ?? 3;
		Log::addLogger(['text_file' => 'plugin.emundus.ammon.php'], Log::ALL, array('plugin.emundus.ammon'));

		$pending_files = $this->getPendingFiles();
		if (!empty($pending_files))
		{
			foreach ($pending_files as $file)
			{
				$force_new_user_if_not_found = $file->force_new_user_if_not_found ?? false;

				$message = '';
				try {
					$repository = new AmmonRepository($file->fnum, $file->session_id, $file->file_status);
					$sent       = $repository->registerFileToSession($force_new_user_if_not_found);
				} catch (\Exception $e) {
					$sent   = false;
					Log::add('Something went wrong when trying to register fnum ' . $file->fnum . ' in ammon api : ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
					$message = $e->getMessage();
				}

				$this->updateQueue($file->fnum, $file->session_id, $sent);

				$dispatcher               = Factory::getApplication()->getDispatcher();
				$onAfterAmmonRegistration = new GenericEvent('onAfterAmmonRegistration', ['fnum' => $file->fnum, 'session_id' => $file->session_id, 'status' => ($sent ? 'success' : 'error'), 'message' => $message]);
				$dispatcher->dispatch('onAfterAmmonRegistration', $onAfterAmmonRegistration);
			}
		}
		else
		{
			Log::add('No pending files found', Log::INFO, 'plugin.emundus.ammon');
			$sent = true;
		}

		return $sent ? Status::OK : Status::INVALID_EXIT;
	}

	/**
	 * @return array
	 */
	private function getPendingFiles()
	{
		$pending_files = [];

		try {
			$query = $this->db->getQuery(true)
				->select('fnum, session_id, file_status, force_new_user_if_not_found')
				->from('#__emundus_ammon_queue')
				->where('status = ' . $this->db->quote(self::PENDING))
				->andWhere('attempts < ' . $this->max_attempts)
				->order('created_date ASC')
				->setLimit($this->limit);

			$pending_files = $this->db->setQuery($query)->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Something went wrong when trying to get pending files : ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
		}

		return $pending_files;
	}

	/**
	 * @param   string  $fnum
	 * @param   int     $session_id
	 * @param   bool    $sent
	 *
	 * @return bool
	 */
	private function updateQueue(string $fnum, int $session_id, bool $sent): bool
	{
		$updated = false;

		if (!empty($fnum) && !empty($session_id)) {
			$query = $this->db->createQuery();
			$query->select('attempts')
				->from('#__emundus_ammon_queue')
				->where('fnum = ' . $this->db->quote($fnum))
				->andWhere('session_id = ' . $this->db->quote($session_id));

			$attempts = $this->db->setQuery($query)->loadResult();

			$new_status = self::PENDING;
			if (!$sent) {
				if ($attempts >= $this->max_attempts) {
					$new_status = self::FAILED;
				}
			} else {
				$new_status = self::SENT;
			}

			$query->clear()
				->update('#__emundus_ammon_queue')
				->set('status = ' . $this->db->quote($new_status))
				->set('attempts = ' . ($attempts + 1))
				->where('fnum = ' . $this->db->quote($fnum))
				->andWhere('session_id = ' . $this->db->quote($session_id));

			$updated = $this->db->setQuery($query)->execute();
		}

		return $updated;
	}
}
