<?php

namespace Joomla\Plugin\Task\Payment\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\CMS\Log\Log;
use Tchooz\Repositories\Payment\TransactionRepository;

final class Payment extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;
	private int $limit = 100;

	private const TASKS_MAP = [
		'plg_task_payment' => [
			'langConstPrefix' => 'PLG_TASK_PAYMENT',
			'form'            => 'cron',
			'method'          => 'updateTransactions',
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

	/**
	 * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
	 *
	 * @return integer  The routine exit code.
	 *
	 * @throws \Exception
	 * @since  5.0.0
	 */
	private function updateTransactions(ExecuteTaskEvent $event): int
	{
		$updated = false;

		Log::addLogger(['text_file' => 'com_emundus.task.payment.php',], Log::ALL, ['com_emundus.task.payment']);

		$transaction_repository = new TransactionRepository();
		$rows_to_manage = $transaction_repository->getTransactionsInQueue();
		if (!empty($rows_to_manage)) {
			Log::add(sizeof($rows_to_manage) . ' pending transactions to manage', Log::INFO, 'com_emundus.task.payment');
			$updated = $transaction_repository->manageQueueTransactions($rows_to_manage);
		} else {
			Log::add('No pending transactions to manage', Log::INFO, 'com_emundus.task.payment');
			$updated = true;
		}

		return $updated ? Status::OK : Status::INVALID_EXIT;
	}
}