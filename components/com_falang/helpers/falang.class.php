<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
  */

// ensure this file is being included by a parent file
defined( '_JEXEC' ) or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

class Falang {

	/**
	 * Translates a list based on cached values
	 * @param array $rows
	 * @param Language $language
	 * @param array $tableArray
	 */
	public function translateListCached ($rows,  $language , $tableArray) {
		Falang::translateList($rows,  $language , $tableArray);
		return $rows;
	}


	/**
	 * Translates a list of items
	 * @param array $rows
	 * @param Language $language
	 * @param array $tableArray
	 */
	public static function translateList( &$rows, $language , $tableArray) {
		//sbou4
		if (!isset($rows) || !is_array($rows)) return $rows;
		$jfManager = FalangManager::getInstance();
		$registry = Factory::getConfig();

		$defaultLang = $registry->get("language");

		$db = Factory::getDBO();
        $tempsql = $db->getQuery(false);
        //sbou TODO test null ?
        if (is_a($tempsql,'JDatabaseQueryMySQLi') || is_a($tempsql,'JDatabaseQueryMySQL') ) {

           $querySQL = $tempsql->__toString();
        } else {
           $querySQL = $tempsql;
        }

                //fin sbou
		// do not try to translate if I have no fields!!!
		if (!isset($tableArray) || count($tableArray)==0) {
			//echo "$tableArray $querySQL<br>";
			return;
		}
		// If I write content in non-default language then this skips the translation!
		//if($language == $defaultLang) return $rows;
		$rowsLanguage = $language;
		if (count($rows)>0){
            //change Falang 1.3.3 : bug on adsmanager need to loop on each alias not only one time
			//foreach ($tableArray["fieldTablePairs"] as $key=>$value){
                //$reftable = $tableArray["fieldTablePairs"][$key];
                //$alias = $tableArray["tableAliases"][$reftable];
            foreach ($tableArray["reverseTableAliases"] as $alias=>$reftable){


				// If there is not translated content for this table then skip it!
				if (!$db->translatedContentAvailable($reftable)) continue;

				// get primary key for tablename
				$idkey = $jfManager->getPrimaryKey( trim($reftable) );

				// I actually need to check the primary key against the alias list!

				for ($i=0;$i<$tableArray["fieldCount"];$i++){
					if (!array_key_exists($i,$tableArray["fieldTableAliasData"])) continue;
					// look for fields from the correct table with the correct name
					if (($tableArray["fieldTableAliasData"][$i]["tableName"]==$reftable) &&
					($tableArray["fieldTableAliasData"][$i]["fieldName"]==$idkey)
					&&  ($tableArray["fieldTableAliasData"][$i]["tableNameAlias"]==$alias)){
						$idkey=$tableArray["fieldTableAliasData"][$i]["fieldNameAlias"];
						break;
					}
				}


				// NASTY KLUDGE TO DEAL WITH SQL CONSTRUCTION IN contact.php, weblinks.php where multiple tables to be translated all use "id" which gets dropped! etc.
				if ($reftable=='categories' && isset($content->catid) && $content->catid>0) {
					$idkey = "catid";
				}
				//if ($reftable=='sections' && count($rows)>0 && isset($content->sectionid) && $content->sectionid>0) {
				//	$idkey = "sectionid";
				//}
				$idstring = "";
				$idlist = array(); // temp variable to make sure all ids in idstring are unique (for neatness more than performance)
				foreach( array_keys( $rows) as $key ) {
					$content = $rows[$key];


					if (isset($content->$idkey) && !in_array($content->$idkey,$idlist)) {
						//print ($idkey ." ".$content->$idkey." list<br>");
						$idstring .= (strlen( $idstring)>0?",":""). $content->$idkey;
						$idlist[] = $content->$idkey;
					}
				}
				if (strlen( $idstring)==0) continue;

				Falang::translateListWithIDs( $rows, $idstring, $reftable, $language , $tableArray, $querySQL, $idkey);
			}
		}
	}

