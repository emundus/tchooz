<?php

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Access Deny');


class modEmundusTimeslotsHelper
{

	public function getCalendars()
	{

		$db = Factory::getContainer()->get('DatabaseDriver');

		// Get the parent cal ID, this is the one we omit from the list
		$eMConfig  = ComponentHelper::getParams('com_emundus');
		$parent_id = $eMConfig->get('parentCalId');

		$query = 'SELECT id, title FROM #__categories
                        WHERE extension LIKE "com_dpcalendar"
                        AND id != ' . $parent_id;

		try {
			$db->setQuery($query);
			return $db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error getting calendars for module/calendar_create_timeslots at query: ' . $query, Log::ERROR, 'com_emundus');

			return false;
		}
	}
}

?>