<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Task.deleteactionlogs
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\SendSMS\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Task\SendSMS\Repository\SendSMSRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * A task plugin. For Delete Action Logs after x days
 * {@see ExecuteTaskEvent}.
 *
 * @since 5.0.0
 */
final class sendSMS extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	private const TASKS_MAP = [
		'plg_task_sms_task_get' => [
			'langConstPrefix' => 'PLG_TASK_SMS',
			'form'            => 'cron',
			'method'          => 'sendPendingSMS',
		],
	];

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
	 * @since  5.0.0
	 * @throws \Exception
	 */
	private function sendPendingSMS(ExecuteTaskEvent $event): int
	{
		$params = $event->getArgument('params');
		$limit = $params->limit ?? 500;
		$maximum_attempts = $params->maximum_attempts ?? 3;
		$used_service = $params->service ?? 'ovh';
		$debug = isset($params->debug) ? $params->debug : false;

		try {
			$repository = new SendSMSRepository($limit, $maximum_attempts, $used_service, $debug);
			$sent = $repository->sendPendingSMS();
		} catch (\Exception $e) {
			$this->getApplication()->enqueueMessage($e->getMessage(), 'error');

			return Status::INVALID_EXIT;
		}

		return $sent ? Status::OK : Status::INVALID_EXIT;
	}
}
