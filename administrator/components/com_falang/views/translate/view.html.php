<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

JLoader::import( 'views.default.view',FALANG_ADMINPATH);

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Joomla\CMS\Toolbar\ToolbarHelper;



/**
 * View class for translation overview
 *
 * @static
 * @since 2.0
 */
class FalangViewTranslate extends FalangViewDefault
{


	/**
	 * Form object for search filters
	 *
	 * @var  Form
	 */
	public $filterForm;
	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;


	/**
	 * Setting up special general attributes within this view
	 * These attributes are independed of the specifc view
	 */
	function _initialize($layout="overview") {
		// get list of active languages
		$langOptions[] = HTMLHelper::_('select.option',  '-1', Text::_('COM_FALANG_SELECT_LANGUAGE') );
		// Get data from the model
		$langActive = $this->get('Languages');		// all languages even non active once
		$defaultLang = $this->get('DefaultLanguage');
		$params = ComponentHelper::getParams('com_falang');
		$showDefaultLanguageAdmin = $params->get("showDefaultLanguageAdmin", false);
		if ( count($langActive)>0 ) {
			foreach( $langActive as $language )
			{
				if($language->lang_code != $defaultLang || $showDefaultLanguageAdmin) {
					$langOptions[] = HTMLHelper::_('select.option',  $language->lang_id, $language->title );
				}
			}
		}
		if ($layout == "overview" || $layout == "default" || $layout == "orphans"){
			$langlist = HTMLHelper::_('select.genericlist', $langOptions, 'select_language_id', 'class="form-select" onchange="if(document.getElementById(\'catid\').value.length>0) document.adminForm.submit();"', 'value', 'text', $this->select_language_id );
		}
		else {
			$confirm="";
			$langlist = HTMLHelper::_('select.genericlist', $langOptions, 'language_id', 'class="form-select" '.$confirm, 'value', 'text', $this->select_language_id );
		}
		$this->langlist = $langlist;
	}
	/**
	 * Control Panel display function
	 *
	 * @param template $tpl
	 */
	function display($tpl = null)
	{

		// Get data from the model
        //$this->items = $this->get('Items');
		$this->state		= $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $document = Factory::getApplication()->getDocument();
		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_TITLE_TRANSLATION'));

		// Set  page title
		ToolBarHelper::title( Text::_( 'COM_FALANG_TITLE_TRANSLATION' ), 'jftranslations' );

		$layout = $this->getLayout();

		$this->_initialize($layout);
		if (method_exists($this,$layout)){
			$this->$layout($tpl);
		} else {
			$this->addToolbar();
		}

        //use for popup
        $input = Factory::getApplication()->input;
        $layout = $input->get('layout', 'default', 'string');
        if ($layout == "popup") {
            // hide version on popup
            $this->showVersion = false;

            Factory::getApplication()->input->set('hidemainmenu', true);
            $style = 'header.header {'
                     . 'display:none;'
                     . '}'
                     .'nav.navbar {'
                     . 'display:none;'
                     . '}'
                     .'body.com_falang {'
                     . 'padding-top:0;'
                     . '}'
                     . '.subhead-fixed {'
                     . 'top:0;'
                     . '}';

            $document->addStyleDeclaration($style);
            //remove save button keep only save&close and cancel
        }

		parent::display($tpl);
	}


    protected function addToolbar()
	{
		// browser title
		$document = Factory::getApplication()->getDocument();
		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_TITLE_TRANSLATE'));

		// set page title
		ToolBarHelper::title( Text::_( 'COM_FALANG_TITLE_TRANSLATE' ), 'translation' );

		// Set toolbar items for the page
		ToolBarHelper::publish("translate.publish");
		ToolBarHelper::unpublish("translate.unpublish");
		ToolBarHelper::editList("translate.edit");
		ToolBarHelper::deleteList(Text::_( 'COM_FALANG_TRANSLATION_DELETE_MSG' ), "translate.remove");
		ToolBarHelper::help( 'screen.translate.overview', true);

        //set filter for the page
        if (isset($this->filterlist) && count($this->filterlist)>0){
            foreach ($this->filterlist as $fl){
                if (is_array($fl) && !empty($fl['position']) && $fl['position'] == 'sidebar')
                Sidebar::addFilter(
                    $fl["title"],
                    $fl["type"].'_filter_value',
	                HTMLHelper::_('select.options', $fl["options"], 'value', 'text', isset($fl['value'])?$fl['value']:null, true)
                );
            }
        }

        $this->sidebar = Sidebar::render();


    }

