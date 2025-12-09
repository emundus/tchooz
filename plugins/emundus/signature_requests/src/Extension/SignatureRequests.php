<?php
namespace Joomla\Plugin\Emundus\SignatureRequests\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Tchooz\Factories\NumericSign\NumericSignServiceFactory;
use Tchooz\Repositories\NumericSign\RequestRepository;

\defined('_JEXEC') or die;

class SignatureRequests extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

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

	/**
	 * @param   GenericEvent  $event
	 *
	 * @return void
	 */
	public function createRequest(GenericEvent $event): void
	{
		try
		{
			$data = $event->getArguments();

			if (empty($data['request_id']))
			{
				return;
			}
			$requestRepository = new RequestRepository();
			$request = $requestRepository->loadRequestById($data['request_id']);

			if (!empty($request))
			{
				$service = NumericSignServiceFactory::fromRequest($request);

				if (!method_exists($service, 'managesRequest'))
				{
					throw new \Exception(Text::_('COM_EMUNDUS_NUMERICSIGN_ERROR_SERVICE_NOT_MANAGING_REQUEST'));
				}

				if (!$service->managesRequest($request))
				{
					throw new \Exception(Text::_('COM_EMUNDUS_NUMERICSIGN_ERROR_SERVICE_NOT_MANAGING_REQUEST'));
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'plg_emundus_signature_requests');
		}
	}

	/**
	 * @param   GenericEvent  $event
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function cancelRequest(GenericEvent $event): void
	{
		$data                  = $event->getArguments();
		$data['cancel_reason'] = $data['cancel_reason'] ?? '';

		if (empty($data['request_id']))
		{
			return;
		}
		$requestRepository = new RequestRepository();
		$request           = $requestRepository->loadRequestById($data['request_id']);

		if (!empty($request))
		{
			$service = NumericSignServiceFactory::fromRequest($request);

			if (!method_exists($service, 'cancelRequest'))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_NUMERICSIGN_ERROR_SERVICE_NOT_MANAGING_REQUEST'));
			}

			if (!$service->cancelRequest($request, $data['cancel_reason']))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_NUMERICSIGN_ERROR_SERVICE_NOT_MANAGING_REQUEST'));
			}
		}
	}
}