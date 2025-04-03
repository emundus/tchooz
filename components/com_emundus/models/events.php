<?php
/**
 * @package        Joomla
 * @subpackage     eMundus
 * @link           http://www.emundus.fr
 * @copyright      Copyright (C) 2018 eMundus. All rights reserved.
 * @license        GNU/GPL
 * @author         eMundus
 */

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Dompdf\Dompdf;
use Dompdf\Options;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');

class EmundusModelEvents extends BaseDatabaseModel
{
	private DatabaseInterface $db;

	private EmundusModelLogs $logger;

	/**
	 * Constructor
	 *
	 * @param $config
	 *
	 * @throws Exception
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->app    = Factory::getApplication();
		$this->db     = $this->getDatabase();
		$this->logger = new EmundusModelLogs();

		Log::addLogger(
			array(
				'text_file' => 'com_emundus.events.php'
			),
			Log::ALL,
			array('com_emundus.events')
		);
	}

	public function getEvents(string $order_by = '', string $sort = 'DESC', string $recherche = '', int|string $lim = 25, int|string $page = 0, int $location = 0, int $id = 0): array
	{
		$events = ['datas' => [], 'count' => 0];

		try
		{
			if (empty($lim) || $lim == 'all')
			{
				$limit = '';
			}
			else
			{
				$limit = $lim;
			}

			if (empty($page) || empty($limit))
			{
				$offset = 0;
			}
			else
			{
				$offset = ($page - 1) * $limit;
			}

			$query = $this->db->getQuery(true);

			$columns = [
				$this->db->quoteName('ee.id'),
				$this->db->quoteName('ee.name', 'label'),
				$this->db->quoteName('del.name', 'location'),
				$this->db->quoteName('ee.slot_duration'),
				$this->db->quoteName('ee.color')
			];

			$query->select('count(ee.id)')
				->from($this->db->quoteName('#__emundus_setup_events', 'ee'))
				->leftJoin($this->db->quoteName('data_events_location', 'del') . ' ON ' . $this->db->quoteName('del.id') . ' = ' . $this->db->quoteName('ee.location'));
			if (!empty($location))
			{
				$query->where($this->db->quoteName('ee.location') . ' = ' . $location);
			}
			if (!empty($recherche))
			{
				$query->where($this->db->quoteName('ee.name') . ' LIKE ' . $this->db->quote('%' . $recherche . '%'));
			}
			if (!empty($id))
			{
				$query->where($this->db->quoteName('ee.id') . ' = ' . $id);
			}
			$this->_db->setQuery($query);
			$events['count'] = $this->_db->loadResult();

			$query->clear('select')
				->select($columns);
			if (!empty($order_by) && !empty($sort))
			{
				$query->order($order_by . ' ' . $sort);
			}

			$this->_db->setQuery($query, $offset, $limit);
			$events['datas'] = $this->_db->loadObjectList();

			foreach ($events['datas'] as $key => $event)
			{
				// Get first slot in future to know the start date
				$query->clear()
					->select('start_date')
					->from($this->db->quoteName('#__emundus_setup_event_slots'))
					->where($this->db->quoteName('event') . ' = ' . $event->id)
					->where($this->db->quoteName('start_date') . ' > NOW()')
					->order('start_date ASC')
					->setLimit(1);
				$this->_db->setQuery($query);
				$event->start_date = $this->_db->loadResult();

				$availabilities = $this->getEventsAvailabilities('', '', [$event->id]);
				// Count total capacity available
				$event->availabilities_count = 0;
				foreach ($availabilities as $availability)
				{
					$event->availabilities_count += $availability->capacity;
				}

				// Get slots reserved
				$query->clear()
					->select('count(DISTINCT er.id)')
					->from($this->db->quoteName('#__emundus_registrants', 'er'))
					->leftJoin($this->db->quoteName('#__emundus_setup_event_slots', 'eses') . ' ON ' . $this->db->quoteName('eses.id') . ' = ' . $this->db->quoteName('er.slot'))
					->where($this->db->quoteName('eses.event') . ' = ' . $event->id);
				$this->_db->setQuery($query);
				$event->booked_count = $this->_db->loadResult();
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while getting events: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $events;
	}

	public function getEventsNames(): array
	{
		$events = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('name')
				->from($this->db->quoteName('#__emundus_setup_events'))
				->order('name ASC');
			$this->_db->setQuery($query);
			$events = $this->_db->loadColumn();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting events names: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $events;
	}

	/**
	 * @param $sort
	 * @param $recherche
	 * @param $lim
	 * @param $page
	 *
	 * @return array
	 *
	 * @since version 2.2.0
	 */
	public function getAllLocations($sort = 'DESC', $recherche = '', $lim = 25, $page = 0)
	{
		$locations = [];

		try
		{
			if (empty($lim) || $lim == 'all')
			{
				$limit = '';
			}
			else
			{
				$limit = $lim;
			}

			if (empty($page) || empty($limit))
			{
				$offset = 0;
			}
			else
			{
				$offset = ($page - 1) * $limit;
			}

			$query = $this->db->getQuery(true);

			$columns = [
				$this->db->quoteName('del.id'),
				$this->db->quoteName('del.name', 'label'),
				$this->db->quoteName('del.address'),
				$this->db->quoteName('del.map_location'),
				'count(' . $this->db->quoteName('ese.id') . ') as nb_events',
				'count(DISTINCT ' . $this->db->quoteName('dlr.id') . ') as nb_rooms'
			];

			$query->select('count(del.id)')
				->from($this->db->quoteName('data_events_location', 'del'))
				->where($this->db->quoteName('del.published') . ' = 1')
				->order('del.name ASC')
				->group('del.id');
			$this->_db->setQuery($query);
			$locations['count'] = $this->_db->loadResult();

			$query->clear('select')
				->select($columns)
				->leftJoin($this->db->quoteName('data_location_rooms', 'dlr') . ' ON ' . $this->db->quoteName('dlr.location') . ' = ' . $this->db->quoteName('del.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_events', 'ese') . ' ON ' . $this->db->quoteName('ese.location') . ' = ' . $this->db->quoteName('del.id'));

			$this->_db->setQuery($query, $offset, $limit);
			$locations['datas'] = $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting locations: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $locations;
	}

	/**
	 *
	 * @return array|mixed
	 *
	 * @since version 2.2.0
	 */
	public function getLocations()
	{
		$locations = [];

		try
		{
			$query = $this->db->getQuery(true);

			$columns = [
				$this->db->quoteName('id', 'value'),
				$this->db->quoteName('name', 'label'),
				$this->db->quoteName('address'),
				$this->db->quoteName('map_location')
			];

			$query->select($columns)
				->from($this->db->quoteName('data_events_location'))
				->where($this->db->quoteName('published') . ' = 1')
				->order('name ASC');
			$this->_db->setQuery($query);
			$locations = $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting locations: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $locations;
	}

	/**
	 * @param $location_id
	 *
	 * @return mixed|null
	 *
	 * @since version 2.2.0
	 */
	public function getLocation($location_id)
	{
		$location = null;

		if (!empty($location_id))
		{
			try
			{
				$query = $this->db->getQuery(true);

				$query->select('id,name,address,description,map_location')
					->from($this->db->quoteName('data_events_location'))
					->where($this->db->quoteName('id') . ' = ' . $location_id);
				$this->_db->setQuery($query);
				$location = $this->_db->loadObject();

				if (!empty($location))
				{
					$location->rooms = $this->getRooms($location_id, true);
				}
			}
			catch (Exception $e)
			{
				Log::add('Error while getting location: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
			}
		}

		return $location;
	}

	/**
	 * @param $location_id
	 * @param $with_specs
	 *
	 * @return array|mixed
	 *
	 * @since version 2.2.0
	 */
	public function getRooms($location_id, $with_specs = false)
	{
		$rooms = [];

		if (!empty($location_id))
		{
			try
			{
				$query = $this->db->getQuery(true);

				$columns = [
					'dlr.id as value',
					'dlr.name as label'
				];
				if ($with_specs)
				{
					$columns[] = 'group_concat(dlrs.specification) as specifications';
				}

				$query->select($columns)
					->from($this->db->quoteName('data_location_rooms', 'dlr'))
					->where($this->db->quoteName('dlr.location') . ' = ' . $location_id);
				if ($with_specs)
				{
					$query->leftJoin($this->db->quoteName('data_location_rooms_specs', 'dlrs') . ' ON ' . $this->db->quoteName('dlrs.room') . ' = ' . $this->db->quoteName('dlr.id'))
						->group('dlr.id');
				}
				$this->_db->setQuery($query);
				$rooms = $this->_db->loadObjectList();
			}
			catch (Exception $e)
			{
				Log::add('Error while getting rooms: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
			}
		}

		return $rooms;
	}

	/**
	 *
	 * @return array|mixed
	 *
	 * @since version 2.2.0
	 */
	public function getSpecifications()
	{
		$specifications = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('id as value,name as label')
				->from($this->db->quoteName('data_specifications'))
				->where($this->db->quoteName('published') . ' = 1')
				->order('name ASC');
			$this->_db->setQuery($query);
			$specifications = $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting specifications: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $specifications;
	}

	/**
	 * @param $event_id
	 *
	 * @return mixed|null
	 *
	 * @since version 2.2.0
	 */
	public function getEvent($event_id)
	{
		$event = null;

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('ee.*')
				->from($this->db->quoteName('#__emundus_setup_events', 'ee'))
				->leftJoin($this->db->quoteName('#__emundus_setup_events_repeat_campaign', 'eserc') . ' ON ' . $this->db->quoteName('eserc.event') . ' = ' . $this->db->quoteName('ee.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_events_repeat_program', 'eserp') . ' ON ' . $this->db->quoteName('eserp.event') . ' = ' . $this->db->quoteName('ee.id'))
				->where($this->db->quoteName('ee.id') . ' = ' . $event_id);

			$this->_db->setQuery($query);
			$event = $this->_db->loadObject();

			if ($event)
			{
				if ($event->available_for == 1)
				{
					$query->clear()
						->select($this->db->quoteName('eserc.campaign'))
						->from($this->db->quoteName('#__emundus_setup_events_repeat_campaign', 'eserc'))
						->where($this->db->quoteName('eserc.event') . ' = ' . $event_id);
					$this->_db->setQuery($query);
					$event->campaigns = $this->_db->loadColumn();
				}
				elseif ($event->available_for == 2)
				{
					$query->clear()
						->select($this->db->quoteName('eserp.programme'))
						->from($this->db->quoteName('#__emundus_setup_events_repeat_program', 'eserp'))
						->where($this->db->quoteName('eserp.event') . ' = ' . $event_id);
					$this->_db->setQuery($query);
					$event->programs = $this->_db->loadColumn();
				}

				// Get slot events
				$columns = [
					'eses.id',
					'eses.start_date as start',
					'eses.end_date as end',
					'eses.parent_slot_id',
					'eses.room',
					'dlr.name as location',
					'eses.slot_capacity',
					'eses.more_infos',
					'group_concat(DISTINCT essu.user) as users',
					'group_concat(DISTINCT concat(eu.lastname," ",eu.firstname)) as people',
					'sum(esa.capacity) as availabilities_count',
					'count(DISTINCT er.id) as booked_count'
				];
				$query->clear()
					->select($columns)
					->from($this->db->quoteName('#__emundus_setup_event_slots', 'eses'))
					->leftJoin($this->db->quoteName('#__emundus_setup_slot_users', 'essu') . ' ON ' . $this->db->quoteName('essu.slot') . ' = ' . $this->db->quoteName('eses.id'))
					->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('essu.user'))
					->leftJoin($this->db->quoteName('data_location_rooms', 'dlr') . ' ON ' . $this->db->quoteName('dlr.id') . ' = ' . $this->db->quoteName('eses.room'))
					->leftJoin($this->db->quoteName('#__emundus_setup_availabilities', 'esa') . ' ON ' . $this->db->quoteName('esa.slot') . ' = ' . $this->db->quoteName('eses.id'))
					->leftJoin($this->db->quoteName('#__emundus_registrants', 'er') . ' ON ' . $this->db->quoteName('er.slot') . ' = ' . $this->db->quoteName('eses.id'))
					->where($this->db->quoteName('eses.event') . ' = ' . $event_id)
					->group('eses.id');
				$this->_db->setQuery($query);
				$slots = $this->_db->loadObjectList();

				foreach ($slots as $slot)
				{
					// Convert UTC dates to platform timezone ($timezone)
					$slot->start = EmundusHelperDate::displayDate($slot->start, 'Y-m-d H:i', 0);
					$slot->end   = EmundusHelperDate::displayDate($slot->end, 'Y-m-d H:i', 0);
				}

				$event->slots = $slots;

				// Get event notifications
				$query->clear()
					->select('*')
					->from($this->db->quoteName('#__emundus_setup_events_notifications'))
					->where($this->db->quoteName('event') . ' = ' . $event_id);
				$this->_db->setQuery($query);
				$event->notifications = $this->_db->loadObject();
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while getting event: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $event;
	}

	/**
	 * @param $start
	 * @param $end
	 * @param $events_ids
	 *
	 * @return array|mixed
	 *
	 * @since version 2.2.0
	 */
	public function getEventsSlots($start = '', $end = '', $events_ids = '')
	{
		$events_slots = [];

		try
		{
			$query = $this->db->getQuery(true);

			$columns = [
				'eses.id',
				'eses.event as event_id',
				'ese.name',
				'ese.color',
				'eses.start_date as start',
				'eses.end_date as end',
				'eses.room',
				'eses.parent_slot_id',
				'dlr.name as location',
				'eses.slot_capacity',
				'ese.slot_duration',
				'eses.more_infos',
				'group_concat(DISTINCT essu.user) as users',
				'group_concat(DISTINCT concat(eu.lastname," ",eu.firstname)) as people',
				'sum(esa.capacity) as availabilities_count',
				'count(DISTINCT er.id) as booked_count'
			];

			$query->select($columns)
				->from($this->db->quoteName('#__emundus_setup_event_slots', 'eses'))
				->leftJoin($this->db->quoteName('#__emundus_setup_events', 'ese') . ' ON ' . $this->db->quoteName('ese.id') . ' = ' . $this->db->quoteName('eses.event'))
				->leftJoin($this->db->quoteName('#__emundus_setup_slot_users', 'essu') . ' ON ' . $this->db->quoteName('essu.slot') . ' = ' . $this->db->quoteName('eses.id'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.id') . ' = ' . $this->db->quoteName('essu.user'))
				->leftJoin($this->db->quoteName('data_location_rooms', 'dlr') . ' ON ' . $this->db->quoteName('dlr.id') . ' = ' . $this->db->quoteName('eses.room'))
				->leftJoin($this->db->quoteName('#__emundus_setup_availabilities', 'esa') . ' ON ' . $this->db->quoteName('esa.slot') . ' = ' . $this->db->quoteName('eses.id'))
				->leftJoin($this->db->quoteName('#__emundus_registrants', 'er') . ' ON ' . $this->db->quoteName('er.slot') . ' = ' . $this->db->quoteName('eses.id'));
			if (!empty($start))
			{
				$query->where($this->db->quoteName('eses.start_date') . ' >= ' . $this->db->quote($start));
			}
			if (!empty($end))
			{
				$query->where($this->db->quoteName('eses.end_date') . ' <= ' . $this->db->quote($end));
			}
			if (!empty($events_ids))
			{
				$query->where($this->db->quoteName('eses.event') . ' IN (' . $events_ids . ')');
			}
			$query->group('eses.id');
			$this->_db->setQuery($query);
			$events_slots = $this->_db->loadObjectList();

			foreach ($events_slots as $slot)
			{
				// Convert UTC dates to platform timezone ($timezone)
				$slot->start = EmundusHelperDate::displayDate($slot->start, 'Y-m-d H:i', 0);
				$slot->end   = EmundusHelperDate::displayDate($slot->end, 'Y-m-d H:i', 0);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while getting events slots: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $events_slots;
	}

	public function getAllEventsAvailabilities($start = '', $end = '', $events_ids = '')
	{
		$events_slots = [];

		try
		{
			$query = $this->db->getQuery(true);

			$columns = [
				'esa.id',
				'eses.event as event_id',
				'eses.id as slot_id',
				'ese.name',
				'ese.color',
				'ese.slot_duration',
				'ese.slot_duration_type',
				'esa.start_date as start',
				'esa.end_date as end',
				'eses.room',
				'eses.parent_slot_id',
				'dlr.name as location',
				'eses.slot_capacity',
				'eses.more_infos',
				'group_concat(DISTINCT essu.user) as users',
				'group_concat(DISTINCT concat(eu.lastname," ",eu.firstname)) as people',
				'esa.capacity as availabilities_count',
				'count(DISTINCT er.id) as booked_count'
			];

			$query->select($columns)
				->from($this->db->quoteName('#__emundus_setup_availabilities', 'esa'))
				->leftJoin($this->db->quoteName('#__emundus_setup_event_slots', 'eses') . ' ON ' . $this->db->quoteName('eses.id') . ' = ' . $this->db->quoteName('esa.slot'))
				->leftJoin($this->db->quoteName('#__emundus_setup_events', 'ese') . ' ON ' . $this->db->quoteName('ese.id') . ' = ' . $this->db->quoteName('esa.event'))
				->leftJoin($this->db->quoteName('#__emundus_setup_slot_users', 'essu') . ' ON ' . $this->db->quoteName('essu.slot') . ' = ' . $this->db->quoteName('eses.id'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.id') . ' = ' . $this->db->quoteName('essu.user'))
				->leftJoin($this->db->quoteName('data_location_rooms', 'dlr') . ' ON ' . $this->db->quoteName('dlr.id') . ' = ' . $this->db->quoteName('eses.room'))
				->leftJoin($this->db->quoteName('#__emundus_registrants', 'er') . ' ON ' . $this->db->quoteName('er.availability') . ' = ' . $this->db->quoteName('esa.id'))
				->leftJoin($this->db->quoteName('#__emundus_registrants_users', 'esru') . ' ON ' . $this->db->quoteName('esru.registrant') . ' = ' . $this->db->quoteName('er.id'));
			if (!empty($start))
			{
				$query->where($this->db->quoteName('esa.start_date') . ' >= ' . $this->db->quote($start));
			}
			if (!empty($end))
			{
				$query->where($this->db->quoteName('esa.end_date') . ' <= ' . $this->db->quote($end));
			}
			if (!empty($events_ids))
			{
				$query->where($this->db->quoteName('esa.event') . ' IN (' . $events_ids . ')');
			}
			$query->group('esa.id');
			$this->_db->setQuery($query);
			$events_slots = $this->_db->loadObjectList();

			foreach ($events_slots as $slot)
			{
				// Convert UTC dates to platform timezone ($timezone)
				$slot->start = EmundusHelperDate::displayDate($slot->start, 'Y-m-d H:i', 0);
				$slot->end   = EmundusHelperDate::displayDate($slot->end, 'Y-m-d H:i', 0);

				// Get details of registrant
				if ($slot->booked_count > 0)
				{
					$slot->registrants = $this->getRegistrants('', 'DESC', '', 'all', 0, '', 0, 0, 0, 0, $slot->id);
				}
			}

		}
		catch (Exception $e)
		{
			Log::add('Error while getting events slots: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $events_slots;
	}

	/**
	 * @param $slot_id
	 *
	 * @return mixed|null
	 *
	 * @since version 2.2.0
	 */
	public function getEventSlot($slot_id)
	{
		$timezone = $this->app->get('offset', 'Europe/Paris');

		$slot = null;

		if (!empty($slot_id))
		{
			try
			{
				$query = $this->db->getQuery(true);

				$columns = [
					'eses.id',
					'eses.event as event_id',
					'ese.name',
					'ese.color',
					'eses.start_date as start',
					'eses.end_date as end',
					'eses.room',
					'eses.parent_slot_id',
					'dlr.name as location',
					'eses.slot_capacity',
					'eses.more_infos',
					'group_concat(DISTINCT essu.user) as users',
					'group_concat(DISTINCT concat(eu.lastname," ",eu.firstname)) as people',
					'sum(esa.capacity) as availabilities_count',
					'count(DISTINCT er.id) as booked_count'
				];

				$query->select($columns)
					->from($this->db->quoteName('#__emundus_setup_event_slots', 'eses'))
					->leftJoin($this->db->quoteName('#__emundus_setup_events', 'ese') . ' ON ' . $this->db->quoteName('ese.id') . ' = ' . $this->db->quoteName('eses.event'))
					->leftJoin($this->db->quoteName('#__emundus_setup_slot_users', 'essu') . ' ON ' . $this->db->quoteName('essu.slot') . ' = ' . $this->db->quoteName('eses.id'))
					->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.id') . ' = ' . $this->db->quoteName('essu.user'))
					->leftJoin($this->db->quoteName('data_location_rooms', 'dlr') . ' ON ' . $this->db->quoteName('dlr.id') . ' = ' . $this->db->quoteName('eses.room'))
					->leftJoin($this->db->quoteName('#__emundus_setup_availabilities', 'esa') . ' ON ' . $this->db->quoteName('esa.slot') . ' = ' . $this->db->quoteName('eses.id'))
					->leftJoin($this->db->quoteName('#__emundus_registrants', 'er') . ' ON ' . $this->db->quoteName('er.slot') . ' = ' . $this->db->quoteName('eses.id'))
					->where($this->db->quoteName('eses.id') . ' = ' . $slot_id);
				$query->group('eses.id');
				$this->_db->setQuery($query);
				$slot = $this->_db->loadObject();

				// Convert UTC dates to platform timezone ($timezone)
				$slot->start = EmundusHelperDate::displayDate($slot->start, 'Y-m-d H:i', 0);
				$slot->end   = EmundusHelperDate::displayDate($slot->end, 'Y-m-d H:i', 0);
			}
			catch (Exception $e)
			{
				Log::add('Error while getting event slot: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
			}
		}

		return $slot;
	}

	/**
	 * @param $name
	 * @param $address
	 * @param $rooms
	 * @param $user_id
	 * @param $id
	 *
	 * @return int|mixed
	 *
	 * @since version 2.2.0
	 */
	public function saveLocation($name, $address, $description, $rooms, $user_id = 0, $id = 0)
	{
		$query       = $this->db->getQuery(true);
		$location_id = 0;

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		try
		{
			$save_location = [
				'name'        => $name,
				'address'     => $address,
				'description' => $description,
			];

			if (!empty($id))
			{
				$save_location['id']         = $id;
				$save_location['updated']    = date('Y-m-d H:i:s');
				$save_location['updated_by'] = $user_id;
			}
			else
			{
				$save_location['date_time']  = date('Y-m-d H:i:s');
				$save_location['created_by'] = $user_id;
			}

			$save_location = (object) $save_location;

			if (!empty($id))
			{
				$this->db->updateObject('data_events_location', $save_location, 'id');
				$location_id = $id;
			}
			else
			{
				$this->db->insertObject('data_events_location', $save_location);
				$location_id = $this->db->insertid();
			}

			if (!empty($location_id))
			{
				$query->select('id,name')
					->from($this->db->quoteName('data_location_rooms'))
					->where($this->db->quoteName('location') . ' = ' . $location_id);
				$this->db->setQuery($query);
				$existing_rooms = $this->db->loadObjectList();

				foreach ($rooms as $key => $room)
				{
					if (!empty($existing_rooms[$key]))
					{
						$room->id    = $existing_rooms[$key]->id;
						$update_room = [
							'id'   => $room->id,
							'name' => $room->name,
						];
						$update_room = (object) $update_room;
						$this->db->updateObject('data_location_rooms', $update_room, 'id');
					}
					else
					{
						$insert_room = [
							'location' => $location_id,
							'name'     => $room->name,
						];
						$insert_room = (object) $insert_room;
						if ($this->db->insertObject('data_location_rooms', $insert_room))
						{
							$room->id = $this->db->insertid();
							foreach ($room->specifications as $specification)
							{
								$insert_spec_room = [
									'room'          => $room->id,
									'specification' => $specification->value
								];
								$insert_spec_room = (object) $insert_spec_room;
								$this->db->insertObject('data_location_rooms_specs', $insert_spec_room);
							}
						}
					}
				}

				// Delete removed rooms
				$existing_rooms_ids = array_column($existing_rooms, 'id');
				$rooms_ids          = array_column($rooms, 'id');
				$deleted_rooms      = array_diff($existing_rooms_ids, $rooms_ids);
				if (!empty($deleted_rooms))
				{
					foreach ($deleted_rooms as $deletedRoom)
					{
						// Check if the room is linked to a slot
						$query->clear()
							->select('id')
							->from($this->db->quoteName('#__emundus_setup_event_slots'))
							->where($this->db->quoteName('room') . ' = ' . $deletedRoom);
						$this->db->setQuery($query);
						$linked_slot = $this->db->loadResult();

						if (empty($linked_slot))
						{
							$query->clear()
								->delete($this->db->quoteName('data_location_rooms'))
								->where($this->db->quoteName('id') . ' IN (' . implode(',', $this->db->quote($deleted_rooms)) . ')');
							$this->db->setQuery($query);
							$this->db->execute();
						}
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while creating location: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $location_id;
	}

	/**
	 * @param $id
	 *
	 * @return bool|mixed
	 *
	 * @since version 2.2.0
	 */
	public function deleteLocation($id)
	{
		$deleted = false;

		try
		{
			if (!empty($id))
			{
				if (!is_array($id))
				{
					$id = [$id];
				}

				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('data_events_location'))
					->where($this->db->quoteName('id') . ' IN (' . implode(',', $this->db->quote($id)) . ')');
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while deleting location: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $deleted;
	}

	/**
	 * @param $name
	 * @param $location
	 * @param $is_conference_link
	 * @param $conference_engine
	 * @param $link
	 * @param $generate_link_by
	 * @param $manager
	 * @param $available_for
	 * @param $campaigns
	 * @param $programs
	 * @param $user_id
	 *
	 * @return int|mixed
	 *
	 * @since version 2.2.0
	 */
	public function createEvent($name, $color, $location, $is_conference_link, $conference_engine, $link, $generate_link_by, $manager, $available_for, $campaigns, $programs, $user_id = 0, $teams_subject = '')
	{
		$event_id = 0;

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		try
		{
			$insert_event = [
				'date_time'                => date('Y-m-d H:i:s'),
				'name'                     => $name,
				'color'                    => $color,
				'location'                 => $location,
				'is_conference_link'       => $is_conference_link,
				'conference_engine'        => $conference_engine,
				'generate_link_by'         => $generate_link_by,
				'link'                     => $link,
				'manager'                  => $manager,
				'available_for'            => $available_for,
				'created_by'               => $user_id,
				'slots_availables_to_show' => 0,
				'slot_can_cancel'          => 0,
				'teams_subject'            => $teams_subject
			];
			$insert_event = (object) $insert_event;

			if ($this->db->insertObject('jos_emundus_setup_events', $insert_event))
			{
				$event_id = $this->db->insertid();

				if ($available_for == 1)
				{
					foreach ($campaigns as $campaign)
					{
						$insert_campaign = [
							'event'    => $event_id,
							'campaign' => $campaign
						];
						$insert_campaign = (object) $insert_campaign;
						$this->db->insertObject('jos_emundus_setup_events_repeat_campaign', $insert_campaign);
					}
				}
				elseif ($available_for == 2)
				{
					foreach ($programs as $program)
					{
						$insert_program = [
							'event'     => $event_id,
							'programme' => $program
						];
						$insert_program = (object) $insert_program;
						$this->db->insertObject('jos_emundus_setup_events_repeat_program', $insert_program);
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while creating event: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $event_id;
	}

	/**
	 * @param   string  $start_date      Start date of the slot
	 * @param   string  $end_date        End date of the slot
	 * @param   int     $room            Room of the slot
	 * @param   int     $slot_capacity   Capacity of the slot
	 * @param   string  $more_infos      More informations about the slot
	 * @param   array   $users           Users linked to the slot
	 * @param   int     $event_id        Event linked to the slot
	 * @param   string  $repeat_dates    Dates to repeat the slot
	 * @param   int     $id              Id of the slot to update
	 * @param   int     $parent_slot_id  Id of the parent slot
	 * @param   int     $mode            1: Save only this slot and dissociate it to parent, 2: Save this slot and futures, so dissociate it to parent it became a parent, link future slots to it, 3: Save all slots linked (childs if slot is parent, parent and childs if slot is child)
	 * @param   int     $user_id         User id of the user who save the slot
	 *
	 * @return array
	 *
	 * @since version 2.2.0
	 */
	public function saveEventSlot($start_date, $end_date, $room, $slot_capacity, $more_infos, $users, $event_id, $repeat_dates, $id = 0, $parent_slot_id = 0, $mode = 1, $availability_config = [], $user_id = 0)
	{
		$query = $this->db->getQuery(true);

		$slots_ids = [];
		$slots     = [];

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		try
		{
			if (!empty($start_date) && !empty($end_date) && !empty($event_id))
			{
				$slot_capacity = empty($slot_capacity) ? 1 : $slot_capacity;

				$slots_linked = [];

				if ($mode == 1)
				{
					$parent_slot_id = 0;
				}

				$timezone = $this->app->get('offset', 'Europe/Paris');

				$save_slot_event = [
					'event'          => $event_id,
					'parent_slot_id' => $parent_slot_id,
					'start_date'     => Factory::getDate($start_date, $timezone)->toSql(),
					'end_date'       => Factory::getDate($end_date, $timezone)->toSql(),
					'room'           => $room == 0 ? null : $room,
					'slot_capacity'  => $slot_capacity,
					'more_infos'     => $more_infos,
				];

				if (!empty($id))
				{
					$save_slot_event['id'] = $id;
				}
				$save_slot_event = (object) $save_slot_event;

				if (!empty($id))
				{
					if ($mode == 1)
					{
						$repeat_dates = '';
					}

					if ($this->db->updateObject('jos_emundus_setup_event_slots', $save_slot_event, 'id'))
					{
						if (empty($parent_slot_id))
						{
							$query->clear()
								->select('*')
								->from($this->db->quoteName('#__emundus_setup_event_slots'))
								->where($this->db->quoteName('parent_slot_id') . ' = ' . $id);
							$this->db->setQuery($query);
							$slots_linked = $this->db->loadObjectList();

							if (!empty($slots_linked) && $mode == 1)
							{
								$first_child_id = $slots_linked[0]->id;

								// We dissociate the parent to other slot so first child slot become parent
								$query->clear()
									->update($this->db->quoteName('#__emundus_setup_event_slots'))
									->set($this->db->quoteName('parent_slot_id') . ' = 0')
									->where($this->db->quoteName('id') . ' = ' . $first_child_id);
								$this->db->setQuery($query);
								$this->db->execute();

								// We link future slots to the first child slot
								$query->clear()
									->update($this->db->quoteName('#__emundus_setup_event_slots'))
									->set($this->db->quoteName('parent_slot_id') . ' = ' . $first_child_id)
									->where($this->db->quoteName('parent_slot_id') . ' = ' . $id);
								$this->db->setQuery($query);
								$this->db->execute();
							}
						}
						else
						{
							$parent_slots = [];
							if ($mode != 2)
							{
								$query->clear()
									->select('*')
									->from($this->db->quoteName('#__emundus_setup_event_slots'))
									->where($this->db->quoteName('id') . ' = ' . $parent_slot_id);
								$this->db->setQuery($query);
								$parent_slots = $this->db->loadObjectList();
							}

							$query->clear()
								->select('*')
								->from($this->db->quoteName('#__emundus_setup_event_slots'))
								->where($this->db->quoteName('parent_slot_id') . ' = ' . $parent_slot_id);
							if ($mode == 2)
							{
								// Get only childs in the future of the current slot saved
								$query->where($this->db->quoteName('start_date') . ' >= ' . $this->db->quote($start_date));
							}
							$this->db->setQuery($query);
							$childs = $this->db->loadObjectList();

							if ($mode == 2)
							{
								// current slot became parent and we link future slots to it
								$query->clear()
									->update($this->db->quoteName('#__emundus_setup_event_slots'))
									->set($this->db->quoteName('parent_slot_id') . ' = ' . $id)
									->where($this->db->quoteName('parent_slot_id') . ' = ' . $parent_slot_id)
									->where($this->db->quoteName('start_date') . ' >= ' . $this->db->quote($start_date));
								$this->db->setQuery($query);
								$this->db->execute();

								$query->clear()
									->update($this->db->quoteName('#__emundus_setup_event_slots'))
									->set($this->db->quoteName('parent_slot_id') . ' = 0')
									->where($this->db->quoteName('id') . ' = ' . $id);
								$this->db->setQuery($query);
								$this->db->execute();

								$repeat_dates = [];
								// Set repeat dates only for future slots
								foreach ($childs as $child)
								{
									$repeat_dates[] = date('Y-m-d', strtotime($child->start_date));
								}
							}

							$slots_linked = array_merge($parent_slots, $childs);
						}
					}
				}
				else
				{
					if ($this->db->insertObject('jos_emundus_setup_event_slots', $save_slot_event))
					{
						$id = $this->db->insertid();
					}
				}

				// Check if values of users are not empty
				$users = array_filter($users);

				// Extract hours from start_date and end_date
				$start_hours = date('H:i:s', strtotime($start_date));
				$end_hours   = date('H:i:s', strtotime($end_date));

				if (!empty($id))
				{
					$slots_ids[] = $id;

					// Manage users
					$query->clear()
						->delete($this->db->quoteName('#__emundus_setup_slot_users'))
						->where($this->db->quoteName('slot') . ' = ' . $id);
					$this->db->setQuery($query);
					if ($this->db->execute())
					{
						foreach ($users as $user)
						{
							$insert_user = [
								'slot' => $id,
								'user' => $user
							];
							$insert_user = (object) $insert_user;
							$this->db->insertObject('jos_emundus_setup_slot_users', $insert_user);
						}
					}
					//

					// Insert new linked slot if repeat_dates is not empty
					if (!is_array($repeat_dates))
					{
						$repeat_dates = explode(',', $repeat_dates);
						$repeat_dates = array_filter($repeat_dates);
					}

					foreach ($repeat_dates as $repeatDate)
					{
						// Check if repeatDate is not current slot
						if (date('Y-m-d', strtotime($repeatDate)) === date('Y-m-d', strtotime($start_date)))
						{
							continue;
						}

						$link_slot_id = 0;
						foreach ($slots_linked as $slot_linked)
						{
							$link_start_date = date('Y-m-d', strtotime($slot_linked->start_date));
							if ($link_start_date === $repeatDate)
							{
								$link_slot_id = $slot_linked->id;
								break;
							}
						}

						$start_date = date('Y-m-d', strtotime($repeatDate));
						$start_date = $start_date . ' ' . $start_hours;

						$end_date = date('Y-m-d', strtotime($repeatDate));
						$end_date = $end_date . ' ' . $end_hours;

						if (empty($link_slot_id))
						{
							$save_slot_event = [
								'event'          => $event_id,
								'start_date'     => Factory::getDate($start_date, $timezone)->toSql(),
								'end_date'       => Factory::getDate($end_date, $timezone)->toSql(),
								'room'           => $room == 0 ? null : $room,
								'slot_capacity'  => $slot_capacity,
								'more_infos'     => $more_infos,
								'parent_slot_id' => $id
							];
							$save_slot_event = (object) $save_slot_event;
							if ($this->db->insertObject('jos_emundus_setup_event_slots', $save_slot_event))
							{
								$slots_ids[] = $this->db->insertid();
							}
						}
					}
					//

					// Update all slots already linked
					foreach ($slots_linked as $slot_linked)
					{
						$start_date = date('Y-m-d', strtotime($slot_linked->start_date));
						$start_date = $start_date . ' ' . $start_hours;

						$end_date = date('Y-m-d', strtotime($slot_linked->end_date));
						$end_date = $end_date . ' ' . $end_hours;

						$save_slot_event = [
							'id'            => $slot_linked->id,
							'start_date'    => Factory::getDate($start_date, $timezone)->toSql(),
							'end_date'      => Factory::getDate($end_date, $timezone)->toSql(),
							'room'          => $room == 0 ? null : $room,
							'slot_capacity' => $slot_capacity,
							'more_infos'    => $more_infos
						];
						$save_slot_event = (object) $save_slot_event;
						if ($this->db->updateObject('jos_emundus_setup_event_slots', $save_slot_event, 'id'))
						{
							$slots_ids[] = $slot_linked->id;
						}

						// TODO: Update assoc users too
					}
					//

					$this->setupAvailabilities($event_id, $availability_config);

					foreach ($slots_ids as $slotsId)
					{
						$slots[] = $this->getEventSlot($slotsId);
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while creating event slot: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $slots;
	}

	/**
	 * @param $event_slot_id
	 * @param $user_id
	 *
	 * @return bool|mixed
	 *
	 * @since version 2.2.0
	 */
	public function deleteEventSlot($event_slot_id, $user_id)
	{
		$deleted = false;

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		try
		{
			if (!empty($event_slot_id))
			{
				$query = $this->db->getQuery(true);
				//TODO: Check if some bookings are already made for this slot

				$query->delete($this->db->quoteName('#__emundus_setup_event_slots'))
					->where($this->db->quoteName('id') . ' = ' . $event_slot_id);
				$this->db->setQuery($query);
				$deleted = $this->db->execute();

				// Delete all availabilities linked to the slot
				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_availabilities'))
					->where($this->db->quoteName('slot') . ' = ' . $event_slot_id);
				$this->db->setQuery($query);
				$this->db->execute();
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while deleting event slot: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $deleted;
	}

	/**
	 * @param $event_id
	 * @param $slot_duration
	 * @param $slot_break_every
	 * @param $slot_break_time
	 * @param $slots_availables_to_show
	 * @param $slot_can_book_until
	 * @param $slot_can_cancel
	 * @param $slot_can_cancel_until
	 * @param $user_id
	 *
	 * @return bool
	 *
	 * @since version 2.2.0
	 */
	public function setupSlot($event_id, $slot_duration, $slot_break_every, $slot_break_time, $slots_availables_to_show, $slot_can_book_until, $slot_can_cancel, $slot_can_cancel_until, $user_id)
	{
		$status = false;

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		try
		{
			if (!empty($event_id) && !empty($slot_duration))
			{
				$slot_duration_type = 'minutes';
				if (strpos($slot_duration, 'hours'))
				{
					$slot_duration_type = 'hours';
				}

				$slot_break_time_type = 'minutes';
				if (strpos($slot_break_time, 'hours'))
				{
					$slot_break_time_type = 'hours';
				}

				$slot_can_book_until_type = 'days';
				if (strpos($slot_can_book_until, 'date'))
				{
					$slot_can_book_until_type = 'date';
				}

				$slot_can_cancel_until_type = 'days';
				if (!empty($slot_can_cancel_until) && strpos($slot_can_cancel_until, 'date'))
				{
					$slot_can_cancel_until_type = 'date';
				}

				$slot_config = [
					'id'                         => $event_id,
					// Store slot_duration in minutes
					'slot_duration'              => (int) $slot_duration,
					'slot_duration_type'         => $slot_duration_type,
					'slot_break_every'           => $slot_break_every,
					// Store slot_break_time in minutes
					'slot_break_time'            => (int) $slot_break_time,
					'slot_break_time_type'       => $slot_break_time_type,
					'slots_availables_to_show'   => $slots_availables_to_show,
					'slot_can_book_until_days'   => ($slot_can_book_until_type == 'days' && !empty(explode(" ", $slot_can_book_until)[0])) ? explode(" ", $slot_can_book_until)[0] : 0,
					'slot_can_book_until_date'   => ($slot_can_book_until_type == 'date' && !empty(explode(" ", $slot_can_book_until)[0])) ? (new DateTime(explode(" ", $slot_can_book_until)[0]))->format('Y-m-d') : null,
					'slot_can_cancel_until_days' => ($slot_can_cancel_until_type == 'days' && !empty(explode(" ", $slot_can_cancel_until)[0])) ? explode(" ", $slot_can_cancel_until)[0] : 0,
					'slot_can_cancel_until_date' => ($slot_can_cancel_until_type == 'date' && !empty(explode(" ", $slot_can_cancel_until)[0])) ? (new DateTime(explode(" ", $slot_can_cancel_until)[0]))->format('Y-m-d') : null,
					'slot_can_cancel'            => $slot_can_cancel,
					'updated_by'                 => $user_id,
					'updated'                    => date('Y-m-d H:i:s')
				];
				$slot_config = (object) $slot_config;
				if ($status = $this->db->updateObject('jos_emundus_setup_events', $slot_config, 'id'))
				{
					$this->setupAvailabilities($event_id);
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while setting up slot: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $status;
	}

	public function saveBookingNotifications($event_id, $booking_notifications, $user_id = 0)
	{
		$status = false;

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		try
		{
			$query = $this->db->getQuery(true);

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_events_notifications'))
				->where($this->db->quoteName('event') . ' = ' . $event_id);
			$this->db->setQuery($query);
			$notification_id = $this->db->loadResult();

			$notification_setup = [
				'event' => $event_id
			];

			foreach ($booking_notifications as $key => $notification)
			{
				$notification_setup[$key] = $notification;
			}

			if (!empty($notification_id))
			{
				$notification_setup['id'] = $notification_id;

				$notification_setup = (object) $notification_setup;
				$status             = $this->db->updateObject('jos_emundus_setup_events_notifications', $notification_setup, 'id');
			}
			else
			{
				$notification_setup = (object) $notification_setup;
				$status             = $this->db->insertObject('jos_emundus_setup_events_notifications', $notification_setup);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while saving booking notifications: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $status;
	}

	/**
	 * @param $event_id
	 * @param $slot_id
	 * @param $slot_duration_type
	 * @param $slot_duration
	 *
	 * @return bool|mixed
	 *
	 * @since version 2.2.0
	 */
	public function setupAvailabilities($event_id, $slot_config = null, $slot_id = 0)
	{
		$saved = false;
		$query = $this->db->getQuery(true);

		if (!empty($event_id))
		{
			try
			{
				if (empty($slot_config))
				{
					$query->select('slot_duration, slot_duration_type, slot_break_every, slot_break_time, slot_break_time_type')
						->from($this->db->quoteName('#__emundus_setup_events'))
						->where($this->db->quoteName('id') . ' = ' . $event_id);
					$this->db->setQuery($query);
					$slot_config = $this->db->loadAssoc();
				}

				// Delete only the availabilities that are not booked
				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_availabilities', 'esa'))
					->where($this->db->quoteName('esa.id') . ' NOT IN (SELECT availability FROM #__emundus_registrants)')
					->where($this->db->quoteName('esa.event') . ' = ' . $event_id);
				if (!empty($slot_id))
				{
					$query->where($this->db->quoteName('esa.slot') . ' = ' . $slot_id);
				}
				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->select('id,start_date,end_date,slot_capacity')
					->from($this->db->quoteName('#__emundus_setup_event_slots'));
				if (empty($slot_id))
				{
					$query->where($this->db->quoteName('event') . ' = ' . $event_id);
				}
				else
				{
					$query->where($this->db->quoteName('id') . ' = ' . $slot_id);
				}
				$this->db->setQuery($query);
				$slots = $this->db->loadObjectList();

				if (!empty($slots))
				{
					$query->clear()
						->insert($this->db->quoteName('#__emundus_setup_availabilities'))
						->columns(['slot', 'event', 'start_date', 'end_date', 'capacity']);
					$insert_availabilities      = [];
					$slot_duration_in_minutes   = $slot_config['slot_duration_type'] == 'hours' ? $slot_config['slot_duration'] * 60 : $slot_config['slot_duration'];
					$slot_break_time_in_minutes = $slot_config['slot_break_time_type'] == 'hours' ? $slot_config['slot_break_time'] * 60 : $slot_config['slot_break_time'];
					foreach ($slots as $slot)
					{
						$availabilities_slot = $this->getAvailableSlots($slot->start_date, $slot->end_date, $slot_duration_in_minutes, $slot_break_time_in_minutes, $slot_config['slot_break_every']);
						foreach ($availabilities_slot as $availability)
						{
							// Do not insert availabilities already booked
							$check_query = $this->db->getQuery(true);
							$check_query->clear()
								->select('id')
								->from($this->db->quoteName('#__emundus_setup_availabilities'))
								->where($this->db->quoteName('slot') . ' = ' . $slot->id)
								->where($this->db->quoteName('start_date') . ' = ' . $this->db->quote($availability['start']))
								->where($this->db->quoteName('end_date') . ' = ' . $this->db->quote($availability['end']));
							$this->db->setQuery($check_query);
							$availability_id = $this->db->loadResult();

							if (empty($availability_id))
							{
								$insert_availabilities[] = $slot->id . ', ' . $event_id . ', ' . $this->db->quote($availability['start']) . ', ' . $this->db->quote($availability['end']) . ', ' . $this->db->quote($slot->slot_capacity);
							}
						}
					}

					if (!empty($insert_availabilities))
					{
						$query->values($insert_availabilities);
						$this->db->setQuery($query);
						$saved = $this->db->execute();
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error while setting up availabilities: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
			}
		}

		return $saved;
	}

	/**
	 * @param $startDateTime
	 * @param $endDateTime
	 * @param $slotDurationMinutes
	 *
	 * @return array
	 *
	 * @since version 2.2.0
	 */
	private function getAvailableSlots($startDateTime, $endDateTime, $slotDurationMinutes = 20, $breakDurationMinutes = 10, $breakAfterSlots = 4)
	{
		$slots       = [];
		$current     = strtotime($startDateTime);
		$end         = strtotime($endDateTime);
		$slotCounter = 0;

		while ($current < $end)
		{
			$slotStart = date('Y-m-d H:i:s', $current);
			$current   += $slotDurationMinutes * 60; // Increment by the slot duration
			$slotEnd   = date('Y-m-d H:i:s', $current);

			// Ensure the slot end does not exceed the end of the range
			if ($current <= $end)
			{
				$slots[] = [
					'start' => $slotStart,
					'end'   => $slotEnd
				];

				$slotCounter++;
			}

			// Add a break after every $breakAfterSlots slots
			if ($slotCounter === $breakAfterSlots)
			{
				$current     += $breakDurationMinutes * 60; // Skip the break time
				$slotCounter = 0;
			}
		}

		return $slots;
	}

	/**
	 * @param $id
	 * @param $name
	 * @param $location
	 * @param $is_conference_link
	 * @param $conference_engine
	 * @param $link
	 * @param $generate_link_by
	 * @param $manager
	 * @param $available_for
	 * @param $campaigns
	 * @param $programs
	 * @param $user_id
	 *
	 * @return mixed
	 *
	 * @since version 2.2.0
	 */
	public function editEvent($id, $name, $color, $location, $is_conference_link, $conference_engine, $link, $generate_link_by, $manager, $available_for, $campaigns, $programs, $teams_subject = '', $user_id = 0)
	{
		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		try
		{
			$insert_event = [
				'id'                 => $id,
				'date_time'          => date('Y-m-d H:i:s'),
				'name'               => $name,
				'color'              => $color,
				'location'           => $location,
				'is_conference_link' => $is_conference_link,
				'conference_engine'  => $conference_engine,
				'generate_link_by'   => $generate_link_by,
				'teams_subject'      => $teams_subject,
				'link'               => $link,
				'manager'            => $manager == 0 ? null : $manager,
				'available_for'      => $available_for,
				'created_by'         => $user_id
			];
			$insert_event = (object) $insert_event;

			if ($this->db->updateObject('jos_emundus_setup_events', $insert_event, 'id'))
			{
				$query = $this->db->getQuery(true)
					->clear()
					->select($this->db->quoteName('eserc.campaign'))
					->from($this->db->quoteName('#__emundus_setup_events_repeat_campaign', 'eserc'))
					->where($this->db->quoteName('eserc.event') . ' = ' . (int) $id);
				$this->db->setQuery($query);
				$existing_campaigns = $this->db->loadColumn();

				$campaigns_to_add    = array_diff($campaigns, $existing_campaigns);
				$campaigns_to_remove = array_diff($existing_campaigns, $campaigns);

				foreach ($campaigns_to_add as $campaign)
				{
					$query = $this->db->getQuery(true)
						->clear()
						->insert($this->db->quoteName('#__emundus_setup_events_repeat_campaign'))
						->columns([$this->db->quoteName('event'), $this->db->quoteName('campaign')])
						->values((int) $id . ', ' . (int) $campaign);
					$this->db->setQuery($query)->execute();
				}

				foreach ($campaigns_to_remove as $campaign)
				{
					$query = $this->db->getQuery(true)
						->clear()
						->delete($this->db->quoteName('#__emundus_setup_events_repeat_campaign'))
						->where($this->db->quoteName('event') . ' = ' . (int) $id)
						->where($this->db->quoteName('campaign') . ' = ' . (int) $campaign);
					$this->db->setQuery($query)->execute();
				}
				$query = $this->db->getQuery(true)
					->clear()
					->select($this->db->quoteName('eserp.programme'))
					->from($this->db->quoteName('#__emundus_setup_events_repeat_program', 'eserp'))
					->where($this->db->quoteName('eserp.event') . ' = ' . (int) $id);
				$this->db->setQuery($query);
				$existing_programs = $this->db->loadColumn();

				$programs_to_add    = array_diff($programs, $existing_programs);
				$programs_to_remove = array_diff($existing_programs, $programs);

				foreach ($programs_to_add as $program)
				{
					$query = $this->db->getQuery(true)
						->clear()
						->insert($this->db->quoteName('#__emundus_setup_events_repeat_program'))
						->columns([$this->db->quoteName('event'), $this->db->quoteName('programme')])
						->values((int) $id . ', ' . (int) $program);
					$this->db->setQuery($query)->execute();
				}

				foreach ($programs_to_remove as $program)
				{
					$query = $this->db->getQuery(true)
						->clear()
						->delete($this->db->quoteName('#__emundus_setup_events_repeat_program'))
						->where($this->db->quoteName('event') . ' = ' . (int) $id)
						->where($this->db->quoteName('programme') . ' = ' . (int) $program);
					$this->db->setQuery($query)->execute();
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while editing event: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $id;
	}

	public function editSlot($registrant_id, $availability, $event_id, $users_id, $ccid)
	{
		require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
		$m_files = new EmundusModelFiles();

		$dispatcher = Factory::getApplication()->getDispatcher();
		PluginHelper::importPlugin('emundus');

		$id = 0;
		try
		{
			$query = $this->db->getQuery(true);

			$query->select([
				'ecc.id',
				'ecc.applicant_id',
				'ecc.fnum',
			])
				->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->where($this->db->quoteName('ecc.id') . ' = ' . $ccid);

			$this->_db->setQuery($query);
			$candidature = $this->_db->loadObject();

			if ($candidature)
			{
				if (!($registrant_id))
				{
					$id = $this->createAvailabilityRegistrant($availability, $candidature->fnum, $users_id);
				}
				else
				{
					$query->clear()
						->select([
							'esa.slot',
						])
						->from($this->db->quoteName('#__emundus_setup_availabilities', 'esa'))
						->where($this->db->quoteName('esa.id') . ' = ' . $availability);

					$this->_db->setQuery($query);
					$slot_id = $this->_db->loadColumn()[0];

					if ($slot_id)
					{
						$query->clear()
							->update($this->db->quoteName('#__emundus_registrants', 'er'))
							->set($this->db->quoteName('er.user') . ' = ' . $this->db->quote($candidature->applicant_id))
							->set($this->db->quoteName('er.event') . ' = ' . $this->db->quote($event_id))
							->set($this->db->quoteName('er.availability') . ' = ' . $this->db->quote($availability))
							->set($this->db->quoteName('er.slot') . ' = ' . $this->db->quote($slot_id))
							->set($this->db->quoteName('er.ccid') . ' = ' . $this->db->quote($ccid))
							->set($this->db->quoteName('er.fnum') . ' = ' . $this->db->quote($candidature->fnum))
							->where($this->db->quoteName('er.id') . ' = ' . $this->db->quote($registrant_id));
						$this->db->setQuery($query);
						$this->db->execute();

						// Assoc users
						$query->clear()
							->select('user')
							->from($this->db->quoteName('#__emundus_registrants_users'))
							->where($this->db->quoteName('registrant') . ' = ' . (int) $registrant_id);
						$this->db->setQuery($query);
						$users_assoc = $this->db->loadColumn();

						if (!empty($users_assoc))
						{
							$query->clear()
								->delete($this->db->quoteName('#__emundus_users_assoc'))
								->where($this->db->quoteName('user_id') . ' IN (' . implode(',', $users_assoc) . ')');
							$this->db->setQuery($query);
							$this->db->execute();
						}

						$query->clear()
							->delete($this->db->quoteName('#__emundus_registrants_users'))
							->where($this->db->quoteName('registrant') . ' = ' . (int) $registrant_id);
						$this->db->setQuery($query);
						$this->db->execute();

						if (!empty($users_id))
						{
							foreach ($users_id as $user)
							{
								$query->clear()
									->insert($this->db->quoteName('#__emundus_registrants_users'))
									->columns([$this->db->quoteName('registrant'), $this->db->quoteName('user')])
									->values((int) $registrant_id . ', ' . (int) $user);

								$this->db->setQuery($query);
								$this->db->execute();
							}

							$read_access     = new \stdClass();
							$read_access->id = 1;
							$read_access->c  = 0;
							$read_access->r  = 1;
							$read_access->u  = 0;
							$read_access->d  = 0;
							$actions         = [$read_access];

							$m_files->shareUsers($users_id, $actions, [$candidature->fnum]);
						}

						// Declare the event
						$onAfterBookingRegistrantEventHandler = new GenericEvent(
							'onCallEventHandler',
							['onAfterBookingRegistrant',
								// Datas to pass to the event
								['fnum' => $candidature->fnum, 'ccid' => $ccid, 'availability' => $availability, 'registrant_id' => $registrant_id]
							]
						);
						$onAfterBookingRegistrant             = new GenericEvent(
							'onAfterBookingRegistrant',
							// Datas to pass to the event
							['fnum' => $candidature->fnum, 'ccid' => $ccid, 'availability' => $availability, 'registrant_id' => $registrant_id]
						);

						// Dispatch the event
						$dispatcher->dispatch('onCallEventHandler', $onAfterBookingRegistrantEventHandler);
						$dispatcher->dispatch('onAfterBookingRegistrant', $onAfterBookingRegistrant);
						//

						$id = $ccid;
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while editing slot: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $id;
	}


	/**
	 * @param $id
	 *
	 * @return bool|mixed
	 *
	 * @since version 2.2.0
	 */
	public function deleteEvent($id)
	{
		$deleted = false;

		if (!empty($id))
		{
			if (!is_array($id))
			{
				$id = [$id];
			}

			try
			{
				$query = $this->db->getQuery(true);

				// Delete only the availabilities that are not booked
				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_availabilities', 'esa'))
					->where($this->db->quoteName('esa.id') . ' NOT IN (SELECT availability FROM #__emundus_registrants)')
					->where($this->db->quoteName('esa.event') . ' IN (' . implode(',', $this->db->quote($id)) . ')');
				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_events'))
					->where($this->db->quoteName('id') . ' IN (' . implode(',', $this->db->quote($id)) . ')');
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
			catch (Exception $e)
			{
				Log::add('Error while deleting event: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
			}
		}

		return $deleted;
	}

	/**
	 * @param $start
	 * @param $end
	 * @param $events_ids
	 * @param $availability_ids
	 *
	 * @return array|mixed
	 *
	 * @since version 2.2.0
	 */
	public function getEventsAvailabilities($start = '', $end = '', $events_ids = [], $availability_ids = [], $convert_dates = true)
	{
		$timezone = $this->app->get('offset', 'Europe/Paris');

		$events_availabilities = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select([
				'ea.id',
				'ea.slot',
				'ea.event as event_id',
				'ea.start_date as start',
				'ea.end_date as end',
				'ea.capacity'
			])
				->from($this->db->quoteName('#__emundus_setup_availabilities', 'ea'));

			if (!empty($availability_ids))
			{
				$query->where($this->db->quoteName('ea.id') . ' IN (' . implode(',', array_map([$this->db, 'quote'], is_array($availability_ids) ? $availability_ids : [$availability_ids])) . ')');
			}
			if (!empty($start))
			{
				$query->where($this->db->quoteName('ea.start_date') . ' >= ' . $this->db->quote($start));
			}
			if (!empty($end))
			{
				$query->where($this->db->quoteName('ea.end_date') . ' <= ' . $this->db->quote($end));
			}
			if (!empty($events_ids))
			{
				$query->where($this->db->quoteName('ea.event') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $events_ids)) . ')');
			}
			$query->group('ea.id');

			$this->_db->setQuery($query);
			$events_availabilities = $this->_db->loadObjectList();

			if ($convert_dates)
			{
				foreach ($events_availabilities as $slot)
				{
					// Convert UTC dates to platform timezone ($timezone)
					$slot->start = EmundusHelperDate::displayDate($slot->start, 'Y-m-d H:i', 0);
					$slot->end   = EmundusHelperDate::displayDate($slot->end, 'Y-m-d H:i', 0);
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while getting events availabilities: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $events_availabilities;
	}

	/**
	 * @param $cid
	 * @param $program_code
	 * @param $start
	 * @param $end
	 *
	 * @return array|mixed
	 *
	 * @throws Exception
	 * @since version 2.2.0
	 */
	public function getAvailabilitiesByCampaignsAndPrograms($cid = 0, $program_code = '', $start = '', $end = '', $location = 0, $check_booking_limit_reached = 0, $events_ids = [], $check_availables_to_show = true)
	{
		$timezone = $this->app->get('offset', 'Europe/Paris');

		$availabilities = [];

		if (empty($cid) && empty($program_code))
		{
			$user = Factory::getApplication()->getSession()->get('emundusUser');
			if ($user)
			{
				$program_code = $user->code;
				$cid          = $user->campaign_id;
			}
		}

		$campaigns_events = $this->getEventsByCampaignIds($cid);
		$programs_events  = $this->getEventsByProgramCodes($program_code);

		$events = array_merge($campaigns_events, $programs_events);

		if (!empty($events_ids))
		{
			$events = array_merge($events, $events_ids);
		}

		if (!empty($events))
		{
			try
			{
				$query = $this->db->getQuery(true);


				// If location is set filter events by location
				if (!empty($location))
				{
					$query->clear()
						->select('id')
						->from($this->db->quoteName('#__emundus_setup_events'))
						->where($this->db->quoteName('location') . ' = ' . $location);
					$this->db->setQuery($query);
					$events = array_intersect($events, $this->db->loadColumn());
				}

				$availabilities = [];
				foreach ($events as $event)
				{
					$query->clear()
						->select('slots_availables_to_show, slot_can_book_until_days, slot_can_book_until_date')
						->from($this->db->quoteName('#__emundus_setup_events', 'ese'))
						->where($this->db->quoteName('ese.id') . ' = ' . $event);
					$this->db->setQuery($query);
					$slots_infos = $this->db->loadObject();

					$query->clear()
						->select([
							'ea.id',
							'ea.slot',
							'ea.event as event_id',
							'ea.start_date as start',
							'ea.end_date as end',
							'ea.capacity'
						])
						->from($this->db->quoteName('#__emundus_setup_availabilities', 'ea'))
						->where($this->db->quoteName('ea.event') . ' = ' . $event);
					if (!empty($start))
					{
						$query->where($this->db->quoteName('ea.start_date') . ' >= ' . $this->db->quote($start));
					}
					if (!empty($end))
					{
						$query->where($this->db->quoteName('ea.end_date') . ' <= ' . $this->db->quote($end));
					}
					$query->group('ea.id');

					if (!empty($slots_infos->slots_availables_to_show) && $check_availables_to_show)
					{
						$query->setLimit($slots_infos->slots_availables_to_show);
					}

					$this->_db->setQuery($query);
					$availabilities = array_merge($availabilities, $this->_db->loadObjectList());

					if ($check_booking_limit_reached)
					{
						$now                     = new DateTime();
						$filtered_availabilities = [];

						if (!empty($slots_infos->slot_can_book_until_date))
						{
							$limitDate = (new DateTime($slots_infos->slot_can_book_until_date))->format('Y-m-d');
							if ($now->format('Y-m-d') <= $limitDate)
							{
								foreach ($availabilities as $slot)
								{
									// Convert UTC dates to platform timezone ($timezone)
									$slot->start = EmundusHelperDate::displayDate($slot->start, 'Y-m-d H:i', 0);
									$slot->end   = EmundusHelperDate::displayDate($slot->end, 'Y-m-d H:i', 0);

									$filtered_availabilities[] = $slot;
								}
							}
						}
						elseif (!empty($slots_infos->slot_can_book_until_days))
						{
							foreach ($availabilities as $slot)
							{
								$limitDate = (new DateTime($slot->start));
								$limitDate->modify('-' . $slots_infos->slot_can_book_until_days . ' days');
								$limitDate = $limitDate->format('Y-m-d');

								if (!($now->format('Y-m-d') > $limitDate))
								{
									// Convert UTC dates to platform timezone ($timezone)
									$slot->start = EmundusHelperDate::displayDate($slot->start, 'Y-m-d H:i', 0);
									$slot->end   = EmundusHelperDate::displayDate($slot->end, 'Y-m-d H:i', 0);

									$filtered_availabilities[] = $slot;
								}
							}
						}
						else
						{
							foreach ($availabilities as $slot)
							{
								// Convert UTC dates to platform timezone ($timezone)
								$slot->start = EmundusHelperDate::displayDate($slot->start, 'Y-m-d H:i', 0);
								$slot->end   = EmundusHelperDate::displayDate($slot->end, 'Y-m-d H:i', 0);

								$filtered_availabilities[] = $slot;
							}
						}

						$availabilities = $filtered_availabilities;
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error while getting availabilities by campaigns and programs: ' . $e->getMessage(), Log::ERROR, 'emundus');
			}
		}

		return $availabilities;
	}

	/**
	 * @param $campaign_ids
	 *
	 * @return array|mixed
	 *
	 * @since version 2.2.0
	 */
	public function getEventsByCampaignIds($campaign_ids)
	{
		$events = [];

		if (!empty($campaign_ids))
		{
			try
			{
				if (!is_array($campaign_ids))
				{
					$campaign_ids = [$campaign_ids];
				}
				$query = $this->db->getQuery(true);

				$query->select('event')
					->from($this->db->quoteName('#__emundus_setup_events_repeat_campaign'))
					->where('campaign IN (' . implode(',', array_map([$this->db, 'quote'], $campaign_ids)) . ')');

				$this->_db->setQuery($query);
				$events = $this->_db->loadColumn();
			}
			catch (Exception $e)
			{
				Log::add('Error while getting events by campaign ID(s): ' . $e->getMessage(), Log::ERROR, 'emundus');
			}
		}

		return $events;
	}

	/**
	 * @param $program_codes
	 *
	 * @return array|mixed
	 *
	 * @since version 2.2.0
	 */
	public function getEventsByProgramCodes($program_codes)
	{
		$events = [];

		if (!empty($program_codes))
		{
			try
			{
				if (!is_array($program_codes))
				{
					$program_codes = [$program_codes];
				}

				$query = $this->db->getQuery(true);

				$query->select('eserp.event')
					->from($this->db->quoteName('#__emundus_setup_events_repeat_program', 'eserp'))
					->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.id') . ' = ' . $this->db->quoteName('eserp.programme'))
					->where('esp.code IN (' . implode(',', array_map([$this->db, 'quote'], $program_codes)) . ')');

				$this->_db->setQuery($query);
				$events = $this->_db->loadColumn();
			}
			catch (Exception $e)
			{
				Log::add('Error while getting events by program code(s): ' . $e->getMessage(), Log::ERROR, 'emundus');
			}
		}

		return $events;
	}

	/**
	 * @param $availability_id
	 * @param $user_ids
	 *
	 * @return array
	 *
	 * @since version 2.2.0
	 */
	public function createAvailabilityRegistrant($availability_id, $fnum, $registrants_users_id = [])
	{
		PluginHelper::importPlugin('emundus');
		$dispatcher = Factory::getApplication()->getDispatcher();

		$registrant_id = 0;

		require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
		$m_files   = new EmundusModelFiles();
		$fnumInfos = $m_files->getFnumInfos($fnum);

		if (!empty($availability_id))
		{

			try
			{
				$availability   = $this->getEventsAvailabilities('', '', [], $availability_id, false)[0];
				$availabilities = $this->getEventsAvailabilities($availability->start, $availability->end, [$availability->event_id]);

				$totalCapacity = array_sum(array_map(function ($availability) {
					return $availability->capacity;
				}, $availabilities));

				$registrants = [];
				foreach ($availabilities as $availability)
				{
					$registrants = array_merge($registrants, $this->getAvailabilityRegistrants($availability->id, 0, 0, ['er.ccid', 'er.fnum']));
				}

				// Check if the user is already registered
				$already_registered = false;

				foreach ($registrants as $registrant)
				{
					if ($registrant->ccid === $fnumInfos['ccid'])
					{
						$already_registered = true;
						break;
					}
				}

				if (!$already_registered && $totalCapacity > count($registrants))
				{
					$insert_registrant = [
						'availability' => $availability_id,
						'event'        => $availability->event_id,
						'slot'         => $availability->slot,
						'user'         => $fnumInfos['applicant_id'],
						'fnum'         => $fnum,
						'ccid'         => $fnumInfos['ccid'],
						'link'         => '',
					];

					$insert_registrant = (object) $insert_registrant;

					if ($this->db->insertObject('jos_emundus_registrants', $insert_registrant))
					{
						$registrant_id = $this->db->insertid();

						if (!empty($registrants_users_id))
						{
							foreach ($registrants_users_id as $user)
							{
								$insert_user = [
									'registrant' => $registrant_id,
									'user'       => $user,
								];
								$insert_user = (object) $insert_user;
								$this->db->insertObject('jos_emundus_registrants_users', $insert_user);
							}

							$read_access     = new \stdClass();
							$read_access->id = 1;
							$read_access->c  = 0;
							$read_access->r  = 1;
							$read_access->u  = 0;
							$read_access->d  = 0;
							$actions         = [$read_access];
							$m_files->shareUsers($registrants_users_id, $actions, [$fnum]);
						}

						// Declare the event
						$onAfterBookingRegistrantEventHandler = new GenericEvent(
							'onCallEventHandler',
							['onAfterBookingRegistrant',
								// Datas to pass to the event
								['fnum' => $fnum, 'ccid' => (int) $fnumInfos['ccid'], 'availability' => $availability, 'registrant_id' => $registrant_id]
							]
						);
						$onAfterBookingRegistrant             = new GenericEvent(
							'onAfterBookingRegistrant',
							// Datas to pass to the event
							['fnum' => $fnum, 'ccid' => (int) $fnumInfos['ccid'], 'availability' => $availability, 'registrant_id' => $registrant_id]
						);

						// Dispatch the event
						$dispatcher->dispatch('onCallEventHandler', $onAfterBookingRegistrantEventHandler);
						$dispatcher->dispatch('onAfterBookingRegistrant', $onAfterBookingRegistrant);
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error while creating registrant(s): ' . $e->getMessage(), Log::ERROR, 'emundus');
			}
		}

		return $registrant_id;
	}

	public function getAvailabilityRegistrants($availability_id = 0, $user_id = 0, $event_id = 0, $more_columns = [])
	{
		$registrants = [];

		try
		{
			$query = $this->db->getQuery(true);

			$columns = [
				'er.id',
				'er.availability',
				'er.event',
				'er.slot',
				'er.link',
				'er.fnum',
				'er.user'
			];

			$columns = array_merge($columns, $more_columns);

			$query->select($columns)
				->from($this->db->quoteName('#__emundus_registrants', 'er'));

			if (!empty($availability_id))
			{
				$query->where('er.availability = ' . $availability_id);
			}
			if (!empty($user_id))
			{
				$query->where('er.user = ' . $user_id);
			}
			if (!empty($event_id))
			{
				$query->where('er.event = ' . $event_id);
			}

			$this->_db->setQuery($query);
			$registrants = $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting registrants by availability id: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $registrants;
	}

	public function getMyBookingsInformations($user_id, $events_ids = [], $ccid = 0)
	{
		$my_bookings = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('er.id, er.availability, er.event, er.slot, er.link as link_registrant')
				->select('esa.start_date as start, esa.end_date as end')
				->select('ese.link as link_event, ese.name as event_name, ese.slot_can_book_until_days as can_book_until_days, ese.slot_can_book_until_date as can_book_until_date, ese.slot_can_cancel as can_cancel, ese.slot_can_cancel_until_days as can_cancel_until_days, ese.slot_can_cancel_until_date as can_cancel_until_date')
				->select('del.name as name_location')
				->select('dlr.name as room_name')
				->from($this->db->quoteName('#__emundus_registrants', 'er'))
				->leftJoin($this->db->quoteName('#__emundus_setup_availabilities', 'esa') . ' ON ' . $this->db->quoteName('esa.id') . ' = ' . $this->db->quoteName('er.availability'))
				->leftJoin($this->db->quoteName('#__emundus_setup_events', 'ese') . ' ON ' . $this->db->quoteName('ese.id') . ' = ' . $this->db->quoteName('er.event'))
				->leftJoin($this->db->quoteName('data_events_location', 'del') . ' ON ' . $this->db->quoteName('del.id') . ' = ' . $this->db->quoteName('ese.location'))
				->leftJoin($this->db->quoteName('data_location_rooms', 'dlr') . ' ON ' . $this->db->quoteName('dlr.location') . ' = ' . $this->db->quoteName('del.id'))
				->where('er.user = ' . $user_id);

			if (!empty($events_ids))
			{
				$query->where('er.event IN (' . implode(',', array_map([$this->db, 'quote'], $events_ids)) . ')');
			}

			if (!empty($ccid))
			{
				$query->where('er.ccid = ' . $ccid);
			}

			$query->group('er.id');

			$this->_db->setQuery($query);
			$my_bookings = $this->_db->loadObjectList();

			foreach ($my_bookings as $booking)
			{
				$booking->start = EmundusHelperDate::displayDate($booking->start, 'Y-m-d H:i', 0);
				$booking->end   = EmundusHelperDate::displayDate($booking->end, 'Y-m-d H:i', 0);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while getting my bookings: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $my_bookings;
	}


	public function deleteBooking(int|array $booking_id)
	{
		$deleted = false;

		if (!empty($booking_id))
		{
			if (!is_array($booking_id))
			{
				$booking_id = [$booking_id];
			}

			try
			{
				$query = $this->db->getQuery(true);

				foreach ($booking_id as $id)
				{
					$query->clear()
						->select('er.user, er.ccid, er.fnum, er.event, er.teams_id')
						->from($this->db->quoteName('#__emundus_registrants', 'er'))
						->where('er.id = ' . $id);
					$this->_db->setQuery($query);
					$registrant = $this->_db->loadObject();

					$query->clear()
						->delete($this->db->quoteName('#__emundus_registrants'))
						->where($this->db->quoteName('id') . ' = ' . $id);
					$this->db->setQuery($query);


					if ($this->db->execute())
					{

						PluginHelper::importPlugin('emundus');
						$dispatcher = Factory::getApplication()->getDispatcher();

						$onAfterUnsubscribeRegistrantEventHandler = new GenericEvent(
							'onCallEventHandler',
							['onAfterUnsubscribeRegistrant',
								// Datas to pass to the event
								['fnum' => $registrant->fnum, 'ccid' => $registrant->ccid, 'availability' => $id, 'registrant_id' => $registrant->user, 'event_id' => $registrant->event, 'teams_id' => $registrant->teams_id]
							]
						);
						$onAfterUnsubscribeRegistrant             = new GenericEvent(
							'onAfterUnsubscribeRegistrant',
							// Datas to pass to the event
							['fnum' => $registrant->fnum, 'ccid' => $registrant->ccid, 'availability' => $id, 'registrant_id' => $registrant->user, 'event_id' => $registrant->event, 'teams_id' => $registrant->teams_id]
						);

						// Dispatch the event
						$dispatcher->dispatch('onCallEventHandler', $onAfterUnsubscribeRegistrantEventHandler);
						$dispatcher->dispatch('onAfterUnsubscribeRegistrant', $onAfterUnsubscribeRegistrant);
					}
				}

				$deleted = true;
			}
			catch (Exception $e)
			{
				Log::add('Error while deleting booking: ' . $e->getMessage(), Log::ERROR, 'emundus');
			}
		}

		return $deleted;
	}

	public function updateLink($registrant_id, $link, $teams_id = null)
	{
		$updated = false;

		try
		{
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_registrants'))
				->set($this->db->quoteName('link') . ' = ' . $this->db->quote($link));
			if (!empty($teams_id))
			{
				$query->set($this->db->quoteName('teams_id') . ' = ' . $this->db->quote($teams_id));
			}
			$query->where($this->db->quoteName('id') . ' = ' . $registrant_id);
			$this->db->setQuery($query);
			$updated = $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Error while updating link: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $updated;
	}

	public function getRegistrantCount($event_id)
	{
		$count = 0;

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('count(id)')
				->from($this->db->quoteName('#__emundus_registrants'))
				->where('event = ' . $event_id);
			$this->_db->setQuery($query);
			$count = $this->_db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting registrant count: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $count;
	}

	public function duplicateEvent($event_id, $user_id = null)
	{
		$new_event_id = 0;

		try
		{
			if (empty($user_id))
			{
				$user_id = $this->app->getIdentity()->id;
			}
			$query = $this->db->getQuery(true);

			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_events'))
				->where('id = ' . $event_id);
			$this->db->setQuery($query);
			$event = $this->db->loadObject();

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_events'))
				->where('name = ' . $this->db->quote($event->name . ' (copy)'));
			$this->db->setQuery($query);
			$existing_event = $this->db->loadResult();

			if (empty($existing_event))
			{
				$event->name       = $event->name . ' (copy)';
				$event->date_time  = date('Y-m-d H:i:s');
				$event->created_by = $user_id;
				$event->updated_by = $user_id;
				$event->created    = date('Y-m-d H:i:s');
				$event->updated    = date('Y-m-d H:i:s');
				$event->id         = null;

				if ($this->db->insertObject('#__emundus_setup_events', $event))
				{
					$new_event_id = $this->db->insertid();

					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__emundus_setup_events_notifications'))
						->where('event = ' . $event_id);
					$this->db->setQuery($query);
					$event_notifications = $this->db->loadObject();

					if (!empty($event_notifications))
					{
						$event_notifications->event = $new_event_id;
						$event_notifications->id    = null;
						$this->db->insertObject('#__emundus_setup_events_notifications', $event_notifications);
					}

					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__emundus_setup_event_slots'))
						->where('event = ' . $event_id)
						->andWhere($this->db->quoteName('parent_slot_id') . ' = 0');
					$this->db->setQuery($query);
					$event_slots = $this->db->loadObjectList();

					foreach ($event_slots as $slot)
					{
						$old_parent_id = $slot->id;

						$slot->event = $new_event_id;
						$slot->id    = null;

						if ($this->db->insertObject('#__emundus_setup_event_slots', $slot))
						{
							$slot->id = $this->db->insertid();

							$query->clear()
								->select('*')
								->from($this->db->quoteName('#__emundus_setup_event_slots'))
								->where('parent_slot_id = ' . $old_parent_id);
							$this->db->setQuery($query);
							$sub_slots = $this->db->loadObjectList();

							foreach ($sub_slots as $sub_slot)
							{
								$sub_slot->event          = $new_event_id;
								$sub_slot->parent_slot_id = $slot->id;
								$sub_slot->id             = null;
								$this->db->insertObject('#__emundus_setup_event_slots', $sub_slot);
							}
						}
					}

					$this->setupAvailabilities($new_event_id);
				}
			}
			else
			{
				$new_event_id = $existing_event;
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while duplicating event: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $new_event_id;
	}

	public function getProgramsCampaignsCount($event_id)
	{
		$count = 0;

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('count(eserc.campaign)')
				->from($this->db->quoteName('#__emundus_setup_events_repeat_campaign', 'eserc'))
				->where('eserc.event = ' . $event_id);
			$this->db->setQuery($query);
			$count = $this->db->loadResult();

			$query->clear()
				->select('count(eserp.programme)')
				->from($this->db->quoteName('#__emundus_setup_events_repeat_program', 'eserp'))
				->where('eserp.event = ' . $event_id);
			$this->db->setQuery($query);
			$count += $this->db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting programs and campaigns: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $count;
	}

	public function getEventsNotifications($events_ids = [])
	{
		$events_notifications = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('esen.*')
				->from($this->db->quoteName('#__emundus_setup_events_notifications', 'esen'));

			if (!empty($events_ids))
			{
				$query->where('esen.event IN (' . implode(',', array_map([$this->db, 'quote'], $events_ids)) . ')');
			}

			$this->_db->setQuery($query);
			$events_notifications = $this->_db->loadObjectList();

		}
		catch (Exception $e)
		{
			Log::add('Error while getting events notitications: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $events_notifications;
	}


	public function getRegistrants(
		string     $filter = '',
		string     $sort = 'DESC',
		string     $recherche = '',
		int|string $lim = 25,
		int        $page = 0,
		string     $order_by = '',
		int|string $event = 0,
		int|string $location = 0,
		int        $applicant = 0,
		int        $assoc_user = 0,
		int        $slot = 0,
		array      $ids = [],
		int        $user_id = 0,
		?string    $day = '',
	): array
	{
		$registrants = ['datas' => [], 'count' => 0];

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		try
		{
			$booking_acl = EmundusHelperAccess::getActionIdFromActionName('booking');

			require_once(JPATH_SITE . '/components/com_emundus/models/programme.php');
			$m_programme = new EmundusModelProgramme;
			$programs    = $m_programme->getUserPrograms($user_id);

			if (empty($lim) || $lim == 'all')
			{
				$limit = '';
			}
			else
			{
				$limit = $lim;
			}

			if (empty($page) || empty($limit))
			{
				$offset = 0;
			}
			else
			{
				$offset = ($page - 1) * $limit;
			}

			$query = $this->db->getQuery(true);

			$columns = [
				$this->db->quoteName('er.id'),
				$this->db->quoteName('er.ccid'),
				$this->db->quoteName('er.fnum'),
				$this->db->quoteName('ese.id', 'event_id'),
				$this->db->quoteName('ese.is_conference_link'),
				$this->db->quoteName('ese.name', 'label'),
				$this->db->quoteName('ese.manager'),
				$this->db->quoteName('ecc.applicant_id', 'user'),
				'CONCAT(eu.lastname," ",eu.firstname) as user_fullname',
				$this->db->quoteName('esa.start_date'),
				$this->db->quoteName('esa.end_date'),
				$this->db->quoteName('del.id', 'location_id'),
				$this->db->quoteName('del.name', 'location'),
				$this->db->quoteName('dlr.name', 'room'),
				$this->db->quoteName('er.link', 'conference_link'),
				'GROUP_CONCAT(DISTINCT COALESCE(esru.user, essu.user)) as assoc_user_id',
				$this->db->quoteName('er.availability'),
			];

			$query->select('count(er.id)')
				->from($this->db->quoteName('#__emundus_registrants', 'er'))
				->leftJoin($this->db->quoteName('#__emundus_setup_events', 'ese') . ' ON ' . $this->db->quoteName('ese.id') . ' = ' . $this->db->quoteName('er.event'))
				->leftJoin($this->db->quoteName('data_events_location', 'del') . ' ON ' . $this->db->quoteName('del.id') . ' = ' . $this->db->quoteName('ese.location'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.id') . ' = ' . $this->db->quoteName('er.ccid'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('ecc.applicant_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_availabilities', 'esa') . ' ON ' . $this->db->quoteName('esa.id') . ' = ' . $this->db->quoteName('er.availability'))
				->leftJoin($this->db->quoteName('#__emundus_setup_event_slots', 'eses') . ' ON ' . $this->db->quoteName('eses.id') . ' = ' . $this->db->quoteName('er.slot'))
				->leftJoin($this->db->quoteName('#__emundus_setup_slot_users', 'essu') . ' ON ' . $this->db->quoteName('essu.slot') . ' = ' . $this->db->quoteName('er.slot'))
				->leftJoin($this->db->quoteName('#__emundus_registrants_users', 'esru') . ' ON ' . $this->db->quoteName('esru.registrant') . ' = ' . $this->db->quoteName('er.id'))
				->leftJoin($this->db->quoteName('data_location_rooms', 'dlr') . ' ON ' . $this->db->quoteName('dlr.id') . ' = ' . $this->db->quoteName('eses.room'));

			if (!empty($programs))
			{
				$query->where($this->db->quoteName('esc.training') . ' IN (' . implode(',', $this->_db->quote($programs)) . ')');
			}

			if (!empty($event))
			{
				$query->where('er.event = ' . $event);
			}
			if (!empty($location))
			{
				$query->where('ese.location = ' . $location);
			}
			if (!empty($slot))
			{
				$query->where('er.availability = ' . $slot);
			}
			if (!empty($applicant))
			{
				$query->where('er.user = ' . $applicant);
			}
			if (!empty($assoc_user))
			{
				$query->where('essu.user = ' . $assoc_user);
			}
			if (!empty($ids))
			{
				$query->where('er.id IN (' . implode(',', $ids) . ')');
			}
			if (!empty($day))
			{
				$query->where('DATE(esa.start_date) = ' . $this->db->quote($day));
			}

			$query->group('er.id');

			$this->_db->setQuery($query);
			$registrants['count'] = $this->_db->loadResult();

			$query->clear('select')
				->select($columns);

			if (!empty($order_by))
			{
				$query->order($order_by . ' ' . $sort);
			}
			else
			{
				$query->order('er.id ' . $sort);
			}

			$this->_db->setQuery($query, $offset, $limit);
			$registrants['datas'] = $this->_db->loadObjectList();

			foreach ($registrants['datas'] as $key => $registrant)
			{
				// Check if we have access to fnum
				if (!EmundusHelperAccess::isUserAllowedToAccessFnum($user_id, $registrant->fnum))
				{
					unset($registrants['datas'][$key]);
				}
			}

			$registrants['datas'] = array_values($registrants['datas']);
		}
		catch (Exception $e)
		{
			Log::add('Error while getting registrants: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $registrants;
	}

	public function getFilterEvents(): array
	{
		$events = [];
		$query  = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select([$this->db->quoteName('id', 'value'), $this->db->quoteName('name', 'label')])
				->from($this->db->quoteName('#__emundus_setup_events'));
			$this->db->setQuery($query);
			$events = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting events: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $events;
	}

	public function getFilterApplicants(): array
	{
		$applicants = [];
		$query      = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select([$this->db->quoteName('er.user', 'value'), 'CONCAT(eu.lastname," ",eu.firstname) as label'])
				->from($this->db->quoteName('#__emundus_registrants', 'er'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('er.user'))
				->group('er.user');
			$this->db->setQuery($query);
			$applicants = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error while getting applicants: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $applicants;
	}

	public function getFilterAssocUsers(): array
	{
		$assoc_users = [];
		$query       = $this->db->getQuery(true);

		try
		{

			$query->clear()
				->select([$this->db->quoteName('esru.user', 'value'), 'CONCAT(eu.lastname," ",eu.firstname) as label'])
				->from($this->db->quoteName('#__emundus_registrants_users', 'esru'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('esru.user'));
			$this->db->setQuery($query);
			$assoc_users_registrants = $this->db->loadObjectList();

			$query->clear()
				->select([$this->db->quoteName('essu.user', 'value'), 'CONCAT(eu.lastname," ",eu.firstname) as label'])
				->from($this->db->quoteName('#__emundus_setup_slot_users', 'essu'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('essu.user'))
				->group('essu.user');
			$this->db->setQuery($query);
			$assoc_users_slots = $this->db->loadObjectList();

			foreach (array_merge($assoc_users_registrants, $assoc_users_slots) as $user)
			{
				$assoc_users[$user->value] = [
					'value' => $user->value,
					'label' => $user->label
				];
			}

			$assoc_users = array_values($assoc_users);

		}
		catch (Exception $e)
		{
			Log::add('Error while getting associated users: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return $assoc_users;
	}

	public function exportBookingsExcel($items, $columns = [])
	{
		if (!class_exists(EmundusModelUsers::class))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/users.php';
		}
		$m_users = new EmundusModelUsers();

		$excel_filename = 'export_reservations' . date('Ymd_His') . '.csv';
		$excel_filepath = JPATH_SITE . '/tmp/' . $excel_filename;
		$fp             = fopen($excel_filepath, 'w');

		if (!empty($items) && $fp !== false)
		{

			fputcsv($fp, $columns, ';');
			$app          = Factory::getApplication();
			$language     = $app->getLanguage();
			$current_lang = $language->getTag();

			foreach ($items as $item)
			{
				$username = null;
				try
				{
					$db    = $this->db;
					$query = $db->getQuery(true)
						->select($db->quoteName('name'))
						->from($db->quoteName('#__users'))
						->where($db->quoteName('id') . ' = ' . (int) $item->user);
					$db->setQuery($query);
					$username = $db->loadResult();
				}
				catch (Exception $e)
				{
					Log::add('Error while getting user name in exportBookingsExcel method : ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');

					return false;
				}

				if ($username !== null)
				{
					$assoc_users = [];
					if (!empty($item->assoc_user_id))
					{

						$users = explode(',', $item->assoc_user_id);
						foreach ($users as $user)
						{
							$assoc_user = $m_users->getUserById($user);
							if (!empty($assoc_user) && !empty($assoc_user[0]))
							{
								$assoc_users[] = $assoc_user[0]->lastname . ' ' . $assoc_user[0]->firstname;
							}
						}
					}

					$event       = $item->label;
					$day         = EmundusHelperDate::displayDate($item->start_date, 'COM_EMUNDUS_BIRTHDAY_FORMAT', 0);
					$hour        = EmundusHelperDate::displayDate($item->start_date, 'H:i', 0);
					$location    = $item->location;
					$room        = $item->room;
					$assoc_users = implode(', ', $assoc_users);

					$row = [
						$username,
						$event,
						$day,
						$hour,
						$location,
						$room,
						$assoc_users
					];

					fputcsv($fp, $row, ';');
				}
			}
		}

		fclose($fp);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/models/users.php');
		$m_users = new EmundusModelUsers();

		$nb_cols  = count($columns);
		$nb_rows  = count($items);
		$xls_file = $m_users->convertCsvToXls($excel_filename, $nb_cols, $nb_rows, 'export_reservations' . date('Ymd_His'), ';');

		if (!empty($xls_file))
		{
			$excel_filepath = JPATH_SITE . '/tmp/' . $xls_file;
		}

		return $excel_filepath;
	}

	public function exportBookingsPDF($items, $checkboxesValuesFromView, $checkboxesValuesFromProfile): string|bool
	{
		$pdf_filename = 'export_reservations' . date('Ymd_His') . '.pdf';
		$pdf_filepath = JPATH_SITE . '/tmp/' . $pdf_filename;

		$logo        = EmundusHelperEmails::getLogo(true);
		$type        = pathinfo($logo, PATHINFO_EXTENSION);
		$data        = file_get_contents(JPATH_SITE . '/images/custom/' . $logo);
		$logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

		$html = '<html>
            <head>
              <title>' . Text::_('COM_EMUNDUS_EVENTS_EMARGEMENT') . '</title>
              <meta name="author" content="eMundus">
            </head>
            <body>';

		$html .= '<header>
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <img src="' . $logo_base64 . '" height="35"/>
                    <h2 style="text-align: center; flex-grow: 1;  margin-top: -30px; font-weight: 400;">' . Text::_('COM_EMUNDUS_EVENTS_EMARGEMENT') . '</h2>
                </div>
                <hr/>
              </header>';


		$html .= "
		        <style>
		            @page { margin: 100px 25px; }
		            header { position: fixed; top: -80px; left: 0px; right: 0px; }
		            header hr {
		                border: none;
		                height: 1px;
		                background-color: #A4A4A4;
		            }
		            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed;}
		            th, td { padding: 10px; border: 1px solid black; text-align: left; word-wrap: break-word; white-space: normal; min-height: 40px;  }
		            th { background-color: #f2f2f2; }
		        </style>";

		if (!empty($items) && (!empty($checkboxesValuesFromView) || !empty($checkboxesValuesFromProfile)))
		{
			$events       = [];
			foreach ($items as $item)
			{
				$event_name = $item->label;
				$event_location = $item->location;

				if (!isset($events[$event_name]))
				{
					$events[$event_name] = [
						'location' => $event_location,
						'reservations' => []
					];
				}
				$events[$event_name]['reservations'][] = $item;
			}

			$firstTable = true;
			foreach ($events as $event_name => $eventData)
			{
				$location = $eventData['location'];
				$reservations = $eventData['reservations'];

				if (!$firstTable)
				{
					$html .= '<div style="page-break-before: always;"></div>';
				}
				$firstTable = false;

				$title = $event_name;
				if (!empty($location)) {
					$title .= ' - ' . $location;
				}

				$html .= '<h3 style="text-align:left; margin-top: -6px; margin-bottom: 10px; font-size: 16px">'
					. $title . '</h3>';

				$html .= '<table>
			            <thead>
			                <tr>';
				foreach ($checkboxesValuesFromView as $checkboxesValueFromView) {
						$html .= '<th>' . Text::_($checkboxesValueFromView) . '</th>';
					}

				foreach ($checkboxesValuesFromProfile as $checkboxesValueFromProfile) {
						$html .= '<th>' . Text::_($checkboxesValueFromProfile->label) . '</th>';
					}


			$html .= ' <th>' . Text::_('COM_EMUNDUS_EVENTS_SIGN') . '</th>
						</tr>
			            </thead>
			            <tbody>';

				foreach ($reservations as $reservation)
				{
					try
					{
						$db    = $this->db;
						$query = $db->getQuery(true)
							->select($db->quoteName('name'))
							->from($db->quoteName('#__users'))
							->where($db->quoteName('id') . ' = ' . (int) $reservation->user);
						$db->setQuery($query);
						$username = $db->loadResult();
					}
					catch (Exception $e)
					{
						Log::add('Error while getting user name in exportBookingsPDF method : ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');

						return false;
					}

					$selectColumns = [];
					$user = null;

					if(!empty($checkboxesValuesFromProfile))
					{
						foreach ($checkboxesValuesFromProfile as $checkboxesValueFromProfile) {
							$selectColumns[] = 'eu.' .  $checkboxesValueFromProfile->name;
						}

						try
						{
							$query = $db->getQuery(true)
								->select($selectColumns)
								->from($db->quoteName('#__emundus_users', 'eu'))
								->where($db->quoteName('user_id') . ' = ' . (int) $reservation->user);
							$db->setQuery($query);
							$user = $db->loadObject();
						}
						catch (Exception $e)
						{
							Log::add('Error while getting user in exportBookingsPDF method : ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');

							return false;
						}
					}

					$day      = EmundusHelperDate::displayDate($item->start_date, 'COM_EMUNDUS_BIRTHDAY_FORMAT', 0);
					$hour     = EmundusHelperDate::displayDate($item->start_date, 'H:i', 0);
					$room     = $item->room;
					$event_name = $item->label;
					$location = $item->location;

					$html .= '<tr>';

					if (in_array('COM_EMUNDUS_ONBOARD_LABEL_REGISTRANTS', $checkboxesValuesFromView)) {
						$html .= '<td>' . $event_name . '</td>';
					}
					if (in_array('COM_EMUNDUS_REGISTRANTS_USER', $checkboxesValuesFromView)) {
						$html .= '<td>' . $username . '</td>';
					}
					if (in_array('COM_EMUNDUS_REGISTRANTS_DAY', $checkboxesValuesFromView)) {
						$html .= '<td>' . $day . '</td>';
					}
					if (in_array('COM_EMUNDUS_REGISTRANTS_HOUR', $checkboxesValuesFromView)) {
						$html .= '<td>' . $hour . '</td>';
					}
					if (in_array('COM_EMUNDUS_REGISTRANTS_LOCATION', $checkboxesValuesFromView)) {
						$html .= '<td>' . $location . '</td>';
					}
					if (in_array('COM_EMUNDUS_REGISTRANTS_ROOM', $checkboxesValuesFromView)) {
						$html .= '<td>' . $room . '</td>';
					}

					if(!empty($selectColumns) && $user)
					{
						foreach ($checkboxesValuesFromProfile as $checkboxesValueFromProfile) {
							$html .= '<td>' . EmundusHelperFabrik::formatElementValue($checkboxesValueFromProfile->name, $user->{$checkboxesValueFromProfile->name}, $checkboxesValueFromProfile->group_id, $reservation->user) . '</td>';
						}
					}

					$html .= '<td style="width: 200px;">
			                   <div style="min-height: 60px;"></div>
							</td>
						   </tr>';
				}
				$html .= '</tbody></table>';
			}
		}

		$html .= '</body></html>';

		$options = new Options();
		$options->set('defaultFont', 'helvetica');
		$options->set('isPhpEnabled', true);
		$dompdf = new Dompdf($options);

		$dompdf->loadHtml($html);
		$dompdf->setPaper('A4', 'landscape');
		$dompdf->render();

		$canvas = $dompdf->getCanvas();
		$font   = $dompdf->getFontMetrics()->get_font("helvetica", "bold");
		$canvas->page_text(750, 570, "Page {PAGE_NUM} / {PAGE_COUNT}", $font, 10);

		file_put_contents($pdf_filepath, $dompdf->output());

		return $pdf_filepath;
	}

	public function resendBooking(int|array $ids): bool
	{
		$sends = [];

		if (!class_exists(EmundusHelperDate::class))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/date.php';
		}

		if (!is_array($ids))
		{
			$ids = [$ids];
		}

		$dispatcher = Factory::getApplication()->getDispatcher();
		PluginHelper::importPlugin('emundus');

		foreach ($ids as $id)
		{
			$query = $this->db->getQuery(true);

			$columns = [
				$this->db->quoteName('er.id'),
				$this->db->quoteName('er.fnum'),
				$this->db->quoteName('er.ccid'),
				$this->db->quoteName('esa.id', 'availability'),
				$this->db->quoteName('er.event', 'event_id'),
				$this->db->quoteName('er.slot'),
				$this->db->quoteName('esa.start_date', 'start'),
				$this->db->quoteName('esa.end_date', 'end'),
				$this->db->quoteName('esa.capacity'),
			];

			$query->select($columns)
				->from($this->db->quoteName('#__emundus_registrants', 'er'))
				->leftJoin($this->db->quoteName('#__emundus_setup_availabilities', 'esa') . ' ON ' . $this->db->quoteName('esa.id') . ' = ' . $this->db->quoteName('er.availability'))
				->where('er.id = ' . $id);
			$this->db->setQuery($query);
			$registrantInfos = $this->db->loadAssoc();

			$availability           = new stdClass();
			$availability->id       = $registrantInfos['availability'];
			$availability->event_id = $registrantInfos['event_id'];
			$availability->slot     = $registrantInfos['slot'];
			$availability->start    = EmundusHelperDate::displayDate($registrantInfos['start'], 'Y-m-d H:i', 0);
			$availability->end      = EmundusHelperDate::displayDate($registrantInfos['end'], 'Y-m-d H:i', 0);
			$availability->capacity = $registrantInfos['capacity'];

			$onAfterBookingRegistrant = new GenericEvent(
				'onAfterBookingRegistrant',
				// Datas to pass to the event
				['fnum' => $registrantInfos['fnum'], 'ccid' => (int) $registrantInfos['ccid'], 'availability' => $availability, 'registrant_id' => $id]
			);
			$event_results            = $dispatcher->dispatch('onAfterBookingRegistrant', $onAfterBookingRegistrant);

			$sends[] = $event_results->getArgument('sent');
		}

		return !empty($sends) && !in_array(false, $sends);
	}

	public function getAssocUsers(array $slots, array $users, int $replace = 1): bool
	{
		$associated_slots = [];

		try
		{
			$query = $this->db->getQuery(true);

			$read_access     = new \stdClass();
			$read_access->id = 1;
			$read_access->c  = 0;
			$read_access->r  = 1;
			$read_access->u  = 0;
			$read_access->d  = 0;
			$actions         = [$read_access];

			if(!class_exists('EmundusModelFiles')) {
				require_once JPATH_SITE . '/components/com_emundus/models/files.php';
			}
			$m_files = new EmundusModelFiles();

			foreach ($slots as $slot)
			{
				$users_ids = [];

				$query->clear()
					->select('fnum')
					->from($this->db->quoteName('#__emundus_registrants'))
					->where('id = ' . $slot);
				$this->db->setQuery($query);
				$fnum = $this->db->loadResult();

				if($replace === 1) {
					$query->clear()
						->select('user')
						->from($this->db->quoteName('#__emundus_registrants_users'))
						->where('registrant = ' . $slot);
					$this->db->setQuery($query);
					$old_users = $this->db->loadColumn();

					if(!empty($old_users))
					{
						$query->clear()
							->delete($this->db->quoteName('#__emundus_users_assoc'))
							->where('fnum = ' . $this->db->quote($fnum))
							->where('user_id IN (' . implode(',', $old_users) . ')');
						$this->db->setQuery($query);
						$this->db->execute();
					}

					$query->clear()
						->delete($this->db->quoteName('#__emundus_registrants_users'))
						->where('registrant = ' . $slot);
					$this->db->setQuery($query);
					$this->db->execute();
				}

				foreach ($users as $user)
				{
					$query->clear()
						->select('id')
						->from($this->db->quoteName('#__emundus_registrants_users'))
						->where('registrant = ' . $slot)
						->where('user = ' . $user);
					$this->db->setQuery($query);
					$associated_slot = $this->db->loadResult();

					if (!empty($associated_slot))
					{
						$associated_slots[] = $associated_slot;
					}
					else
					{
						$insert = [
							'registrant' => $slot,
							'user'       => $user
						];
						$insert = (object) $insert;

						if ($this->db->insertObject('#__emundus_registrants_users', $insert))
						{
							$associated_slots[] = $this->db->insertid();
							$users_ids[] 	  = $user;
						}
					}
				}

				if(!empty($users_ids))
				{
					$m_files->shareUsers($users_ids, $actions, [$fnum]);
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while getting associated users: ' . $e->getMessage(), Log::ERROR, 'com_emundus.events');
		}

		return !empty($associated_slots);
	}
}