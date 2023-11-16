<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Cron;
 
 // Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

// Load plugin language
$lang = Factory::getLanguage();
$lang->load('plg_system_securitycheckpro_cron');

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
		 
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('PLG_SECURITYCHECKPRO_CRON_SCHEDULE_LABEL'), 'securitycheckpro');
		
		ToolBarHelper::apply();
		 
		// Filtro
        $this->state= $this->get('State');
        $lists = $this->state->get('filter.lists_search');

        // Obtenemos el modelo
        $model = $this->getModel();

        //  Parámetros del plugin
        $items= $model->getCronConfig();

        // Información para la barra de navegación
        $logs_pending = $model->LogsPending();
        $trackactions_plugin_exists = $model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;

        // Extraemos los elementos que nos interesan...
        $tasks= null;
        $launch_time = null;
        $periodicity = null;

        if (!is_null($items['tasks'])) {
            $tasks = $items['tasks'];    
        }

        if (!is_null($items['launch_time'])) {
            $launch_time = $items['launch_time'];    
        }

        if (!is_null($items['periodicity'])) {
            $periodicity = $items['periodicity'];    
        }
        // ... y los ponemos en el template
        $this->tasks = $tasks;
        $this->launch_time = $launch_time;
        $this->periodicity = $periodicity;
        
        parent::display($tpl);  
    }


}