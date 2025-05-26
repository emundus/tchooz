<?php

namespace Joomla\Plugin\Task\CleanTransactions\Extension;

use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Repositories\Payment\TransactionRepository;
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


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * A task plugin. For Delete Action Logs after x days
 * {@see ExecuteTaskEvent}.
 *
 * @since 5.0.0
 */
final class CleanTransactions extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	private const TASKS_MAP = [
		'plg_task_cleantransactions' => [
			'langConstPrefix' => 'PLG_TASK_CLEANTRANSACTIONS',
			'form'            => 'cron',
			'method'          => 'cleanTransactions',
		],
	];


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

	public function cleanTransactions(ExecuteTaskEvent $event)
	{
		$sent = false;

		$this->db = Factory::getContainer()->get('DatabaseDriver');

		// get all iniated transactions that have been created more than 2 hours ago
		$transaction_repository = new TransactionRepository();

		$now_minus_2_hours = date('Y-m-d H:i:s', strtotime('-2 hours'));
		$transactions = $transaction_repository->getTransactions(9999, 1, ['status' => TransactionStatus::INITIATED->value], $now_minus_2_hours);

		if (empty($transactions)) {
			Log::add('No transactions to clean', Log::INFO, 'com_emundus.task.cleantransactions');
			return Status::OK;
		} else {
			Log::add('Cleaning transactions', Log::INFO, 'com_emundus.task.cleantransactions');
			$task_automated_user = 1;

			foreach ($transactions as $transaction) {
				$transaction->setStatus(TransactionStatus::FAILED);
				$transaction->setUpdatedAt(date('Y-m-d H:i:s'));
				$transaction->setUpdatedBy($task_automated_user);

				try {
					$transaction_repository->saveTransaction($transaction, $task_automated_user);
				} catch (\Exception $e) {
					Log::add('Error cleaning transaction: ' . $e->getMessage(), Log::ERROR, 'com_emundus.task.cleantransactions');
					return Status::INVALID_EXIT;
				}
			}
		}

		return $sent ? Status::OK : Status::INVALID_EXIT;
	}
}
