<?php

namespace Joomla\Plugin\Task\SignatureRequests\Extension;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Tchooz\Enums\NumericSign\SignStatusEnum;
use Tchooz\Factories\NumericSign\NumericSignServiceFactory;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Component\Scheduler\Administrator\Task\Status;

class SignatureRequests extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	private RequestRepository $repository;

	protected const TASKS_MAP = [
		'yousign.api' => [
			'langConstPrefix' => 'PLG_TASK_SIGNATURE_REQUESTS',
			'form'            => 'signatureRequestsForm',
			'method'          => 'manageSignatureRequests',
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

	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->repository = new RequestRepository();
		Log::addLogger(['text_file' => 'plg_emundus_signature_requests.log'], Log::ALL, ['plg_emundus_signature_requests']);
	}

	/**
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return int
	 * @throws Exception
	 */
	public function manageSignatureRequests(ExecuteTaskEvent $event): int
	{
		$failed = false;
		$db     = $this->getDatabase();
		$params = $event->getArgument('params');

		$requests = $this->repository->getRequests([
			'esr.status' => [
				SignStatusEnum::TO_SIGN->value,
				SignStatusEnum::AWAITING->value,
				SignStatusEnum::REMINDER_SENT->value,
			],
		]);

		if (!empty($requests))
		{
			foreach ($requests as $request)
			{
				try
				{
					$service = NumericSignServiceFactory::fromRequest($request);

					if (!$service->managesRequest($request))
					{
						$failed = true;
					}
				}
				catch (\Exception $e)
				{
					Log::add($e->getMessage(), Log::ERROR, 'plg_emundus_signature_requests');
					$failed = true;
				}
			}
		}

		return $failed ? Status::INVALID_EXIT : Status::OK;
	}
}