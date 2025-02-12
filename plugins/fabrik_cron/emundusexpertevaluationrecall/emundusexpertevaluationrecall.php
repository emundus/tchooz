<?php
/**
 * A cron task to email a recall to expert
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @copyright   Copyright (C) 2015 emundus.fr - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

/**
 * A cron task to email records to a give set of expert
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.emundusrecall
 * @since       3.0
 */
class PlgFabrik_Cronemundusexpertevaluationrecall extends PlgFabrik_Cron
{

	/**
	 * Check if the user can use the plugin
	 *
	 * @param   string  $location  To trigger plugin on
	 * @param   string  $event     To trigger plugin on
	 *
	 * @return  bool can use or not
	 */
	public function canUse($location = null, $event = null)
	{
		return true;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array  &$data  data
	 *
	 * @return  int  number of records updated
	 * @throws Exception
	 */
	public function process(&$data, &$listModel)
	{
		require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
		$m_emails = new EmundusModelEmails();

		$params       = $this->getParams();
		$reminder_day = $params->get('reminder_day', '');

		if (!empty($reminder_day))
		{
			$reminder_days = array_map('intval', explode(',', $reminder_day));
			$model_email   = $params->get('reminder_mail_id', '');
			$accepted_state = 3;

			$query = $this->_db->getQuery(true);

			// Check if files does not have 2 files request at sending state
			$query->select(array('efr.email', 'efr.keyid', 'efrr.parent_id', 'efrr.date_acceptation', 'efrr.fnum_expertise', 'efrr.last_acceptation_notification'))
				->from($this->_db->quoteName('#__emundus_files_request', 'efr'))
				->leftJoin($this->_db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
				->where($this->_db->quoteName('efrr.etat') . ' = ' . $accepted_state);
			$this->_db->setQuery($query);
			$date_first_invitation = $this->_db->loadObjectList();

			$current_date  = new DateTime();
			$emails_sended = [];

			foreach ($date_first_invitation as $row)
			{
				$acceptation_date              = new DateTime($row->date_acceptation);
				$last_acceptation_notification = new DateTime($row->last_acceptation_notification);

				foreach ($reminder_days as $day)
				{
					$reminder_date = clone $acceptation_date;

					// Date of invitation + number of days of reminder (if time_date = 26/01 and day = 4 then $reminder_date = 30/01)
					$reminder_date->modify("+{$day} days");

					// If the current date is equal to the dunning date and the last dunning date is different from the current date
					if (
						$current_date->format('Y-m-d') === $reminder_date->format('Y-m-d') &&
						(($current_date->format('Y-m-d') !== $last_acceptation_notification->format('Y-m-d')) || empty($row->last_acceptation_notification))
					)
					{
						if (!in_array($row->email, $emails_sended))
						{
							if ($m_emails->sendEmailNoFnum($row->email, $model_email))
							{
								$emails_sended[] = $row->email;
							}
						}

						$query->clear()
							->update($this->_db->quoteName('#__emundus_files_request_1614_repeat'))
							->set($this->_db->quoteName('last_acceptation_notification') . ' = ' . $this->_db->quote($current_date->format('Y-m-d H:i:s')))
							->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($row->parent_id))
							->andWhere($this->_db->quoteName('fnum_expertise') . ' LIKE ' . $this->_db->quote($row->fnum_expertise));
						try
						{
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
						catch (Exception $e)
						{
							Log::add('Failed to update state and number of relaunch of repeat request : ' . $row->parent_id . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
						}
					}
				}
			}
		}
	}
}