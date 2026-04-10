<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Emundus.anonymization
 *
 * @copyright   (C) 2024 emundus.fr. All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\Anonymization\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class Anonymization extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

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

	/**
	 * Apply anonymization policy when a new application file is created.
	 *
	 * @param   GenericEvent  $event  The event object containing:
	 *   - user_id (int)
	 *   - fnum (string)
	 *   - cid (int) campaign id
	 *
	 * @return bool
	 */
	public function onCreateNewFile(GenericEvent $event): bool
	{
		$data = $event->getArguments();

		$campaign_id = (int) ($data['cid'] ?? 0);
		$fnum = $data['fnum'] ?? '';

		if (empty($campaign_id) || empty($fnum))
		{
			return true;
		}

		try
		{
			$campaignRepository = new CampaignRepository(false);
			$campaign = $campaignRepository->getById($campaign_id);

			if (empty($campaign))
			{
				return true;
			}

			$policy = $campaign->getAnonymizationPolicy();
			$anonymous = null;

			switch ($policy)
			{
				case AnonymizationPolicyEnum::FORCED:
					$anonymous = 1;
					break;

				case AnonymizationPolicyEnum::FORBIDDEN:
					$anonymous = 0;
					break;

				case AnonymizationPolicyEnum::OPTIONAL:
					// Keep the user's choice, do nothing
					break;

				case AnonymizationPolicyEnum::GLOBAL:
				default:
					$addonRepository = new AddonRepository();
					$addon = $addonRepository->getByName('anonymous');

					if ($addon !== null && $addon->getValue()->isEnabled())
					{
						$addonPolicy = $addon->getValue()->getParams()['policy'] ?? 'forbidden';
						$addonPolicy = AnonymizationPolicyEnum::tryFrom($addonPolicy) ?? AnonymizationPolicyEnum::FORBIDDEN;

						if ($addonPolicy === AnonymizationPolicyEnum::FORCED)
						{
							$anonymous = 1;
						}
						elseif ($addonPolicy === AnonymizationPolicyEnum::FORBIDDEN || $addonPolicy === AnonymizationPolicyEnum::GLOBAL)
						{
							$anonymous = 0;
						}
					}
					else
					{
						$anonymous = 0;
					}
					break;
			}

			if ($anonymous !== null)
			{
				$db = $this->getDatabase();
				$query = $db->getQuery(true)
					->update($db->quoteName('#__emundus_campaign_candidature'))
					->set($db->quoteName('anonymous') . ' = ' . (int) $anonymous)
					->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
				$db->setQuery($query);
				$updated = $db->execute();

				if ($updated && isset($data['application_file']) && $data['application_file'] instanceof ApplicationFileEntity)
				{
					$data['application_file']->setIsAnonymous((bool) $anonymous);
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error applying anonymization policy for fnum ' . $fnum . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.anonymization');
		}

		return true;
	}
}

