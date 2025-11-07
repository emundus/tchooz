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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

class  FalangControllerHelper  {

    /**
     * Sets up ContentElement Cache - mainly used for data to determine primary key id for tablenames ( and for
     * future use to allow tables to be dropped from translation even if contentelements are installed )
     *
     * update 5.3 improve performance (don't delete/create element each time)
     */
    static function _setupContentElementCache()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        //get installed content elements in database
        $query = $db->getQuery(true);
        $query->select('*')->from('#__falang_tableinfo');
        $db->setQuery($query);
        $elements = $db->loadObjectList('joomlatablename');

        $falangManager = FalangManager::getInstance();
        $contentElements = $falangManager->getContentElements(true);

        //update database with installed content elements
        $sql = "INSERT INTO `#__falang_tableinfo` (joomlatablename,tablepkID) VALUES ";
        $newCE = false;//new content element to add
        foreach ($contentElements as $key => $jfElement){
            if (array_key_exists($key,$elements)){continue;}
            $tablename = $jfElement->getTableName();
            $refId = $jfElement->getReferenceID();
            $sql .= $newCE?",":"";
            $sql .= " ('".$tablename."', '".$refId."')";
            $newCE = true;
        }

        //only launch update query if something to add
        if ($newCE){
            $db->setQuery( $sql);
            $db->execute();
        }

