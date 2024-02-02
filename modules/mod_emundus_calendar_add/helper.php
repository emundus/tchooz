<?php

use Joomla\CMS\Factory;

defined('_JEXEC') or die('Access Deny');

class modEmundusCalendarAddHelper
{

	public function getPrograms()
	{
		try {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$db->setQuery('SELECT code, label FROM #__emundus_setup_programmes');

			return $db->loadObjectList();
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
	}
}

?>