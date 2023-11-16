<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Router;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Menu\MenuFactoryInterface;
use Joomla\CMS\Router\SiteRouter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Event\ConnectionEvent;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

//Global definitions use for front
if( !defined('DS') ) {
    define( 'DS', DIRECTORY_SEPARATOR );
}


jimport('joomla.plugin.plugin');

/**
 * Falang Driver Plugin
 */
class plgSystemFalangdriver extends CMSPlugin
{

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  3.10.2
     */
    protected $autoloadLanguage = true;

    public function __construct(&$subject, $config = array())
    {


        parent::__construct($subject, $config);

        $this->setupCoreFileOverride();

        //use for finder content plugin
        $this->displayInfoMessage();

        // This plugin is only relevant for use within the frontend!
        if (Factory::getApplication()->isClient('administrator')) {
            return;
        }

        //@since 2.9.0
        //add this setup in the constuctor due to system plugin who use $this->db (constucted by reflexion of JPlugin)
        //and no more in the onAfterInitialise
        if (!$this->isFalangDriverActive()) {
            $this->setupDatabaseDriverOverride();
        }

    }

    /**
     * System Event: onAfterInitialise
     *
     * @return    string
     *
     * @since 4.5 add fix for admin tools
     */
    function onAfterInitialise()
    {
        // This plugin is only relevant for use within the frontend!
        if (Factory::getApplication()->isClient('administrator')) {
            return;
        }

        //fix for joomla > 3.4.0
        $app = Factory::getApplication();
        if ($app->isClient('site')) {
            //$router = $app->getRouter();
            $router = Factory::getContainer()->get(SiteRouter::class);
            //sbou5

            // attach build rules for translation on SEF
            $router->attachBuildRule(array($this, 'buildRule'), Router::PROCESS_BEFORE);

            // attach build rules for translation on SEF
            $router->attachParseRule(array($this, 'parseRule'), Router::PROCESS_BEFORE);

            //fix for admintools , reset the menu
            if (PluginHelper::isEnabled('system', 'admintools'))
            {
                $db = Factory::getDbo();
                $opt['db'] = $db;
                $opt['language'] = Factory::getLanguage();
                $app = Factory::getApplication();
                $app->getMenu()->__construct($opt);
                $app->getMenu()->load();
            }
        }
        //end fix

    }


