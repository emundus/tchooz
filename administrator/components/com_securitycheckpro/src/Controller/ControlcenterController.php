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
use Joomla\Input\Input;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\CMS\Application\CMSApplication;

class ControlcenterController extends SecuritycheckproController
{
			
    /* Redirecciona las peticiones al componente */
    function redireccion():void
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }


    /* Guarda los cambios y redirige al cPanel */
    public function save(bool $redirect=true):void
    {
		// CSRF
        if (!Session::checkToken('request')) {
            Factory::getApplication()->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            return;
        }
		
		$model = $this->getModel('Base');
		if (!$model instanceof BaseModel) {
			Factory::getApplication()->enqueueMessage('Base model not found. Error saving data', 'error');
			return;
		}
        
        $input = Factory::getApplication()->getInput();
		$data = $input->post->getArray();
		$model->saveConfig($data, 'controlcenter');
		
		if ($redirect){
			$this->setRedirect('index.php?option=com_securitycheckpro&view=cpanel');
		}
    }

    /* Guarda los cambios */
    public function apply():void
    {
        $this->save(false);
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=controlcenter&view=controlcenter&'. Session::getFormToken() .'=1');
    }
	
	/* Download log file */
    function download_controlcenter_log():void
    {
		if (!Session::checkToken()) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
		
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();
		/** @var Input $jinput */
		$jinput = $mainframe->getInput();
		
		/** @var bool $is_error_log */
		$is_error_log = $jinput->get('error_log', false);
		
		if ($is_error_log) {
			$filename = "error.php";
		} else {
			$filename = $mainframe->getUserState('download_controlcenter_log', null);
		}

        if (!empty($filename)) {
			$filename = basename($filename);
			$folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
			$fullPath = realpath($folder_path . $filename);
			$baseDirReal = realpath($folder_path);
			if ($fullPath === false || $baseDirReal === false || strpos($fullPath, $baseDirReal . DIRECTORY_SEPARATOR) !== 0) {
				Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_LOGFILENOTEXISTS'), 'error');
				parent::display();
				return;
			}
			if (file_exists($fullPath)) {
				$safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($fullPath));
				ob_clean();
				flush();
				readfile($fullPath);
				exit;
			} else {
				Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_LOGFILENOTEXISTS'),'error');				
			}
            
        }else
        {
			Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ERROR_RETRIEVING_FILE'),'error');
        } 
        
        parent::display();    
        
    }
	
	/* Delete log file */
    function delete_controlcenter_log():void
    {
		if (!Session::checkToken()) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
		
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();
		$filename = $mainframe->getUserState('download_controlcenter_log', null);
		$folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
		$baseDirReal = realpath($folder_path);
		$res = false;

        if (!empty($filename)) {
			$filename = basename($filename);
			$fullPath = realpath($folder_path . $filename);
			if ($fullPath !== false && $baseDirReal !== false && strpos($fullPath, $baseDirReal . DIRECTORY_SEPARATOR) === 0) {
				$res = File::delete($fullPath);
				$errorPath = $folder_path . "error.php";
				if (file_exists($errorPath)) {
					File::delete($errorPath);
				}
			} else {
				Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_LOGFILENOTEXISTS'),'error');
			}
        } else {
			$errorPath = $folder_path . "error.php";
			if (file_exists($errorPath)) {
				$res = File::delete($errorPath);
			} else {
				Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ERROR_RETRIEVING_FILE'),'error');
			}
        }
		if ($res) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_FILE_DELETED'),'message');
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('controlcenter_log'));
			$db->setQuery($query);
			$db->execute();
		}
        
        parent::display();    
        
    }

}
