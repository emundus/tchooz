<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\ApplicationChoices\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class ApplicationChoices extends CMSPlugin implements SubscriberInterface
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
			'onCreateNewFile' => 'onCreateNewFile',
		];
	}

	public function onCreateNewFile(GenericEvent $event): bool
	{
		$data = $event->getArguments();
		$db   = $this->getDatabase();

		if(!empty($data['application_choice']))
		{
			try
			{
				$campaignRepository = new CampaignRepository();
				$applicationChoiceCampaign = $campaignRepository->getById((int) $data['application_choice']);
				$applicationChoiceRepository = new ApplicationChoicesRepository();

				$applicationChoiceEntity = new ApplicationChoicesEntity(
					fnum: $data['fnum'],
					user: $this->getUserFactory()->loadUserById($data['user_id']),
					campaign: $applicationChoiceCampaign,
					order: 0
				);

				if(!$applicationChoiceRepository->flush($applicationChoiceEntity))
				{
					throw new \Exception(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_FAILED'));
				}
			}
			catch (\Exception $e)
			{
				$this->getApplication()->enqueueMessage($e->getMessage(), 'warning');
			}
		}

		return true;
	}
}