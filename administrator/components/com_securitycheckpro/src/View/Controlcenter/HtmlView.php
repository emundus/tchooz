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


/**
 * Main Admin View
 */
class HtmlView extends BaseHtmlView {
    
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_CONTROLCENTER_TEXT'), 'securitycheckpro');
		ToolBarHelper::apply();
		ToolbarHelper::save();
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('bootstrap.toast')  
		  ->useScript('com_securitycheckpro.Controlcenter');

        // Obtenemos el modelo
        $model = $this->getModel();

        //  Parámetros del plugin
        $items= $model->getControlCenterConfig();

        // Información para la barra de navegación
        $logs_pending = $model->LogsPending();
        $trackactions_plugin_exists = $model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;
		
		$filemanager_model = new FilemanagerModel();
		$this->log_filename = $filemanager_model->get_log_filename("controlcenter_log", true);
		if ( !empty($this->log_filename) ) {
			Factory::getApplication()->setUserState('download_controlcenter_log', $this->log_filename);
		} else {
			Factory::getApplication()->setUserState('download_controlcenter_log', null);
		}
		
		// Chequeamos si existe el fichero de error
		$this->error_file_exists = 0;
		$folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
		if (file_exists($folder_path . "error.php")) {
			$this->error_file_exists = 1;
		}

        // Extraemos los elementos que nos interesan...
        $control_center_enabled= null;
        $secret_key= null;
		$control_center_url = null;


        if (!is_null($items['control_center_enabled'])) {
            $control_center_enabled = $items['control_center_enabled'];    
        }

        if (!is_null($items['secret_key'])) {
            $secret_key = $items['secret_key'];    
        }
		
		if (!is_null($items['control_center_url'])) {
            $control_center_url = $items['control_center_url'];    
        }

        // ... y los ponemos en el template
        $this->control_center_enabled = $control_center_enabled;
        $this->secret_key = $secret_key;
		$this->control_center_url = $control_center_url;
		
        
        parent::display($tpl);  
    }


}