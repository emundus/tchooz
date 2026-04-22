<?php
/**
 * @package     Tchooz\Subscribers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Subscribers;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Event\SubscriberInterface;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Reference\InternalReferenceRepository;
use Tchooz\Repositories\Settings\ConfigurationRepository;
use Tchooz\Services\Reference\InternalReferenceFormat;
use Tchooz\Services\Reference\InternalReferenceService;

class GenerateReferenceSubscriber extends EmundusSubscriber
{
	public static function getSubscribedEvents(): array
	{
		return [
			'onCreateNewFile'     => 'generateShortReference',
			'onAfterStatusChange' => 'generateReference',
		];
	}

	public function generateShortReference(GenericEvent $event): void
	{
		try
		{
			$data = $event->getArguments();
			if (empty($data) || empty($data['fnum']))
			{
				return;
			}

			$applicationFileRepository = new ApplicationFileRepository();
			$internalReferenceService = new InternalReferenceService(
				new DateProvider(),
				$applicationFileRepository
			);

			$applicationFileEntity           = $applicationFileRepository->getByFnum($data['fnum']);
			$shortReference = $internalReferenceService->generateShortReference($applicationFileEntity);
			$applicationFileEntity->setShortReference($shortReference);
			if(!$applicationFileRepository->flush($applicationFileEntity))
			{
				Log::add('Failed to flush generated short reference for file ' . $applicationFileEntity->getFnum(), Log::ERROR);
			}

			// Maybe we have to generate a custom reference on the base status at file creation
			$event->addArgument('state', $applicationFileEntity->getStatus()->getStep());
			$event->addArgument('old_state', $applicationFileEntity->getStatus()->getStep());
			$this->generateReference($event);
		}
		catch (\Exception $e)
		{
			Log::add('Error while generating reference: ' . $e->getMessage(), Log::ERROR);

			return;
		}
	}

	public function generateReference(GenericEvent $event): void
	{
		try
		{
			$data = $event->getArguments();

			if (empty($data) || empty($data['fnum']) || !isset($data['state']) || !isset($data['old_state']))
			{
				return;
			}

			$applicationFileRepository = new ApplicationFileRepository();
			$internalReferenceService = new InternalReferenceService(
				new DateProvider(),
				$applicationFileRepository
			);
			$customReferenceFormatEntity = $internalReferenceService->getCustomReferenceFormatEntity();

			if (empty($customReferenceFormatEntity->getTriggeringStatus()) || $data['state'] != $customReferenceFormatEntity->getTriggeringStatus()->getStep())
			{
				return;
			}

			// We only want to generate if the application file does not have a reference yet
			$ccid                      = $applicationFileRepository->getIdByFnum($data['fnum']);
			if (empty($ccid))
			{
				return;
			}

			$internalReferenceRepository = new InternalReferenceRepository();
			$existingReference           = $internalReferenceRepository->getActiveReference($ccid);
			if (!empty($existingReference))
			{
				return;
			}

			if (!empty($data['context']))
			{
				assert($data['context'] instanceof EventContextEntity);

				$user = $data['context']->getUser();
			}
			else
			{
				$user = Factory::getApplication()->getIdentity();
			}

			$applicationFile = $applicationFileRepository->getById($ccid);
			$target          = new ActionTargetEntity(
				$user,
				$applicationFile->getFnum(),
				$applicationFile->getUser()->id,
			);

			$reference                = $internalReferenceService->generateReference($customReferenceFormatEntity, $target);

			if (!$internalReferenceRepository->flush($reference))
			{
				Log::add('Failed to flush generated reference for file ' . $data['fnum'], Log::ERROR);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error while generating reference: ' . $e->getMessage(), Log::ERROR);

			return;
		}
	}
}