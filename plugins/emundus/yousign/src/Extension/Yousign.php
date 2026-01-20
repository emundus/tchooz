<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\Yousign\Extension;

require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
require_once JPATH_SITE . '/components/com_emundus/models/application.php';
require_once JPATH_SITE . '/components/com_emundus/models/files.php';

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Tchooz\Enums\NumericSign\SignConnectorsEnum;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;
use Tchooz\Repositories\NumericSign\YousignRequestsRepository;
use Tchooz\Services\NumericSign\YousignService;
use Tchooz\Synchronizers\NumericSign\YousignSynchronizer;
use Tchooz\Traits\TraitAutomatedTask;
use Tchooz\Traits\TraitDispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

final class Yousign extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;
	use TraitDispatcher;
	use TraitAutomatedTask;

	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterRequestSaved'     => 'createRequest',
			'onAfterRequestCancelled' => 'cancelRequest',
		];
	}

	public function createRequest(GenericEvent $event): void
	{
		try
		{
			$data              = $event->getArguments();
			$requestRepository = new RequestRepository();
			$request           = $requestRepository->loadRequestById($data['request_id']);

			if ($request->getConnector() !== SignConnectorsEnum::YOUSIGN)
			{
				return;
			}

			if (empty($data['request_id']))
			{
				return;
			}

			$user                       = $this->getAutomatedTaskUser();
			$db                         = $this->getDatabase();
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

			if (!$yousign_service->manageRequest($data['request_id']))
			{
				throw new \Exception('Failed to create Yousign request');
			}
		}
		catch (\Exception $e)
		{
			Log::add('Yousign cancelRequest error: ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
		}
	}

	public function cancelRequest(GenericEvent $event): void
	{
		try
		{
			$name = $event->getName();
			$data = $event->getArguments();

			if (empty($data['request_id']))
			{
				return;
			}

			$request_repository = new RequestRepository($this->getDatabase());
			$request            = $request_repository->loadRequestById($data['request_id']);
			if ($request->getConnector()->value !== 'yousign')
			{
				return;
			}

			$m_sync = new \EmundusModelSync();
			$api    = $m_sync->getApi(0, 'yousign');

			if (!empty($api) && $api->enabled == 1)
			{
				$yousign_request_repository = new YousignRequestsRepository($this->getDatabase());
				$yousign_request            = $yousign_request_repository->loadYousignRequestByRequestId($request);

				if (!empty($yousign_request) && !empty($yousign_request->getProcedureId()))
				{
					$yousign_synchronizer = new YousignSynchronizer();
					$api_cancel           = $yousign_synchronizer->cancelRequest($yousign_request->getProcedureId(), $data['cancel_reason']);
					if ($api_cancel['status'] !== 201)
					{
						throw new \Exception($api_cancel['message'], $api_cancel['status']);
					}

					if (!class_exists('EmundusModelFiles'))
					{
						require_once JPATH_SITE . '/components/com_emundus/models/files.php';
					}
					$m_files = new \EmundusModelFiles();

					$application_file = $m_files->getFnumInfos($request->getFnum());
					$this->dispatchJoomlaEvent('onYousignRequestCancelled', [
						'status'           => 'success',
						'yousign_request'  => $yousign_request,
						'request'          => $request,
						'application_file' => $application_file
					]);
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Yousign cancelRequest error: ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
		}
	}
}