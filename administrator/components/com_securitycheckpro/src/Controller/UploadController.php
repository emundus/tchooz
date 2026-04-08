<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\UploadModel;

class UploadController extends SecuritycheckproBaseController
{
    /**
     * Acciones al pulsar el botón 'Import settings'
     *
     * @return void
     */
    function read_file():void {
		$model = $this->getModel('Upload');
		if (!$model instanceof UploadModel) {
			Factory::getApplication()->enqueueMessage('Upload model not found', 'error');
			return;
		}
        $res = $model->read_file();
        
        if ($res) {
            $this->setRedirect('index.php?option=com_securitycheckpro');        
        } else
        {
            $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=upload&'. Session::getFormToken() .'=1');    
        }
    }
            
}