	/**
	 * Function to translate a section object
	 * @param array $rows
	 * @param array $ids
	 * @param string $reference_table
	 * @param JFLanguage $language
	 * @param array $tableArray
	 * @param string $querySQL
     * @param string $refTablePrimaryKey
	 * @param boolean $allowfallback
     *
     * since 4.0 change method signature parameters order
     *       4.5 change onBeforeTranslation parameters order
     *           change onAfterTranslation parameters order
     *       4.11 add onBeforeFieldTranslation trigger
     * @update 5.4 use main query without alias to allow tables locking
     *             rewrite this query
	 */
	public static function translateListWithIDs( &$rows, $ids, $reference_table, $language, & $tableArray, $querySQL, $refTablePrimaryKey="id", $allowfallback=true )
	{
        $params = ComponentHelper::getParams('com_falang');

		//v2.2.1
		//use this to translate categories routes
		if ($reference_table == 'categories'){
			$jfm = FalangManager::getInstance();
			$tmpLang = $jfm::getLanguageForUrlTranslation();
			if (!empty($tmpLang)){
				$language = $tmpLang;
			}
		}

        //if we use association we have to remove the :alias in the id's
        //new since 1.3.3
		// JLanguageAssociations::isEnabled() only since Joomla 3.2
        $pos = strpos($ids,':');
        if ($pos !== false){return;}

        if (!isset($language) || $language == '') {
            $lang = Factory::getLanguage();
            $language = $lang->getTag();
       }


		$db = Factory::getDBO();

		// setup falang pluginds
		PluginHelper::importPlugin('falang');

		if ($reference_table == "falang_content" ) {
			return;					// I can't translate myself ;-)
		}

		$results = Factory::getApplication()->triggerEvent('onBeforeTranslation', array (&$rows, &$ids, $reference_table, $language, & $tableArray, $querySQL, $refTablePrimaryKey, $allowfallback));

		// if onBeforeTranslation has cleaned out the list then just return at this point
		if (strlen($ids)==0) return ;

		// find reference table alias
		$reftableAlias = $reference_table;
		for ($i=0;$i<$tableArray["fieldCount"];$i++){
			if (!array_key_exists($i,$tableArray["fieldTableAliasData"])) continue;
			if ($tableArray["fieldTableAliasData"][$i]["tableName"]==$reference_table &&
			$tableArray["fieldTableAliasData"][$i]["fieldNameAlias"]==$refTablePrimaryKey ){
				$reftableAlias = $tableArray["fieldTableAliasData"][$i]["tableNameAlias"];
				break;
			}
		}

		// NASTY KLUDGE TO DEAL WITH SQL CONSTRUCTION IN contact.php, weblinks.php where multiple tables to be translated all use "id" which gets dropped! etc.
		$currentRow = current($rows);
		// must not check on catid>0 since this would be uncategorised items
		if ($reference_table=='categories' && count($rows)>0 && isset($currentRow->catid) ) {
			$reftableAlias = $tableArray["tableAliases"]["categories"];
		}
		if ($reference_table=='sections' && count($rows)>0 && isset($currentRow->sectionid)) {
			$reftableAlias = $tableArray["tableAliases"]["sections"];
		}

        if ($params->get('debug')) {
			echo "<p><strong>Falang debug (new):</strong><br>"
			. "reference_table=$reference_table<br>"
			. "$refTablePrimaryKey  IN($ids)<br>"
			. "language=$language<br>"
			.(count($rows)>0? "class=" .get_class(current($rows)):"")
			. "</p>";
		}

		static $languages;
		if (!isset($languages)){
			$jfm = FalangManager::getInstance();
			$languages = $jfm->getLanguagesIndexedByCode();
		}

		// process fallback language
        //since 5.16 the fallback is no more in the language
		$fallbacklanguage = false;
		$fallbackrows=array();
		$idarray = explode(",",$ids);
		$fallbackids=array();
//		if (isset($languages[$language]) && $languages[$language]->fallback_code!="") {
//			$fallbacklanguage = $languages[$language]->fallback_code;
//			if (!array_key_exists($fallbacklanguage, $languages)){
//				$allowfallback=false;
//			}
//		}
		if (!$fallbacklanguage) {
			$allowfallback=false;
		}

		if (isset($ids) && $reference_table!='') {
            $query = $db->getQuery(true);
            $query->select('reference_field, value, reference_id, original_value')
                ->from($db->quoteName('#__falang_content'))
                ->where($db->quoteName('language_id').'='.$languages[$language]->lang_id)
                ->where($db->quoteName('published').'= 1')
                ->where($db->quoteName('reference_id').'IN ('.$ids.')')
                ->where($db->quoteName('reference_table').'= '.$db->quote($reference_table));

            $db->setQuery($query);

            $translations = $db->loadObjectList('', 'stdClass', false);
			if (count($translations)>0){
				$fieldmap = null;
				foreach( array_keys( $rows) as $key ) {
					$row_to_translate = $rows[$key];
					$rowTranslationExists=false;
					//print_r ($row_to_translate); print"<br>";
					if( isset( $row_to_translate->$refTablePrimaryKey ) ) {
						foreach ($translations as $row){
							if ($row->reference_id!=$row_to_translate->$refTablePrimaryKey) continue;
							// TODO - consider building array for refFields.  Some queries may have multiple aliases e.g. SELECT a.*, a.field as fieldalias
							$refField = $row->reference_field;
							// adjust refField for aliases (make sure the field is from the same table!).
							// I could reduce the calculation by building an array of translation reference fields against their mapping number
							// but this refinement can wait!

							$fieldmatch=false; // This is used to confirm the field is from the correct table
							for ($i=0;$i<$tableArray["fieldCount"];$i++){
								if (!array_key_exists($i,$tableArray["fieldTableAliasData"])) continue;
									// look for fields from the correct table with the correct name
									if ($tableArray["fieldTableAliasData"][$i]["tableName"]==$reference_table &&
									$tableArray["fieldTableAliasData"][$i]["fieldName"]==$refField &&
									$tableArray["fieldTableAliasData"][$i]["tableNameAlias"] == $reftableAlias){
										$refField=$tableArray["fieldTableAliasData"][$i]["fieldNameAlias"];
										$fieldmatch=true;
										break;
									}
							}
							$fieldIndex = $i;
							if ($fieldmatch && isset( $row->reference_id)  && $row->reference_id==$row_to_translate->$refTablePrimaryKey && $fieldIndex<=$tableArray["fieldCount"]){
								if (is_subclass_of($row_to_translate, 'mosDBTable')) {
									$row_to_translate->set($row->reference_field, $row->value);
								} else {
                                    // Trigger the before field translation event.
                                    $eventResult = Factory::getApplication()->triggerEvent('onBeforeFieldTranslation', array ($row_to_translate,$row, $language,$reference_table));
                                    if (!in_array(false,$eventResult,true)){
                                        $row_to_translate->$refField = $row->value;
                                    }
								}
								$rowTranslationExists=true;
								//print_r( $row_to_translate);print"<br>";
							}
						}
						if (!$rowTranslationExists){
							if ($allowfallback && isset($rows[$key]->$refTablePrimaryKey)){
								$fallbackrows[$key] = $rows[$key];
								$fallbackids[$key] = $rows[$key]->$refTablePrimaryKey;
							}
							else {
								Factory::getApplication()->triggerEvent('onMissingTranslation', array (&$row_to_translate, $language,$reference_table, $tableArray, $querySQL));
							}
						}
					}
				}
			}
			else {
				foreach( array_keys( $rows ) as $key ) {
					if ($allowfallback && isset($rows[$key]->$refTablePrimaryKey)){
						$fallbackrows[$key] = $rows[$key];
						$fallbackids[$key] = $rows[$key]->$refTablePrimaryKey;
					}
					else {
						//sbou4
						$results = Factory::getApplication()->triggerEvent('onMissingTranslation', array (&$rows[$key], $language,$reference_table, $tableArray, $querySQL));
					}
				}
			}


			if ($allowfallback && count($fallbackrows)>0 ){
				$fallbackids = implode($fallbackids,",");
				Falang::translateListWithIDs( $fallbackrows, $fallbackids, $reference_table, $fallbacklanguage,  $tableArray,$querySQL, $refTablePrimaryKey, false);
			}

			Factory::getApplication()->triggerEvent('onAfterTranslation', array (&$rows, $ids, $reference_table, $language, $tableArray, $refTablePrimaryKey, $querySQL, $allowfallback));
		}
	}

