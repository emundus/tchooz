<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.Globalcheckin
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Parcoursup\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Emundus\Parcoursup\Factory\ParcoursupFactory;
use Joomla\Plugin\Emundus\Parcoursup\Factory\UserFactory;
use Joomla\Plugin\Emundus\Parcoursup\Repository\ParcoursupRepository;
use Joomla\Plugin\Emundus\Parcoursup\Repository\UserRepository;

/**
 * Task plugin with routines to check in a checked out item.
 *
 * @since  5.0.0
 */
class Parcoursup extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	protected const TASKS_MAP = [
		'plg_task_parcoursup_task_get' => [
			'langConstPrefix' => 'PLG_TASK_PARCOURSUP',
			'form'            => 'parcoursupForm',
			'method'          => 'makeCheckin',
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
	 * Standard method for the checkin routine.
	 *
	 * @param   ExecuteTaskEvent  $event  The onExecuteTask event
	 *
	 * @return  integer  The exit code
	 *
	 * @since   5.0.0
	 */
	protected function makeCheckin(ExecuteTaskEvent $event): int
	{
		$failed = false;
		$db     = $this->getDatabase();

		$params = $event->getArgument('params');

		$debugMode           = (bool) $params->debug_mode ?? false;
		$skipActivation      = (bool) $params->skip_activation ?? true;
		$deleteFiles         = (int) $params->delete_files_not_updated ?? 0;
		$deleteUsers         = (int) $params->delete_users ?? 0;
		$delayBeforeDeletion = (int) $params->delay_before_deletion ?? 3;

		try
		{
			$dispatcher = Factory::getApplication()->getDispatcher();

			Log::addLogger(['text_file' => 'com_emundus.parcoursup.log.php'], Log::DEBUG, 'com_emundus.parcoursup');
			Log::addLogger(['text_file' => 'com_emundus.parcoursup.error.php'], Log::ERROR, 'com_emundus.parcoursup');
			require_once JPATH_SITE . '/components/com_emundus/models/users.php';

			$mUsers            = new \EmundusModelUsers();
			$userRepository    = new UserRepository($mUsers);
			$userFactory       = new UserFactory();
			$repository        = new ParcoursupRepository($this->getDatabase(), $userRepository);
			$parcoursupFactory = new ParcoursupFactory($this->getDatabase(), $userFactory);

			$datasToImport = $repository->getDatas();

			foreach ($datasToImport as $dataToImport)
			{
				if (!empty($dataToImport))
				{
					$buildDatas = $parcoursupFactory->buildDatas($dataToImport, $skipActivation);

					if (!$repository->flush($buildDatas))
					{
						$onWebhookCallbackFailed = new GenericEvent(
							'onWebhookCallbackFailed',
							// Datas to pass to the event
							['type' => 'parcoursup', 'datas' => $buildDatas->getApplicationFile()]
						);
						$dispatcher->dispatch('onWebhookCallbackFailed', $onWebhookCallbackFailed);
					}
				}
			}

			if($deleteFiles === 1)
			{
				$idsParcoursupToDelete = $parcoursupFactory->getDatasToDelete($delayBeforeDeletion);

				foreach ($idsParcoursupToDelete as $idParcoursup)
				{
					$repository->deleteApplicationFile($idParcoursup, $deleteUsers);
				}
			}
		}
		catch (ExecutionFailureException $e)
		{
			$failed = true;
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}
}
