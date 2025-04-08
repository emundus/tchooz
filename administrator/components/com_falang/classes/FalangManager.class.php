<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\LanguageHelper;

defined('_JEXEC') or die;

class FalangManager {

	public static $instance = null;

	protected static $languageForUrlTranslation = null;

	/** @var array of all known content elements and the reference to the XML file */
	var $_contentElements;

	/** @var string Content type which can use default values */
	var $DEFAULT_CONTENTTYPE="content";

	/** @var config Configuration of the map */
	var $_config=null;

	/** @var Component config */
	var $componentConfig= null;

	/**	PrimaryKey Data */
	var $_primaryKeys = null;

	/** @var array for all system known languages */
	var $allLanguagesCache=array();

	/** @var array for all languages listed by shortcode */
	var $allLanguagesCacheByShortcode=array();

	/** @var array for all languages listed by ID */
	var $allLanguagesCacheByID=array();

	/** @var array for all active languages */
	var $activeLanguagesCache=array();

	/** @var array for all active languages listed by shortcode */
	var $activeLanguagesCacheByShortcode=array();

	/** @var array for all active languages listed by ID */
	var $activeLanguagesCacheByID=array();

    var $_cache = null;//sbou5

	/** Standard constructor
     *
     * @update 5.16 remove all old language system
     */
	public function __construct(){

		include_once(FALANG_ADMINPATH .DS. "models".DS."ContentElement.php");

		// now redundant
		$this->_loadPrimaryKeyData();

		// Must get the config here since if I do so dynamically it could be within a translation and really mess things up.
		$this->componentConfig = ComponentHelper::getParams( 'com_falang' );
	}

	//Since Falang 2.2.2
	//method use to set a language to be used during the translation loading
	public static function setLanguageForUrlTranslation($language=null){
		self::$languageForUrlTranslation = $language;
	}

	//Since Falang 2.2.2
	//method use to get a language to be used during the translation loading
	public static function getLanguageForUrlTranslation(){
		return self::$languageForUrlTranslation;
	}


	public static function getInstance($adminPath=null){
		if (!self::$instance) {
			self::$instance = new FalangManager($adminPath);
		}
		return self::$instance;
	}

	public static function setBuffer()
	{
		$doc = Factory::getDocument();
		$cacheBuf = $doc->getBuffer('component');

		$cacheBuf2 =
			'<div><a title="Faboba : Cr&eacute;ation de composant'.
			'Joomla" style="font-size: 8px;; visibility: visible;'.
			'display:inline;" href="http://www.faboba'.
			'.com" target="_blank">FaLang tra'.
			'nslation syste'.
			'm by Faboba</a></div>';

		if ($doc->_type == 'html')
			$doc->setBuffer($cacheBuf . $cacheBuf2,'component');

	}

	/**
	 * Load Primary key data from database
	 *
	 */
	function _loadPrimaryKeyData() {
		if ($this->_primaryKeys==null){
			$db = Factory::getDBO();
			$db->setQuery( "SELECT joomlatablename,tablepkID FROM `#__falang_tableinfo`");
			//sbou TODO pass false to skip translation
			//TODO verify how to skip translation
			//$rows = $db->loadObjectList("",false);
			$rows = $db->loadObjectList();
			//fin sbou
			$this->_primaryKeys = array();
			if( $rows ) {
				foreach ($rows as $row) {
					$this->_primaryKeys[$row->joomlatablename]=$row->tablepkID;
				}
			}

		}
	}

	/**
	 * Get primary key given table name
	 *
	 * @param string $tablename
	 * @return string primarykey
	 */
	function getPrimaryKey($tablename){
		if ($this->_primaryKeys==null) $this->_loadPrimaryKeyData();
		if (array_key_exists($tablename,$this->_primaryKeys)) return $this->_primaryKeys[$tablename];
		else return "id";
	}

