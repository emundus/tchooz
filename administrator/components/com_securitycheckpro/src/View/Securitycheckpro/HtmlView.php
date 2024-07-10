<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Securitycheckpro;

defined('_JEXEC') or die;

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
		   
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | '.Text::_('COM_SECURITYCHECKPRO_VULNERABILITIES'), 'securitycheckpro');
        ToolBarHelper::custom('mostrar', 'database', 'database', 'COM_SECURITYCHECKPRO_LIST', false);
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		  
		  ->useScript('com_securitycheckpro.Securitycheckpros');

        $jinput = Factory::getApplication()->input;

        // Obtenemos los datos del modelo...
        $model = $this->getModel();
        $update_database_plugin_enabled = $model->PluginStatus(3);
        $update_database_plugin_exists = $model->PluginStatus(4);
        $last_check = $model->get_campo_bbdd('securitycheckpro_update_database', 'last_check');
        $database_version = $model->get_campo_bbdd('securitycheckpro_update_database', 'version');
        $database_message = $model->get_campo_bbdd('securitycheckpro_update_database', 'message');
        $logs_pending = $model->LogsPending();
       $trackactions_plugin_exists = $model->PluginStatus(8);

        if ($update_database_plugin_exists) {
            $plugin_id = $model->get_plugin_id(1);
            $last_update = $model->get_last_update();    
        } else 
        {
            $last_update = 'Jun 17 2024';
        }

        // Filtro por tipo de extensión
         $this->state= $this->get('State');
        $type= $this->state->get('filter.extension_type');
        $vulnerable= $this->state->get('filter.vulnerable');
		
        if (($type == '') && ($vulnerable == '')) { //No hay establecido ningún filtro de búsqueda
			$this->items = $this->get('Data');
            $this->pagination = $this->get('Pagination');
        } else 
        {        		
			$this->items = $this->get('FilterData');
            $this->pagination = $this->get('FilterPagination');
        }

        // Obtenemos los datos del modelo (junto con '$items' y '$pagination' obtenidos anteriormente)
		$this->eliminados = $jinput->get('comp_eliminados', '0', 'string');
        $this->core_actualizado = $jinput->get('core_actualizado', '0', 'string');
        $this->comps_actualizados = $jinput->get('componentes_actualizados', '0', 'string');
        $this->comp_ok = $jinput->get('comp_ok', '0', 'string');

        // Ponemos los datos y la paginación en el template
        $this->update_database_plugin_exists = $update_database_plugin_exists;
        $this->update_database_plugin_enabled = $update_database_plugin_enabled;
        $this->last_check = $last_check;
        $this->database_version = $database_version;
        $this->database_message = $database_message;
        $this->last_update = $last_update;

        if ($update_database_plugin_exists) {
            $this->plugin_id = $plugin_id;
        }
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;

        parent::display($tpl);
    }


}