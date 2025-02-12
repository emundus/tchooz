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

class PlgFabrik_Cronemundusexpertrecallafterinvitation extends PlgFabrik_Cron {

    /**
     * Check if the user can use the plugin
     *
     * @param   string  $location  To trigger plugin on
     * @param   string  $event     To trigger plugin on
     *
     * @return  bool can use or not
     */
    public function canUse($location = null, $event = null) {
        return true;
    }

    /**
     * Do the plugin action
     *
     * @param array  &$data data
     *
     * @return  int  number of records updated
     * @throws Exception
     */
    public function process(&$data, &$listModel) {
        require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
        $m_emails = new EmundusModelEmails();
		
        $params = $this->getParams();
        $reminder_day = $params->get('reminder_day', '');

	    if(!empty($reminder_day))
	    {
		    $reminder_days = array_map('intval', explode(',', $reminder_day));
		    $model_email   = $params->get('reminder_mail_id', '');
		    $form_id       = $params->get('reminder_form_id', '');
		    $last_day      = $params->get('reminder_last_day', 8);
		    $sending_state = 2;
			$no_reply_state = 5;
			
		    $query         = $this->_db->getQuery(true);

		    // Check if files does not have 2 files request at sending state
		    $query->select(array('efr.email', 'efr.keyid', 'efrr.parent_id', 'efrr.time_date', 'efrr.fnum_expertise', 'efrr.nb_relance', 'efrr.last_notification'))
			    ->from($this->_db->quoteName('#__emundus_files_request', 'efr'))
			    ->leftJoin($this->_db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
			    ->where($this->_db->quoteName('efrr.etat') . ' = ' . $sending_state);
		    $this->_db->setQuery($query);
		    $date_first_invitation = $this->_db->loadObjectList();

		    $current_date  = new DateTime();
		    $emails_sended = [];

		    foreach ($date_first_invitation as $row)
		    {
			    $time_date         = new DateTime($row->time_date);
			    $last_notification = new DateTime($row->last_notification);
			    $new_nb_relance    = (int) $row->nb_relance + 1;

			    foreach ($reminder_days as $day)
			    {
				    $reminder_date = clone $time_date;

				    // Date of invitation + number of days of reminder (if time_date = 26/01 and day = 4 then $reminder_date = 30/01)
				    $reminder_date->modify("+{$day} days");

				    // If the current date is equal to the dunning date and the last dunning date is different from the current date
				    if (
						$current_date->format('Y-m-d') === $reminder_date->format('Y-m-d') &&
						(($current_date->format('Y-m-d') !== $last_notification->format('Y-m-d')) || empty($row->last_notification))
				    )
				    {
					    $fields = [];

					    if ($day != $last_day)
					    {
						    if (!in_array($row->email, $emails_sended)) // évite d'envoyer un mail à la même adresse
						    {
							    $link = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $form_id . '&keyid=' . $row->keyid;
							    $post = array(
								    'EXPERT_ACCEPT_LINK_RELATIVE' => $link
							    );

							    if ($m_emails->sendEmailNoFnum($row->email, $model_email, $post))
							    {
								    $emails_sended[] = $row->email;
							    }

							    $fields = [
								    $this->_db->quoteName('nb_relance') . ' = ' . $this->_db->quote($new_nb_relance),
								    $this->_db->quoteName('last_notification') . ' = ' . $this->_db->quote($current_date->format('Y-m-d H:i:s'))
							    ];
						    }
					    }
					    else
					    {
						    $fields = [
							    $this->_db->quoteName('nb_relance') . ' = ' . $this->_db->quote($new_nb_relance),
							    $this->_db->quoteName('etat') . ' = ' . $no_reply_state
						    ];

						    // Passage de de l'état à 1 pour envoyer la prochaine invitation
						    $query->clear()
							    ->update($this->_db->quoteName('#__emundus_files_request_1614_repeat'))
							    ->set($this->_db->quoteName('etat') . ' = 1')
							    ->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($row->parent_id))
							    ->andWhere($this->_db->quoteName('etat') . ' = 0')
							    ->order($this->_db->quoteName('rang') . ' ASC');
						    try
						    {
							    $this->_db->setQuery($query);
							    $this->_db->execute();
						    }
						    catch (Exception $e)
						    {
							    Log::add('Failed to update state of repeat request : ' . $row->parent_id . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
						    }
					    }

						if(!empty($fields))
						{
							$query->clear()
								->update($this->_db->quoteName('#__emundus_files_request_1614_repeat'))
								->set($fields)
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
}