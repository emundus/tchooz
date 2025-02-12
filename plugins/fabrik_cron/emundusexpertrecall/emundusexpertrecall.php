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
use Joomla\CMS\Factory;
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
class PlgFabrik_Cronemundusexpertrecall extends PlgFabrik_Cron
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
        $m_emails    = new EmundusModelEmails();
		
        $params      = $this->getParams();
        $model_email = $params->get('reminder_mail_id', '');
        $form_id     = $params->get('reminder_form_id', '');
        $invitationByFnum = $params->get('invitation_by_fnum', '2');
        $now         = date('Y-m-d H:i:s');

	    $sending_state = 2;
		$accepted_state = 3;
		$evaluated_state = 6;

        $query = $this->_db->getQuery(true);

        $columns = [
            'efrr.fnum_expertise',
            'group_concat(' . $this->_db->quoteName('efr.email') . ') AS email',
            'group_concat(' . $this->_db->quoteName('efrr.parent_id') . ') AS parent_id',
            'group_concat(' . $this->_db->quoteName('efr.keyid') . ') AS keyid',
            'group_concat(' . $this->_db->quoteName('efr.student_id') . ') AS student_id',
            'efr.attachment_id',
            'group_concat(' . $this->_db->quoteName('efrr.rang') . ') AS rang'
        ];

        $query->clear()
            ->select($columns)
            ->from($this->_db->quoteName('#__emundus_files_request', 'efr'))
            ->leftJoin($this->_db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
            ->where($this->_db->quoteName('efrr.etat') . ' = 1')
            ->andWhere($this->_db->quoteName('efr.uploaded') . ' = 0 ')
            ->andWhere($this->_db->quoteName('efrr.nb_relance') . ' = 0 OR ' . $this->_db->quoteName('efrr.nb_relance') . ' IS NULL')
            ->group('efrr.fnum_expertise')
            ->order('efrr.rang ASC');
        $fnums = $this->_db->setQuery($query)->loadAssocList('fnum_expertise');

        foreach ($fnums as $fnum => $value)
        {
            // I'm checking that the fnum doesn't already have 2 requests with the status sent
            $query->clear()
                ->select('count(efrr.id)')
                ->from($this->_db->quoteName('#__emundus_files_request', 'efr'))
                ->leftJoin($this->_db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
                ->where($this->_db->quoteName('efrr.etat') . ' = ' . $sending_state)
                ->andWhere($this->_db->quoteName('efrr.fnum_expertise') . ' = ' . $this->_db->quote($fnum));
            $this->_db->setQuery($query);
            $already_sent = $this->_db->loadResult();

            // I check that the fnum does not already have 2 requests with the status accepted or evaluated
            $query->clear()
                ->select('count(efrr.id)')
                ->from($this->_db->quoteName('#__emundus_files_request', 'efr'))
                ->leftJoin($this->_db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
                ->where($this->_db->quoteName('efrr.etat') . ' = ' . $accepted_state)
                ->orWhere($this->_db->quoteName('efrr.etat') . ' = ' . $evaluated_state)
                ->andWhere($this->_db->quoteName('efrr.fnum_expertise') . ' = ' . $this->_db->quote($fnum));
            $this->_db->setQuery($query);
            $already_accepted = $this->_db->loadResult();

            if ($already_sent == $invitationByFnum || $already_accepted == $invitationByFnum)
            {
                unset($fnums[$fnum]);
            }

            $fnums[$fnum]['sent'] = $already_sent + $already_accepted;
        }

        $emails_sended = [];
        foreach ($fnums as $fnum => $value)
        {
            $experts     = explode(',', $value['email']);
            $rangs       = explode(',', $value['rang']);
            $keys        = explode(',', $value['keyid']);
            $parents_ids = explode(',', $value['parent_id']);

            $expertsArray = [];
            foreach ($experts as $key => $expert)
            {
                $expertsArray[$expert] = [
                    'rang'      => $rangs[$key],
                    'key'       => $keys[$key],
                    'parent_id' => $parents_ids[$key]
                ];
            }

	        uasort($expertsArray, function ($a, $b) {
				return $a['rang'] <=> $b['rang'];
			});

            $expertsToSend = array_slice($expertsArray, 0, ($invitationByFnum - $value['sent']), true);

            foreach ($expertsToSend as $email => $expert)
            {
                if (!in_array($email, $emails_sended))
                {
                    $link = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $form_id . '&keyid=' . $expert['key'];
                    $post = array(
                        'EXPERT_ACCEPT_LINK_RELATIVE' => $link
                    );

                    if ($m_emails->sendEmailNoFnum($email, $model_email, $post))
                    {
                        $emails_sended[] = $email;
                    }
                }

                $fields = [
                    $this->_db->quoteName('etat') . ' = ' . $this->_db->quote($sending_state),
                    $this->_db->quoteName('time_date') . ' = ' . $this->_db->quote($now),
                ];
                $query->clear()
                    ->update($this->_db->quoteName('#__emundus_files_request_1614_repeat'))
                    ->set($fields)
                    ->where($this->_db->quoteName('rang') . ' = ' . $this->_db->quote($expert['rang']))
                    ->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($expert['parent_id']))
                    ->andWhere($this->_db->quoteName('fnum_expertise') . ' LIKE ' . $this->_db->quote($fnum));
                try
                {
                    $this->_db->setQuery($query);
                    $this->_db->execute();
                }
                catch (Exception $e)
                {
                    Log::add('Failed to update state and time date of repeat request : ' . $value['parent_id'] . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
                }
            }
        }
    }
}