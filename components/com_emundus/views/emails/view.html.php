<?php
/**
 * @package     Joomla
 * @subpackage  com_emunudus_onboard
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * eMundus Onboard Email View
 *
 * @since  0.0.1
 */
class EmundusViewEmails extends JViewLegacy
{
	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$jinput = $app->input;

		// Display the template
		$layout = $jinput->getString('layout', null);
		if ($layout == 'add') {
			$this->id = $jinput->get->get('eid', null);
		}

		if ($layout === 'triggeredit') {
			$this->id = $jinput->getInt('id', 0);
			// check if sms is activated
			require_once(JPATH_ROOT . '/components/com_emundus/models/sms.php');
			$m_sms = new EmundusModelSMS();
			$this->smsActivated = $m_sms->activated;

			if ($this->id == 0) {
				$program_id = $jinput->getInt('program_id', 0);
				$campaign_id = $jinput->getInt('campaign_id', 0);

				if (!empty($program_id)) {
					$this->defaultProgramId = $program_id;
				} else if (!empty($campaign_id)) {
					$db = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->createQuery();

					$query->select('esp.id')
						->from($db->quoteName('#__emundus_setup_programmes', 'esp'))
						->leftJoin($db->quoteName('#__emundus_setup_campaigns','esc') . ' ON esc.training = esp.code')
						->where('esc.id = ' . $campaign_id);

					$db->setQuery($query);
					$program_id = $db->loadResult();

					if (!empty($program_id)) {
						$this->defaultProgramId =  $program_id;
					}
				}
			}
		}

		// Display the template
		parent::display($tpl);
	}
}
