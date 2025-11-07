<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');
JLoader::import( 'views.default.view',FALANG_ADMINPATH);

class ExportViewExport extends FalangViewDefault
{
    protected $form;


    function display($tpl = null)
    {
        $document = Factory::getApplication()->getDocument();
        $document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' . Text::_('COM_FALANG_TITLE_EXPORT'));

        $this->addToolbar();

        $this->form = $this->get('Form');
        $this->sourceLanguages = $this->get('sourceLanguages');

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        Factory::getApplication()->input->set('hidemainmenu', true);

        // set page title
        ToolBarHelper::title( Text::_( 'COM_FALANG_TITLE_EXPORT' ), 'falang-export');


        //add toolbar actions
        ToolBarHelper::cancel('export.cancel',Text::_('JCANCEL'));

    }
}