<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\Input\Input;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\OnlinechecksModel;

class OnlinechecksController extends SecuritycheckproBaseController
{
    	
    // Borra ficheros de logs
    function delete_files():void {
        $model = $this->getModel("Onlinechecks");
		if (!$model instanceof OnlinechecksModel) {
			Factory::getApplication()->enqueueMessage('Onlinechecks model not found', 'error');
			return;
		}
		
        $model->delete_files();   		
        
		parent::display();       
    }

    // Download suspicious file log
    function download_log_file():void {
        $model = $this->getModel("Onlinechecks");
		if (!$model instanceof OnlinechecksModel) {
			Factory::getApplication()->enqueueMessage('Onlinechecks model not found', 'error');
			return;
		}
        $model->download_log_file();
        
		parent::display();      
    }    
            
}