	/**
	 * Cached extraction of content element field information
	 * this cached version is shared between pages and hence makes a big improvement to load times
	 * for newly visited pages in a cached scenario
	 *
	 * @param string $reference_table
	 * @return value
	 */
	public static function contentElementFields($reference_table){
		static $info;
		if (!isset($info)){
			$info = array();
		}
		if (!isset($info[$reference_table])){
			$cacheDir = JPATH_CACHE;
			$cacheFile = $cacheDir."/".$reference_table."_cefields.cache";
			if (file_exists($cacheFile)){
				$cacheFileContent = file_get_contents($cacheFile);
				$info[$reference_table] = unserialize($cacheFileContent);
			}
			else {
				$jfm = FalangManager::getInstance();
				$contentElement = $jfm->getContentElement( $reference_table );
				// The language is not relevant for this function so just use the current language
				$registry = Factory::getConfig();
				$lang = $registry->get("config.jflang");

				include_once( JPATH_ADMINISTRATOR.DS."components".DS."com_falang".'/models/ContentObject.php');
				$contentObject = new ContentObject( $jfm->getLanguageID($lang), $contentElement );
				$textFields = $contentObject->getTextFields();
				$info[$reference_table]["textFields"] = $textFields ;
				$info[$reference_table]["fieldTypes"] = array();
				if( $textFields !== null ) {
					$defaultSet = false;
					foreach ($textFields as $field) {
						$info[$reference_table]["fieldTypes"][$field] = $contentObject->getFieldType($field);
					}
				}
				$cacheFileContent = serialize($info[$reference_table]);
				$handle = @fopen($cacheFile,"w");
				if ($handle){
					fwrite($handle,$cacheFileContent);
					fclose($handle);
				}
			}
		}

		return $info[$reference_table];
	}

	/**
	  * Version information of the component
	  *
	  */
	public function version() {
		return FalangManager::getVersion();
	}
}