    /*
     * @since 4.0.1
     * @since 4.5 test is_array before $uri->getVar('catid'), sometimes it's an array, why?
     * */
    public function buildRule(&$router, &$uri)
    {
        $lang = $uri->getVar('lang');
        $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');

        //we build the route for category list article
        if ($lang != $default_lang && $uri->getVar('id') != null && $uri->getVar('catid') != null &&
            $uri->getVar('option') == 'com_content') {//&& $uri->getVar('view') == 'article'

            $fManager = FalangManager::getInstance();
            $id_lang = $fManager->getLanguageID($lang);

            // Make sure we have the id and the alias
            if (strpos($uri->getVar('id'), ':') > 0) {
                list($tmp, $id) = explode(':', $uri->getVar('id'), 2);
                $db = Factory::getDbo();
                $dbQuery = $db->getQuery(true)
                    ->select('fc.value')
                    ->from('#__falang_content fc')
                    ->where('fc.reference_id = ' . (int)$tmp)
                    ->where('fc.language_id = ' . (int)$id_lang)
                    ->where('fc.reference_field = \'alias\'')
                    ->where('fc.published = 1')
                    ->where('fc.reference_table = \'content\'');

                $db->setQuery($dbQuery);
                $alias = $db->loadResult();
                if (isset($alias)) {
                    $uri->setVar('id', $tmp . ':' . $alias);
                }
            }
            // Make sure we have the id and the alias
            if (!is_array($uri->getVar('catid'))) {
                if (strpos($uri->getVar('catid'), ':') > 0) {
                    list($tmp2, $catid) = explode(':', $uri->getVar('catid'), 2);

                    $db = Factory::getDbo();
                    $dbQuery = $db->getQuery(true)
                        ->select('fc.value')
                        ->from('#__falang_content fc')
                        ->where('fc.reference_id = ' . (int)$tmp2)
                        ->where('fc.language_id = ' . (int)$id_lang)
                        ->where('fc.reference_field = \'alias\'')
                        ->where('fc.published = 1')
                        ->where('fc.reference_table = \'categories\'');

                    $db->setQuery($dbQuery);
                    $alias = $db->loadResult();
                    if (isset($alias)) {
                        $uri->setVar('catid', $tmp2 . ':' . $alias);
                    }
                }
            }
        }

        //fix canonical if sef plugin is enabled
        $sef_plugin = PluginHelper::getPlugin('system', 'sef');
        if (!empty($sef_plugin)) {
            if ($lang != $default_lang && $uri->getVar('id') != null && $uri->getVar('catid') != null &&
                $uri->getVar('option') == 'com_content') {//&& $uri->getVar('view') == 'article'
                $fManager = FalangManager::getInstance();
                $id_lang = $fManager->getLanguageID($lang);

                // Make sure we have the id and the alias
                if (strpos($uri->getVar('id'), ':') === false) {
                    //we use id in the query to be translated.
                    $db = Factory::getDbo();
                    $dbQuery = $db->getQuery(true)
                        ->select('alias,id')
                        ->from('#__content')
                        ->where('id=' . (int)$uri->getVar('id'));
                    $db->setQuery($dbQuery);
                    $alias = $db->loadResult();
                    if (isset($alias)) {
                        $uri->setVar('id', $uri->getVar('id') . ':' . $alias);
                    }
                }
            }
        }

        //build route for hikashop product
        if ($uri->getVar('option') == 'com_hikashop' && $uri->getVar('ctrl') == 'product' && $uri->getVar('task') == 'show') {
            // on native language look in falang table
            if ($default_lang != $lang) {
                $fManager = FalangManager::getInstance();
                $id_lang = $fManager->getLanguageID($lang);
                $id = $uri->getVar('cid');
                $db = Factory::getDbo();
                $dbQuery = $db->getQuery(true)
                    ->select('fc.value')
                    ->from('#__falang_content fc')
                    ->where('fc.reference_id = ' . (int)$id)
                    ->where('fc.language_id = ' . (int)$id_lang)
                    ->where('fc.reference_field = \'product_alias\'')
                    ->where('fc.published = 1')
                    ->where('fc.reference_table = \'hikashop_product\'');

                $db->setQuery($dbQuery);
                $alias = $db->loadResult();
                if (isset($alias)) {
                    $uri->setVar('name', $alias);
                }

            } else {
                // translated languague look in native table
                $id = $uri->getVar('cid');
                $db = Factory::getDbo();
                $dbQuery = $db->getQuery(true)
                    ->select('product_alias')
                    ->from('#__hikashop_product')
                    ->where('product_id = ' . (int)$id);
                $db->setQuery($dbQuery);
                $alias = $db->loadResult();
                if (isset($alias)) {
                    $uri->setVar('name', $alias);
                }
            }
            //
        }
        //build route for hikahsop category list
        if ($uri->getVar('option') == 'com_hikashop' && $uri->getVar('ctrl') == 'category' && $uri->getVar('task') == 'listing') {
            // on native language look in falang table
            if ($default_lang != $lang) {
                $fManager = FalangManager::getInstance();
                $id_lang = $fManager->getLanguageID($lang);
                $id = $uri->getVar('cid');
                $db = Factory::getDbo();
                $dbQuery = $db->getQuery(true)
                    ->select('fc.value')
                    ->from('#__falang_content fc')
                    ->where('fc.reference_id = ' . (int)$id)
                    ->where('fc.language_id = ' . (int)$id_lang)
                    ->where('fc.reference_field = \'category_alias\'')
                    ->where('fc.published = 1')
                    ->where('fc.reference_table = \'hikashop_category\'');

                $db->setQuery($dbQuery);
                $alias = $db->loadResult();
                if (isset($alias)) {
                    $uri->setVar('name', $alias);
                }

            } else {
                // translated languague look in native table
                $id = $uri->getVar('cid');
                $db = Factory::getDbo();
                $dbQuery = $db->getQuery(true)
                    ->select('category_alias')
                    ->from('#__hikashop_category')
                    ->where('category_id = ' . (int)$id);
                $db->setQuery($dbQuery);
                $alias = $db->loadResult();
                if (isset($alias)) {
                    $uri->setVar('name', $alias);
                }
            }
        }
        //build route for k2 category list
        //v2.2.2 add download test due to download link bug in other case.
        if ($uri->getVar('option') == 'com_k2' && $uri->getVar('view') == 'item' && $uri->getVar('task') != 'download') {
            // on native language look in falang table
            if ($default_lang != $lang) {
                $fManager = FalangManager::getInstance();
                $id_lang = $fManager->getLanguageID($lang);

                // Make sure we have the id and the alias
                if (strpos($uri->getVar('id'), ':') > 0) {
                    list($tmp, $id) = explode(':', $uri->getVar('id'), 2);
                    $db = Factory::getDbo();
                    $dbQuery = $db->getQuery(true)
                        ->select('fc.value')
                        ->from('#__falang_content fc')
                        ->where('fc.reference_id = ' . (int)$tmp)
                        ->where('fc.language_id = ' . (int)$id_lang)
                        ->where('fc.reference_field = \'alias\'')
                        ->where('fc.published = 1')
                        ->where('fc.reference_table = \'k2_items\'');

                    $db->setQuery($dbQuery);
                    $alias = $db->loadResult();
                    if (isset($alias)) {
                        $uri->setVar('id', $tmp . ':' . $alias);
                    }
                }
            } else {
                // translated languague look in native table
                $tmp = $uri->getVar('id');
                // Make sure we have the id and the alias
                if (strpos($tmp, ':') > 0) {
                    list($tmp, $id) = explode(':', $tmp, 2);
                }

                $db = Factory::getDbo();
                $dbQuery = $db->getQuery(true)
                    ->select('alias')
                    ->from('#__k2_items')
                    ->where('id = ' . (int)$tmp);
                $db->setQuery($dbQuery);
                $alias = $db->loadResult();
                if (isset($alias)) {
                    $uri->setVar('id', $tmp . ':' . $alias);
                }
            }
        }

        if ($uri->getVar('option') == 'com_djcatalog2' && $uri->getVar('view') == 'item') {
            $this->buildRuleAlias($uri, 'djc2_items', 'alias');
        }

        //event booking translate event alias
        if ($uri->getVar('option') == 'com_eventbooking' && $uri->getVar('view') == 'event') {
            //see components/com_eventbooking/router.php
            $uri->setVar('al',$lang);
        }

        return array();
    }