        //remove element from db who don't have content element
        foreach ($elements as $element){
            $search = $element->joomlatablename;
            if (!array_key_exists($search,$contentElements)){
                $query = $db->getQuery(true);
                $query->delete($db->quoteName('#__falang_tableinfo'));
                $query->where( $db->quoteName('joomlatablename') . ' = ' . $db->quote($search));
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

	public static function _checkDBCacheStructure (){

        //TODO : sbou revoir la methode de cache
        return true;
/*
		JCacheStorageJfdb::setupDB();

		$db =  JFactory::getDBO();
		$sql = "SHOW COLUMNS FROM #__dbcache LIKE 'value'";
		$db->setQuery($sql);
		$data = $db->loadObject();
		if (isset($data) && strtolower($data->Type)!=="mediumblob"){
			$sql = "DROP TABLE #__dbcache";
			$db->setQuery($sql);
			$db->query();

			JCacheStorageJfdb::setupDB();
		}
*/
	}

	public static function _checkDBStructure (){
		$db =  Factory::getContainer()->get(DatabaseInterface::class);
		$sql = "SHOW INDEX FROM #__falang_content";// where key_name = 'jfContent'";
		$db->setQuery($sql);
		$data = $db->loadObjectList("Key_name");

        if (isset($data['combo'])){
            $sql = "ALTER TABLE `#__falang_content` DROP INDEX `combo`" ;
            $db->setQuery($sql);
            $db->execute();
        }
        if (!isset($data['idxFalang1'])){

            $sql = "ALTER TABLE `#__falang_content` ADD INDEX `idxFalang1` ( `reference_id` , `reference_field` , `reference_table` )" ;
            $db->setQuery($sql);
            $db->execute();
        }

		if (!isset($data['falangContent'])){
			$sql = "ALTER TABLE `#__falang_content` ADD INDEX `falangContent` ( `language_id` , `reference_id` , `reference_table` )" ;
			$db->setQuery($sql);
			$db->execute();
		}

        if (!isset($data['falangContentLanguage'])){
            $sql = "ALTER TABLE `#__falang_content` ADD INDEX `falangContentLanguage` (`reference_id`, `reference_field`, `reference_table`, `language_id`)" ;
            $db->setQuery($sql);
            $db->execute();
        }

		if (!isset($data['reference_id'])){
			$sql = "ALTER TABLE `#__falang_content` ADD INDEX `reference_id` (`reference_id`)" ;
			$db->setQuery($sql);
			$db->execute();
        }
        if (!isset($data['language_id'])){
            $sql = "ALTER TABLE `#__falang_content` ADD INDEX `language_id` (`language_id`)" ;
            $db->setQuery($sql);
            $db->execute();
        }
        if (!isset($data['reference_table'])){
            $sql = "ALTER TABLE `#__falang_content` ADD INDEX `reference_table` (`reference_table`)" ;
            $db->setQuery($sql);
            $db->execute();
        }
        if (!isset($data['reference_field'])){
            $sql = "ALTER TABLE `#__falang_content` ADD INDEX `reference_field` (`reference_field`)" ;
            $db->setQuery($sql);
            $db->execute();
        }
	}

	/**
	 * Check Plugin Order since Joomla 3.6.2, language filter need to be set before FalangDatabaseDriver plgin
     * set order to 1 and 2 - other plugin set to -1 stay at -1
	 *
	 * @since 2.7.0
     * @since 4.5   add message to have admintools (if installed) ,fields , language filter , falangdriver order
     *              Check Plugin System Fields (need to be ordered befor Falang Driver plugin
     *              Necessary for Categories field translation
     * @update 5.0 add debug to the order list
     *
	 */
	public static function _reorderPlugin(){

		$db     = Factory::getContainer()->get(DatabaseInterface::class);
		$query  = $db->getQuery(true);

		//language filter must be before falang database driver
		$query->select('extension_id,element,ordering ');
		$query->from('#__extensions');

		$query->where($query->quoteName('type') . '=' . $query->quote('plugin'));
		$query->where($query->quoteName('folder') . '=' . $query->quote('system'));
		$query->where($query->quoteName('element') . 'IN ("admintools","languagefilter","fields","falangdriver","debug")');
		$query->order('ordering ASC');

		$db->setQuery($query);
		$list = $db->loadObjectList('element');
        if (  (int)$list['fields']->ordering >=  (int)$list['falangdriver']->ordering ||
              (int)$list['languagefilter']->ordering >=  (int)$list['falangdriver']->ordering){
            if (isset($list)) {
                $idx = 0;

                if (isset($list['admintools'])) {
                    $pks[] = (int)$list['admintools']->extension_id;
                    $order[] = $idx;
                    $idx = $idx + 1;
                }
                if (isset($list['languagefilter'])) {
                    $pks[] = (int)$list['languagefilter']->extension_id;
                    $order[] = $idx;
                    $idx = $idx + 1;
                }
                if (isset($list['fields'])) {
                    $pks[] = (int)$list['fields']->extension_id;
                    $order[] = $idx;
                    $idx = $idx + 1;
                }
                if (isset($list['falangdriver'])) {
                    $pks[] = (int)$list['falangdriver']->extension_id;
                    $order[] = $idx;
                    $idx = $idx + 1;
                }
                if (isset($list['debug'])) {
                    $pks[] = (int)$list['debug']->extension_id;
                    $order[] = $idx;
                    $idx = $idx + 1;
                }

                $pluginsModel = BaseDatabaseModel::getInstance('Plugin', 'PluginsModel');

                // Save the ordering
                $return = $pluginsModel->saveorder($pks, $order);

                if ($return === false) {
                    Factory::getApplication()->enqueueMessage(Text::_('COM_FALANG_PLUGINS_SYSTEM_ORDER_FAILED'), 'error');
                } else {
                    Factory::getApplication()->enqueueMessage(Text::_('COM_FALANG_PLUGINS_SYSTEM_ORDER_FIXED'), 'notice');

                }
            }
		}

	}

    /**
     * Check if PDO Driver is used
     *
     * @from 4.11
     *
     */
	public static function _checkPdoDriver(){
	    $db = Factory::getContainer()->get(DatabaseInterface::class);
	    if ($db->getName() == 'pdo' || $db->getName() == 'mysql' ){
            Factory::getApplication()->enqueueMessage(Text::_('COM_FALANG_PDO_DRIVER_NOT_SUPPORTED'), 'error');
        }
    }

}
