<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Falang\Component\Administrator\Table\FalangContentTable;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Router;
use Joomla\CMS\Table\Table;
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
use Joomla\Registry\Registry;

//Global definitions use for front
if( !defined('DS') ) {
    define( 'DS', DIRECTORY_SEPARATOR );
}

/**
 * Falang Driver Plugin
 * can't use SubscriberInterface in other case problem with the other event
 *
 */
final class plgSystemFalangdriver extends CMSPlugin
{
    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  3.10.2
     * @update 5.2 change constructor
     *             add onAfterDisconnect
     */
    protected $autoloadLanguage = true;

    /*
     * @from 1.0
     * @update 5.4 test site and not administrator
     * @update 6.1 move displayMessage on onAfterDispach
     * */
    public function __construct(DispatcherInterface $dispatcher, $config = array())
    {
        parent::__construct($dispatcher, $config);

        //add onAfterDisconnect event support from SubscriberInterface
        //fix database already close
        if (Factory::getApplication()->isClient('site')) {
            $dispatcher->addListener('onAfterDisconnect', [$this,'onAfterDisconnect']);
        }

        $this->setupCoreFileOverride();

        // This plugin is only relevant for use within the frontend!
        // need to test site
        if (!Factory::getApplication()->isClient('site')) {
            return;
        }

        //@since 2.9.0
        //add this setup in the constuctor due to system plugin who use $this->db (constucted by reflexion of JPlugin)
        //and no more in the onAfterInitialise
        if (!$this->isFalangDriverActive()) {
            $this->setupDatabaseDriverOverride();
        }

    }


    /*
     * @since 5.2 set the connection too null fix the mysqli already close event
     *            need to access to the protected var $connection
     * */
    public function onAfterDisconnect(ConnectionEvent $event){
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if (is_a($db, 'JFalangDatabase')){
            $reflectionClass = new ReflectionClass('JFalangDatabase');
            $reflectionProperty = $reflectionClass->getProperty('connection');
            $reflectionProperty->setAccessible(true); // only required prior to PHP 8.1.0
            $reflectionProperty->setValue($db, null);
        }
    }

    /**
     * System Event: onAfterInitialise
     *
     * @return    string
     *
     * @since 4.5 add fix for admin tools
     * @update 6.1 remove warnign when language filter not published (not multilanguage)
     *             use this to be sure it's work with other sef tools
     */
    function onAfterInitialise()
    {
        // This plugin is only relevant for use within the frontend!
        if (Factory::getApplication()->isClient('administrator')) {
            return;
        }
        $multilang = Multilanguage::isEnabled();
        if (!$multilang){
            return ;
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
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $opt['db'] = $db;
                $opt['language'] = Factory::getApplication()->getLanguage();
                $app = Factory::getApplication();
                $app->getMenu()->__construct($opt);
                $app->getMenu()->load();
            }
        }
        //end fix

    }


    /*
     * @since 4.0.1
     * @update 4.5 test is_array before $uri->getVar('catid'), sometimes it's an array, why?
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
                $db = Factory::getContainer()->get(DatabaseInterface::class);
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

                    $db = Factory::getContainer()->get(DatabaseInterface::class);
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
                    $db = Factory::getContainer()->get(DatabaseInterface::class);
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
                $db = Factory::getContainer()->get(DatabaseInterface::class);
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
                $db = Factory::getContainer()->get(DatabaseInterface::class);
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
                $db = Factory::getContainer()->get(DatabaseInterface::class);
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
                $db = Factory::getContainer()->get(DatabaseInterface::class);
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
                    $db = Factory::getContainer()->get(DatabaseInterface::class);
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

                $db = Factory::getContainer()->get(DatabaseInterface::class);
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
                $db = Factory::getContainer()->get(DatabaseInterface::class);
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

            $db = Factory::getContainer()->get(DatabaseInterface::class);
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
            $conf = Factory::getApplication()->getConfig();
            $lang = Factory::getApplication()->getLanguage();
            $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');

            //fix for virtuemart / lang must be reset
            if (ComponentHelper::isEnabled('com_virtuemart', true)){
                if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
                VmConfig::loadConfig();
                \vmLanguage::$jSelLangTag = false;
                \vmLanguage::initialise(true);
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
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        return is_a($db, 'JFalangDatabase');
    }


    /*
     * @update 6.1 move the diplay infomessage from constructor to onAfterDispache
     * */
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

        //use for finder content plugin
        if (Factory::getApplication()->isClient('administrator')) {
            $this->displayInfoMessage();
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
        $doc = Factory::getApplication()->getDocument();
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
     * and load the custom fields in the translated form
     *
     * @udpate 5.22 remove the load custom fields in the translated form to falangcf
     */
    function onContentPrepareForm(Form $form, $data)
    {
        if (Factory::getApplication()->isClient('site')){return;}

	    $this->enabledTplTranslation($form,$data);

    }



    /**
     *
     * Enable template by langugage (paid version only)
     *
     * @update 5.14 clean code remove jimport
     *              $this->_subject->setError not working anymaore enqueueMessage
     *
     */
	private function enabledTplTranslation($form, $data){

		$params = ComponentHelper::getParams('com_falang');
		$show_tpl_lang = $params->get('show_tpl_lang');

		if (!isset($show_tpl_lang) || $show_tpl_lang == '0' ) {return;}

		if (!($form instanceof JForm))
		{
            Factory::getApplication()->enqueueMessage('JERROR_NOT_A_FORM',Factory::getApplication()::MSG_ALERT);
            return;
		}
		if ((is_array($data) && array_key_exists('home', $data))
			|| ((is_object($data) && isset($data->home) ))) {
			$form->setFieldAttribute('home', 'readonly', 'false');
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