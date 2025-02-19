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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;

class EmundusControllerEvents extends BaseController
{
	protected $app;

	private $user;
	private $m_events;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   2.2.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'events.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');

		$this->app      = Factory::getApplication();
		$this->user     = $this->app->getIdentity();
		$this->m_events = $this->getModel('Events');
	}

	public function getevents()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$filter    = $this->input->getString('filter', '');
			$sort      = $this->input->getString('sort', '');
			$recherche = $this->input->getString('recherche', '');
			$lim       = $this->input->getInt('lim', 0);
			$page      = $this->input->getInt('page', 0);
			$location  = $this->input->getInt('location', 0);

			$events = $this->m_events->getEvents($filter, $sort, $recherche, $lim, $page, $location);
			if (count($events) > 0)
			{
				$fabrik_list_id = $this->m_events->getFabrikListId();

				// this data formatted is used in onboarding lists
				foreach ($events['datas'] as $key => $event)
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
								'classes' => 'tw-flex tw-rounded-coordinator tw-bg-yellow-100 tw-p-2',
								'display' => 'blocs'
							]
						];
					}

					if (!empty($fabrik_list_id))
					{
						$registrant_count = $this->m_events->getRegistrantCount($event->id);
						$registrants_menu = Factory::getApplication()->getMenu()->getItems('link', 'index.php?option=com_fabrik&view=list&listid=' . $fabrik_list_id, true);

						$event->additional_columns = [
							[
								'key'     => Text::_('COM_EMUNDUS_ONBOARD_EVENTS_COUNT_REGISTRANTS'),
								'value'   => '<a class="em-profile-color hover:tw-font-semibold tw-font-semibold em-text-underline" href="/' . $registrants_menu->route . '?resetfilters=1&clearordering=0&jos_emundus_registrants___event_raw[value]=' . $event->id . '" style="line-height: unset;font-size: unset;">' . $registrant_count . ' ' . Text::_('COM_EMUNDUS_EVENTS_BOOKING') . '</a>',
								'classes' => 'go-to-campaign-link',
								'display' => 'blocs'
							],
							[
								'key'     => Text::_('COM_EMUNDUS_ONBOARD_EVENTS_COUNT_REGISTRANTS'),
								'value'   => '<a target="_blank" class="em-profile-color hover:tw-font-semibold tw-font-semibold em-text-underline" href="/' . $registrants_menu->route . '?resetfilters=1&clearordering=0&jos_emundus_registrants___event_raw[value]=' . $event->id . '" style="line-height: unset;font-size: unset;">' . $registrant_count . ' ' . Text::_('COM_EMUNDUS_EVENTS_BOOKING') . '</a>',
								'classes' => 'go-to-campaign-link',
								'display' => 'table'
							],
						];
					}

					$event->additional_columns = array_merge($event->additional_columns, $no_campaigns_programs);
				}
			}

			$response['data'] = $events;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getalllocations()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$sort      = $this->input->getString('sort', '');
			$recherche = $this->input->getString('recherche', '');
			$lim       = $this->input->getInt('lim', 0);
			$page      = $this->input->getInt('page', 0);
			$locations = $this->m_events->getAllLocations($sort, $recherche, $lim, $page);

			if (count($locations) > 0)
			{
				// this data formatted is used in onboarding lists
				foreach ($locations['datas'] as $key => $location)
				{
					$location->label = ['fr' => $location->label, 'en' => $location->label];
				}
			}

			$response['data'] = $locations;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getlocations()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$locations        = $this->m_events->getLocations();
			$response['data'] = $locations;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getlocation()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$location_id = $this->input->getInt('location_id', 0);

			if (!empty($location_id))
			{
				$location         = $this->m_events->getLocation($location_id);
				$response['data'] = $location;

				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
			}
		}

		echo json_encode($response);
		exit();
	}

	public function getrooms()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$location_id = $this->input->getInt('location_id', 0);

			if (!empty($location_id))
			{
				$rooms            = $this->m_events->getRooms($location_id);
				$response['data'] = $rooms;

				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
			}
		}

		echo json_encode($response);
		exit();
	}

	public function getspecifications()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$specifications   = $this->m_events->getSpecifications();
			$response['data'] = $specifications;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getevent()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$event_id = $this->input->getInt('event_id', 0);

			if (!empty($event_id))
			{
				$event            = $this->m_events->getEvent($event_id);
				$response['data'] = $event;

				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
			}
		}

		echo json_encode($response);
		exit();
	}

	public function geteventsslots()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$start      = $this->input->getString('start', '');
			$end        = $this->input->getString('end', '');
			$events_ids = $this->input->getString('events_ids', '');

			$event_slots      = $this->m_events->getEventsSlots($start, $end, $events_ids);
			$response['data'] = $event_slots;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function geteventsavailabilities()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$start      = $this->input->getString('start', '');
			$end        = $this->input->getString('end', '');
			$events_ids = $this->input->getString('events_ids', '');

			$event_availabilities = $this->m_events->getAllEventsAvailabilities($start, $end, $events_ids);
			$response['data']     = $event_availabilities;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function savelocation()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$id      = $this->input->getString('id', 0);
			$name    = $this->input->getString('name', '');
			$address = $this->input->getString('address', '');
			$rooms   = $this->input->getRaw('rooms', '[]');
			$rooms   = json_decode($rooms);

			$location_id = $this->m_events->saveLocation($name, $address, $rooms, $this->user->id, $id);

			if (!empty($location_id))
			{
				$response['data'] = $location_id;

				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
			}
		}

		echo json_encode($response);
		exit();
	}

	public function deletelocation()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$id = $this->input->getInt('id', 0);

			if (!empty($id))
			{
				$response['status'] = $this->m_events->deleteLocation($id);

				if ($response['status'])
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
				else
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_ERROR');
				}
			}
		}

		echo json_encode($response);
		exit();
	}

	public function createevent()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
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

			if (!empty($event_id))
			{
				$response['data'] = $event_id;

				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
			}
		}

		echo json_encode($response);
		exit();
	}

	public function duplicateevent()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$event_id = $this->input->getInt('id', 0);

			$redirect_link = Factory::getApplication()->getMenu()->getItems('link', 'index.php?option=com_emundus&view=events&layout=add', true);

			if (!empty($event_id))
			{
				$event_id = $this->m_events->duplicateEvent($event_id);

				if (!empty($event_id))
				{
					$response['data']     = $event_id;
					$response['redirect'] = $redirect_link->route . '?event=' . $event_id;

					$response['status']  = true;
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
			}
		}

		echo json_encode($response);
		exit();
	}

	public function saveeventslot()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
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

			if (!empty($event_id) && !empty($start_date) && !empty($end_date))
			{
				$availability_config = [
					'slot_duration'        => $duration,
					'slot_duration_type'   => $duration_type,
					'slot_break_every'     => $break_every,
					'slot_break_time'      => $break_time,
					'slot_break_time_type' => $break_time_type
				];
				$users               = explode(',', $users);
				$slots               = $this->m_events->saveEventSlot($start_date, $end_date, $room, $slot_capacity, $more_infos, $users, $event_id, $repeat_dates, $id, $parent_slot_id, $mode, $availability_config, $this->user->id);

				if (!empty($slots))
				{
					$response['data'] = $slots;

					$response['status']  = true;
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
			}
		}

		echo json_encode($response);
		exit();
	}

	public function deleteeventslot()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$event_slot_id = $this->input->getInt('id', 0);

			if (!empty($event_slot_id))
			{
				$response['status'] = $this->m_events->deleteEventSlot($event_slot_id, $this->user->id);

				if ($response['status'])
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
				else
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_ERROR');
				}
			}
		}

		echo json_encode($response);
		exit();
	}

	public function setupslot()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$event_id                 = $this->input->getInt('event_id', 0);
			$slot_duration            = $this->input->getString('slot_duration', '');
			$slot_break_every         = $this->input->getInt('slot_break_every', 0);
			$slot_break_time          = $this->input->getString('slot_break_time', '');
			$slots_availables_to_show = $this->input->getInt('slots_availables_to_show', 0);
			$slot_can_book_until      = $this->input->getString('slot_can_book_until', '');
			$slot_can_cancel          = $this->input->getInt('slot_can_cancel', 0);
			$slot_can_cancel_until    = $this->input->getString('slot_can_cancel_until', '');

			if (!empty($event_id) && !empty($slot_duration))
			{
				$response['status'] = $this->m_events->setupSlot($event_id, $slot_duration, $slot_break_every, $slot_break_time, $slots_availables_to_show, $slot_can_book_until, $slot_can_cancel, $slot_can_cancel_until, $this->user->id);

				if ($response['status'])
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
				else
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_ERROR');
				}
			}
		}

		echo json_encode($response);
		exit();
	}

	public function savebookingnotifications()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
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

			if (!empty($event_id))
			{
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
				$response['status']    = $this->m_events->saveBookingNotifications($event_id, $booking_notifications, $this->user->id);

				if ($response['status'])
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
				else
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_ERROR');
				}
			}
		}

		echo json_encode($response);
		exit();
	}

	public function editevent()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$id                 = $this->input->getInt('id', 0);
			$name               = $this->input->getString('name', '');
			$color              = $this->input->getString('color', '#000000');
			$location           = $this->input->getInt('location', 0);
			$is_conference_link = $this->input->getInt('is_conference_link', 0);
			$conference_engine  = $this->input->getString('conference_engine', '');
			$link               = $this->input->getString('link', '');
			$generate_link_by   = $this->input->getInt('generate_link_by', 0);
			$manager            = $this->input->getInt('manager', 0);
			$manager            = json_decode($manager);
			$available_for      = $this->input->getInt('available_for', 1);
			$campaigns          = $this->input->getRaw('campaigns', '[]');
			$campaigns          = json_decode($campaigns);
			$programs           = $this->input->getRaw('programs', '[]');
			$programs           = json_decode($programs);

			$id = $this->m_events->editEvent($id, $name, $color, $location, $is_conference_link, $conference_engine, $link, $generate_link_by, $manager, $available_for, $campaigns, $programs);

			if (!empty($id))
			{
				$response['data'] = $id;

				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
			}
		}

		echo json_encode($response);
		exit();
	}

	public function deleteevent()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$id = $this->input->getInt('id', 0);

			if (!empty($id))
			{
				$response['status'] = $this->m_events->deleteEvent($id);

				if ($response['status'])
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
				else
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_ERROR');
				}
			}
		}

		echo json_encode($response);
		exit();
	}

	public function getavailabilitiesbycampaignsandprograms()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$start                       = $this->input->getString('start', '');
			$end                         = $this->input->getString('end', '');
			$location                    = $this->input->getInt('location', 0);
			$check_booking_limit_reached = $this->input->getInt('check_booking_limit_reached', 0);

			$program_code = '';
			$cid          = 0;
			$user         = $this->app->getSession()->get('emundusUser');
			if ($user)
			{
				$program_code = $user->code;
				$cid          = $user->campaign_id;
			}

			$event_availabilities = $this->m_events->getAvailabilitiesByCampaignsAndPrograms($cid, $program_code, $start, $end, $location, $check_booking_limit_reached);
			$response['data']     = $event_availabilities;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getavailabilityregistrants()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$availability_id = $this->input->getInt('availability', 0);

			$availability_registrants = $this->m_events->getAvailabilityRegistrants($availability_id);
			$response['data']         = $availability_registrants;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getmybookings()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$e_user = $this->app->getSession()->get('emundusUser');
			if (!empty($e_user))
			{
				$cid          = $e_user->campaign_id;
				$program_code = $e_user->code;

				if (empty($cid) || empty($program_code))
				{
					if (!class_exists('EmundusModelFiles'))
					{
						require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
					}
					$m_files   = new EmundusModelFiles();
					$fnumInfos = $m_files->getFnumInfos($e_user->fnum);

					$cid          = $fnumInfos['id'];
					$program_code = $fnumInfos['training'];
				}
			}

			$campaigns_events = $this->m_events->getEventsByCampaignIds($cid);
			$programs_events  = $this->m_events->getEventsByProgramCodes($program_code);

			$events = array_merge($campaigns_events, $programs_events);

			$my_bookings      = $this->m_events->getMyBookingsInformations($this->user->id, $events);
			$response['data'] = $my_bookings;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getapplicantbookings()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if ($this->user->guest)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$applicant_bookings      = $this->m_events->getMyBookingsInformations($this->user->id);
			$response['data'] = $applicant_bookings;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function deletebooking()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		$booking_id = $this->input->getInt('booking_id', 0);

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) && !EmundusHelperAccess::isBookingMine($this->user->id, $booking_id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			if (!empty($booking_id))
			{
				$response['status'] = $this->m_events->deleteBooking($booking_id);

				if ($response['status'])
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
				else
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_ERROR');
				}
			}
		}

		echo json_encode($response);
		exit();
	}
}