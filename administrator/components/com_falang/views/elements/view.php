<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die;

JLoader::import( 'views.default.view',FALANG_ADMINPATH);

/**
 * HTML View class for the WebLinks component
 *
 * @static
 * @package		Joomla
 * @subpackage	Falang
 * @since 1.0
 */
class ElementsViewElements extends FalangViewDefault
{
	function display($tpl = null)
	{
		$document = Factory::getApplication()->getDocument();
		// browser title
		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_TITLE_CONTENT_ELEMENTS'));
		// set page title
		ToolBarHelper::title( Text::_( 'COM_FALANG_TITLE_CONTENT_ELEMENTS' ), 'extension' );
		
		$layout = $this->getLayout();
		if (method_exists($this,$layout)){
			$this->$layout($tpl);
		} else {
			$this->addToolbar();
		}
		parent::display($tpl);
	}

	protected function addToolbar() {
		// Set toolbar items for the page
		ToolBarHelper::custom("elements.installer","archive","archive", Text::_( 'COM_FALANG_INSTALL' ),false);
        ToolBarHelper::custom("elements.detail","eye","eye", Text::_( 'COM_FALANG_DETAIL' ),true);

		ToolBarHelper::deleteList(Text::_("COM_FALANG_TRANSLATION_DELETE_MSG"), "elements.remove");
		ToolBarHelper::help( 'screen.elements', true);

        Sidebar::setAction('index.php?option=com_falang&view=element');
	}
	
	function edit($tpl = null)
	{
		// Set toolbar items for the page
        ToolBarHelper::cancel("elements.cancel");
		ToolBarHelper::help( 'screen.elements', true);
		// hide the sub menu
		$this->_hideSubmenu();		
	}	

	function installer($tpl = null)
	{
		// browser title
		$document = Factory::getApplication()->getDocument();
		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_CONTENT_ELEMENT_INSTALLER'));
		
		// set page title
		ToolBarHelper::title( Text::_('COM_FALANG_TITLE') .' :: '. Text::_( 'COM_FALANG_CONTENT_ELEMENT_INSTALLER' ), 'falang' );

		// Set toolbar items for the page
        ToolBarHelper::cancel("elements.cancel");
		ToolBarHelper::help( 'screen.elements', true);

		// hide the sub menu
		$this->_hideSubmenu();
	}	
}