	function edit($tpl = null)
	{
		// browser title
		$document = Factory::getApplication()->getDocument();
		$jinput = Factory::getApplication()->input;

		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_TITLE_TRANSLATE'));

		// set page title
		ToolBarHelper::title( Text::_( 'COM_FALANG_TITLE_TRANSLATE' ), 'translation' );

		//TODO put in falng css
            $css = '
            table.adminform  tr th.falang  {
                border-bottom: 1px solid #DDDDDD;
                background-color: #f9f9f9;
            }

            table.adminform tr.row0 td{background-color: #ffffff;border:none;}
            table.adminform tr.row1 td{background-color: #ffffff;border:none;}

            input, textarea, .uneditable-input {width:auto;}

            ';

            $document->addStyleDeclaration($css);


		// Set toolbar items for the page
		if ($jinput->get("catid","")=="content"){

            $toolbar =  Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar('toolbar');
			// Add a special preview button by hand
            $preview_url = 'index.php?option=com_falang&task=translate.preview&tmpl=component';
            $toolbar->preview($preview_url, 'JGLOBAL_PREVIEW')
                ->bodyHeight(80)
                ->modalWidth(90);
		}
		ToolBarHelper::save("translate.save");

        $layout = $jinput->get('layout', 'default', 'string');
        if ($layout != "popup") {
            ToolBarHelper::apply("translate.apply");
        }
		ToolBarHelper::cancel("translate.cancel");
		ToolBarHelper::help( 'screen.translate.edit', true);

		$jinput->set('hidemainmenu',1);
	}

	function orphans($tpl = null)
	{
		// browser title
		$document = Factory::getApplication()->getDocument();
		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_TITLE_CLEANUP_ORPHANS'));

		// set page title
		ToolBarHelper::title( Text::_( 'COM_FALANG_TITLE_CLEANUP_ORPHANS' ), 'orphan' );

		// Set toolbar items for the page
		ToolBarHelper::deleteList(Text::_('COM_FALANG_TRANSLATION_DELETE_MSG'), "translate.removeorphan");
		ToolBarHelper::help( 'screen.translate.orphans', true);

        Sidebar::setAction('index.php?option=com_falang&view=translate');

        //set filter for the page
        if (isset($this->filterlist) && count($this->filterlist)>0){
            foreach ($this->filterlist as $fl){
                if (is_array($fl) && $fl['position'] == 'sidebar')
                    Sidebar::addFilter(
                        $fl["title"],
                        $fl["type"].'_filter_value',
	                    HTMLHelper::_('select.options', $fl["options"], 'value', 'text', $this->state->get('filter.'.$fl["type"]), true)
                    );
            }
        }

        $this->sidebar = Sidebar::render();

	}

	function orphandetail($tpl = null)
	{
		// browser title
		$document = Factory::getApplication()->getDocument();
		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_TITLE_CLEANUP_ORPHANS'));

		// set page title
		ToolBarHelper::title( Text::_( 'COM_FALANG_TITLE_CLEANUP_ORPHANS' ), 'orphan' );

		// Set toolbar items for the page
		ToolBarHelper::back();
		ToolBarHelper::help( 'screen.translate.orphans', true);

		// hide the sub menu
		// This won't work
		$submenu =  JModuleHelper::getModule("submenu");
		$submenu->content = "\n";
        Factory::getApplication()->getInput()->set('hidemainmenu',1);
	}

	function preview($tpl = null)
	{
		// hide the sub menu
		$this->_hideSubmenu();
		//parent::display($tpl);

	}
}
