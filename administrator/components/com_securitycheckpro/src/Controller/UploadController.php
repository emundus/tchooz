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
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;

class UploadController extends SecuritycheckproBaseController
{
       
    /* Acciones al pulsar el botón 'Import settings' */
    function read_file()
    {
        $model = $this->getModel("upload");
        $res = $model->read_file();
        
        if ($res) {
            $this->setRedirect('index.php?option=com_securitycheckpro');        
        } else
        {
            $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=upload&'. Session::getFormToken() .'=1');    
        }
    }
            
}
