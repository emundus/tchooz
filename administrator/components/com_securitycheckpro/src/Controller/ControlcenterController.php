<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproController;

/**
 * Securitycheckpros  Controller
 */
class ControlcenterController extends SecuritycheckproController
{

    /* Redirecciona las peticiones al componente */
    function redireccion()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }


    /* Guarda los cambios y redirige al cPanel */
    public function save()
    {
        $model = $this->getModel('base');
        $jinput = Factory::getApplication()->input;
        $data = $jinput->getArray($_POST);
		$model->saveConfig($data, 'controlcenter');

        $this->setRedirect('index.php?option=com_securitycheckpro&view=controlcenter&'. Session::getFormToken() .'=1', Text::_('COM_SECURITYCHECKPRO_CONFIGSAVED'));
    }

    /* Guarda los cambios */
    public function apply()
    {
        $this->save('cron_plugin');
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=controlcenter&view=controlcenter&'. Session::getFormToken() .'=1', Text::_('COM_SECURITYCHECKPRO_CONFIGSAVED'));
    }
	
	/* Download log file */
    function download_controlcenter_log($log_name=null)
    {
				
		$mainframe = Factory::getApplication();
		
		$is_error_log = $mainframe->input->get('error_log', null);
		
		if ($is_error_log) {
			$filename = "error.php";
		} else {
			$filename = $mainframe->getUserState('download_controlcenter_log', null);
		}	
											
        if (!empty($filename)) {  
			// Establecemos la ruta donde se almacenan los escaneos
			$folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;			
			if (file_exists($folder_path.$filename)) {				

				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment;filename=' . $filename);
				header('Expirer: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Lenght: ' . filesize($folder_path.$filename));
				ob_clean();
				flush();
				readfile($folder_path.$filename);
				exit;
			} else {
				Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_LOGFILENOTEXISTS'), 'error');
			}
            
        }else
        {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ERROR_RETRIEVING_FILE'), 'error');    
        } 
        
        parent::display();    
        
    }
	
	/* Delete log file */
    function delete_controlcenter_log()
    {
		$mainframe = Factory::getApplication();
		$filename = $mainframe->getUserState('download_controlcenter_log', null);
		// Establecemos la ruta donde se almacenan los escaneos
		$folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
											
        if (!empty($filename)) {  
			
			if (file_exists($folder_path.$filename)) {
				$res = File::delete($folder_path.$filename);
				// Let's delete the error.log if exists
				if (file_exists($folder_path."error.php")) {
					File::delete($folder_path."error.php");
				}
			} else {
				Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_LOGFILENOTEXISTS'), 'error');
			}
            
        }else
        {
			// Let's delete the error.log if exists
			if (file_exists($folder_path."error.php")) {
				$res = File::delete($folder_path."error.php");
			} else {
				Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ERROR_RETRIEVING_FILE'), 'error'); 
			}
               
        } 
		if ($res) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_FILE_DELETED'));
			$db = Factory::getContainer()->get(DatabaseInterface::class);		
			$sql = "DELETE FROM #__securitycheckpro_storage WHERE storage_key='controlcenter_log'";
			$db->setQuery($sql);
			$db->execute();  
		}
        
        parent::display();    
        
    }

}
