<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Event\Application\AfterDispatchEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;


//Global definitions use for front
if( !defined('DS') ) {
    define( 'DS', DIRECTORY_SEPARATOR );
}

//Joomla\Filesystem\File::exists don't work on Joomla 5.1 in the plugin/system
if (file_exists(JPATH_SITE.'/components/com_falang/helpers/defines.php')){
	require_once( JPATH_SITE.'/components/com_falang/helpers/defines.php' );
}
if (file_exists(JPATH_SITE.'/components/com_falang/helpers/falang.class.php')) {
	require_once( JPATH_SITE.'/components/com_falang/helpers/falang.class.php' );
}


class plgSystemFalangquickjump extends CMSPlugin
{

    /**
     * Application object.
     *
     * @var    \Joomla\CMS\Application\CMSApplication
     * @since  3.7.0
     */
    protected $app;

    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();

        //load
        if ($this->app->isClient('administrator')) {
            //check if the compnent is removed (not the package)
            if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_falang/classes/FalangManager.class.php')) {
                return;
            };

            require_once( JPATH_ADMINISTRATOR."/components/com_falang/classes/FalangManager.class.php");
        }
    }

	/*
	 * Add display modal windows
	 *
	 * @update 5.20 only for html document (fix ajax call and json response showtime)
	 * */
	public function onAfterRender(){
    	if ($this->app->isClient('administrator')) {

            $document = Factory::getApplication()->getDocument();

            //only for html document
            if (!($document instanceof \Joomla\CMS\Document\HtmlDocument)) {
                return;
            }

            //test if falang plugin is enabled.
            $falang_driver = PluginHelper::isEnabled('system', 'falangdriver');
            if (!$falang_driver) {
                return;
            }


            $input = $this->app->input;
            $option = $input->get('option', null, 'cmd');
            $view = $input->get('view', 'default', 'cmd');
            $task = $input->get('task', null, 'cmd');

            //fix ArticleAnywhere Problem FalangManager not defined
            $rl_qp = $input->get('rl_qp', null, 'cmd');
            if (isset($rl_qp)) {
                return;
            }

            jimport('joomla.application.component.helper');
            $params = ComponentHelper::getParams('com_falang');

            //get supported component <form></form>
            $falangManager = FalangManager::getInstance();
            $component = $falangManager->loadQJComponent();
            if (!isset($component)) {
                return;
            }

            if (!is_null($view) || is_null($task)) {
                if (is_null($view)) {
                    $view = 'default';
                }
                //$view = $jd->getView($option, $view);
            } elseif (!is_null($task)) {
                //$view = $jd->getViewByTask($option, $task);
            }

            //display only on view $taksk is null
            if (is_null($task)) {
                $supported_views = explode(',', $component[3]);
                if (!in_array($view, $supported_views)) {
                    return;
                }
            }
            if (isset($view)) {
                $this->addQuickModalWindows();
            }
        }
	}

	/*
	 * Add quickmodal before body
	 * */
	public function addQuickModalWindows(){
		$app = Factory::getApplication();
		$quickModal = '<div id="quickModal" class="joomla-modal modal fade" role="dialog" tabindex="-1">';
		$quickModal .= '  <div class="modal-dialog modal-lg jviewport-width90 " role="document">';
		$quickModal .= '    <div class="modal-content">';
		$quickModal .= '      <div class="modal-header">';
		$quickModal .= '        <h3 class="modal-title">'.Text::_('PLG_SYSTEM_FALANGQUICKJUMP_TRANSLATE_TITLE').'</h3>';
		$quickModal .= '        <button class="btn-close novalidate" type="button" data-bs-dismiss="modal" aria-label="'.Text::_('JCLOSE').'" >';
		$quickModal .= '        </button>';
		$quickModal .= '      </div>';
		$quickModal .= '      <div class="modal-body modal-body jviewport-height90">';
		$quickModal .= '        <iframe></iframe>';
		$quickModal .='       </div>';
		$quickModal .= '    </div>';
		$quickModal .= '  </div>';
		$quickModal .= '</div>';

		$app->appendBody($quickModal);
	}

    //onAfterRoute : the document is not initalize
    public function onAfterRoute()
    {

        if ($this->app->isClient('administrator')) {

            $params = ComponentHelper::getParams('com_falang');

            if (!$this->displayQuickJump()){
                return;
            }

            // Intercept the grid.id HTML Field to insert translation status
            // need to be done in the onAfterRoute
            if ($params->get('show_list', true)) {
              require_once(JPATH_PLUGINS.'/system/falangquickjump/classes/GridIdHook.php');
              HTMLHelper::getServiceRegistry()->register('grid', Joomla\CMS\HTML\Helpers\GridIdHook::class, true);
            }
        }
    }

    /*
     * Document initialize
     * */
    public function onAfterDispatch(AfterDispatchEvent $event):void
    {
        if ($this->app->isClient('administrator')) {

            if (!$this->displayQuickJump()){
                return;
            }

            $params = ComponentHelper::getParams('com_falang');
            if ($params->get('show_list', true)) {
                $this->addGridHtml();
            }
            if ($params->get('show_form',true)) {
                $this->addToolbar();//need to be done in the after dispatch
            }
        }
    }

    /*
     * Method to know if the quickjump system need to be displayed on this view
     *
     * @since 6.0
     * */
    private function displayQuickJump(){
        $input = $this->app->input;
        $view = $input->get('view', 'default', 'cmd');
        $task = $input->get('task', null, 'cmd');

        //get supported component <form></form>
        $falangManager = FalangManager::getInstance();
        $component = $falangManager->loadQJComponent();
        if (!isset($component)){
            return false;
        }

        if (!is_null($view) || is_null($task)) {
            if (is_null($view)) {
                $view = 'default';
            }
        }

        //display only on view $taksk is null
        if (is_null($task)) {
            $supported_views = explode(',', $component[3]);
            if (!in_array($view, $supported_views)) {
                return false;
            }
        }

        if (isset($view)) {
            return true;
        }

        return false;
    }

    /*
     * @since 4.0.7 load jquery only on backend
     * @update 5.7 add bootstrap render modal to allow popup (Fix for J5.1)
     * @update 5.14 text::script need to be done for admin only
     *
     * */
    public function onBeforeRender(){
        $app = Factory::getApplication();
        if ($app->isClient('administrator'))
        {
            HTMLHelper::_('jquery.framework');
            HTMLHelper::_('bootstrap.renderModal');
            Text::script('LIB_FALANG_TRANSLATION');
        }
    }

    public function addGridHtml(){
        $this->loadLanguage();//necessary to load the javascript language
        Factory::getApplication()->getDocument()->addStyleSheet(URI::root().'administrator/components/com_falang/assets/css/falang.css', array('version' => 'auto', 'relative' => false));
        Factory::getApplication()->getDocument()->addScript(URI::root().'plugins/system/falangquickjump/assets/falangqj.js', array('version' => 'auto', 'relative' => false));
    }

    /**
     * Adds the translation toolbar button to the toolbar based on the
     * given parameters.
     *
     * @update 5.16 add the FalangContentTable require once need by ContentObject
     * @update 6.00 stylesheet is loaded in onAfterDispatch
     *
     */
    public function addToolbar() {
        //check if we are in backend
        $app = Factory::getApplication();
        if (!$app->isClient('administrator')) {return;}

        $falangManager = FalangManager::getInstance();
        $input = $app->input;

        $option = $input->get('option', false, 'cmd');
        $view 	= $input->get('view', false, 'cmd');
        $task = $input->get('task', false, 'cmd');
        $layout = $input->get('layout', 'default', 'string');

        if (!$option || (!$view && !$task) || !$layout) {
            return;
        }

        $mapping = $falangManager->loadQJComponent();

        if (!isset($mapping)){
            return;
        }

        //GET KEY FROM CONTENT ELEMENT
        $id = $input->get($mapping[2], 0, 'int');

        if (empty($id)) {
            return;
        }

        //Fix for joomla 3.5
        if (is_array($id)){$id = $id[0];}

        $bar    = Factory::getApplication()->getDocument()->getToolbar();

        //Load Language
        //$languages	= $this->getLanguages();
        $languages = $falangManager->getLanguagesForTranslation();

        // @deprecated used for Joomla 2.5

        //TODO use library ?
        $bar->addButtonPath(JPATH_PLUGINS.'/system/falangquickjump/toolbar/button/');
        require_once JPATH_PLUGINS.'/system/falangquickjump/toolbar/button/itrpopup.php';
        $buttontype = 'itrPopup';
        $width = '95%';
        $height = '99%';

        //Add button by language
        foreach ($languages as $language) {
            //get Falang Object info
            $contentElement = $falangManager->getContentElement($mapping[1]);
            require_once(FALANG_ADMINPATH.'/src/Table/FalangContentTable.php');
            JLoader::import( 'models.ContentObject',FALANG_ADMINPATH);
            $actContentObject = new ContentObject( $language->lang_id, $contentElement );
            $loaded = $actContentObject->loadFromContentID( $id );

	        //hide quickicon button if speicific language is set to the item
	        if (!$loaded){
	        	continue;
	        }

            $class="quickmodal ";
            //-1 not exist, 0 old , 1 uptodate
            switch($actContentObject->state) {
                case 1:
                        $class .= "uptodate";
                        break;
                case 0:
                        $class .= "old";
                        break;
                case -1:
                        $class .= "notexist";
                        break;
                default :
                        $class .= "notexist";
                        break;
            }
            $publish = (isset($actContentObject->published) && $actContentObject->published == 1 )?" icon-publish":" icon-unpublish";

            //free and paid mmust be on 1 line
            
            /* >>> [PAID] >>> */$url = 'index.php?option=com_falang&task=translate.edit&layout=popup&catid=' . $mapping[1] .'&cid[]=0|'.$id.'|'.$language->lang_id.'&select_language_id='. $language->lang_id.'&direct=1';/* <<< [PAID] <<< */

            //$newButton = new JToolbarButtonItrPopup('falang-quicktranslate-'.$language->lang_id,$language->title,$option);
            $options = array();
            $options['name'] = 'falang-quicktranslate-'.$language->lang_id;
            $options['title'] = $language->title;
            $options['url'] = $url;
            $options['flag'] = $language->image;
            $options['modalWidth'] = 95;
            $options['bodyHeight'] = 80;//bodyHeigh 80 max due to the <90
            $options['publish'] = $publish;
            $options['class'] = $class;
            $newButton = new JToolbarButtonItrPopup('falang-quicktranslate-'.$language->lang_id,$language->title,$options);
	        $bar->appendButton($newButton);

        }
    }



}