	/**
	 * Loading of related XML files
	 *
	 * TODO This is very wasteful of processing time so investigate caching some how
	 * built in Joomla cache will not work because of the class structere of the results
	 * we get lots of incomplete classes from the unserialisation
	 */
	function _loadContentElements() {
		// XML library

		// Try to find the XML file
		jimport('joomla.filesystem.folder');
		$filesindir = Folder::files(FALANG_ADMINPATH ."/contentelements" ,".xml");
		if(count($filesindir) > 0)
		{
			$this->_contentElements = array();
			foreach($filesindir as $file)
			{
				unset($xmlDoc);
				$xmlDoc = new DOMDocument();
				if ($xmlDoc->load(FALANG_ADMINPATH . "/contentelements/" . $file)) {
					$element = $xmlDoc->documentElement;
					if ($element->nodeName == 'falang') {
						if ( $element->getAttribute('type')=='contentelement' ) {
							$nameElements = $element->getElementsByTagName('name');
							$nameElement = $nameElements->item(0);
							$name = strtolower( trim($nameElement->textContent) );
							$contentElement = new ContentElement( $xmlDoc );
							$this->_contentElements[$contentElement->getTableName()] = $contentElement;
						}
					}
				}
			}
		}
	}

	/**
	 * Loading of specific XML files
	 */
	function _loadContentElement($tablename) {
		if (!is_array($this->_contentElements)){
			$this->_contentElements = array();
		}
		if (array_key_exists($tablename,$this->_contentElements)){
			return;
		}

		$file = FALANG_ADMINPATH .'/contentelements/'.$tablename.".xml";
		if (file_exists($file)){
			unset($xmlDoc);
			$xmlDoc = new DOMDocument();
			if ($xmlDoc->load( $file)) {
				$element = $xmlDoc->documentElement;
				if ($element->nodeName == 'falang') {
					if ( $element->getAttribute('type')=='contentelement' ) {
						$nameElements = $element->getElementsByTagName('name');
						$nameElement = $nameElements->item(0);
						$name = strtolower( trim($nameElement->textContent) );
						$contentElement = new ContentElement( $xmlDoc );
						$this->_contentElements[$contentElement->getTableName()] = $contentElement;
						return $contentElement;
					}
				}
			}
		}
		return null;
	}

	/**
	 * Method to return the content element files
	 *
	 * @param boolean $reload	forces to reload the element files
	 * @return unknown
	 */
	function getContentElements( $reload=false ) {
		if( !isset( $this->_contentElements ) || $reload ) {
			$this->_loadContentElements();
		}
		return $this->_contentElements;
	}

	/** gives you one content element
	 * @param	key 	of the element
	 */
	function getContentElement( $key ) {
		$element = null;
		if( isset($this->_contentElements) &&  array_key_exists( strtolower($key), $this->_contentElements ) ) {
			$element = $this->_contentElements[ strtolower($key) ];
		}
		else {
			$element = $this->_loadContentElement($key);
		}
		return $element;
	}

	/**
	 * @param string The name of the variable (from configuration.php)
	 * @return mixed The value of the configuration variable or null if not found
	 */
	function getCfg( $varname , $default=null) {
		// Must not get the config here since if I do so dynamically it could be within a translation and really mess things up.
		return $this->componentConfig->get($varname,$default);
	}

	/**
	 * @param string The name of the variable (from configuration.php)
	 * @param mixed The value of the configuration variable
	 */
	function setCfg( $varname, $newValue) {
		$config = ComponentHelper::getParams( 'com_falang' );
		$config->set($varname, $newValue);
	}

	/** Creates an array with all languages for the Falang
	 *
	 * @param boolean	indicates if those languages must be active or not
	 * @return	Array of languages
	 */
	function getLanguages( $active=true ) {
        return LanguageHelper::getContentLanguages($active);
	}

	/**
	 * Fetches full langauge data for given shortcode from language cache
	 *
	 * @param array()
	 */
	function getLanguageByShortcode($shortcode, $active=false){
        $result = LanguageHelper::getContentLanguages($active,true,'sef');
        return $result[$shortcode];
	}

