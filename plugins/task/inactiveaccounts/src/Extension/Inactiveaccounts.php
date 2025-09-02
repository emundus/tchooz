<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.CheckFiles
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Inactiveaccounts\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Task\Inactiveaccounts\Enum\Mode;
use Joomla\Plugin\Task\Inactiveaccounts\Service\InactiveService;
use Joomla\Plugin\Task\Inactiveaccounts\Service\ReminderService;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

if (!function_exists('json_validate')) {
	function json_validate(string $string): bool {
		json_decode($string);
		return (json_last_error() === JSON_ERROR_NONE);
	}
}

/**
 * Task plugin with routines that offer checks on inactive accounts.
 *
 * @since  4.1.0
 */
final class Inactiveaccounts extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	protected const TASKS_MAP = [
		'plg_task_inactiveaccounts_task_get' => [
			'langConstPrefix' => 'PLG_TASK_INACTIVEACCOUNTS',
			'form'            => 'inactiveaccounts_params',
			'method'          => 'makeChekInactiveAccounts',
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

	protected $autoloadLanguage = true;

	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);
	}

	protected function makeChekInactiveAccounts(ExecuteTaskEvent $event): int
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$emParams                              = ComponentHelper::getParams('com_emundus');
		$disableInactiveAccountsAfterDelay     = (int) $emParams->get('disable_inactive_accounts_after_delay', 18);
		$disableInactiveTestAccountsAfterDelay = 3;
		
		// Get last offset from task note
		$taskId = $event->getTaskId();

		$taskNote = $this->getNote($taskId, $db, $query);
		$lastOffset = $this->getLastOffset($taskNote);
		$lastTestOffset = $this->getLastTestOffset($taskNote);

		try
		{
			$reminderService = new ReminderService($db, $query, $this->getApplication());

			$this->logTask(sprintf('Disable inactive accounts after %d days', $disableInactiveAccountsAfterDelay));

			// Global accounts
			$inactiveService = new InactiveService(
				$disableInactiveAccountsAfterDelay,
				[],
				$reminderService,
				$db,
				$query,
				$taskId,
				$lastOffset,
				false,
				Mode::DISABLE
			);
			$inactiveService->run();
			//

			// Testing accounts
			$inactiveService->setDelay($disableInactiveTestAccountsAfterDelay);
			$inactiveService->setCheckTestAccounts(true);
			$inactiveService->setOffset($lastTestOffset);
			$inactiveService->run();
			//
		}
		catch (\Exception $e)
		{
			$this->logTask(sprintf('Error checking inactive accounts: %s', $e->getMessage()), 'error');

			// Ignore it
			return Status::KNOCKOUT;
		}

		return TaskStatus::OK;
	}

	private function getNote(int $taskId, DatabaseInterface $db, QueryInterface $query): ?string
	{
		$query->select($db->quoteName('note'))
			->from($db->quoteName('#__scheduler_tasks'))
			->where($db->quoteName('id') . ' = ' . $taskId);
		$db->setQuery($query);
		return $db->loadResult();
	}

	private function getLastOffset(?string $note): int
	{
		if(!empty($note) && json_validate($note))
		{
			$note = json_decode($note, true);
			if(isset($note['last_offset']) && is_numeric($note['last_offset']) && $note['last_offset'] >= 0)
			{
				return (int)$note['last_offset'];
			}
		}

		return 0;
	}

	private function getLastTestOffset(?string $note): int
	{
		if(!empty($note) && json_validate($note))
		{
			$note = json_decode($note, true);
			if(isset($note['last_test_offset']) && is_numeric($note['last_test_offset']) && $note['last_test_offset'] >= 0)
			{
				return (int)$note['last_test_offset'];
			}
		}

		return 0;
	}
}
