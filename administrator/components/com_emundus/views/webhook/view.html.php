﻿<?php
/**
 * Emundus Webhook Home Page View
 * @package Joomla.Administrator
 * @subpackage eMundus
 * @copyright Copyright (C) 2015-2023 emundus.fr. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView;

jimport('joomla.application.component.view');
jimport( 'joomla.application.component.helper' );

class EmundusAdminViewWebhook extends HtmlView
{
    public $_user = null;

    function __construct($config = array()) {
        require_once (JPATH_SITE.'/components/com_emundus/helpers/access.php');

        $this->_user = Factory::getUser();

        parent::__construct($config);
    }

    function display($tpl = null)
    {
        if (!EmundusHelperAccess::asAdministratorAccessLevel($this->_user->id)) {
            die(JText::_("ACCESS_DENIED"));
        }

        JHTML::stylesheet('administrator/components/com_emundus/assets/css/emundus.css');

        $document = Factory::getApplication()->getDocument();
        $document->setTitle(JText::_('COM_EMUNDUS_TITLE') . ' :: ' .JText::_('COM_EMUNDUS_CONTROL_PANEL'));

        // Set toolbar items for the page
        ToolBarHelper::title(JText::_('COM_EMUNDUS_TITLE') .' :: '. JText::_( 'COM_EMUNDUS_HEADER' ), 'emundus');
        ToolBarHelper::preferences('com_emundus', '580', '750');
        ToolBarHelper::help('screen.cpanel', true);

        parent::display($tpl);
    }
}