    public function buildRuleAlias(&$uri, $reference_table, $alias_name)
    {
        $lang = $uri->getVar('lang');
        $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');

        //look in Falang Table
        if ($default_lang != $lang) {
            $fManager = FalangManager::getInstance();
            $id_lang = $fManager->getLanguageID($lang);

            // Make sure we have the id and the alias
            if (strpos($uri->getVar('id'), ':') > 0) {
                list($id, $tmp) = explode(':', $uri->getVar('id'), 2);
                $db = Factory::getDbo();
                $dbQuery = $db->getQuery(true);
                $dbQuery->select('fc.value')
                    ->from('#__falang_content fc')
                    ->where('fc.reference_id = ' . $dbQuery->q($id))
                    ->where('fc.language_id = ' . $dbQuery->q($id_lang))
                    ->where('fc.reference_field = ' . $dbQuery->q($alias_name))
                    ->where('fc.published = 1')
                    ->where('fc.reference_table = ' . $dbQuery->q($reference_table));

                $db->setQuery($dbQuery);
                $alias = $db->loadResult();
                if (isset($alias)) {
                    $uri->setVar('id', $id . ':' . $alias);
                }
            }
        } else {
            // translated languague look in native table
            $tmp = $uri->getVar('id');
            // Make sure we have the id and the alias
            if (strpos($tmp, ':') > 0) {
                list($tmp, $id) = explode(':', $tmp, 2);
            }

            $db = Factory::getDbo();
            $dbQuery = $db->getQuery(true);
            $dbQuery->select($dbQuery->qn($alias_name))
                ->from($dbQuery->qn('#__' . $reference_table))
                ->where('id = ' . $dbQuery->q($tmp));
            $db->setQuery($dbQuery);
            $alias = $db->loadResult();
            if (isset($alias)) {
                $uri->setVar('id', $tmp . ':' . $alias);
            }
        }
    }


