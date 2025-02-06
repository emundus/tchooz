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

class PlgFabrik_Cronemundusexpertrecall extends PlgFabrik_Cron {

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
        

        // J'envoi un mail aux référents et j'update la request repeat pour changer l'etat et la date d'invitation
        require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
        $m_emails = new EmundusModelEmails();
        $params = $this->getParams();
        $model_email = $params->get('reminder_mail_id', '');
        $form_id = $params->get('reminder_form_id', '');
        $year = date('Y');
        $now = date('Y-m-d H:i:s');
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        
        $query->clear() //select des files request dont l'état est non envoyé et uplaoded à 0 et nb_relance = 0 ordonner par rang croissant
            ->select(array('email', 'parent_id', 'keyid', 'fnum_expertise'))
            ->from('(SELECT efr.email, 
                        efrr.parent_id, 
                        efr.keyid, 
                        efrr.fnum_expertise,
                        ROW_NUMBER() OVER (PARTITION BY efrr.parent_id ORDER BY efrr.rang ASC) AS row_num
                    FROM ' . $db->quoteName('#__emundus_files_request') . ' AS efr
                    LEFT JOIN ' . $db->quoteName('#__emundus_files_request_1614_repeat') . ' AS efrr 
                    ON efr.id = efrr.parent_id
                    WHERE efrr.etat = 1 
                    AND efr.uploaded = 0 
                    AND (efrr.nb_relance = 0 OR efrr.nb_relance IS NULL)
                ) AS subquery')
            ->where('row_num <= 2'); // Filtrer pour ne garder que 2 résultats max par parent_id

        $db->setQuery($query);
        $fnums = $db->loadAssocList('fnum_expertise');

        
        foreach($fnums as $fnum => $value){
            $query->clear() //Je vérifie si le fnum n'a pas déjà 2 request avec l'état envoyé
                ->select('Count(efrr.id)')
                ->from($db->quoteName('#__emundus_files_request', 'efr'))
                ->leftJoin($db->quoteName('#__emundus_files_request_1614_repeat','efrr'). ' ON efr.id = efrr.parent_id')
                ->where($db->quoteName('efrr.etat').' = 2 ')
                ->andWhere($db->quoteName('efrr.parent_id').' = '.$db->quote($value['parent_id']));
            $db->setQuery($query);
            $already_sent = $db->loadResult();
            
            if($already_sent == 2){
                unset($fnums[$fnum]);
            }
        }
        $emails_sended = [];
        foreach($fnums as $fnum => $value){ // J'envoi un mail aux référents et j'update la request repeat pour changer l'etat et la date d'invitation
            
            if(!in_array($value['email'], $emails_sended))
            {
                $link = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $form_id . '&keyid=' . $value['keyid'];
                $post = array(
                    'EXPERT_ACCEPT_LINK_RELATIVE' => $link
                );

                $sent = $m_emails->sendEmailNoFnum($value['email'], $model_email, $post, null, null, null, true);
                if($sent) {
                    $emails_sended[] = $value['email'];
                }
            }

            $fields = [
                $db->quoteName('etat') . ' = ' . $db->quote(2),
                $db->quoteName('time_date') . ' = ' . $db->quote($now)
            ];
            $query->clear()
                ->update($db->quoteName('#__emundus_files_request_1614_repeat'))
                ->set($fields)
                ->where($db->quoteName('parent_id') . ' = ' . $db->quote($value['parent_id']))
                ->andWhere($db->quoteName('fnum_expertise') . ' LIKE ' . $db->quote($value['fnum_expertise']));
            try {
                $db->setQuery($query);
                $db->execute();
            } catch (Exception $e) {
                JLog::add('Failed to update state and time date of repeat request : ' . $value['parent_id'] . ' ' .  $e->getMessage(), Log::ERROR, 'com_emundus.error');
            }
        }
    }
}