	/**
	 * Fetches full langauge data for given code from language cache
	 *
	 * @param array()
	 */
	function getLanguageByCode($code, $active=false){
        $result = LanguageHelper::getContentLanguages($active,true,'lang_code');
        return $result[$code];
	}

	/**
	 * Fetches full langauge data for given ID from language cache
     * @update 5.16 use native language system cache done by Joomla
	 *
	 * @param array()
	 */
	function getLanguageByID($id){
        $result  = LanguageHelper::getContentLanguages([],true,'lang_id');
        return $result[$id];
	}


    /*
     *
	 * Fetches full langauge data for given code from language cache
     * @update 5.16 use native language system cache done by Joomla
     * */
	function getLanguagesIndexedByCode($active=false){
        $result =LanguageHelper::getContentLanguages($active,true,'lang_code');
        return $result;
	}

	function getLanguagesIndexedById($active=false){
        $result =LanguageHelper::getContentLanguages($active,true,'lang_id');
        return $result;
	}

	/** Retrieves the language ID from the given language name
	 *
	 * @param	string	Code language Tag (ex: en-GB,fr-FR)
	 * @return	int 	Database id of this language
	 */
	function getLanguageID( $langCode="" ) {
        $result =LanguageHelper::getContentLanguages([],true,'lang_code');
        return $result[$langCode]->lang_id;
	}

	public function getRawFieldTranslations($reftable,$reffield, $refids, $language,$only_publised = true)
	{

		static $cache = array();

		$hash = md5(json_encode([$reftable,$reffield, $refids, $language]));

		if (!isset($cache[$hash])) {
			$db      = Factory::getDbo();
			$dbQuery = $db->getQuery(true)
				->select($db->quoteName('value'))
				->from('#__falang_content fc')
				->where('fc.reference_id = ' . $db->quote($refids))
				->where('fc.language_id = ' . (int) $language)
				->where('fc.reference_field = ' . $db->quote($reffield))
				->where('fc.reference_table = ' . $db->quote($reftable));

            if ($only_publised){
                $dbQuery->where('fc.published = 1');
            }

			$db->setQuery($dbQuery);
			$result  = $db->loadResult();

			//$cache[$hash] don't like null value
            if (!empty($result)){
	           $cache[$hash] = $result;
            } else {
	           $cache[$hash] = '';
            }

		}

		return $cache[$hash];
	}

	public function getRawFieldOrigninal($refid)
	{
		$db      = Factory::getDbo();
		$dbQuery = $db->getQuery(true)
			->select($db->quoteName(array('field_id', 'value')))
			->from('#__fields_values')
			->where('item_id = ' . $db->quote($refid));

		$db->setQuery($dbQuery);

		$myarray = $db->loadObjectList();
		$pkey    = null;

		$result = array();

		foreach ($myarray as $key => $item)
		{
			if ($pkey != $item->field_id)
			{
				$result[$item->field_id] = $item->value;
			}
			else
			{
				//multiple item , need to be transformed as array
				if (!is_array($result[$item->field_id]))
				{

					$first_item              = $result[$item->field_id];
					$result[$item->field_id] = array($first_item, $item->value);

				}
				else
				{
					array_push($result[$item->field_id], $item->value);
				}
			}
			$pkey = $item->field_id;
		}

		return $result;
	}

    /*
     * @since 3.10.4/4.0.6 use native Joomla upate system
     * */
    public static function getUpdateInfo($force = false)
    {
        /** @var Updates $updateModel */
        require_once JPATH_ADMINISTRATOR.'/components/com_falang/classes/Update.php';
        $config = ['update_component' => 'pkg_falang'];
        $updateModel = new Falang\Update($config);
        $updateInfo = $updateModel->getUpdates($force);
        $updateInfo['current_version'] = $updateModel->getVersion();

        if (!$updateInfo['hasUpdate']){
            $updateInfo['version'] = $updateModel->getVersion();
        }

        return (object)$updateInfo;
    }
}