    /*
     * @since 4.7 add route translation use for subitem menu not translated
     * */
    public function parseRule(&$router, &$uri)
    {
        static $done = false;
        if (!$done) {
            $done = true;
            $conf = Factory::getConfig();
            $lang = Factory::getLanguage();
            $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');

            //fix for virtuemart / lang must be reset
            if (ComponentHelper::isEnabled('com_virtuemart', true)){
                if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
                VmConfig::loadConfig();
                vmLanguage::$jSelLangTag = false;
                vmLanguage::initialise(true);
            }

            Factory::getApplication()->getMenu()->__construct();
            //translate path when subitem not translate
            $app = Factory::getApplication();
            $menu = $app->getMenu()->getMenu();

            foreach($menu as &$item) {
                $item->route = '';
                if ($item->level > 1) {
                    if (array_key_exists($item->parent_id, $menu)) {
                        $item->route = $menu[$item->parent_id]->route.'/';
                    }
                }
                $item->route .= $item->alias;
            }

        }
        return array();
    }

    public function isFalangDriverActive()
    {
        $db = Factory::getDBO();

        return is_a($db, 'JFalangDatabase');
    }


    function onAfterDispatch()
    {
        if (Factory::getApplication()->isClient('site') && $this->isFalangDriverActive()) {
            include_once(JPATH_ADMINISTRATOR . '/components/com_falang/version.php');
            $version = new FalangVersion();
            if ($version->_versiontype == 'free') {
                FalangManager::setBuffer();
            }
            return true;
        }
    }

    /*
     * since 4.0.8 fix debug / change option load (jdiciton way)
     * @since 4.3 fix menu translation (Joomla >= 4.2.0)
     * @since 4.4 fix for content translation (Joomla >= 4.2.1)
     * */
    function setupDatabaseDriverOverride()
    {
        //override only the override file exist
        if (file_exists(dirname(__FILE__) . '/falang_database.php')) {
            require_once(dirname(__FILE__) . '/falang_database.php');

            $container = Factory::getContainer();

            $conf = $container->get('config');

            $dbtype = $conf->get('dbtype');

            // We only support mysqli at this time
            if (strtolower($dbtype) !== 'mysqli') {
                return;
            }

            $options = [
                'driver'   => $dbtype,
                'host'     => $conf->get('host'),
                'user'     => $conf->get('user'),
                'password' => $conf->get('password'),
                'database' => $conf->get('db'),
                'prefix'   => $conf->get('dbprefix'),
                'select'   => true,
            ];

            // Enable utf8mb4 connections for mysql adapters
            if (strtolower($dbtype) === 'mysqli') {
                $options['utf8mb4'] = true;
            }

            if (JDEBUG) {
                $options['monitor'] = new \Joomla\Database\Monitor\DebugMonitor;
            }

            $db = new JFalangDatabase($options);

            Factory::$database = null;
            Factory::$database = $db;

            //Joomla 4.2.0 : override the Database Driver in the MenuFactory
            //Joomla 4.2.1 : need the $db in the container
            if (version_compare(JVERSION ,'4.2', '>=' )){
                $db->setDispatcher($container->get(DispatcherInterface::class));

                // Disable isProtected for the database resource
                $databaseResource = $container->getResource(DatabaseInterface::class);
                $databaseResourceProtected = (new ReflectionObject($databaseResource))->getProperty('protected');
                $databaseResourceProtected->setAccessible(true);
                $databaseResourceProtected->setValue($databaseResource, false);

                $container->set(DatabaseInterface::class, $db, true);

                // Activate isProtected for the database resource
                $databaseResourceProtected->setValue($databaseResource, true);

                //fix menu translation
                $menuFactory = $container->getResource(MenuFactoryInterface::class);
                $menuFactory->getInstance()->setDatabase($db);

            }

        }

    }

    private function setBuffer()
    {
        $doc = Factory::getDocument();
        $cacheBuf = $doc->getBuffer('component');

        $cacheBuf2 =
            '<div><a title="Faboba : Cr&eacute;ation de composant'.
            'Joomla" style="font-size: 9px;; visibility: visible;'.
            'display:inline;" href="http://www.faboba'.
            '.com" target="_blank">FaLang tra'.
            'nslation syste'.
            'm by Faboba</a></div>';

        if ($doc->_type == 'html')
            $doc->setBuffer($cacheBuf . $cacheBuf2,'component');

    }


    /*
     * Use trigger to activate the language selection in the template
     */
    function onContentPrepareForm($form, $data)
    {
        if (Factory::getApplication()->isClient('site')){return;}

	    $this->enabledTplTranslation($form,$data);

	    $custom_fields = PluginHelper::isEnabled('system', 'fields');
	    if ($custom_fields){
		    $this->loadCustomFields($form, $data);
	    }
    }

