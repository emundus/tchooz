<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Access Denied');

class modEmundusChecklistHelper
{
	static function getApplication($fnum)
	{
		$application = null;

		if (!empty($fnum)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select(['ecc.*', 'esc.*', 'ess.step', 'ess.value', 'ess.class'])
				->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $db->quoteName('esc.id') . ' = ' . $db->quoteName('ecc.campaign_id'))
				->leftJoin($db->quoteName('#__emundus_setup_status', 'ess') . ' ON ' . $db->quoteName('ess.step') . ' = ' . $db->quoteName('ecc.status'))
				->where($db->quoteName('ecc.fnum') . ' LIKE ' . $db->quote($fnum))
				->order($db->quoteName('esc.end_date') . ' DESC');

			try {
				$db->setQuery($query);
				$application = $db->loadObject();
			} catch (Exception $e) {
				Log::addLogger(['text_file' => 'mod_emundus_checklist.php'], Log::ERROR, 'mod_emundus_checklist');
				Log::add('Failed to get application with fnum: ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'mod_emundus_checklist');
			}
		}

		return $application;
	}
}
