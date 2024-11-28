<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Access Deny');

class modEmundusEventsHelper {

	public static function getEvents($table, $period)
	{
		$events = [];

		try
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('id, title, description, start_date, end_date, link')
				->from($table)
				->where('published = 1')
				->andWhere('end_date >= CURDATE() - INTERVAL 1 DAY');

			if($period == 2 || $period == 3)
			{
				$where = 'YEAR(start_date) <= YEAR(NOW()) AND YEAR(end_date) >= YEAR(NOW())';
				if($period == 3) {
					$where .= ' AND MONTH(start_date) <= MONTH(NOW()) AND MONTH(end_date) >= MONTH(NOW())';
				}
				$query->andWhere($where);
			}

			$query->order('start_date ASC');

			$db->setQuery($query);
			$events = $db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error: ' . $e->getMessage(), Log::ERROR, 'mod_emundus_events');
		}

		return $events;
	}
}
