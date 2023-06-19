<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2017. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') or die;

class CpanelController extends JControllerLegacy  {

    /**
     * @update 4.11 add PDO check message
     *
     */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'show',  'display' );

		// ensure DB cache table is created and up to date
		JLoader::import( 'helpers.controllerHelper',FALANG_ADMINPATH);
		//v 1.4 remove cache table creation and check
        //JLoader::import( 'classes.JCacheStorageJFDB',FALANG_ADMINPATH);
        //FalangControllerHelper::_checkDBCacheStructure();
		FalangControllerHelper::_checkDBStructure();

		//need since Joomal 4.1 to have the right order back
        // Reorder the Admin Tools plugin if necessary
        if (ComponentHelper::getParams('com_falang')->get('reorderplugin', 1))
        {
            FalangControllerHelper::_reorderPlugin();
        }

        FalangControllerHelper::_checkPdoDriver();

	}

	/**
	 * Standard display control structure
	 * 
	 */
	function display($cachable = false, $urlparams = array())
	{
		$this->view =  $this->getView('cpanel');
		parent::display();
	}
	
	function cancel()
	{
		$this->setRedirect( 'index.php?option=com_falang' );
	}

    /*
     * @since 4.0.6 use native joomla update system
     * */
    function checkUpdates() {
        //force information reload

        $updateInfo = FalangManager::getUpdateInfo(true);
        //send json response
        $document = JFactory::getDocument();
        $document->setMimeEncoding('application/json');

        if ($updateInfo->hasUpdate) {
            $msg = JText::_('COM_FALANG_CPANEL_OLD_VERSION').'<a href="index.php?option=com_installer&view=update&filter[search]=falang&filter[type]=package"/> '.JText::_('COM_FALANG_CPANEL_UPDATE_LINK').'</a>';
            echo json_encode(array('update' => "true",'version' => $updateInfo->version, 'message' => $msg));
        } else {
            $msg = JText::_('COM_FALANG_CPANEL_LATEST_VERSION');
            echo json_encode(array('update' => "false",'version' => $updateInfo->version, 'message' => $msg));
        }
        return true;
    }

}