    /*
    * use to set the value of the custom fields to the falang translation form
    * actually can't work perfectly because fields_values table don't have id key
    *
    * @since 4.0.5 load publish/unpublish custom field translation
    *
    */
	private function loadCustomFields($form, $data){

		$input = Factory::getApplication()->input;
		$option = $input->get('option');
		$task = $input->get('task');
		$catid = $input->get('catid');
		$reference_id = $input->get('reference_id');
		$cid = $input->get('cid');
		$language_id = $input->get('language_id'); //from quickump it's this one
		$select_language_id = $input->get('select_language_id');//from falang list it's selectec langauge

		if ($option == 'com_falang' && ($task == 'translate.edit' || ($task == 'translate.apply') )) {

			$context = $form->getName();

			// When a category is edited, the context is com_categories.categorycom_content
			if (strpos($context, 'com_categories.category') === 0) {
				$context = str_replace('com_categories.category', '', $context) . '.categories';
			}

			$parts = FieldsHelper::extract($context, $form);

			if (!$parts) {
				return true;
			}

			if (empty($reference_id)){
				$reference_id = current($cid);
			}

			//load category from original item to set to the translation
			//necessary to load the related custom fields even if a translation for them don't exist.
    			if ( !empty($reference_id) && $catid == 'content'){
                $mvcFactory = Factory::getApplication()->bootComponent('com_content')->getMVCFactory();
                $articleModel = $mvcFactory->createModel('Article', 'Site', ['ignore_request' => true]);
                $contentParams = ComponentHelper::getParams('com_content');
                $articleModel->setState('params', $contentParams);
                $item =  $articleModel->getItem($reference_id);
                $data->catid = $item->catid;
			}
            if ( !empty($reference_id) && $catid == 'categories'){
                $categoryModel = Factory::getApplication()->bootComponent('com_categories')
                    ->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
                $item =  $categoryModel->getItem($reference_id);
            }

			// Getting the fields
			$fields = FieldsHelper::getFields($parts[0] . '.' . $parts[1], $data);

			//prepare the fields
            FieldsHelper::prepareForm($parts[0] . '.' . $parts[1], $form, $data);


            $db = Factory::getDbo();
			$fManager = FalangManager::getInstance();
			$content_element = $fManager->getContentElement($catid);

			if (empty($content_element)){
				return;
				return;
			}


			if (empty($language_id)){
				$language_id = $select_language_id;
			}
			//load com_fields values (json format) published or unpublish value
			$translations =  $fManager->getRawFieldTranslations($content_element->getTableName(),'com_fields',$reference_id,$language_id,false);


			if (empty($translations)) {
				$params = ComponentHelper::getParams('com_falang');
				$copy_cusom_fields = $params->get('copy_custom_fields',false);


				if ($copy_cusom_fields == false){
					return true;
				}

				$original = $fManager->getRawFieldOrigninal($reference_id);

				//load orinal customfield to translation
				foreach ($fields as $field)
				{
					if (isset($original[$field->id])){
						$value  = $original[$field->id];
						$form->setValue($field->name, 'com_fields', $value);
					}

				}

			}

			$json_value = json_decode($translations);
			foreach ($fields as $field)
			{
				if (isset($json_value->{$field->name})) {
					$form->setValue($field->name, 'com_fields', $json_value->{$field->name});
				}
			}

		}

		return true;
	}

