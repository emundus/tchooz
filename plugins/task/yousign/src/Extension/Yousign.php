<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.Globalcheckin
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Yousign\Extension;

use Tchooz\Entities\NumericSign\Request;
use Tchooz\Entities\NumericSign\YousignRequests;
use Tchooz\Enums\ApiStatus;
use Tchooz\Enums\NumericSign\SignStatus;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;
use Tchooz\Repositories\NumericSign\YousignRequestsRepository;
use Tchooz\Services\NumericSign\YousignService;
use Tchooz\Synchronizers\NumericSign\YousignSynchronizer;
use EmundusModelApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Event\SubscriberInterface;

class Yousign extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	protected const TASKS_MAP = [
		'yousign.api' => [
			'langConstPrefix' => 'PLG_TASK_YOUSIGN',
			'form'            => 'yousignForm',
			'method'          => 'makeCheckin',
		],
	];

	protected $autoloadLanguage = true;

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

		if (!class_exists('EmundusModelFiles'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/files.php';
		}
		if (!class_exists('EmundusModelApplication'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/application.php';
		}
	}

	protected function makeCheckin(ExecuteTaskEvent $event): int
	{
		$failed = false;
		$db     = $this->getDatabase();
		$app    = Factory::getApplication();

		$params = $event->getArgument('params');

		$debug_mode = (bool) $params->debug_mode ?? false;
		$order      = $params->order ?? '';

		if (!class_exists('EmundusModelSync'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
		}
		$m_sync = new \EmundusModelSync();
		$api    = $m_sync->getApi(0, 'yousign');

		if (!empty($api) && $api->enabled == 1)
		{
			try
			{
				$dispatcher = $app->getDispatcher();

				Log::addLogger(['text_file' => 'com_emundus.yousign.log.php'], Log::DEBUG, 'com_emundus.yousign');
				Log::addLogger(['text_file' => 'com_emundus.yousign.error.php'], Log::ERROR, 'com_emundus.yousign');

				$em_config = ComponentHelper::getParams('com_emundus');
				$automated_user_id = $em_config->get('automated_task_user', 1);
				$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($automated_user_id);

				$request_repository         = new RequestRepository($db);
				$request_signers_repository = new RequestSignersRepository($db);
				$yousign_repository         = new YousignRequestsRepository($db);
				$yousign_synchronizer       = new YousignSynchronizer();
				$m_files                    = new \EmundusModelFiles();
				$m_application              = new \EmundusModelApplication();

				$yousign_service = new YousignService(
					$yousign_synchronizer,
					$yousign_repository,
					$request_repository,
					$request_signers_repository,
					$m_files,
					$m_application,
					$user,
				);

				$not_signed_requests = $request_repository->getNotSignedRequests('yousign');
				if(!empty($not_signed_requests))
				{
					$yousign_requests = [];
					$cursor = null;

					do
					{
						$api_requests = $yousign_synchronizer->getRequests($cursor);
						if ($api_requests['status'] === 200)
						{
							$yousign_requests = array_merge($yousign_requests, $api_requests['data']->data);
							$cursor = $api_requests['data']->meta->next_cursor;
						}
					}
					while($api_requests['status'] === 200 && $cursor !== null);

					foreach ($not_signed_requests as $not_signed_request)
					{
						$failed = !$yousign_service->manageRequest($not_signed_request->getId(), $yousign_requests);
					}
				}
			}
			catch (ExecutionFailureException|\Exception $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus.yousign');
				$failed = true;
			}
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}
}
