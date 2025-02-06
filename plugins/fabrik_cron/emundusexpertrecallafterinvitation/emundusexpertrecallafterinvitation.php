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
        //Select les invitations avec l'état envoyé en récupérant la date 
        $params = $this->getParams();
        $reminder_day = $params->get('reminder_day', '');
        $reminder_days = array_map('intval', explode(',', $reminder_day));
        $model_email = $params->get('reminder_mail_id', '');
        $form_id = $params->get('reminder_form_id', '');
        $last_day = $params->get('reminder_last_day', 8);
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->clear() //Je vérifie si le fnum n'a pas déjà 2 request avec l'état envoyé
            ->select(array('efr.email','efr.keyid','efrr.parent_id', 'efrr.time_date', 'efrr.fnum_expertise','efrr.nb_relance','efrr.last_notification'))
            ->from($db->quoteName('#__emundus_files_request', 'efr'))
            ->leftJoin($db->quoteName('#__emundus_files_request_1614_repeat','efrr'). ' ON efr.id = efrr.parent_id')
            ->where($db->quoteName('efrr.etat').' = 2 ');
        $db->setQuery($query);
        $date_first_invitation = $db->loadObjectList();

        $current_date = new DateTime(); // Date actuelle
        $emails_sended = [];
        foreach ($date_first_invitation as $row) {
            $time_date = new DateTime($row->time_date);
            $last_notification = new DateTime($row->last_notification);
            $new_nb_relance = (int)$row->nb_relance + 1;
            foreach ($reminder_days as $day) {
                $reminder_date = clone $time_date;
                $reminder_date->modify("+{$day} days"); // Date de l'invitation + le nombre de jour de relance (si time_date = 26/01 et day = 4 alors $reminder_date = 30/01)
                if ($current_date->format('Y-m-d') === $reminder_date->format('Y-m-d') && (($current_date->format('Y-m-d') !== $last_notification->format('Y-m-d')) || empty($row->last_notification))) { // Si la date du jour et égale à la date de relance et que la date de dernière relance et différente de la date du jour 
                    if($day != $last_day){
                        if(!in_array($row->email, $emails_sended)) // évite d'envoyer un mail à la même adresse
                        {
                            $link = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $form_id . '&keyid=' . $row->keyid;
                            $post = array(
                                'EXPERT_ACCEPT_LINK_RELATIVE' => $link
                            );
            
                            $sent = $m_emails->sendEmailNoFnum($row->email, $model_email, $post, null, null, null, true); // envoi du mail de relance
                            if($sent) {
                                $emails_sended[] = $row->email;
                            }
                            $fields = [
                                $db->quoteName('nb_relance') . ' = ' . $db->quote($new_nb_relance),
                                $db->quoteName('last_notification') . ' = ' . $db->quote($current_date->format('Y-m-d H:i:s'))
                            ];
                        }
                    }
                    else{
                        // fields pour changer l'état à Sans réponse
                        $fields = [
                            $db->quoteName('nb_relance') . ' = ' . $db->quote($new_nb_relance),
                            $db->quoteName('etat') . ' = 5'
                        ];
                        // Passage de de l'état à 1 pour envoyer la prochaine invitation
                        $query->clear()
                        ->update($db->quoteName('#__emundus_files_request_1614_repeat'))
                        ->set($db->quoteName('etat') . ' = 1')
                        ->where($db->quoteName('parent_id') . ' = ' . $db->quote($row->parent_id))
                        ->andWhere($db->quoteName('etat') . ' = 0')
                        ->order($db->quoteName('rang') . ' ASC');
                    try {
                        $db->setQuery($query);
                        $db->execute();
                    } catch (Exception $e) {
                        JLog::add('Failed to update state of repeat request : ' . $row->parent_id . ' ' .  $e->getMessage(), Log::ERROR, 'com_emundus.error');
                    }
                    }
                    
                    // Mise à jour des lignes qui ont l'état à 2 pour soit passer l'état à Sans réponse soit incrémenter le nombre de relance et mettre à jour la date de dernière relance
                    $query->clear()
                        ->update($db->quoteName('#__emundus_files_request_1614_repeat'))
                        ->set($fields)
                        ->where($db->quoteName('parent_id') . ' = ' . $db->quote($row->parent_id))
                        ->andWhere($db->quoteName('fnum_expertise') . ' LIKE ' . $db->quote($row->fnum_expertise));
                    try {
                        $db->setQuery($query);
                        $db->execute();
                    } catch (Exception $e) {
                        JLog::add('Failed to update state and number of relaunch of repeat request : ' . $row->parent_id . ' ' .  $e->getMessage(), Log::ERROR, 'com_emundus.error');
                    }
                }
            }
        }        
    }
}