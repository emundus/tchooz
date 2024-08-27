<?php
/**
 * @version 2: EmundusAssigntogroup 2020-02 Benjamin Rivalland
 * @package Fabrik
 * @copyright Copyright (C) 2020 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Assign application to group
 */


// No direct access
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */

class PlgFabrik_FormEmunduscheckformsfilled extends plgFabrik_Form {
    /**
     * Status field
     *
     * @var  string
     */
    protected $URLfield = '';

    /**
     * Get an element name
     *
     * @param   string  $pname  Params property name to look up
     * @param   bool    $short  Short (true) or full (false) element name, default false/full
     *
     * @return	string	element full name
     */
    public function getFieldName($pname, $short = false) {
        $params = $this->getParams();

        if ($params->get($pname) == '')
            return '';

        $elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pname));

        return $short ? $elementModel->getElement()->name : $elementModel->getFullName();
    }

    /**
     * Get the fields value regardless of whether its in joined data or no
     *
     * @param   string  $pname    Params property name to get the value for
     * @param   array   $data     Posted form data
     * @param   mixed   $default  Default value
     *
     * @return  mixed  value
     */
    public function getParam($pname, $default = '') {
        $params = $this->getParams();

        if ($params->get($pname) == '') {
            return $default;
        }

        return $params->get($pname);
    }

	/**
	 * Main script.
	 *
	 */
    public function onLoad()
    {
        $form_to_check = $this->getParam('form_to_check', 0);

        if (!empty($form_to_check)) {
            $formModel = $this->getModel();
            $current_table_name = $formModel->getForm()->db_table_name;

            $fnum_tag = '{'.$current_table_name.'___fnum}';
            // get fnum using multiple options otherwise it could be empty
            if (empty($fnum_tag) || strpos($fnum_tag, '{') === 0) {
                $fnum = $this->app->input->get('rowid');
            } else {
                $fnum = $fnum_tag;
            }
            if (empty($fnum)) {
                $fnum = $this->app->input->get($current_table_name.'___fnum');
            }
            if (empty($fnum)) {
                $fnum = $this->app->getSession()->get('emundusUser')->fnum;
            }

            if (!empty($fnum)) {
                $query = $this->_db->getQuery(true);

                $query->select('jfl.db_table_name')
                    ->from($this->_db->quoteName('#__fabrik_lists', 'jfl'))
                    ->leftJoin($this->_db->quoteName('#__fabrik_forms', 'jff') . ' ON jff.id = jfl.form_id')
                    ->where($this->_db->quoteName('jff.id') . ' = ' . $this->_db->quote($form_to_check));

                try {
                    $this->_db->setQuery($query);
                    $table_name = $this->_db->loadResult();

                    if (!empty($table_name)) {
                        // Do not redirect if the user is not an applicant
                        $session = $this->app->getSession();
                        $current_user = $session->get('emundusUser');

                        if ($current_user->applicant == 1) {
                            $query->clear()
                                ->select('id')
                                ->from($table_name)
                                ->where('fnum LIKE ' . $this->_db->quote($fnum));
                            $this->_db->setQuery($query);
                            $id = $this->_db->loadResult();

                            if (empty($id)) {
                                $menu = $this->app->getMenu();
                                $current_menu = $menu->getActive();
                                if ($this->app->getIdentity()->id == 95) {
                                    $menutype = 'menu-profile1015';
                                } else {
                                    $menutype = $current_menu->menutype;
                                }

                                $query->clear()
                                    ->select('jm.id as `itemid`, jfl.db_table_name')
                                    ->from($this->_db->quoteName('#__menu','jm'))
                                    ->leftJoin($this->_db->quoteName('#__fabrik_forms','jff').' ON '.$this->_db->quoteName('jff.id').' = SUBSTRING_INDEX(SUBSTRING('.$this->_db->quoteName('jm.link').', LOCATE("formid=", '.$this->_db->quoteName('jm.link').') + 7, 4), "&", 1)')
                                    ->leftJoin($this->_db->quoteName('#__fabrik_lists','jfl').' ON '.$this->_db->quoteName('jfl.form_id').' = '.$this->_db->quoteName('jff.id'))
                                    ->where($this->_db->quoteName('jm.menutype').' = '.$this->_db->quote($menutype))
                                    ->andWhere($this->_db->quoteName('jm.link').' LIKE '.$this->_db->quote('%formid%'));
                                $this->_db->setQuery($query);
                                $menuforms = $this->_db->loadObjectList();

                                $form_to_redirect = '';
                                foreach($menuforms as $form) {
                                    if ($form->db_table_name == $table_name) {
                                        $form_to_redirect = $form->itemid;
                                    }
                                }

                                $query->clear()
                                    ->select('path')
                                    ->from('#__menu')
                                    ->where('id = '. $this->_db->quote($form_to_redirect));
                                $this->_db->setQuery($query);
                                $path = $this->_db->loadResult();

                                if (!empty($path)) {
                                    $this->app->enqueueMessage(Text::_('PLG_FABRIK_FORM_EMUNDUS_CHECKFORMSFILLED_REDIRECT_MESSAGE'));
                                    $this->app->redirect($path);
                                } else {
                                    $this->app->enqueueMessage(Text::_('PLG_FABRIK_FORM_EMUNDUS_CHECKFORMSFILLED_ERROR_COULD_NOT_REDIRECT'), 'error');
                                    $this->app->redirect('/');
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::add('Error occured ' .  $e->getMessage(), Log::ERROR, 'plugin_emunduscheckformsfilled.error');
                }
            }
        }
    }
}
