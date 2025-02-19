<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\Teams\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
final class Teams extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher
	 * @param   array                $config      An optional associative array of configuration settings
	 *
	 * @since   3.9.0
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterBookingRegistrant'     => 'generateTeamsLink',
			'onAfterUnsubscribeRegistrant' => 'deleteTeamsLink',
		];
	}

	public function generateTeamsLink(GenericEvent $event): void
	{
		$name = $event->getName();
		$data = $event->getArguments();

		$m_sync = new \EmundusModelSync();
		$api    = $m_sync->getApi(0, 'teams');

		if (!empty($api) && $api->enabled == 1)
		{
			require_once JPATH_SITE . '/components/com_emundus/models/events.php';
			$m_events = new \EmundusModelEvents();

			//TODO: Manage generation via configuration of the event
			$event = $m_events->getEvent($data['availability']->event_id);

			if ($event->is_conference_link && $event->conference_engine === 'teams')
			{
				$config = json_decode($api->config);

				if (!empty($config->authentication->email))
				{
					$meetingId = '';

					$offset     = Factory::getApplication()->get('offset');
					$dateFormat = 'Y-m-d\TH:i:s\Z';
					$start_date = new \DateTime($data['availability']->start, new \DateTimeZone('UTC'));
					$start_date = $start_date->format($dateFormat);
					$end_date   = new \DateTime($data['availability']->end, new \DateTimeZone('UTC'));
					$end_date   = $end_date->format($dateFormat);

					$params = [
						//TODO: Add parameter to define name
						'subject'               => 'Meeting with ' . $data['fnum'],
						'body'                  => [
							'contentType' => 'html',
							'content'     => '',
						],
						'start'                 => [
							'dateTime' => $start_date,
							'timeZone' => $offset
						],
						'end'                   => [
							'dateTime' => $end_date,
							'timeZone' => $offset
						],
						'location'              => [
							'displayName' => 'Microsoft Teams Meeting'
						],
						'isOnlineMeeting'       => true,
						'onlineMeetingProvider' => 'teamsForBusiness',
					];

					require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
					$m_sync = new \EmundusModelSync();

					$result = $m_sync->callApi($api, 'users/' . $config->authentication->email . '/calendar/events', 'post', $params);

					if ($result['status'] == 201)
					{
						$link     = $result['data']->onlineMeeting->joinUrl;
						$teams_id = $result['data']->id;

						if (!empty($link))
						{
							// TODO: Check where to save the link
							$m_events->updateLink($data['registrant_id'], $link, $teams_id);
						}
					}
				}
			}
		}
	}

	public function deleteTeamsLink(GenericEvent $event): void
	{
		$name = $event->getName();
		$data = $event->getArguments();
	}
}