<?php
/**
 * @package     Joomla\Plugin\Task\Inactiveaccounts\Service
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Task\Inactiveaccounts\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Plugin\Task\Inactiveaccounts\Enum\Mode;
use Joomla\Plugin\Task\Inactiveaccounts\Helper\Date;

class InactiveService
{
	private const LIMIT = 100;

	public function __construct(
		private int                      $delay,
		private array                    $reminders,
		private readonly ReminderService $reminderService,
		private                          $db,
		private                          $query,
		private int $taskId,
		private int                      $offset = 0,
		private bool                     $checkTestAccounts = false,
		private Mode                     $mode = Mode::DISABLE,
	)
	{
	}

	public function run(): bool
	{
		try
		{
			$results = [];

			if ($this->delay > 0)
			{
				$daysToDisableAfter = $this->delay * 30;

				$inactiveAccounts = $this->getInactiveAccounts($daysToDisableAfter, false, 0, 0);
				if (!empty($inactiveAccounts))
				{
					foreach ($inactiveAccounts as $inactiveAccount)
					{
						$account         = new User($inactiveAccount);
						$testing_account = $account->getParam('testing_account', 0) == 1;
						if ($account->activation != -1 && $testing_account == $this->checkTestAccounts)
						{
							if ($this->mode == Mode::DISABLE)
							{
								$account->activation = -2;
								$results[]           = $account->save();
							}
							elseif ($this->mode == Mode::DELETE)
							{
								// TODO: Send email with an archive of the account data
								$results[] = $account->delete();
							}
						}
					}
				}
				//

				// REMINDERS
				if (!empty($this->reminders))
				{
					$inactiveAccountsToReminder = $this->getInactiveAccounts(($daysToDisableAfter - $this->reminders[0]), true, $this->offset);
					if (!empty($inactiveAccountsToReminder))
					{
						foreach ($inactiveAccountsToReminder as $inactiveAccount)
						{
							$account         = new User($inactiveAccount);
							$testing_account = $account->getParam('testing_account', 0) == 1;

							if ($testing_account == $this->checkTestAccounts)
							{
								if (!$this->checkTestAccounts)
								{
									$this->reminderService->sendReminder($account, $daysToDisableAfter, $this->reminders);
								}
								else
								{
									$this->reminderService->sendReminder($account, $daysToDisableAfter, $this->reminders, 'PLG_TASK_INACTIVEACCOUNTS_TEST_EMAIL_SUBJECT', 'PLG_TASK_INACTIVEACCOUNTS_TEST_EMAIL_BODY');
								}
							}
						}
					}

					if(count($inactiveAccountsToReminder) < self::LIMIT)
					{
						$this->updateTaskOffset(0);
					}
					else
					{
						$this->updateTaskOffset($this->offset + self::LIMIT);
					}
				}
				//
			}

			return !in_array(false, $results, true);
		}
		catch (\Exception $e)
		{
			throw new \Exception($e);
		}
	}

	private function getInactiveAccounts(int $days, ?bool $activation = false, int $offset = 0, int $limit = self::LIMIT): array
	{
		$this->query->clear()
			->select('id')
			->from($this->db->quoteName('#__users'))
			->where($this->db->quoteName('lastvisitdate') . ' IS NOT NULL')
			->where($this->db->quoteName('lastvisitdate') . ' < ' . $this->db->quote(Date::getModifiedDate($days, true)));
		if ($activation)
		{
			$this->query->where($this->db->quoteName('activation') . ' <> -2');
		}
		$this->query->order('lastvisitdate ASC');
		if(!empty($limit))
		{
			$this->query->setLimit($limit, $offset);
		}
		$this->db->setQuery($this->query);

		try
		{
			return $this->db->loadColumn();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Error fetching inactive accounts: ' . $e->getMessage());
		}
	}

	public function setDelay(int $delay): void
	{
		$this->delay = $delay;
	}

	public function setReminders(array $reminders): void
	{
		$this->reminders = $reminders;
	}

	public function setCheckTestAccounts(bool $checkTestAccounts): void
	{
		$this->checkTestAccounts = $checkTestAccounts;
	}

	public function setMode(Mode $mode): void
	{
		$this->mode = $mode;
	}

	public function setOffset(int $offset): void
	{
		$this->offset = $offset;
	}

	public function updateTaskOffset(int $newOffset): bool
	{
		$this->query->clear()
			->select('id, note')
			->from($this->db->quoteName('#__scheduler_tasks'))
			->where($this->db->quoteName('id') . ' = ' . $this->taskId);
		$this->db->setQuery($this->query);

		try
		{
			$task = $this->db->loadObject();
			if(!empty($task->note))
			{
				$task->note = json_decode($task->note, true);
			}
			else
			{
				$task->note = [];
			}

			if($this->checkTestAccounts) {
				$task->note['last_test_offset'] = $newOffset;
			}
			else
			{
				$task->note['last_offset'] = $newOffset;
			}

			$task->note = json_encode($task->note);
			return $this->db->updateObject('#__scheduler_tasks', $task, 'id');
		}
		catch (\Exception $e)
		{
			throw new \Exception('Error updating task offset: ' . $e->getMessage());
		}
	}
}