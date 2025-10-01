<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

namespace Faboba\Module\Falang\Site\Helper;

use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Menus\Administrator\Helper\MenusHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class FalangHelper
{
	public static function getList(&$params)
	{
        $app	= Factory::getApplication();
		$lang   = $app->getLanguage();
		$languages	= LanguageHelper::getLanguages();
		$sitelangs = LanguageHelper::getInstalledLanguages(0);
		$levels = $app->getIdentity()->getAuthorisedViewLevels();

		//use to remove default language code in url
        $lang_codes 	= LanguageHelper::getLanguages('lang_code');
        $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
        $default_sef 	= $lang_codes[$default_lang]->sef;

		$cparams = ComponentHelper::getParams('com_falang');

        $menu = $app->getMenu();
        $active = $menu->getActive();

        //falang 4.9 the clone of the $uri seem no more usefull and fix problem with remidpassword without menu item attached to it
        $router = Factory::getContainer()->get(SiteRouter::class);
        $vars = $router->getVars();

        //On edit mode the flag/name must be disabled

        // Get menu home items
        $homes = array();

        foreach ($menu->getMenu() as $item)
        {
            if ($item->home)
            {
                $homes[$item->language] = $item;
            }
        }

        $assoc =  Associations::isEnabled();

		if ($assoc) {
			if ($active) {
				$associations = MenusHelper::getAssociations($active->id);
			}

            $option = $app->input->get('option');
            $component = $app->bootComponent($option);

            if ($component instanceof AssociationServiceInterface)
            {
                $cassociations = $component->getAssociationsExtension()->getAssociationsForItem();
            }
            else {
            // Load component associations
                $class = str_replace('com_', '', $option) . 'HelperAssociation';
                \JLoader::register($class, JPATH_SITE . '/components/' . $option . '/helpers/association.php');
            if (class_exists($class) && is_callable(array($class, 'getAssociations')))
            {
                //don't load association for eshop , hikashop and Os property /jsshopping
                if ( $class != 'eshopHelperAssociation'
                    && $class != 'hikashopHelperAssociation'
                    && $class != 'eventbookingHelperAssociation'
                    && $class != 'jshoppingHelperAssociation'
                    && $class != 'ospropertyHelperAssociation'){
                    $cassociations = call_user_func(array($class, 'getAssociations'));
                }
            }
		}
		}
   		foreach($languages as $i => &$language) {

		    // Do not display language without frontend UI check user access level
		    if (!array_key_exists($language->lang_code, $sitelangs))
		    {
			    unset($languages[$i]);
		    }

		    // Do not display language without authorized access level
		    if (isset($language->access) && $language->access && !in_array($language->access, $levels))
		    {
			    unset($languages[$i]);
		    }

            $multilang = Multilanguage::isEnabled();

            //set language active before language filter use for sh404 notice
		    $language->active = ($language->lang_code === $lang->getTag());

		    // Fetch language rtl
		    // If loaded language get from current JLanguage metadata
		    if ($language->active)
		    {
			    $language->rtl = $lang->isRtl();
		    }
		    // If not loaded language fetch metadata directly for performance
		    else
		    {
			    $languageMetadata = LanguageHelper::getMetadata($language->lang_code);
			    $language->rtl    = $languageMetadata['rtl'];
		    }


            //since v1.4 change in 1.5 , ex rsform preview don't have active
            //this method don't set display for component association set after
            if (isset($active)){
                $language->display = ($active->language == '*' || $language->active)?true:false;
            } else {
                $language->display = true;
            }

            if (FaLangHelper::isEditMode()){
                $language->display = false;
            }

            if ($multilang) {
                //use component association with menu item translated.
                if (isset($cassociations[$language->lang_code])) {
                    $language->link = Route::_($cassociations[$language->lang_code] . '&lang=' . $language->sef);
                    //fix mijoshop link
					if (isset($_GET['mijoshop_store_id'])) {
						$_link = explode('?', $language->link);
						$language->link = $_link[0];
					}
                    //if association existe for this language display flag.
                    $language->display = true;
                }elseif (isset($associations[$language->lang_code]) && $menu->getItem($associations[$language->lang_code])) {
                    //use menu association.
                    $language->display = true;
                    $itemid = $associations[$language->lang_code];

	                //3.4 Hikashop try to fix on product swither with native joomla menu
	                $extraparams = FaLangHelper::fixHikashopProductSwitch($language,$lang,$default_lang,$vars);

	                $language->link = Route::_('index.php?lang=' . $language->sef . '&Itemid=' . $itemid .$extraparams  );

                }
                else {
                    //sef case
                    if ($app->getCfg('sef')=='1') {

                        if (FaLangHelper::forSefEnabled()){
                            //TODO Check when the following JSite::getRouter(); is removed
                            $router = Factory::getContainer()->get(SiteRouter::class);;
                            $urlvars = $router->getVars();
                            $urlvars['lang'] = $language->sef;
                            unset($urlvars['format']);
                            $url = 'index.php?'.URI::buildQuery($urlvars);
                            $language->link = Route::_($url);
                            continue;
                        }

                         //workaround to fix index language
                         $vars['lang'] = $language->sef;

						//fix for home menu on Joomla 3.7
	                    if (isset($vars['Itemid']) && ($vars['Itemid'] == $homes['*']->id) ){
		                    $language->link = Route::_('index.php?lang=' . $language->sef . '&Itemid=' . $homes['*']->id);
		                    continue;
	                    }

	                    //2.9.0

	                    //look on menu_show for translated language by default is disabled due to extra query
	                    if ($cparams->get('advanced_menu_show',false) && !empty($vars['Itemid']))
	                    {
		                    $menu_show = 1;//default visible

		                    if ($lang->getTag() != $language->lang_code)
		                    {
                                $fManager = \FalangManager::getInstance();
			                    $id_lang  = $fManager->getLanguageID($language->lang_code);
			                    $db       = Factory::getDbo();
			                    // get translated path if exist
			                    $query = $db->getQuery(true);
			                    $query->select('fc.value')
				                    ->from('#__falang_content fc')
				                    ->where('fc.reference_id = ' . (int) $vars['Itemid'])
				                    ->where('fc.language_id = ' . (int) $id_lang)
				                    ->where('fc.reference_field = \'params\'')
				                    ->where('fc.published = 1')
				                    ->where('fc.reference_table = \'menu\'');
			                    $db->setQuery($query);
			                    $translatedParams = $db->loadResult();

			                    $registry = new \Joomla\Registry\Registry();
			                    $registry->loadString($translatedParams);
			                    $menu_show = (int)$registry->get('menu_show','1');

		                    } else {
			                    $_menu        = $menu->getItem($vars['Itemid']);
			                    $_menu_params = $_menu->getParams();
			                    $menu_show    = (int)$_menu_params->get('menu_show','1');
		                    }
		                    if ($menu_show == 0){$language->display = false;}
	                    }
	                    //fin 2.8.45



	                    //since 2.2.1
                        //case of article category view
                        //set the language used to reload category with the right language
                        $jfm = \FalangManager::getInstance();
                        if (!empty($vars['view']) && $vars['view'] == 'category'  && !empty($vars['option']) && $vars['option'] == 'com_content') {
                            if (($language->lang_code != $default_lang) || ($lang->getTag() != $default_lang) ){
                                Categories::$instances = array();
                                $jfm->setLanguageForUrlTranslation($language->lang_code);
                            }
                        }
                        //end since 2.2.1

                        //case of category article
                        //set the language used to reload category with the right language
                        if (!empty($vars['view']) && $vars['view'] == 'article'  && !empty($vars['option']) && $vars['option'] == 'com_content') {

                            //since 2.2.1
                            if (($language->lang_code != $default_lang) || ($lang->getTag() != $default_lang) ){
                                Categories::$instances = array();
                                $jfm->setLanguageForUrlTranslation($language->lang_code);
                            }
                            //end 2.2.1

                            BaseDatabaseModel::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');
                            $model = BaseDatabaseModel::getInstance('Article', 'ContentModel', array('ignore_request'=>true));
                            $appParams = Factory::getApplication()->getParams();


                            $model->setState('params', $appParams);

                            $item = $model->getItem($vars['id']);

                            //get alias of content item without the id , so i don't have the translation
                            //4.0.2 get alias translation for the itemid
                            //4.7 fix alias for category blog article use original if not translated
                            //TODO use a method for this
                            if ($language->lang_code != $default_lang) {

                                $db = Factory::getDbo();
                                $dbQuery = $db->getQuery(true)
                                    ->select('fc.value')
                                    ->from('#__falang_content fc')
                                    ->where('fc.reference_id = ' . (int)$item->id)
                                    ->where('fc.language_id = ' . (int)$jfm->getLanguageID($language->lang_code))
                                    ->where('fc.reference_field = \'alias\'')
                                    ->where('fc.published = 1')
                                    ->where('fc.reference_table = \'content\'');

                                $db->setQuery($dbQuery);
                                $alias = $db->loadResult();
                                if (isset($alias)) {
                                    $vars['id'] = $item->id . ':' . $alias;
                                } else {
                                    //use the alias of the original
                                    $dbQuery = $db->getQuery(true);
                                    $dbQuery->select($dbQuery->qn('alias'))
                                        ->from($dbQuery->qn('#__content' ))
                                        ->where('id = ' . $dbQuery->q($item->id));
                                    $db->setQuery($dbQuery);
                                    $alias = $db->loadResult();
                                    if (isset($alias)) {
                                        $vars['id'] = $item->id . ':' . $alias;
                                    }
                                }
                            } else {
                                //for default langauge get alias from #content without the id to not be override by falang
                            $db = Factory::getDbo();
                            $query = $db->getQuery(true);
                            $query->select('alias')->from('#__content')->where('id = ' . (int) $item->id);
                            $db->setQuery($query);
                            $alias = $db->loadResult();
                                if (isset($alias)) {
                            $vars['id'] = $item->id.':'.$alias;
                                }
                            }
                            $vars['catid'] =$item->catid.':'.$item->category_alias;
                        }

                        //2.9.0
                        //5.5 add the dirty unset on task
                        //    make it compatible J4 change
                        //case of k2 item with specific language set
	                    if (!empty($vars['view']) && $vars['view'] == 'item'  && !empty($vars['option']) && $vars['option'] == 'com_k2') {
                            unset($vars['task']);//task is set in sef on on k2forJ4 why
                            \JLoader::register('K2ModelItem', JPATH_ADMINISTRATOR .'/components/com_k2/models/item.php');
                            $model = \K2Model::getInstance('Item', 'K2Model');
		                    $item = $model->getData();

		                    if ($item->language != '*' && $language->lang_code != $item->language){
			                    $language->display = false;
		                    }
	                    }
						//end 2.9.0

                        //new version 1.5
                        //case for k2 item alias write twice
                        //since k2 v 1.6.9 $vars['task'] don't exist.
                        //v2.2.3 fix for archive notice
                        if (isset($vars['option']) && $vars['option'] == 'com_k2'){
                            if (isset($vars['task']) && isset($vars['id']) && ($vars['task'] == $vars['id'])){
                                unset($vars['id']);
                            }
                        }

                        //new 2.5.0
                        //fix for virtuemart url with showall, limitstart, limit on productsdetail page
                        if (isset($vars['option']) && $vars['option'] == 'com_virtuemart'){
                            if (isset($vars['view']) && $vars['view'] == 'productdetails'){
	                            \vmLanguage::setLanguageByTag($language->lang_code);
	                            unset($vars['showall']);
                                unset($vars['limitstart']);
                                unset($vars['limit']);
                            }
                        }


                        //fix for hikashop url with start on product page
                        //update 5.6 remove all filter var even of non product page
                        if (isset($vars['option']) && $vars['option'] == 'com_hikashop'){
							unset($vars['start']);
                            unset($vars['limitstart']);
                            unset($vars['limit']);
                            unset($vars['filter_Trierpar_1']);
                            unset($vars['product_sort_price--lth']);
						}

                        //fix for OsProperties need to have the l parameter
                        if (isset($vars['option']) && $vars['option'] == 'com_osproperty'){
                            if (isset($vars['task']) && $vars['task'] == 'property_details'){
                                $langcode = $language->lang_code;
                                $prefix = explode("-",$langcode);
                                $prefix = '_'.$prefix[0];
                                $vars['l'] = $prefix;
                            }
                        }

                        //fix for Creative contact form
                        if (isset($vars['option']) && $vars['option'] == 'com_creativecontactform'){
                            if (isset($vars['view']) && $vars['view'] == 'creativecontactform'){
                                unset($vars['form']);
                            }
                        }

                        //fix for VikRentCar // car details
                        if (isset($vars['option']) && $vars['option'] == 'com_vikrentcar'){
                            if (isset($vars['view']) && $vars['view'] == 'cardetails'){
                                unset($vars['orderby']);
                                unset($vars['category_id']);
                                unset($vars['ordertype']);
                                unset($vars['lim']);
                                unset($vars['layoutstyle']);
                            }
                        }

                        $url = 'index.php?'.URI::buildQuery($vars);
                        $language->link = Route::_($url);

                        //since 2.2.1
                        //on restaure les categories pour le cas des liste de categories
                        if (!empty($vars['view']) && $vars['view'] == 'category'  && !empty($vars['option']) && $vars['option'] == 'com_content') {
                            if (($language->lang_code != $default_lang) || ($lang->getTag() != $default_lang)) {
                                Categories::$instances = array();
                                $jfm->setLanguageForUrlTranslation(null);
                            }
                        }

                        if (!empty($vars['view']) && $vars['view'] == 'article'  && !empty($vars['option']) && $vars['option'] == 'com_content') {

                            if (($language->lang_code != $default_lang) || ($lang->getTag() != $default_lang)) {
                                Categories::$instances = array();
                                $jfm->setLanguageForUrlTranslation(null);
                            }
                        }
                        //end 2.2.1


                        //TODO check performance 3 queries by languages -1
                        /**
                         * Replace the slug from the language switch with correctly translated slug.
                         * $language->lang_code language de la boucle (icone lien)
                         * $lang->getTag() => language en cours sur le site
                         * $default_lang langue par default du site
                         */
                        if($lang->getTag() != $language->lang_code && !empty($vars['Itemid']))
                        {
                            $fManager = \FalangManager::getInstance();
                            $id_lang = $fManager->getLanguageID($language->lang_code);

                            //get translated path form existing falang menu item translation
                            $translatedPath = FaLangHelper::getTranslatedPathFromMenuItem($vars['Itemid'],$id_lang);

                            //not exist simulate the build like when we create it on backend
                            //always return a value
                            if (empty($translatedPath)){
                                $translatedPath = FaLangHelper::getMenuPath('',$vars['Itemid'],$id_lang);
                            }

                            // $translatedPath not exist if not translated or site default language
                            // don't pass id to the query , so no translation given by falang
                            $db = Factory::getDbo();
                            $query = $db->getQuery(true);
                            $query->select('m.path')
                                ->from('#__menu m')
                                ->where('m.id = '.(int)$vars['Itemid']);
                            $db->setQuery($query);
                            $originalPath = $db->loadResult();

                            $pathInUse = null;
                            //si on est sur une page traduite on doit récupérer la traduction du path en cours
                            $id_lang = $fManager->getLanguageID($lang->getTag());
                            $pathInUse = FaLangHelper::getTranslatedPathFromMenuItem($vars['Itemid'],$id_lang);
                            if (empty($pathInUse)){
                                $pathInUse = FaLangHelper::getMenuPath('',$vars['Itemid'],$id_lang);
                            }

                            //make replacement in the url
                            //si language de boucle et language site
                            if($language->lang_code == $default_lang) {
                                if (isset($pathInUse) && isset($originalPath)){
                                    $language->link = str_replace($pathInUse, $originalPath, $language->link);
                                }
                            } else {
                                if (isset($pathInUse) && isset($translatedPath)){
                                    $language->link = str_replace($pathInUse, $translatedPath, $language->link);
                                }
                            }

                        }
                    }
                    //default case
             else
             {
	             //fix 2.8.4
	             // bug when menu item translation don't link to the same item or type
	             // need to load the path of the ItemId
	             //si on est sur une page traduite on doit récupérer la traduction du path en cours
	             $uri = Uri::getInstance();
	             $uri->setVar('lang', $language->sef);

	             $input = Factory::getApplication()->input;
	             $vars = $input->getArray();

	             //set language link
	             $language->link = FaLangHelper::getLinkWithoutSefEnabled($language, $lang, $default_lang, $vars);

	             //v2.9.0
	             //for specific language item on content
	             //set display to false exept for the display language and for associated item.
	             if ($uri->getVar('view') == 'article' && $uri->getVar('option') == 'com_content')
	             {
                     BaseDatabaseModel::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');
		             $model = BaseDatabaseModel::getInstance('Article', 'ContentModel', array('ignore_request'=>true));
		             $appParams = Factory::getApplication()->getParams();
		             $model->setState('params', $appParams);
		             $item = $model->getItem($uri->getVar('id'));

		             if ($item->language != '*' && $language->lang_code != $item->language)
		             {
			             $language->display = false;
		             }
	             }//end 2.9.0

	             //2.9.0
	             //case of k2 item with specific language set
	             if ($uri->getVar('view') == 'item'  && $uri->getVar('option') == 'com_k2') {
                     BaseDatabaseModel::addIncludePath(JPATH_SITE.'/components/com_k2/models', 'K2Model');
		             $model = BaseDatabaseModel::getInstance('Item', 'K2Model', array('ignore_request'=>true));
		             $item = $model->getData();

		             if ($item->language != '*' && $language->lang_code != $item->language){
			             $language->display = false;
		             }
	             }//end 2.9.0



	             //fix problem on mod_login (same position before falang module
	             Uri::reset();
             }//end sef
                }
            }
            //no language filter published
            else {
                $language->link = 'index.php';
            }

		}
		return $languages;
	}

    public static function isFalangDriverActive() {
        $db = Factory::getDBO();
        if (!is_a($db,"JFalangDatabase")){
           return false;
        }
           return true;
    }

    /*
     * @since 4.0.2
     * Detect Edit mode for article or yootheme
     *
     * @update 5.22 change return for yootheme customiser
     *              allow language changing
     * */
    public static function isEditMode(){
        $return = false;
        $layout = Factory::getApplication()->input->get('layout');
        if ($layout == 'edit'){
            $return =  true;
        }
        //customizer is for yootheme
        $customizer = Factory::getApplication()->input->get('customizer');
        if (!empty($customizer)){
            $return =  false;
        }

        return $return;
    }

    /**
     * @since 4.14 add 4sef suport
     * @return true|void
     */
    public static function forSefEnabled() {
        $forSeffilename =  JPATH_PLUGINS. '/system/forsef/vendor/weeblr/forsef/api.php';
        if (File::exists($forSeffilename)) {
            if (\Forsef::isEnabled())
            {
                return true;
            }
        }
    }

	/**
	 *
	 * New method to build link for non sef url when the translated url don't link to the same item
	 * $language language in the loop of flang
	 * $lang Language displayed on the site
	 * $default_lang site default langue
	 *
	 * @since version 2.8.4
	 */
    public static function getLinkWithoutSefEnabled($language,$lang,$default_lang,$vars)
    {

	    //workaround to fix index language
	    if (empty($vars['lang'])){
		    $vars['lang'] = $language->sef;
	    }

	    $link = null;

	    //build link for a language look not for the page language displayed
	    if (($lang->getTag() != $language->lang_code) && !empty($vars['Itemid']))
	    {
		    $fManager = \FalangManager::getInstance();
		    $id_lang  = $fManager->getLanguageID($language->lang_code);
		    $db       = Factory::getDbo();
		    // get translated path if exist
		    $query = $db->getQuery(true);
		    $query->select('fc.value')
			    ->from('#__falang_content fc')
			    ->where('fc.reference_id = ' . (int) $vars['Itemid'])
			    ->where('fc.language_id = ' . (int) $id_lang)
			    ->where('fc.reference_field = \'link\'')
			    ->where('fc.published = 1')
			    ->where('fc.reference_table = \'menu\'');
		    $db->setQuery($query);
		    $translatedPath = $db->loadResult();

		    // $translatedPath not exist if not translated or site default language
		    // don't pass id to the query , so no translation given by falang
		    $query = $db->getQuery(true);
		    $query->select('m.link')
			    ->from('#__menu m')
			    ->where('m.id = ' . (int) $vars['Itemid']);
		    $db->setQuery($query);
		    $originalPath = $db->loadResult();

            //v3.4.4
            //fix case for item linked to a blog menu and display submenu (the itemId is releated to the blog menu
            //not item
            if (($originalPath == $translatedPath) || empty($translatedPath)){
                return Uri::getInstance()->toString(array('scheme', 'host', 'port', 'path', 'query'));
            }

		    //si language de boucle et language site
		    if (isset($translatedPath)){
		    	$link = $translatedPath;
		    } else {
		    	$link = $originalPath;
		    }
		    $link = $link . '&Itemid=' . (int) $vars['Itemid'] . '&lang=' . $language->sef;

		    return $link;
	    }
	    return Uri::getInstance()->toString(array('scheme', 'host', 'port', 'path', 'query'));
    }

    /**
     *
     * get the extra param's to build the route for hikahsop product page with default joomal menu
     * $loop_language language in the loop of flang
     * $site_language Language tag displayed on the site
     * $default_language site default language
     * $vars
     *
     * @since version 3.4
     */
    public static function fixHikashopProductSwitch($loop_language,$site_language,$default_lang,$vars){
        $name ='';
        if (isset($vars['option']) && $vars['option'] == 'com_hikashop') {
            if (isset($vars['ctrl']) && $vars['ctrl'] == 'product' && isset($vars['task'])  && $vars['task'] == 'show') {
                if (isset($vars['cid'])) {
                    //si langue deu site la variable name est la bonne
                    if ($loop_language == $site_language){
                        $name = $vars['name'];
                    }
                    // si pas la langue par default traduction dans falang
                    elseif ( $default_lang != $loop_language->lang_code ) {
                        $fManager = \FalangManager::getInstance();
                        $id_lang  = $fManager->getLanguageID( $loop_language->lang_code );
                        $db       = Factory::getDbo();
                        $dbQuery  = $db->getQuery( true )
                            ->select( 'fc.value' )
                            ->from( '#__falang_content fc' )
                            ->where( 'fc.reference_id = ' . (int) $vars['cid'] )
                            ->where( 'fc.language_id = ' . (int) $id_lang )
                            ->where( 'fc.reference_field = \'product_alias\'' )
                            ->where( 'fc.published = 1' )
                            ->where( 'fc.reference_table = \'hikashop_product\'' );

                        $db->setQuery( $dbQuery );
                        $alias = $db->loadResult();
                        if ( isset( $alias ) ) {
                            $name = $alias;
                        }

                    }

                    //pas de traduction et pas de langue du site affiché
                    //on prends l'alias du produit
                    if (empty($name)){
                        // translated languague look in native table
                        $db      = Factory::getDbo();
                        $dbQuery = $db->getQuery( true )
                            ->select( 'product_alias' )
                            ->from( '#__hikashop_product' )
                            ->where( 'product_id = ' . (int) $vars['cid'] );
                        $db->setQuery( $dbQuery );
                        $alias = $db->loadResult();
                        if ( isset( $alias ) ) {
                            $name = $alias;
                        }

                    }

                    return '&cid='.$vars['cid'].'&name='.$name.'&ctrl=product';
                }

            }

        }


        return $name;

    }

    public static function getTranslatedPathFromMenuItem($ItemID,$idLang){
        $db = Factory::getDbo();
        // get translated path if exist
        $query = $db->getQuery(true);
        $query->select('fc.value')
            ->from('#__falang_content fc')
            ->where('fc.reference_id = '.$ItemID)
            ->where('fc.language_id = '.(int) $idLang )
            ->where('fc.reference_field = \'path\'')
            ->where('fc.published = 1')
            ->where('fc.reference_table = \'menu\'');
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    //copy from administrator/components/com_falang/models/ContentObject.php
    public static function getMenuPath($alias,$reference_id,$lang_id)
    {

        $table = Table::getInstance("Menu");
        // TODO get this from the translation!

        $table->load($reference_id);
        // Get the path from the node to the root (translated)
        $db     = Factory::getDBO();
        $query  = $db->getQuery(true);
        $select = 'p.*, jfc.value as jfcvalue';
        $query->select($select);
        $query->from('#__menu AS n, #__menu AS p');
        $query->join('left', "#__falang_content as jfc ON jfc.reference_table='menu' AND jfc.reference_id=p.id AND jfc.language_id='$lang_id' and jfc.reference_field='alias' ");
        $query->where('n.lft BETWEEN p.lft AND p.rgt');
        $query->where('n.id = ' . (int) $reference_id);
        $query->where('p.client_id = 0');
        $query->order('p.lft');

        $db->setQuery($query);
        $sql       = (string) $db->getQuery();
        $pathNodes = $db->loadObjectList('', 'stdClass', false);

        $segments = array();
        foreach ($pathNodes as $node)
        {
            // Don't include root in path
            if ($node->alias != 'root')
            {
                //we don't use the alias stored for this translation only the alias posted.
                if (isset($node->jfcvalue) && ($node->id != $reference_id))
                {
                    $segments[] = $node->jfcvalue;
                }
                else
                {
                    //use the alias value from post directly and not the node alias if not empty
                    if (($node->id == $reference_id) && !empty($alias))
                    {
                        $segments[] = $alias;
                    }
                    else
                    {
                        $segments[] = $node->alias;
                    }
                }
            }
        }
        $newPath = trim(implode('/', $segments), ' /\\');
        return $newPath;
    }
}
