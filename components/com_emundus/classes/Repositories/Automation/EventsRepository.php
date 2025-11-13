<?php

namespace Tchooz\Repositories\Automation;

use EmundusHelperCache;
use Joomla\CMS\Factory;
use Tchooz\Entities\Automation\EventEntity;
use Joomla\Database\DatabaseDriver;
use Tchooz\Factories\Automation\EventFactory;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');

class EventsRepository
{
	private DatabaseDriver $db;

	private \EmundusHelperCache $cache;

	public function __construct(?DatabaseDriver $db = null, ?EmundusHelperCache $cache = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
		$this->cache = $cache ?? new EmundusHelperCache();
	}

	public function getEventById(int $id): ?EventEntity
	{
		$event = null;

		if (!empty($id))
		{
			$events = $this->cache->get('events_list');

			if (!empty($events)) {
				foreach ($events as $cachedEvent) {
					if ($cachedEvent->getId() === $id) {
						$event = $cachedEvent;
					}
				}
			}

			if (empty($event)) {
				$query = $this->db->getQuery(true);
				$query->select('*')
					->from($this->db->quoteName('#__emundus_plugin_events'))
					->where($this->db->quoteName('id') . ' = ' . $id);

				$this->db->setQuery($query);
				$result = $this->db->loadObject();

				if ($result)
				{
					$events = EventFactory::fromDbObjects([$result]);
					$event = $events[0];
				}
			}
		}

		return $event;
	}

	public function getEventByName(string $name): ?EventEntity
	{
		$event = null;

		if (!empty($name))
		{
			$events = $this->cache->get('events_list');

			if (!empty($events)) {
				foreach ($events as $cachedEvent) {
					if ($cachedEvent->getLabel() === $name) {
						$event = $cachedEvent;
					}
				}
			}

			if (empty($event)) {
				$query = $this->db->getQuery(true);
				$query->select('*')
					->from($this->db->quoteName('#__emundus_plugin_events'))
					->where($this->db->quoteName('label') . ' = ' . $this->db->quote($name));

				$this->db->setQuery($query);
				$result = $this->db->loadObject();

				if ($result)
				{
					$events = EventFactory::fromDbObjects([$result]);
					$event = $events[0];
				}
			}
		}

		return $event;
	}

	public function getEventsList(): array
	{
		$events = $this->cache->get('events_list');

		if (empty($events)) {
			$events = [];

			try {
				$query = $this->db->getQuery(true);
				$query->select('*')
					->from($this->db->quoteName('#__emundus_plugin_events'))
					->where('published = 1')
					->andWhere('available = 1');

				$this->db->setQuery($query);
				$results = $this->db->loadObjectList();

				if ($results)
				{
					$events = EventFactory::fromDbObjects($results);
				}

				$this->cache->set('events_list', $events); // Cache for 1 hour
			}
			catch (\RuntimeException $e)
			{
				// Log the error or handle it as needed
			}
		}

		return $events;
	}
}