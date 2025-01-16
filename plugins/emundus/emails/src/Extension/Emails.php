<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\Emails\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
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
final class Emails extends CMSPlugin implements SubscriberInterface
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
			'onAfterBookingRegistrant'    => 'checkNotifications',
		];
	}

	public function checkNotifications(GenericEvent $event): void
	{
		$data = $event->getArguments();
		$db = $this->getDatabase();

		if(!empty($data['availability']->event_id) && !empty($data['fnum']))
		{
			$query = $db->getQuery(true);

			try
			{
				$query->select('applicant_notify_email')
					->from($db->quoteName('#__emundus_setup_events_notifications'))
					->where($db->quoteName('event') . ' = :eventId')
					->where($db->quoteName('applicant_notify') . ' = 1')
					->bind(':eventId', $data['availability']->event_id, ParameterType::INTEGER);
				$db->setQuery($query);
				$email_to_send = $db->loadResult();

				if(!empty($email_to_send)) {
					require_once(JPATH_BASE . '/components/com_emundus/helpers/date.php');
					require_once(JPATH_BASE . '/components/com_emundus/models/emails.php');
					$m_emails = new \EmundusModelEmails();

					$start_date = \EmundusHelperDate::displayDate($data['availability']->start, 'DATE_FORMAT_LC1');
					$start_hour = \EmundusHelperDate::displayDate($data['availability']->start, 'H:i');
					$end_hour = \EmundusHelperDate::displayDate($data['availability']->end, 'H:i');

					$location_link = $data['availability']->link;

					$complete_location = [];
					if(empty($location_link)) {
						$select = "CASE WHEN (er.link IS NOT NULL AND er.link <> '') THEN er.link WHEN (er.link IS NULL OR er.link = '') AND (ese.link IS NOT NULL AND ese.link <> '') THEN ese.link ELSE del.name END AS link,del.name,del.address,del.description";
						$query->clear()
							->select($select)
							->from($db->quoteName('#__emundus_registrants','er'))
							->leftJoin($db->quoteName('#__emundus_setup_events','ese').' ON '.$db->quoteName('er.event').' = '.$db->quoteName('ese.id'))
							->leftJoin($db->quoteName('data_events_location','del').' ON '.$db->quoteName('del.id').' = '.$db->quoteName('ese.location'))
							->where($db->quoteName('er.id').' = :registrantId')
							->bind(':registrantId', $data['registrant_id'], ParameterType::INTEGER);
						$db->setQuery($query);
						$complete_location = $db->loadAssoc();
					}

					if(!empty($complete_location)) {
						$location_link = $complete_location['link'];
						if(strpos($location_link, 'http') === false && !empty($complete_location['address'])) {
							$location_link = 'https://www.google.com/maps?q='.urlencode($complete_location['address']);
						}
					} else {
						$complete_location = [
							'link' => $location_link,
							'name' => $location_link,
							'address' => '',
							'description' => ''
						];
					}

					$post = [
						'BOOKING_START_DATE' => $start_date,
						'BOOKING_START_HOUR' => $start_hour,
						'BOOKING_END_HOUR' => $end_hour,
						'BOOKING_LOCATION' => $complete_location['name'] . (!empty($complete_location['address']) ? ' - ' . $complete_location['address'] : ''),
						'BOOKING_LOCATION_DESCRIPTION' => $complete_location['description'],
						'BOOKING_LOCATION_LINK' => $location_link,
					];

					// Generate ICS file to tmp folder and attach it to the email
					$ics_file = JPATH_BASE . '/tmp/' . $data['fnum'] . '.ics';
					$ics = "BEGIN:VCALENDAR\n";
					$ics .= "VERSION:2.0\n";
					$ics .= "PRODID:-//hacksw/handcal//NONSGML v1.0//EN\n";
					$ics .= "BEGIN:VEVENT\n";
					$ics .= "DTSTART:" . date('Ymd\THis\Z', strtotime($data['availability']->start)) . "\n";
					$ics .= "DTEND:" . date('Ymd\THis\Z', strtotime($data['availability']->end)) . "\n";
					$ics .= "SUMMARY:Emundus Booking\n";
					$ics .= "LOCATION:" . $complete_location['name'] . "\n";
					$ics .= "DESCRIPTION:" . $location_link . "\n";
					$ics .= "END:VEVENT\n";
					$ics .= "END:VCALENDAR\n";
					file_put_contents($ics_file, $ics);

					$sent = $m_emails->sendEmail($data['fnum'], $email_to_send, $post, [$ics_file]);
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error: ' . $e->getMessage(), Log::ERROR, 'emundus');
			}
		}
	}
}