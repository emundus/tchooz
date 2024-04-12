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
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;

class OnlinechecksController extends SecuritycheckproBaseController
{
    	
    // Borra ficheros de logs
    function delete_files()
    {
        $model = $this->getModel("onlinechecks");
        $model->delete_files();    
        $jinput = Factory::getApplication()->input;
        $jinput->set('view', 'onlinechecks');
       
    }

    // Download suspicious file log
    function download_log_file()
    {
        $model = $this->getModel("onlinechecks");    
        $model->download_log_file();
        
        $jinput = Factory::getApplication()->input;
        $jinput->set('view', 'onlinechecks');
           
        
    }

    // View onlinechecks log
    function view_log()
    {
        $model = $this->getModel("onlinechecks");    
        $model->view_log();  

		parent::display();
    }
            
}