	//use to enable template by langugage (paid version only)
	private function enabledTplTranslation($form, $data){
		jimport('joomla.application.component.helper');
		$params = ComponentHelper::getParams('com_falang');
		$show_tpl_lang = $params->get('show_tpl_lang');

		if (!isset($show_tpl_lang) || $show_tpl_lang == '0' ) {return;}


		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}
		if ((is_array($data) && array_key_exists('home', $data))
			|| ((is_object($data) && isset($data->home) ))) {
			$form->setFieldAttribute('home', 'readonly', 'false');
		}
	}

    /**
     * Save custom fields translation
     * Throw by Falang (Backend)
     *
     * @since 4.0.5 add cateogry custom fields support
     * @update 4.12 save original text (null not accepted)
     * @update 5.0 original value is empty
     *
     * @param $post
     * @return bool|void
     * @throws Exception
     *
     *
     */
	public function onAfterTranslationSave($post){
	    $supported_context = array('content','categories');
		//system fields plugins need to be published.
		$fields_plugin = PluginHelper::getPlugin('system', 'fields');
		if (empty($fields_plugin)){return true;}

		$input = Factory::getApplication()->input;
		$catid = $input->get('catid');
		$language_id = $input->get('select_language_id');
		$reference_id = $input->get('reference_id');
		$formData = new Registry($input->get('jform', '', 'array'));
		$context = $catid;

		//content and category supported
        if (!in_array($context,$supported_context)){return;}

		//TODO not set article here
		//load the content item to have the custom field associated with the categories of this item.
		if ( !empty($reference_id) && $catid == 'content'){
			$model =  new Joomla\Component\Content\Administrator\Model\ArticleModel;
			$contentParams = ComponentHelper::getParams('com_content');
			$model->setState('params', $contentParams);
			$item = $model->getItem($reference_id);
            $fields = FieldsHelper::getFields('com_'.$context. '.' . 'article', $item);
		}
        if ( !empty($reference_id) && $catid == 'categories'){
            Factory::getApplication()->input->set('extension', 'com_content');//not necessary by default com_content is used
            $model =  new Joomla\Component\Categories\Administrator\Model\CategoryModel;
            $contentParams = ComponentHelper::getParams('com_content');
            $model->setState('params', $contentParams);//TODO check
            $item = $model->getItem($reference_id);
            $fields = FieldsHelper::getFields('com_content'. '.' . 'categories', $item);//load com_content
        }

		if (!$fields) {
			return true;
		}

		// Get the translated fields data
		$fieldsData = !empty($formData) ? (array)$formData['com_fields'] : array();

		$db = Factory::getDbo();
		$user = Factory::getUser();

		$values = array();
		// Loop over the fields
		foreach ($fields as $field) {
			// Determine the value if it is available from the data
			$value = key_exists($field->name, $fieldsData) ? $fieldsData[$field->name] : null;
			$values[$field->name] = $value;
		}


		//save $values array in json format
		if (!empty($values)){
			//get previous com_fields falang translation
			//get previous value if exit to make update or insert

			$query = $db->getQuery(true);
			$query->select($query->qn('id'))
				->from($query->qn('#__falang_content'))
				->where($db->qn('language_id') . ' = ' . $db->q($language_id))
				->where($db->qn('reference_id') . ' = ' . $reference_id)
				->where($db->qn('reference_field') . ' = ' . $db->q('com_fields'))
				->where($db->qn('reference_table') . ' = ' . $db->q($context));

			$db->setQuery($query);
			$falangId = $db->loadResult();


			$jsonValues = json_encode($values);
			$fieldContent = new falangContent($db);
			if (isset($falangId)){$fieldContent->id = $falangId;}
			$fieldContent->reference_id = $reference_id ;
			$fieldContent->language_id = $language_id;
			$fieldContent->reference_table= $context;
			$fieldContent->reference_field= 'com_fields';
			$fieldContent->value = $jsonValues;
			// the original value don't exist for custom_fields.
			$fieldContent->original_value = '';
            $fieldContent->original_text = "";

			$fieldContent->modified =  Factory::getDate()->toSql();

			$fieldContent->modified_by = $user->id;
			$fieldContent->published= true;

			$fieldContent->store();

		}

		return true;
	}

	/**
	 * We need to prepare custom fields per plugin because #__fields_values doesn't have a primary key
     *
     * $since 4.0.5 load only published by param's
     *              fix subform custom field translation
     * @since 4.0.7 fix for categories custom fields
	 *
	 * @param $context
	 * @param $item
	 * @param $field
	 */
	public function onCustomFieldsBeforePrepareField($context, $item, $field) {

		// We only work in frontend
		if (!Factory::getApplication()->isClient('site')) {
			return;
		}

        //only for transalted language
        $current_lang = Factory::getApplication()->getLanguage()->getTag();
        $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
        //nothing to do for default langauge
        if ($current_lang == $default_lang ){
            return;
        }

		list($component, $view) = explode('.', $context, 2);

		if (strpos($component, "com_")=== 0)	{
			$component_name = substr($component, 4);
		} else {
			$component_name = $component;
		}

        //fix for com_content.categories
        if ($view == 'categories'){
            $component_name = 'categories';
        }

		$fManager = FalangManager::getInstance();

		$content_element = $fManager::getInstance()->getContentElement($component_name);

		if (empty($content_element)){
			return;
		}

		$languageTag  = Factory::getLanguage()->getTag();
		$id_lang = $fManager->getLanguageID($languageTag);

		$translations = FalangManager::getInstance()->getRawFieldTranslations($content_element->getTableName(),'com_fields',$item->{$content_element->getReferenceId()},$id_lang,true);//load only published

		if (empty($translations)) {
			return;
		}
		//supposed to be array
		$json_value = json_decode($translations,true);

        if (isset($json_value[$field->name])) {

            $field->valueUntranslated    = $field->value;
            $field->rawvalueUntranslated = $field->rawvalue;

            switch ($field->type) {
                case 'repeatable':
                case 'subform' :
                case 'media' :
                    $field->value                = json_encode($json_value[$field->name]);
                    $field->rawvalue             = json_encode($json_value[$field->name]);
                    break;
                default :
                    $field->value                = $json_value[$field->name];
                    $field->rawvalue             = $json_value[$field->name];
                    break;
            }

		}

	}

	/*
	 * Override for site content and tags Router.
	 * $since 4.0.6
	 *
	 * */
    public function onAfterExtensionBoot(\Joomla\Event\EventInterface $event)
    {
        //only for site
        if (Factory::getApplication()->isClient('administrator')) {
            return;
        }
        // Test that this is a component.
        if ($event->getArgument('type') !== 'Joomla\\CMS\\Extension\\ComponentInterface') {
            return;
        }

        // Test that this is com_content and com_tags component.
        if ($event->getArgument('extensionName') !== 'content' && $event->getArgument('extensionName') !== 'tags') {
            return;
        }


        $extensionName = $event->getArgument('extensionName');

        // Get the container.
        $container = $event->getArgument('container');

        if(!$container->has(RouterFactoryInterface::class)){
            return;
        }

        $container->extend(
            RouterFactoryInterface::class,
            function (RouterFactoryInterface $router,Container $container)
            {
                return new class($router) implements RouterFactoryInterface{

                    private $router;

                    public function __construct(RouterFactoryInterface $router)
                    {
                        $this->router = $router;
                    }

                    /*
                     * Access private data namespace of the Router
                     * use to find the component to override
                    */
                    public function reader($object, $property){
                        $value = \Closure::bind(function () use ($property) {
                            return $this->$property;
                        }, $object, $object)->__invoke();

                        return $value;
                    }

                    public function createRouter(CMSApplicationInterface
                                                 $application, AbstractMenu $menu): RouterInterface
                    {

                        //access all private router var
                        $namespace = $this->reader($this->router, 'namespace');
                        $categoryFactory = $this->reader($this->router, 'categoryFactory');
                        $db = $this->reader($this->router, 'db');


                        $extensionName = str_replace('\\Joomla\\Component\\','',$namespace);
                        $className = 'Falang\\Component\\'.$extensionName.'\\Site\\Service\\FalangRouter';
                        $router_file_path  = JPATH_PLUGINS . '/system/falangdriver/routers/com_' . strtolower($extensionName) . '/Router.php';
                        if (file_exists($router_file_path)){
                            require_once $router_file_path;
                        }
                        if (!class_exists($className))
                        {
                            throw new \RuntimeException('No router available for this application.');
                        }

                        return new $className($application, $menu, $categoryFactory, $db);
                    }
                };
            },
            true
        );

    }

	//@since 3.4.3
	public function setupCoreFileOverride(){
		//for front and back
		//override Front-end Language file for site and admin section. use for user language configuration
		JLoader::register('Joomla\CMS\Form\Field\FrontendlanguageField', dirname(__FILE__).'/overrides/libraries/src/Form/Field/FrontendlanguageField.php', true);

		//for back

		//for front

	}

    /*
     * Display message on Indexed content view
     * since 3.10.2
     * */
    public function displayInfoMessage()
    {
        if (Factory::getApplication()->isClient('site')) {
            return;
        }
        $input = Factory::getApplication()->input;
        $option = $input->get('option');
        $view = $input->get('view', 'index');
        if ($option == 'com_finder' && $view == 'index') {
            //check if finder content is publisshed
            $enabled_finder_content = PluginHelper::isEnabled('finder', 'content');
            $enabled_finder_falang_content = PluginHelper::isEnabled('finder', 'falangcontent');

            if ($enabled_finder_content && $enabled_finder_falang_content) {
                Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_FALANGDRIVER_CONTENT_FINDER_INFO_MSG'), 'warning');
            }
        }
    }
}