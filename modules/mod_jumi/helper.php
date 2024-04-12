<?php
/**
* @version   $Id$
* @package   Jumi
* @copyright (C) 2008 - 2010 Martin Hajek, 2011 Edvard Ananyan
* @license   GNU/GPL v3 http://www.gnu.org/licenses/gpl.html
*/

use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');
if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
}

class modJumiHelper {
    public static function getCodeWritten(&$params) { //returns code written or ""
        return trim($params->get( 'code_written' ));
    }

    public static function getStorageSource(&$params) { //returns filepathname or a record id or ""
        $storage=trim($params->get('source_code_storage'));
        if($storage!="") {
            if($id = substr(strchr($storage,"*"),1)) { //if record id return it
                return (int)$id;
            }
            else { // else return filepathname
                return $params->def('default_absolute_path',JPATH_ROOT).DS.$storage;
            }
        }
        else {
            return "";
        }
    }

    public static function getCodeStored($source) { //returns code stored in the database or null.
        $database = Factory::getContainer()->get('DatabaseDriver');
        //$user      = &JFactory::getUser();
        //$database->setQuery("select custom_script from #__jumi where id = '{$source}' and access <= {$user->gid} and published = 1");
        $database->setQuery("select custom_script from #__jumi where id = $source");
        return $database->loadResult();
    }
}