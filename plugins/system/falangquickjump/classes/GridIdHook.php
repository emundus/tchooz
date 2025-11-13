<?php

namespace Joomla\CMS\HTML\Helpers;

use JLoader;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Filesystem\File;

abstract class GridIdHook extends Grid
{

    /*
     * Override the Grid::id function to add the Falang quickjump
     * */
    public static function id($rowNum, $recId, $checkedOut = false, $name = 'cid', $stub = 'cb', $title = '', $formId = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('list-view');

        $res = parent::id($rowNum, $recId, $checkedOut, $name, $stub , $title , $formId );

        $row = func_get_arg(0);
        $id = func_get_arg(1);

        $result = self::addFalangCol($row,$id);

        $res .= $result;
        return $res;
    }

    public static function addFalangCol($row,$id)
    {

        $ext = Factory::getApplication()->input->get('option', '', 'cmd');
        //get table by component
        $falangManager = \FalangManager::getInstance();
        $component = $falangManager->loadQJComponent();
        $table = $component[1];
        $result = array();

        //$languages = $jd->getLanguages();
        //on peut mutualiser
        require_once( JPATH_ADMINISTRATOR."/components/com_falang/classes/FalangManager.class.php");
        $falangManager = \FalangManager::getInstance();
        $languages	= $falangManager->getLanguagesForTranslation();

        foreach($languages as $language) {
            //get Falang Object info
            $contentElement = $falangManager->getContentElement($component[1]);
            JLoader::import( 'models.ContentObject',FALANG_ADMINPATH);
            require_once(FALANG_ADMINPATH.'/src/Table/FalangContentTable.php');
            $actContentObject = new \ContentObject( $language->lang_id, $contentElement );
            $loaded = $actContentObject->loadFromContentID( $id );

            if (!$loaded){
                $result['hide'] = 'true';
                continue;
            }

            $result['status'][$language->sef] = $actContentObject->state . '|' .$actContentObject->published;

            //free and paid mmust be on 1 line
            
            /* >>> [PAID] >>> */$result['link-'.$language->sef] = 'index.php?option=com_falang&task=translate.edit&layout=popup&catid=' . $component[1] .'&cid[]=0|'.$id.'|'.$language->lang_id.'&select_language_id='. $language->lang_id.'&direct=1';/* <<< [PAID] <<< */

        }

        //add id / use to update row status
        $result['id'] = $id;

        // create array
        if ($row == 0) {
            $table = new \stdClass;
            if ($component[0] != 'com_k2') {
                $table->tableselector = ".table";
            } else {
                $table->tableselector = ".adminlist";
            }
            if (false) {
            }
            $first = 'var jFalangTable = '.json_encode($table).', falang = {}; ';
        } else {
            $first = '';
        }
        $res = '<script>'.$first.'falang['.$row.']='.json_encode($result).';</script>';



        return $res;
    }
}