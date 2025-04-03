<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.Globalcheckin
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Microsoftdynamics\Extension;

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
use Joomla\Plugin\Emundus\MicrosoftDynamics\Factory\MicrosoftDynamicsFactory;
use Joomla\Plugin\Emundus\MicrosoftDynamics\Repository\MicrosoftDynamicsRepository;

require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';

class Microsoftdynamics extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	protected const TASKS_MAP = [
		'plg_task_microsoftdynamics_task_get' => [
			'langConstPrefix' => 'PLG_TASK_MICROSOFT_DYNAMICS',
			'form'            => 'microsoftForm',
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

	protected function makeCheckin(ExecuteTaskEvent $event): int
	{
		$failed = false;
		$db     = $this->getDatabase();

		$params = $event->getArgument('params');

		$debugMode = (bool) $params->debug_mode ?? false;
		$order = $params->order ?? '';

		if (!class_exists('EmundusModelSync'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
		}
		$mSync = new \EmundusModelSync();
		$api   = $mSync->getApi(0, 'microsoft_dynamics');

		if (!empty($api) && $api->enabled == 1)
		{

			try
			{
				$dispatcher = Factory::getApplication()->getDispatcher();

				Log::addLogger(['text_file' => 'com_emundus.microsoftdynamics.log.php'], Log::DEBUG, 'com_emundus.microsoftdynamics');
				Log::addLogger(['text_file' => 'com_emundus.microsoftdynamics.error.php'], Log::ERROR, 'com_emundus.microsoftdynamics');

				$repository = new MicrosoftDynamicsRepository($this->getDatabase());
				if (!class_exists('EmundusModelApplication'))
				{
					require_once JPATH_SITE . '/components/com_emundus/models/application.php';
				}
				$modelApplication = new \EmundusModelApplication();
				if (!class_exists('EmundusModelFiles'))
				{
					require_once JPATH_SITE . '/components/com_emundus/models/files.php';
				}
				$modelFiles = new \EmundusModelFiles();
				$crmFactory = new MicrosoftDynamicsFactory($this->getDatabase(), $modelApplication, $repository);

				$datasToImport = $repository->getDatas($order);

				foreach ($datasToImport as $dataToImport)
				{
					// If JSON is empty we have to prepare the data
					$config        = json_decode($dataToImport['config'], true);
					$json          = json_decode($dataToImport['json'], true);
					$lookupFilters = json_decode($dataToImport['lookup_filters'], true);

					if (empty($json))
					{
						$data           = json_decode($config['data'], true);
						$data['fnum']   = $dataToImport['fnum'];
						$configurations = $crmFactory->getMicrosoftDynamicsConfig($config['event'], $data, $config['training']);

						if (!empty($configurations))
						{
							foreach ($configurations as $config)
							{
								if ($config['action'] === $dataToImport['action'] && $config['collectionname'] === $dataToImport['collectionname'] && $config['name'] === $dataToImport['name'])
								{
									$fnumInfos = $modelFiles->getFnumInfos($dataToImport['fnum']);
									if (!empty($fnumInfos))
									{
										$data['fnumInfos'] = $fnumInfos;
									}

									if ($crmFactory->prepareDatas($api, $config, $data))
									{
										$json          = $repository->getJsonData($dataToImport);
										$lookupFilters = $repository->getLookupFilters($dataToImport);
									}
								}
							}
						}
					}


					$rowId = $repository->getRowId($dataToImport['name'], $dataToImport['collectionname'], $api, $lookupFilters);
					if (!empty($rowId))
					{
						$eventName = 'onAfterMicrosoftDynamicsUpdate';
					}
					else
					{
						$eventName = 'onAfterMicrosoftDynamicsCreate';
					}

					$result = $repository->flushApi($dataToImport['collectionname'], json_encode($json), $api, $rowId);

					if ($result['status'] == 204)
					{
						$log_status = 'success';
						$message    = '';

						$repository->deleteData($dataToImport['id']);
					}
					else
					{
						$log_status = 'error';
						$message    = $result['message'];
					}

					$onAfterMicrosoftDynamicsEventHandler = new GenericEvent(
						'onCallEventHandler',
						[$eventName,
							// Datas to pass to the event
							['fnum' => $dataToImport['fnum'], 'data' => $json, 'config' => ['name' => $dataToImport['name'], 'collectionname' => $dataToImport['collectionname']], 'status' => $log_status, 'message' => $message]
						]
					);
					$onAfterMicrosoftDynamics             = new GenericEvent(
						$eventName,
						// Datas to pass to the event
						['fnum' => $dataToImport['fnum'], 'data' => $json, 'config' => ['name' => $dataToImport['name'], 'collectionname' => $dataToImport['collectionname']], 'status' => $log_status, 'message' => $message]
					);

					// Dispatch the event
					$dispatcher->dispatch('onCallEventHandler', $onAfterMicrosoftDynamicsEventHandler);
					$dispatcher->dispatch($eventName, $onAfterMicrosoftDynamics);
				}
			}
			catch (ExecutionFailureException $e)
			{
				$failed = true;
			}
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}
}
