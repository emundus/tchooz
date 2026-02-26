<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 *
 * @license     GNU/GPL
 * @author      eMundus
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

jimport('joomla.application.component.controller');

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Traits\TraitResponse;
use Tchooz\Controller\EmundusController;

class EmundusControllerEvents extends EmundusController
{
	private $m_events;

	private int $booking_access_id;

	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . '/components/com_emundus/models/events.php');

		$this->m_events          = $this->getModel('Events');
		$actionRepository        = new ActionRepository();
		$bookingAccess           = $actionRepository->getByName('booking');
		$this->booking_access_id = $bookingAccess->getId();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'event', 'mode' => CrudEnum::READ]])]
	public function getevents(): EmundusResponse
	{
		$order_by  = $this->input->getString('order_by', '');
		$sort      = $this->input->getString('sort', '');
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$location  = $this->input->getInt('location', 0);

		$actionRepository = new ActionRepository();
		$bookingAction = $actionRepository->getByName('booking');
		$bookingAccess = EmundusHelperAccess::asAccessAction($bookingAction->getId(), CrudEnum::READ->value, $this->user->id);

		$emundusUserRepository = new EmundusUserRepository();

		$userPrograms  = $emundusUserRepository->getUserProgramsCodes($this->user->id);
		if(empty($userPrograms))
		{
			throw new RuntimeException('User has no program assigned');
		}

		$events = $this->m_events->getEvents($order_by, $sort, $recherche, $lim, $page, $location, 0, $userPrograms);
		if (count($events) > 0)
		{
			// Search menu by link index.php?option=com_emundus&view=events&layout=registrants
			$emundusUser      = $this->app->getSession()->get('emundusUser');
			$registrants_menu = $this->app->getMenu()->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=events&layout=registrants', $emundusUser->menutype], 'true');

			foreach ($events['datas'] as $event)
			{
				$nb_programs_campaigns = $this->m_events->getProgramsCampaignsCount($event->id);

				$event->label = ['fr' => $event->label, 'en' => $event->label];

				$event->additional_columns = [];
				$no_campaigns_programs     = [];

				if ($nb_programs_campaigns === 0)
				{
					$no_campaigns_programs = [
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_EVENTS_CAMPAIGNS'),
							'value'   => '<span class="material-symbols-outlined tw-mr-2">warning</span>' . Text::_('COM_EMUNDUS_ONBOARD_EVENTS_NO_CAMPAIGNS'),
							'classes' => 'tw-flex tw-rounded-status tw-bg-yellow-100 tw-p-2',
							'display' => 'blocs'
						]
					];
				}


				$event->registrant_count = $this->m_events->getRegistrantCount($event->id);

				$event->additional_columns = [
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_EVENTS_COUNT_REGISTRANTS'),
						'value'   => $bookingAccess ? '<a class="em-profile-color hover:tw-font-semibold hover:tw-underline tw-font-semibold" href="/' . $registrants_menu->route . '?event=' . $event->id . '" style="line-height: unset;font-size: unset;">' . $event->registrant_count . ' ' . Text::_('COM_EMUNDUS_EVENTS_BOOKING') . '</a>' : $event->registrant_count . ' ' . Text::_('COM_EMUNDUS_EVENTS_BOOKING'),
						'classes' => 'go-to-campaign-link',
						'display' => 'blocs'
					],
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_EVENTS_COUNT_REGISTRANTS'),
						'value'   => $bookingAccess ? '<a class="em-profile-color hover:tw-font-semibold hover:tw-underline tw-font-semibold" href="/' . $registrants_menu->route . '?event=' . $event->id . '" style="line-height: unset;font-size: unset;">' . $event->registrant_count . ' ' . Text::_('COM_EMUNDUS_EVENTS_BOOKING') . '</a>': $event->registrant_count . ' ' . Text::_('COM_EMUNDUS_EVENTS_BOOKING'),
						'classes' => 'go-to-campaign-link',
						'display' => 'table'
					],
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_CAPACITY'),
						'value'   => $event->booked_count . ' / ' . $event->availabilities_count . ' ' . Text::_('COM_EMUNDUS_ONBOARD_ADD_EVENT_BOOKED_SLOT_NUMBER'),
						'classes' => '',
						'display' => 'all'
					],
				];

				$event->additional_columns = array_merge($event->additional_columns, $no_campaigns_programs);
			}
		}

		return EmundusResponse::ok($events);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function geteventsnames(): EmundusResponse
	{
		$events = $this->m_events->getEventsNames();

		return EmundusResponse::ok($events);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'event', 'mode' => CrudEnum::READ]])]
	public function getevent(): EmundusResponse
	{
		$event_id = $this->input->getInt('event_id', 0);
		if (empty($event_id))
		{
			throw new InvalidArgumentException('Event ID is required');
		}

		$event = $this->m_events->getEvent($event_id);

		return EmundusResponse::ok($event);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'event', 'mode' => CrudEnum::CREATE]])]
	public function createevent(): EmundusResponse
	{
		$name               = $this->input->getString('name', '');
		$color              = $this->input->getString('color', '#000000');
		$location           = $this->input->getString('location', 0);
		$is_conference_link = $this->input->getString('is_conference_link', 0);
		$conference_engine  = $this->input->getString('conference_engine', '');
		$link               = $this->input->getString('link', '');
		$generate_link_by   = $this->input->getString('generate_link_by', 0);
		$manager            = $this->input->getString('manager', 0);
		$manager            = json_decode($manager);
		$available_for      = $this->input->getString('available_for', 1);
		$campaigns          = $this->input->getRaw('campaigns', '[]');
		$campaigns          = json_decode($campaigns);
		$programs           = $this->input->getRaw('programs', '[]');
		$programs           = json_decode($programs);

		$event_id = $this->m_events->createEvent($name, $color, $location, $is_conference_link, $conference_engine, $link, $generate_link_by, $manager, $available_for, $campaigns, $programs, $this->user->id);
		if (empty($event_id))
		{
			throw new RuntimeException('Error creating event');
		}

		return EmundusResponse::ok($event_id);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'event', 'mode' => CrudEnum::UPDATE]])]
	public function editevent(): EmundusResponse
	{
		$id                 = $this->input->getInt('id', 0);
		$name               = $this->input->getString('name', '');
		$color              = $this->input->getString('color', '#000000');
		$location           = $this->input->getInt('location', 0);
		$is_conference_link = $this->input->getInt('is_conference_link', 0);
		$conference_engine  = $this->input->getString('conference_engine', '');
		$link               = $this->input->getString('link', '');
		$generate_link_by   = $this->input->getInt('generate_link_by', 0);
		$teams_subject      = $this->input->getString('teams_subject', '');
		$manager            = $this->input->getInt('manager', 0);
		$manager            = json_decode($manager);
		$available_for      = $this->input->getInt('available_for', 1);
		$campaigns          = $this->input->getRaw('campaigns', '[]');
		$campaigns          = json_decode($campaigns);
		$programs           = $this->input->getRaw('programs', '[]');
		$programs           = json_decode($programs);
		if (empty($id))
		{
			throw new InvalidArgumentException('Event ID is required');
		}

		$id = $this->m_events->editEvent($id, $name, $color, $location, $is_conference_link, $conference_engine, $link, $generate_link_by, $manager, $available_for, $campaigns, $programs, $teams_subject);
		if (empty($id))
		{
			throw new RuntimeException('Error editing event');
		}

		return EmundusResponse::ok($id);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'booking', 'mode' => CrudEnum::UPDATE]])]
	public function editslot(): EmundusResponse
	{
		$registrant_id = $this->input->getInt('id', 0);
		$availability  = $this->input->getInt('booking', 0);
		$event_id      = $this->input->getInt('event_id', 0);
		$users_id      = $this->input->getString('juror');
		$ccid          = $this->input->getInt('user', 0);

		if (!empty($users_id))
		{
			$users_id = explode(',', $users_id);
		}
		else
		{
			$users_id = [];
		}

		if (empty($registrant_id) || empty($availability) || empty($event_id))
		{
			throw new InvalidArgumentException('Missing required parameters');
		}

		$id = $this->m_events->editSlot($registrant_id, $availability, $event_id, $users_id, $ccid);
		if (empty($id))
		{
			throw new RuntimeException('Error editing slot');
		}

		return EmundusResponse::ok($id);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'booking', 'mode' => CrudEnum::CREATE]])]
	public function duplicateevent(): void
	{
		$event_id      = $this->input->getInt('id', 0);
		$redirect_link = $this->app->getMenu()->getItems('link', 'index.php?option=com_emundus&view=events&layout=add', true);
		if (empty($event_id))
		{
			throw new InvalidArgumentException('Event ID is required');
		}

		$event_id = $this->m_events->duplicateEvent($event_id);
		if (empty($event_id))
		{
			throw new RuntimeException('Error duplicating event');
		}

		$response['data']     = $event_id;
		$response['redirect'] = $redirect_link->route . '?event=' . $event_id;
		$response['status']   = true;
		$response['message']  = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'event', 'mode' => CrudEnum::DELETE]])]
	public function deleteevent(): EmundusResponse
	{
		$id = $this->input->getInt('id', 0);
		if (empty($id))
		{
			$ids = $this->input->getString('ids');
			$id  = explode(',', $ids);
		}

		if (empty($id))
		{
			throw new InvalidArgumentException('Event ID(s) is required');
		}

		if (!$this->m_events->deleteEvent($id))
		{
			throw new RuntimeException('Error deleting event(s)');
		}

		return EmundusResponse::ok($id, Text::_('COM_EMUNDUS_ONBOARD_EVENT_DELETED_SUCCESSFULLY'));
	}

	public function getalllocations(): EmundusResponse
	{
		$sort      = $this->input->getString('sort', '');
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$locations = $this->m_events->getAllLocations($sort, $recherche, $lim, $page);

		if (count($locations) > 0)
		{
			// this data formatted is used in onboarding lists
			foreach ($locations['datas'] as $location)
			{
				$location->label = ['fr' => $location->label, 'en' => $location->label];

				$location->additional_columns = [
					[
						'key'     => Text::_('COM_EMUNDUS_REGISTRANTS_LOCATION_NB_ROOMS'),
						'value'   => $location->nb_rooms,
						'classes' => '',
						'display' => 'table'
					],
					[
						'key'     => Text::_('COM_EMUNDUS_REGISTRANTS_LOCATION_NB_ROOMS'),
						'value'   => $location->nb_rooms > 1 ? $location->nb_rooms . ' ' . Text::_('COM_EMUNDUS_REGISTRANTS_LOCATION_ROOMS') : $location->nb_rooms . ' ' . Text::_('COM_EMUNDUS_REGISTRANTS_LOCATION_ROOM'),
						'classes' => '',
						'display' => 'blocs'
					],
				];
			}
		}

		return EmundusResponse::ok($locations);
	}

	public function getlocations(): EmundusResponse
	{
		$locations = $this->m_events->getLocations();

		return EmundusResponse::ok($locations);
	}

	public function getlocation(): EmundusResponse
	{
		$location_id = $this->input->getInt('location_id', 0);
		if (empty($location_id))
		{
			throw new InvalidArgumentException('Location ID is required');
		}

		$location = $this->m_events->getLocation($location_id);

		return EmundusResponse::ok($location);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function savelocation(): EmundusResponse
	{
		$id          = $this->input->getString('id', 0);
		$name        = $this->input->getString('name', '');
		$address     = $this->input->getString('address', '');
		$description = $this->input->getString('description', '');
		$rooms       = $this->input->getRaw('rooms', '[]');
		$rooms       = json_decode($rooms);

		$location_id = $this->m_events->saveLocation($name, $address, $description, $rooms, $this->user->id, $id);
		if (empty($location_id))
		{
			throw new RuntimeException('Error saving location');
		}

		return EmundusResponse::ok($location_id);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function deletelocation(): EmundusResponse
	{
		$id = $this->input->getInt('id', 0);
		if (empty($id))
		{
			$ids = $this->input->getString('ids');
			$id  = explode(',', $ids);
		}

		if (empty($id))
		{
			throw new InvalidArgumentException('Location ID(s) is required');
		}

		if (!$this->m_events->deleteLocation($id))
		{
			throw new RuntimeException('Error deleting location(s)');
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_ONBOARD_LOCATION_DELETED_SUCCESSFULLY'));
	}

	public function getrooms(): EmundusResponse
	{
		$location_id = $this->input->getInt('location_id', 0);
		if (empty($location_id))
		{
			throw new InvalidArgumentException('Location ID is required');
		}

		$rooms = $this->m_events->getRooms($location_id);

		return EmundusResponse::ok($rooms);
	}

	public function getspecifications(): EmundusResponse
	{
		$specifications = $this->m_events->getSpecifications();

		return EmundusResponse::ok($specifications);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function geteventsslots(): EmundusResponse
	{
		$start      = $this->input->getString('start', '');
		$end        = $this->input->getString('end', '');
		$events_ids = $this->input->getString('events_ids', '');

		$event_slots = $this->m_events->getEventsSlots($start, $end, $events_ids);

		return EmundusResponse::ok($event_slots);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function geteventsavailabilities(): EmundusResponse
	{
		$start      = $this->input->getString('start', '');
		$end        = $this->input->getString('end', '');
		$events_ids = $this->input->getString('events_ids', '');

		$event_availabilities = $this->m_events->getAllEventsAvailabilities($start, $end, $events_ids);

		return EmundusResponse::ok($event_availabilities);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function saveeventslot(): EmundusResponse
	{
		$event_id        = $this->input->getInt('event_id', 0);
		$duration        = $this->input->getString('duration', '');
		$duration_type   = $this->input->getString('duration_type', '');
		$break_every     = $this->input->getInt('break_every', 0);
		$break_time      = $this->input->getString('break_time', '');
		$break_time_type = $this->input->getString('break_time_type', '');
		$mode            = $this->input->getInt('mode', 0);
		$id              = $this->input->getInt('id', 0);
		$parent_slot_id  = $this->input->getInt('parent_slot_id', 0);
		$start_date      = $this->input->getString('start_date', '');
		$end_date        = $this->input->getString('end_date', '');
		$room            = $this->input->getString('room', 0);
		$slot_capacity   = $this->input->getInt('slot_capacity', 0);
		$more_infos      = $this->input->getString('more_infos', '');
		$users           = $this->input->getRaw('users', '');
		$repeat_dates    = $this->input->getString('repeat_dates', '');

		if (empty($event_id) || empty($start_date) || empty($end_date))
		{
			throw new InvalidArgumentException('Missing required parameters');
		}

		$availability_config = [
			'slot_duration'        => $duration,
			'slot_duration_type'   => $duration_type,
			'slot_break_every'     => $break_every,
			'slot_break_time'      => $break_time,
			'slot_break_time_type' => $break_time_type
		];
		$users               = explode(',', $users);

		$slots = $this->m_events->saveEventSlot($start_date, $end_date, $room, $slot_capacity, $more_infos, $users, $event_id, $repeat_dates, $id, $parent_slot_id, $mode, $availability_config, $this->user->id);
		if (!$slots['status'])
		{
			throw new RuntimeException($slots['message']);
		}

		return EmundusResponse::ok($slots['slots'], $slots['message']);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function deleteeventslot(): EmundusResponse
	{
		$event_slot_id = $this->input->getInt('id', 0);
		if (empty($event_slot_id))
		{
			throw new InvalidArgumentException('Event slot ID is required');
		}

		if (!$this->m_events->deleteEventSlot($event_slot_id, $this->user->id))
		{
			throw new RuntimeException('Error deleting event slot');
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_ONBOARD_EVENT_SLOT_DELETED_SUCCESSFULLY'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'event', 'mode' => CrudEnum::CREATE],
		['id' => 'event', 'mode' => CrudEnum::UPDATE]
	])]
	public function setupslot(): void
	{
		$event_id                 = $this->input->getInt('event_id', 0);
		$slot_duration            = $this->input->getString('slot_duration', '');
		$slot_break_every         = $this->input->getInt('slot_break_every', 0);
		$slot_break_time          = $this->input->getString('slot_break_time', '');
		$slots_availables_to_show = $this->input->getInt('slots_availables_to_show', 0);
		$slot_can_book_until      = $this->input->getString('slot_can_book_until', '');
		$slot_can_cancel          = $this->input->getInt('slot_can_cancel', 0);
		$slot_can_cancel_until    = $this->input->getString('slot_can_cancel_until', '');

		if (empty($event_id) || empty($slot_duration))
		{
			throw new InvalidArgumentException('Missing required parameters');
		}

		$response = $this->m_events->setupSlot($event_id, $slot_duration, $slot_break_every, $slot_break_time, $slots_availables_to_show, $slot_can_book_until, $slot_can_cancel, $slot_can_cancel_until, $this->user->id);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'event', 'mode' => CrudEnum::UPDATE]])]
	public function savebookingnotifications(): EmundusResponse
	{
		$event_id                   = $this->input->getInt('event_id', 0);
		$applicant_notify           = $this->input->getInt('applicant_notify', 0);
		$applicant_notify_email     = $this->input->getInt('applicant_notify_email', null);
		$applicant_recall           = $this->input->getInt('applicant_recall', 0);
		$applicant_recall_frequency = $this->input->getInt('applicant_recall_frequency', 0);
		$applicant_recall_email     = $this->input->getInt('applicant_recall_email', null);
		$manager_recall             = $this->input->getInt('manager_recall', 0);
		$manager_recall_frequency   = $this->input->getInt('manager_recall_frequency', 0);
		$manager_recall_email       = $this->input->getInt('manager_recall_email', null);
		$users_recall               = $this->input->getInt('users_recall', 0);
		$users_recall_frequency     = $this->input->getInt('users_recall_frequency', 0);
		$users_recall_email         = $this->input->getInt('users_recall_email', null);
		$ics_event_name             = $this->input->getString('ics_event_name', '');

		if (empty($event_id))
		{
			throw new InvalidArgumentException('Event ID is required');
		}

		if ($applicant_notify && empty($applicant_notify_email))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_ERROR_EVENT_APPLICANT_NOTIFY_EMAIL_EMPTY'));
		}

		$booking_notifications = [
			'applicant_notify'           => $applicant_notify,
			'applicant_notify_email'     => $applicant_notify_email,
			'applicant_recall'           => $applicant_recall,
			'applicant_recall_frequency' => $applicant_recall_frequency,
			'applicant_recall_email'     => $applicant_recall_email,
			'manager_recall'             => $manager_recall,
			'manager_recall_frequency'   => $manager_recall_frequency,
			'manager_recall_email'       => $manager_recall_email,
			'users_recall'               => $users_recall,
			'users_recall_frequency'     => $users_recall_frequency,
			'users_recall_email'         => $users_recall_email,
			'ics_event_name'             => $ics_event_name
		];
		if (!$this->m_events->saveBookingNotifications($event_id, $booking_notifications, $this->user->id))
		{
			throw new RuntimeException('Error saving booking notifications');
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_ONBOARD_SUCCESS'));
	}

	public function getavailabilitiesbycampaignsandprograms(): EmundusResponse
	{
		$start              = $this->input->getString('start', '');
		$end                = $this->input->getString('end', '');
		$location           = $this->input->getInt('location', 0);
		$application_choice = $this->input->getInt('application_choice', 0);
		$events_ids         = $this->input->getString('events_ids', '');
		$events_ids         = explode(',', $events_ids);

		$events_ids = array_map('trim', $events_ids);

		$program_code = '';
		$cid          = 0;
		$user         = $this->app->getSession()->get('emundusUser');
		if ($user)
		{
			$program_code = $user->code;
			$cid          = $user->campaign_id;
		}

		if (!empty($application_choice))
		{
			$applicationChoiceRepository = new ApplicationChoicesRepository();
			$applicationChoice           = $applicationChoiceRepository->getById($application_choice);
			if (!empty($applicationChoice->getCampaign()->getId()))
			{
				$cid = $applicationChoice->getCampaign()->getId();
			}
		}

		$check_availables_to_show    = $user->applicant || (!EmundusHelperAccess::asAccessAction($this->booking_access_id, 'c', $this->user->id) && !EmundusHelperAccess::asAccessAction($this->booking_access_id, 'u', $this->user->id));
		$check_booking_limit_reached = $user->applicant || (!EmundusHelperAccess::asAccessAction($this->booking_access_id, 'c', $this->user->id) && !EmundusHelperAccess::asAccessAction($this->booking_access_id, 'u', $this->user->id));

		$event_availabilities = $this->m_events->getAvailabilitiesByCampaignsAndPrograms($cid, $program_code, $start, $end, $location, $check_booking_limit_reached, $events_ids, $check_availables_to_show);

		return EmundusResponse::ok($event_availabilities);
	}

	public function getavailabilityregistrants(): EmundusResponse
	{
		$availability_id = $this->input->getInt('availability', 0);

		$availability_registrants = $this->m_events->getAvailabilityRegistrants($availability_id);

		return EmundusResponse::ok($availability_registrants);
	}

	public function getmybookings(): EmundusResponse
	{
		$e_user = $this->app->getSession()->get('emundusUser');
		if (!empty($e_user))
		{
			if (!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
			}
			$m_files   = new EmundusModelFiles();
			$fnumInfos = $m_files->getFnumInfos($e_user->fnum);

			$cid          = $e_user->campaign_id;
			$program_code = $e_user->code;

			if (empty($cid) || empty($program_code))
			{
				$cid          = $fnumInfos['id'];
				$program_code = $fnumInfos['training'];
			}
		}

		$campaignRepository = new CampaignRepository();
		$childrenCampaigns  = $campaignRepository->getChildrenCampaigns($cid);
		$cids               = [$cid];
		foreach ($childrenCampaigns as $childCampaign)
		{
			$cids[] = $childCampaign->getId();
		}

		$campaigns_events = $this->m_events->getEventsByCampaignIds($cids);
		$programs_events  = $this->m_events->getEventsByProgramCodes($program_code);

		$events = array_merge($campaigns_events, $programs_events);

		$my_bookings = $this->m_events->getMyBookingsInformations($this->user->id, $events, $fnumInfos['ccid']);

		return EmundusResponse::ok($my_bookings);
	}

	public function getapplicantbookings(): EmundusResponse
	{
		$applicant_bookings = $this->m_events->getMyBookingsInformations($this->user->id);

		return EmundusResponse::ok($applicant_bookings);
	}

	public function deletebooking(): EmundusResponse
	{
		$booking_id = $this->input->getInt('booking_id', 0);
		if (empty($booking_id))
		{
			$booking_id = $this->input->getInt('id', 0);

			if (empty($booking_id))
			{
				$ids        = $this->input->getString('ids');
				$booking_id = explode(',', $ids);
			}
		}

		if (is_array($booking_id))
		{
			// Remove booking not mine
			$booking_id = array_filter($booking_id, function ($id) {
				return EmundusHelperAccess::isBookingMine($this->user->id, $id) || EmundusHelperAccess::asAccessAction($this->booking_access_id, 'd', $this->user->id);
			});
		}

		if (empty($booking_id))
		{
			throw new InvalidArgumentException('Booking ID(s) is required');
		}

		if (!$this->m_events->deleteBooking($booking_id))
		{
			throw new RuntimeException('Error deleting booking(s)');
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_ONBOARD_BOOKING_DELETED_SUCCESSFULLY'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'booking', 'mode' => CrudEnum::READ]])]
	public function getregistrants(): EmundusResponse
	{
		$view      = $this->input->getString('view', '');
		$filter    = $this->input->getString('filter', '');
		$sort      = $this->input->getString('sort', 'DESC');
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$order_by  = $this->input->getString('order_by', 'er.id');

		$event             = $this->input->getInt('event', 0);
		$location          = $this->input->getInt('location', 0);
		$room              = $this->input->getInt('room', 0);
		$applicant         = $this->input->getInt('applicant', 0);
		$assoc_user_filter = $this->input->getString('assoc_user', '');
		$day               = $this->input->getString('day', '');
		$hour              = $this->input->getString('hour', '');
		if ($view !== 'calendar')
		{
			require_once JPATH_SITE . '/components/com_emundus/models/users.php';
			$m_users = new EmundusModelUsers();

			$registrants = $this->m_events->getRegistrants($filter, $sort, $recherche, $lim, $page, $order_by, $event, $location, $applicant, $assoc_user_filter, 0, [], $this->user->id, $day, $room, $hour);
			if (!empty($registrants) && $registrants['count'] > 0)
			{
				// Search for a files or evaluation view
				$menu        = Factory::getApplication()->getMenu();
				$emundusUser = $this->app->getSession()->get('emundusUser');
				$files_menu  = $menu->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=files', $emundusUser->menutype], 'true');
				if (empty($files_menu))
				{
					$files_menu = $menu->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=evaluation', $emundusUser->menutype], 'true');
				}

				foreach ($registrants['datas'] as $registrant)
				{
					$registrant->label = ['fr' => $registrant->label, 'en' => $registrant->label];
					$day               = EmundusHelperDate::displayDate($registrant->start_date, 'COM_EMUNDUS_BIRTHDAY_FORMAT', 0);
					$hour              = EmundusHelperDate::displayDate($registrant->start_date, 'H:i', 0);

					$assoc_users = [];
					if (!empty($registrant->assoc_user_id))
					{

						$users = explode(',', $registrant->assoc_user_id);
						foreach ($users as $user)
						{
							$assoc_user = $m_users->getUserById($user);
							if (!empty($assoc_user) && !empty($assoc_user[0]))
							{
								$assoc_users[] = $assoc_user[0]->lastname . ' ' . $assoc_user[0]->firstname;
							}
						}
						if ($order_by === 'assoc_users')
						{
							if ($sort === 'ASC')
							{
								usort($assoc_users, function ($a, $b) {
									return strcasecmp($a, $b);
								});
							}
							else
							{
								if ($sort === 'DESC')
								{
									usort($assoc_users, function ($a, $b) {
										return strcasecmp($b, $a);
									});
								}
							}
						}
					}
					$registrant->assoc_users = $assoc_users;

					if ($registrant->is_conference_link == 0)
					{
						// Get google maps link of adresse
						$location = $this->m_events->getLocation($registrant->location_id);
						if (!empty($location->address))
						{
							$registrant->conference_link = 'https://www.google.com/maps?q=' . urlencode($location->address);
						}
					}

					$registrant->additional_columns = [
						[
							'key'      => Text::_('COM_EMUNDUS_REGISTRANTS_USER'),
							'value'    => !empty($files_menu) ? '<a class="tw-cursor-pointer hover:tw-underline" href="' . $files_menu->route . '#' . $registrant->fnum . '" target="_blank">' . $registrant->user_fullname . '</a>' : $registrant->user_fullname,
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'user_fullname'
						],
						[
							'key'      => Text::_('COM_EMUNDUS_REGISTRANTS_DAY'),
							'value'    => $day,
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'esa.start_date'
						],
						[
							'key'      => Text::_('COM_EMUNDUS_REGISTRANTS_HOUR'),
							'value'    => $hour,
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'TIME(esa.start_date)'
						],
						[
							'key'      => Text::_('COM_EMUNDUS_REGISTRANTS_LOCATION'),
							'value'    => '<a class="tw-cursor-pointer hover:tw-underline" target="_blank" href="' . $registrant->conference_link . '">' . $registrant->location . '</a>',
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'location'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_REGISTRANTS_ROOM'),
							'value'   => $registrant->room,
							'classes' => '',
							'display' => 'table'
						],
						[
							'key'      => Text::_('COM_EMUNDUS_REGISTRANTS_ASSOC_USER'),
							'value'    => implode(', ', $assoc_users),
							'id'       => $registrant->assoc_user_id,
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'assoc_users'
						],
					];
				}
				if ($order_by === 'assoc_users')
				{
					usort($registrants['datas'], function ($a, $b) use ($sort) {
						$aEmpty = empty($a->assoc_users);
						$bEmpty = empty($b->assoc_users);

						if ($aEmpty && !$bEmpty) return 1;
						if (!$aEmpty && $bEmpty) return -1;
						if ($aEmpty && $bEmpty) return 0;

						$aUsers = implode(',', $a->assoc_users);
						$bUsers = implode(',', $b->assoc_users);

						return $sort === 'ASC'
							? strcasecmp($aUsers, $bUsers)
							: strcasecmp($bUsers, $aUsers);
					});
				}
			}

			$data = $registrants;
		}
		else
		{
			$events = $this->m_events->getEvents($filter, $sort, $recherche, $lim, $page, $location, $event);
			if (count($events) > 0)
			{
				foreach ($events['datas'] as $event)
				{
					$event->label = ['fr' => $event->label, 'en' => $event->label];
				}
			}

			$data = $events;
		}

		return EmundusResponse::ok($data);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getfilterapplicants(): EmundusResponse
	{
		$applicants = $this->m_events->getFilterApplicants();

		return EmundusResponse::ok($applicants);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getfilterassocusers(): EmundusResponse
	{
		$assoc_users = $this->m_events->getFilterAssocUsers();

		return EmundusResponse::ok($assoc_users);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getfilterevents(): EmundusResponse
	{
		$events = $this->m_events->getFilterEvents();

		return EmundusResponse::ok($events);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getfilterrooms(): EmundusResponse
	{
		$applicants = $this->m_events->getFilterRooms();

		return EmundusResponse::ok($applicants);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'booking', 'mode' => CrudEnum::READ]])]
	public function exportexcel(): void
	{
		$ids                         = $this->input->getString('ids', '');
		$checkboxesValuesFromView    = $this->input->getString('checkboxesValuesFromView', '');
		$checkboxesValuesFromProfile = $this->input->getString('checkboxesValuesFromProfile', '');

		if (!empty($ids))
		{
			$ids = explode(',', $ids);
		}
		else
		{
			$ids = [];
		}
		if (!empty($checkboxesValuesFromView) && $checkboxesValuesFromView !== "[]")
		{
			$checkboxesValuesFromView = json_decode($checkboxesValuesFromView, true);
		}
		else
		{
			$checkboxesValuesFromView = [];
		}
		if (!empty($checkboxesValuesFromProfile) && $checkboxesValuesFromProfile !== "[]")
		{
			$checkboxesValuesFromProfile = json_decode($checkboxesValuesFromProfile, true);
			$m_users                     = new EmundusModelUsers();
			$checkboxesValuesFromProfile = $m_users->getColumnsFromProfileForm($checkboxesValuesFromProfile);
		}
		else
		{
			$checkboxesValuesFromProfile = [];
		}

		$items = $this->m_events->getRegistrants('', 'DESC', '', 0, 0, '', 0, 0, 0, 0, 0, $ids);
		if (!empty($items) && !empty($items['datas']))
		{
			$excel_filepath = $this->m_events->exportBookingsExcel($items['datas'], $checkboxesValuesFromView, $checkboxesValuesFromProfile);

			if ($excel_filepath && file_exists($excel_filepath))
			{
				$response['status'] = true;
				$extension          = pathinfo($excel_filepath, PATHINFO_EXTENSION);

				if ($extension === 'xls' || $extension === 'xlsx')
				{
					header('Content-Type: application/vnd.ms-excel');
				}
				else
				{
					header('Content-Type: text/csv');
				}

				header('Content-Disposition: attachment; filename="' . basename($excel_filepath) . '"');
				header('Content-Length: ' . filesize($excel_filepath));


				$response['download_file'] = Uri::root() . 'tmp/' . basename($excel_filepath);

			}
			else
			{
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_ERROR');
			}
		}

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'booking', 'mode' => CrudEnum::READ]])]
	public function exportpdf(): void
	{
		$ids                         = $this->input->getString('ids', '');
		$checkboxesValuesFromView    = $this->input->getString('checkboxesValuesFromView', '');
		$checkboxesValuesFromProfile = $this->input->getString('checkboxesValuesFromProfile', '');

		if (!empty($ids))
		{
			$ids = explode(',', $ids);
		}
		else
		{
			$ids = [];
		}
		if (!empty($checkboxesValuesFromView) && $checkboxesValuesFromView !== "[]")
		{
			$checkboxesValuesFromView = json_decode($checkboxesValuesFromView, true);
		}
		else
		{
			$checkboxesValuesFromView = [];
		}
		if (!empty($checkboxesValuesFromProfile) && $checkboxesValuesFromProfile !== "[]")
		{
			$checkboxesValuesFromProfile = json_decode($checkboxesValuesFromProfile, true);
			$m_users                     = new EmundusModelUsers();
			$checkboxesValuesFromProfile = $m_users->getColumnsFromProfileForm($checkboxesValuesFromProfile);
		}
		else
		{
			$checkboxesValuesFromProfile = [];
		}

		$items = $this->m_events->getRegistrants('', 'DESC', '', 0, 0, '', 0, 0, 0, 0, 0, $ids);

		if (!empty($items) && !empty($items['datas']))
		{
			$pdf_filepath = $this->m_events->exportBookingsPDF($items['datas'], $checkboxesValuesFromView, $checkboxesValuesFromProfile);

			if ($pdf_filepath && file_exists($pdf_filepath))
			{
				$response['status'] = true;
				header('Content-Type: application/pdf');
				header('Content-Disposition: attachment; filename="' . basename($pdf_filepath) . '"');
				header('Content-Length: ' . filesize($pdf_filepath));

				$response['download_file'] = Uri::root() . 'tmp/' . basename($pdf_filepath);

			}
			else
			{
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_ERROR');
			}
		}

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'booking', 'mode' => CrudEnum::READ]])]
	public function resend(): EmundusResponse
	{
		$booking_id = $this->input->getInt('booking_id', 0);
		if (empty($booking_id))
		{
			$booking_id = $this->input->getInt('id', 0);
		}
		if (empty($booking_id))
		{
			$ids        = $this->input->getString('ids');
			$booking_id = explode(',', $ids);
		}

		if (empty($booking_id))
		{
			throw new InvalidArgumentException('Booking ID(s) is required');
		}

		if (!$this->m_events->resendBooking($booking_id))
		{
			throw new RuntimeException('Error resending booking email(s)');
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_ONBOARD_SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'booking', 'mode' => CrudEnum::UPDATE]])]
	public function assocusers(): EmundusResponse
	{
		$slots       = $this->input->getString('slots');
		$assoc_users = $this->input->getString('users');
		$replace     = $this->input->getInt('replace', 1);
		if (empty($slots) || empty($assoc_users))
		{
			throw new InvalidArgumentException('Slots and users are required');
		}

		$slots       = explode(',', $slots);
		$assoc_users = explode(',', $assoc_users);

		if(!$this->m_events->getAssocUsers($slots, $assoc_users, $replace))
		{
			throw new RuntimeException('Error associating users to slots');
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_ONBOARD_SUCCESS'));
	}
}