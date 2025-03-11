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

class EmundusControllerEvents extends BaseController
{
	protected $app;

	private $user;
	private $m_events;

	private $booking_access_id = 0;

	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . '/components/com_emundus/models/events.php');
		require_once(JPATH_BASE . '/components/com_emundus/helpers/access.php');

		$this->user     = $this->app->getIdentity();
		$this->m_events = $this->getModel('Events');

		$this->booking_access_id = EmundusHelperAccess::getActionIdFromActionName('booking');
	}

	public function getevents()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$order_by  = $this->input->getString('order_by', '');
			$sort      = $this->input->getString('sort', '');
			$recherche = $this->input->getString('recherche', '');
			$lim       = $this->input->getInt('lim', 0);
			$page      = $this->input->getInt('page', 0);
			$location  = $this->input->getInt('location', 0);

			$events = $this->m_events->getEvents($order_by, $sort, $recherche, $lim, $page, $location);
			if (count($events) > 0)
			{
				// Search menu by link index.php?option=com_emundus&view=events&layout=registrants
				$emundusUser      = $this->app->getSession()->get('emundusUser');
				$registrants_menu = Factory::getApplication()->getMenu()->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=events&layout=registrants', $emundusUser->menutype], 'true');

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


					$event->registrant_count = $this->m_events->getRegistrantCount($event->id);

					$event->additional_columns = [
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_EVENTS_COUNT_REGISTRANTS'),
							'value'   => '<a class="em-profile-color hover:tw-font-semibold hover:tw-underline tw-font-semibold" href="/' . $registrants_menu->route . '?event=' . $event->id . '" style="line-height: unset;font-size: unset;">' . $event->registrant_count . ' ' . Text::_('COM_EMUNDUS_EVENTS_BOOKING') . '</a>',
							'classes' => 'go-to-campaign-link',
							'display' => 'blocs'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_EVENTS_COUNT_REGISTRANTS'),
							'value'   => '<a class="em-profile-color hover:tw-font-semibold hover:tw-underline tw-font-semibold" href="/' . $registrants_menu->route . '?event=' . $event->id . '" style="line-height: unset;font-size: unset;">' . $event->registrant_count . ' ' . Text::_('COM_EMUNDUS_EVENTS_BOOKING') . '</a>',
							'classes' => 'go-to-campaign-link',
							'display' => 'table'
						],
					];

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

	public function geteventsnames()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$events = $this->m_events->getEventsNames();

			$response['data'] = $events;

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

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
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
			$teams_subject      = $this->input->getString('teams_subject', '');
			$manager            = $this->input->getInt('manager', 0);
			$manager            = json_decode($manager);
			$available_for      = $this->input->getInt('available_for', 1);
			$campaigns          = $this->input->getRaw('campaigns', '[]');
			$campaigns          = json_decode($campaigns);
			$programs           = $this->input->getRaw('programs', '[]');
			$programs           = json_decode($programs);

			$id = $this->m_events->editEvent($id, $name, $color, $location, $is_conference_link, $conference_engine, $link, $generate_link_by, $manager, $available_for, $campaigns, $programs, $teams_subject);

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

	public function editslot()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asAccessAction($this->booking_access_id, 'u', $this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
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

			$id = $this->m_events->editSlot($registrant_id, $availability, $event_id, $users_id, $ccid);

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
			if (empty($id))
			{
				$ids = $this->input->getString('ids');
				$id  = explode(',', $ids);
			}

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
			$id          = $this->input->getString('id', 0);
			$name        = $this->input->getString('name', '');
			$address     = $this->input->getString('address', '');
			$description = $this->input->getString('description', '');
			$rooms       = $this->input->getRaw('rooms', '[]');
			$rooms       = json_decode($rooms);

			$location_id = $this->m_events->saveLocation($name, $address, $description, $rooms, $this->user->id, $id);

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
			if (empty($id))
			{
				$ids = $this->input->getString('ids');
				$id  = explode(',', $ids);
			}

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

	public function geteventsslots()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
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

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
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
			$events_ids                  = $this->input->getString('events_ids', '');
			$events_ids                  = explode(',', $events_ids);

			$events_ids = array_map('trim', $events_ids);


			$program_code = '';
			$cid          = 0;
			$user         = $this->app->getSession()->get('emundusUser');
			if ($user)
			{
				$program_code = $user->code;
				$cid          = $user->campaign_id;
			}

			$event_availabilities = $this->m_events->getAvailabilitiesByCampaignsAndPrograms($cid, $program_code, $start, $end, $location, $check_booking_limit_reached, $events_ids);
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

			$campaigns_events = $this->m_events->getEventsByCampaignIds($cid);
			$programs_events  = $this->m_events->getEventsByProgramCodes($program_code);

			$events = array_merge($campaigns_events, $programs_events);

			$my_bookings      = $this->m_events->getMyBookingsInformations($this->user->id, $events, $fnumInfos['ccid']);
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
			$applicant_bookings = $this->m_events->getMyBookingsInformations($this->user->id);
			$response['data']   = $applicant_bookings;

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
			header('HTTP/1.1 403 Forbidden');
		}
		else
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

		echo json_encode($response);
		exit();
	}

	public function getregistrants()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asAccessAction($this->booking_access_id, 'r', $this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$view      = $this->input->getString('view', '');
			$filter    = $this->input->getString('filter', '');
			$sort      = $this->input->getString('sort', 'DESC');
			$recherche = $this->input->getString('recherche', '');
			$lim       = $this->input->getInt('lim', 0);
			$page      = $this->input->getInt('page', 0);
			$order_by  = $this->input->getString('order_by', 'er.id');

			$event       = $this->input->getInt('event', 0);
			$location    = $this->input->getInt('location', 0);
			$applicant   = $this->input->getInt('applicant', 0);
			$assoc_users = $this->input->getInt('assoc_users', 0);
			$day         = $this->input->getString('day', '');

			if ($view !== 'calendar')
			{
				require_once JPATH_SITE . '/components/com_emundus/models/users.php';
				$m_users = new EmundusModelUsers();

				$registrants = $this->m_events->getRegistrants($filter, $sort, $recherche, $lim, $page, $order_by, $event, $location, $applicant, $assoc_users,0,[],$this->user->id,$day);
				if (!empty($registrants) && $registrants['count'] > 0)
				{
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
						}

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
								'value'    => $registrant->user_fullname,
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
								'key'     => Text::_('COM_EMUNDUS_REGISTRANTS_HOUR'),
								'value'   => $hour,
								'classes' => '',
								'display' => 'table'
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
								'key'     => Text::_('COM_EMUNDUS_REGISTRANTS_ASSOC_USER'),
								'value'   => implode(', ', $assoc_users),
								'id'      => $registrant->assoc_user_id,
								'classes' => '',
								'display' => 'table'
							],
						];
					}
				}

				$response['data'] = $registrants;
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

				$response['data'] = $events;
			}

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getfilterapplicants()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$applicants       = $this->m_events->getFilterApplicants();
			$response['data'] = $applicants;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getfilterassocusers()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$assoc_users      = $this->m_events->getFilterAssocUsers();
			$response['data'] = $assoc_users;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function getfilterevents()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$events           = $this->m_events->getFilterEvents();
			$response['data'] = $events;

			$response['status']  = true;
			$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
		}

		echo json_encode($response);
		exit();
	}

	public function exportexcel()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asAccessAction($this->booking_access_id, 'r', $this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$ids = $this->input->getString('ids', '');

			if (!empty($ids))
			{
				$ids   = explode(',', $ids);
				$items = $this->m_events->getRegistrants('', 'DESC', '', 0, 0, '', 0, 0, 0, 0, 0, $ids);

				if (!empty($items) && !empty($items['datas']))
				{
					$columns        = [
						Text::_('COM_EMUNDUS_APPLICATION_APPLICANT'),
						Text::_('COM_EMUNDUS_ONBOARD_LABEL_REGISTRANTS'),
						Text::_('COM_EMUNDUS_REGISTRANTS_DAY'),
						Text::_('COM_EMUNDUS_REGISTRANTS_HOUR'),
						Text::_('COM_EMUNDUS_REGISTRANTS_LOCATION'),
						Text::_('COM_EMUNDUS_REGISTRANTS_ROOM'),
						Text::_('COM_EMUNDUS_REGISTRANTS_ASSOC_USER')
					];
					$excel_filepath = $this->m_events->exportBookingsExcel($items['datas'], $columns);

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
			}
		}

		echo json_encode($response);
		exit();
	}

	public function exportpdf()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asAccessAction($this->booking_access_id, 'r', $this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$ids = $this->input->getString('ids', '');

			if (!empty($ids))
			{
				$ids   = explode(',', $ids);
				$items = $this->m_events->getRegistrants('', 'DESC', '', 0, 0, '', 0, 0, 0, 0, 0, $ids);

				if (!empty($items) && !empty($items['datas']))
				{
					$pdf_filepath = $this->m_events->exportBookingsPDF($items['datas']);

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
			}
		}
		echo json_encode($response);
		exit();
	}

	public function resend()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if(!EmundusHelperAccess::asAccessAction($this->booking_access_id, 'r', $this->user->id)) {
			header('HTTP/1.1 403 Forbidden');
		}
		else
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
				header('HTTP/1.1 403 Forbidden');
			}
			else
			{
				$response['status'] = $this->m_events->resendBooking($booking_id);
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