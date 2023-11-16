<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Protection;
 
// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;


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
		
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_HTACCESS_PROTECTION_TEXT'), 'securitycheckpro');
        // Si existe el fichero .htaccess, mostramos la opción para borrarlo.
        // Obtenemos el modelo
        $model = $this->getModel();
        // ... y el tipo de servidor web
        $mainframe = Factory::getApplication();
        $server = $mainframe->getUserState("server", 'apache');

        if (($server == 'apache') || ($server == 'iis')) {
            if ($model->ExistsFile('.htaccess.original')) {
                  ToolBarHelper::custom('restore_htaccess', 'redo-2', 'redo-2', 'COM_SECURITYCHECKPRO_RESTORE_HTACCESS', false);
            }
            if ($model->ExistsFile('.htaccess')) {
                ToolBarHelper::custom('delete_htaccess', 'file-remove', 'file-remove', 'COM_SECURITYCHECKPRO_DELETE_HTACCESS', false);
            }
            ToolBarHelper::custom('protect', 'key', 'key', 'COM_SECURITYCHECKPRO_PROTECT', false);
        } else if ($server == 'nginx') {
            ToolBarHelper::custom('generate_rules', 'key', 'key', 'COM_SECURITYCHECKPRO_GENERATE_RULES', false);
        }

        ToolBarHelper::apply();

        // Obtenemos la configuración actual...
        $config = $model->getConfig();
        // ... y la que hemos aplicado en el fichero .htaccess existente
        $config_applied = $model->GetconfigApplied();

        $this->protection_config = $config;
        $this->config_applied = $config_applied;
        $this->ExistsHtaccess = $model->ExistsFile('.htaccess');
        $this->server = $server;

        // Extraemos información necesaria 
        $common_model = new BaseModel();

        $logs_pending = $common_model->LogsPending();
        $trackactions_plugin_exists = $common_model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;
        
        parent::display($tpl);  
    }


}