<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.BookingRecall
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Plugin\Task\BookingRecall\Extension;

use DateTime;
use EmundusModelEmails;
use EmundusModelEvents;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\Log\Extension\Log;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Task plugin with routines that check whether a reminder mail should be sent for an event availability reservation
 *
 * @since  4.1.0
 */
final class BookingRecall extends CMSPlugin implements SubscriberInterface
{
	use TaskPluginTrait;

	/**
	 * @var string[]
	 *
	 * @since 4.1.0
	 */
	protected const TASKS_MAP = [
		'booking.recall' => [
			'langConstPrefix' => 'PLG_TASK_BOOKING_RECALL',
			'form'            => 'bookingrecall_param',
			'method'          => 'sendBookingRecalEmails',
		],
	];

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 4.1.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * @var boolean
	 * @since 4.1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The root directory path
	 *
	 * @var    string
	 * @since  4.2.0
	 */
	private $rootDirectory;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher     The dispatcher
	 * @param   array                $config         An optional associative array of configuration settings
	 * @param   string               $rootDirectory  The root directory to look for images
	 *
	 * @since   4.2.0
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config, string $rootDirectory)
	{
		parent::__construct($dispatcher, $config);

		$this->rootDirectory = $rootDirectory;
	}

	/**
	 * @param   ExecuteTaskEvent  $event  The onExecuteTask event
	 *
	 * @return integer  The exit code
	 *
	 * @throws \RuntimeException
	 * @throws \LogicException
	 * @since 4.1.0
	 */
	protected function sendBookingRecalEmails(ExecuteTaskEvent $event): int
	{
		include_once(JPATH_SITE . '/components/com_emundus/models/events.php');
		$m_events             = new EmundusModelEvents();
		$events_notifications = $m_events->getEventsNotifications();

		$db	= Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		foreach ($events_notifications as $notification)
		{
			if ($notification->applicant_recall || $notification->manager_recall || $notification->users_recall)
			{
				$registrants = $m_events->getAvailabilityRegistrants(0, 0, $notification->event);

				if ($notification->applicant_recall)
				{
					foreach ($registrants as $registrant)
					{
						$availability = $m_events->getEventsAvailabilities('', '', [], $registrant->availability)[0];
						$startDate    = new DateTime($availability->start);
						$currentDate  = new DateTime();
						$daysToAdd    = $notification->applicant_recall_frequency;
						$currentDate->modify("+{$daysToAdd} days");

						if ($startDate->format('Y-m-d') == $currentDate->format('Y-m-d'))
						{
							include_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
							$m_emails = new EmundusModelEmails;

							$post = $this->preparePost($availability, $registrant);

							$sent = $m_emails->sendEmail($registrant->fnum, $notification->applicant_recall_email, $post);

							// to avoid being considered as a spam process or DDoS
							sleep(5);
						}
					}
				}

				if ($notification->manager_recall)
				{
					try
					{
						$db    = Factory::getDbo();
						$query = $db->getQuery(true);

						$manager = null;
						$query->select($db->quoteName('manager'))
							->from($db->quoteName('#__emundus_setup_events'))
							->where($db->quoteName('id') . ' = ' . $db->quote($notification->event))
							->setLimit(1);
						$db->setQuery($query);
						$manager = $db->loadColumn();
						$manager = !empty($manager) ? $manager[0] : null;
					}
					catch (\Exception $e)
					{
						Log::add('Error when getting event manager: ' . $e->getMessage(), Log::ERROR, 'emundus');

						return TaskStatus::KNOCKOUT;
					}

					if ($manager !== null)
					{
						foreach ($registrants as $registrant)
						{
							$availability = $m_events->getEventsAvailabilities('', '', [], $registrant->availability)[0];
							$startDate    = new DateTime($availability->start);
							$currentDate  = new DateTime();
							$daysToAdd    = $notification->manager_recall_frequency;
							$currentDate->modify("+{$daysToAdd} days");

							if ($startDate->format('Y-m-d') == $currentDate->format('Y-m-d'))
							{
								$manager_email = null;
								try
								{
									$query->clear()
										->select($db->quoteName('email'))
										->from($db->quoteName('#__users'))
										->where($db->quoteName('id') . ' = ' . $db->quote($manager))
										->setLimit(1);
									$db->setQuery($query);

									$manager_email = $db->loadColumn();
									$manager_email = !empty($manager_email) ? $manager_email[0] : null;
								}
								catch (\Exception $e)
								{
									Log::add('Error when getting event manager email: ' . $e->getMessage(), Log::ERROR, 'emundus');

									return TaskStatus::KNOCKOUT;
								}

								if ($manager_email !== null)
								{
									include_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
									$m_emails = new EmundusModelEmails;

									$post = $this->preparePost($availability, $registrant);

									$m_emails->sendEmailNoFnum($manager_email, $notification->manager_recall_email,$post,null,[],$registrant->fnum);
									// to avoid being considered as a spam process or DDoS
									sleep(5);

								}
							}
						}
					}
				}

				if ($notification->users_recall)
				{
					$db    = Factory::getDbo();
					$query = $db->getQuery(true);

					foreach ($registrants as $registrant)
					{
						$users = null;
						try
						{
							$query->clear()
								->select($db->quoteName('user'))
								->from($db->quoteName('#__emundus_setup_slot_users'))
								->where($db->quoteName('slot') . ' = ' . $db->quote($registrant->slot));
							$db->setQuery($query);

							$users = $db->loadColumn();
						}
						catch (\Exception $e)
						{
							Log::add('Error when getting event users : ' . $e->getMessage(), Log::ERROR, 'emundus');

							return TaskStatus::KNOCKOUT;
						}

						if (!empty($users))
						{
							$availability = $m_events->getEventsAvailabilities('', '', [], $registrant->availability)[0];
							$startDate    = new DateTime($availability->start);
							$currentDate  = new DateTime();
							$daysToAdd    = $notification->users_recall_frequency;
							$currentDate->modify("+{$daysToAdd} days");

							if ($startDate->format('Y-m-d') == $currentDate->format('Y-m-d'))
							{
								$users_email = null;
								try
								{
									$query->clear()
										->select($db->quoteName('email'))
										->from($db->quoteName('#__users'))
										->where($db->quoteName('id') . ' IN (' . implode(',', array_map([$db, 'quote'], $users)) . ')');
									$db->setQuery($query);

									$users_email = $db->loadColumn();
								}
								catch (\Exception $e)
								{
									Log::add('Error when getting event users email: ' . $e->getMessage(), Log::ERROR, 'emundus');

									return TaskStatus::KNOCKOUT;
								}

								include_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
								$m_emails = new EmundusModelEmails;

								foreach ($users_email as $user_email)
								{
									if ($user_email !== null)
									{
										$post = $this->preparePost($availability, $registrant);

										$sent = $m_emails->sendEmailNoFnum($user_email, $notification->users_recall_email,$post,null,[],$registrant->fnum);
										// to avoid being considered as a spam process or DDoS
										sleep(5);
									}
								}
							}
						}
					}
				}
			}
		}

		return TaskStatus::OK;
	}

	private function preparePost($availability, $registrant): array
	{
		$post = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			$start_date = \EmundusHelperDate::displayDate($availability->start, 'DATE_FORMAT_LC1');
			$start_hour = \EmundusHelperDate::displayDate($availability->start, 'H:i');
			$end_hour = \EmundusHelperDate::displayDate($availability->end, 'H:i');

			$location_link = $availability->link;

			$complete_location = [];
			if(empty($location_link)) {
				$select = "CASE WHEN (er.link IS NOT NULL AND er.link <> '') THEN er.link WHEN (er.link IS NULL OR er.link = '') AND (ese.link IS NOT NULL AND ese.link <> '') THEN ese.link ELSE del.name END AS link,del.name,del.address,del.description";
				$query->clear()
					->select($select)
					->from($db->quoteName('#__emundus_registrants','er'))
					->leftJoin($db->quoteName('#__emundus_setup_events','ese').' ON '.$db->quoteName('er.event').' = '.$db->quoteName('ese.id'))
					->leftJoin($db->quoteName('data_events_location','del').' ON '.$db->quoteName('del.id').' = '.$db->quoteName('ese.location'))
					->where($db->quoteName('er.id').' = :registrantId')
					->bind(':registrantId', $registrant->id, ParameterType::INTEGER);
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
		}
		catch (\Exception $e)
		{
			Log::add('Error when getting event manager: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $post;
	}
}
