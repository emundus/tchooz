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
use Joomla\CMS\Log\Log;
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

	private \EmundusModelSync $m_sync;
	private \EmundusModelEvents $m_events;
	private object $api;

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

		require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
		require_once JPATH_SITE . '/components/com_emundus/models/events.php';
		$this->m_sync   = new \EmundusModelSync();
		$this->m_events = new \EmundusModelEvents();
		$this->api      = $this->m_sync->getApi(0, 'teams');
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

		if (!empty($this->api) && is_object($data['availability']) && !empty($data['availability']->event_id))
		{
			$teams_id = null;
			if(!empty($data['registrantInfos']))
			{
				// Link already exists, do not create a new one
				$teams_id = $data['registrantInfos']['teams_id'] ?? null;
			}
			elseif (!empty($data['registrant_id']))
			{
				// Get via registrant_id
				$teams_id = $this->getTeamsId($data['registrant_id']);
			}

			if(!empty($teams_id))
			{
				// Link already exists, do not create a new one
				return;
			}

			$event = $this->checkTeamsEnabled($data['availability']->event_id);
			if(!empty($event))
			{
				$config = json_decode($this->api->config);

				if (!empty($config->authentication->email))
				{
					$meetingId = '';

					$offset     = Factory::getApplication()->get('offset');
					$dateFormat = 'Y-m-d\TH:i:s\Z';
					$start_date = new \DateTime($data['availability']->start, new \DateTimeZone('UTC'));
					$start_date = $start_date->format($dateFormat);
					$end_date   = new \DateTime($data['availability']->end, new \DateTimeZone('UTC'));
					$end_date   = $end_date->format($dateFormat);

					$post    = [
						'APPLICANT_NAME' => $this->getApplicantName($data['ccid']),
						'EVENT_NAME'     => $event->name,
					];
					$subject = $event->teams_subject;
					foreach ($post as $key => $value)
					{
						$subject = str_replace('[' . $key . ']', $value, $subject);
					}

					$params = [
						'subject'               => $subject,
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

					$result = $this->m_sync->callApi($this->api, 'users/' . $config->authentication->email . '/calendar/events', 'post', $params);

					if ($result['status'] == 201)
					{
						$link     = $result['data']->onlineMeeting->joinUrl;
						$teams_id = $result['data']->id;

						if (!empty($link))
						{
							// TODO: Check where to save the link
							$this->m_events->updateLink($data['registrant_id'], $link, $teams_id);
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

		if (!empty($data['teams_id']) && !empty($this->api) && !empty($this->checkTeamsEnabled($data['event_id'])))
		{
			$config = json_decode($this->api->config);
			$result = $this->m_sync->callApi($this->api, 'users/' . $config->authentication->email . '/calendar/events/' . $data['teams_id'], 'delete', []);

			if($result['status'] != 204)
			{
				Log::add('Error deleting Teams link: ' . $result['message'], Log::ERROR, 'com_emundus.api');
			}
		}
	}

	private function getApplicantName($ccid): string
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->clear()
			->select('concat(eu.lastname, " ", eu.firstname) as name')
			->from($db->quoteName('#__emundus_campaign_candidature', 'cc'))
			->leftJoin($db->quoteName('#__emundus_users', 'eu') . ' ON ' . $db->quoteName('eu.user_id') . ' = ' . $db->quoteName('cc.applicant_id'))
			->where('cc.id = ' . $ccid);
		$db->setQuery($query);

		return $db->loadResult();
	}

	private function getTeamsId(int $registrant_id): string|null
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->clear()
			->select($db->quoteName('teams_id'))
			->from($db->quoteName('#__emundus_registrants'))
			->where($db->quoteName('id') . ' = ' . $registrant_id);
		$db->setQuery($query);

		return $db->loadResult();
	}

	private function checkTeamsEnabled(int $event_id): object|null
	{
		$event = null;

		if (!empty($this->api) && $this->api->enabled == 1)
		{
			//TODO: Manage generation via configuration of the event
			$event = $this->m_events->getEvent($event_id);
			if ($event->is_conference_link && $event->conference_engine === 'teams')
			{
				return $event;
			} else {
				return null;
			}
		}

		return $event;
	}
}