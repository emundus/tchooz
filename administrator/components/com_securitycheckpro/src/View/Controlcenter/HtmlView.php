<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Controlcenter;
 
 // Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\ControlcenterModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\CMS\Application\CMSApplication;

class HtmlView extends BaseHtmlView {
	
	/**
	 * Nombre del fichero de logs
	 *
	 * @var  string
	 */
    public $log_filename = '';
	
	/**
	 * Indica si el archivo error.php existe
	 *
	 * @var  bool|int
	 */
    public $error_file_exists = 0;
	
	/**
	 * Indica si la opción 'Control Center' está habilitada
	 *
	 * @var  bool|int
	 */
    public $control_center_enabled = false;
	
	/**
	 * Clave secreta
	 *
	 * @var  string
	 */
    public $secret_key = '';
	
	/**
	 * Url del Control Center
	 *
	 * @var  string
	 */
    public $control_center_url = '';
	
	/**
	 * Token
	 *
	 * @var  string
	 */
    public $token = '';
	
	/**
	 * @var BaseModel
	 */
	public $basemodel;
    
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_CONTROLCENTER_TEXT'), 'securitycheckpro');
		ToolbarHelper::apply();
		ToolbarHelper::save();
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('bootstrap.toast')  
		  ->useScript('com_securitycheckpro.Controlcenter');

        // Obtenemos el modelo de esta vista (Controlcenter)
		/** @var ControlcenterModel $model */
        $model = $this->getModel();
		
        //  Parámetros del plugin
        $items= $model->getControlCenterConfig();
		
		// BaseModel
        $this->basemodel = new BaseModel();
		
		 /** @var FilemanagerModel $filemanager_model */
		$filemanager_model = new FilemanagerModel();
				
		$this->log_filename = $filemanager_model->get_log_filename("controlcenter_log", true);
		if ( !empty($this->log_filename) ) {
			$app->setUserState('download_controlcenter_log', $this->log_filename);
		} else {
			$app->setUserState('download_controlcenter_log', null);
		}
		
		// Chequeamos si existe el fichero de error		
		$folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
		if (file_exists($folder_path . "error.php")) {
			$this->error_file_exists = 1;
		}

        if ( array_key_exists('control_center_enabled',$items) ) {
            $this->control_center_enabled = $items['control_center_enabled'];    
        }

        if ( array_key_exists('secret_key',$items) ) {
            $this->secret_key = $items['secret_key'];    
        }
		
		if ( array_key_exists('control_center_url',$items) ) {
            $this->control_center_url = $items['control_center_url'];    
        }
		
		if ( array_key_exists('token',$items ) ) {
            $this->token = $items['token'];    
        }		
        
        parent::display($tpl);  
    }


}