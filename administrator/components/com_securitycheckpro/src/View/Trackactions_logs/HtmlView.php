<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Trackactions_logs;

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
		
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_TRACKACTIONS_LOGS_TEXT'), 'securitycheckpro');
        if (Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_securitycheckpro')) {
            ToolBarHelper::custom('delete', 'delete', 'delete', 'COM_SECURITYCHECKPRO_DELETE');
            ToolBarHelper::custom('delete_all', 'delete', 'delete', 'COM_SECURITYCHECKPRO_DELETE_ALL', false);
        }
        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_userlogs') || Factory::getApplication()->getIdentity()->authorise('core.options', 'com_userlogs')) {
            ToolBarHelper::custom('exportLogs', 'out-2', 'out-2', 'COM_SECURITYCHECKPRO_EXPORT_LOGS_CSV', false);
        }
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common');

        // Obtenemos los datos del modelo
            
        $this->state= $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $search = $this->state->get('filter.search');
        $user = $this->state->get('filter.user');
        $extension= $this->state->get('filter.extension');
        $ip_address = $this->state->get('filter.ip_address');
        $daterange = $this->state->get('daterange');
            
        $app        = Factory::getApplication();
        $search = $app->getUserState('filter.search', '');
        $listDirn = $this->state->get('list.direction');
        $listOrder = $this->state->get('list.ordering');

        // Extraemos información necesaria 
       $common_model = new BaseModel();

        $logs_pending = $common_model->LogsPending();
        $trackactions_plugin_exists = $common_model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;    

        //  Parámetros del componente
        $items= $this->get('Items');
        $this->pagination = $this->get('Pagination');

        // ... y los ponemos en el template
        $this->items = $items;

        if (!empty($items)) {
            $this->pagination = $this->get('Pagination');            
        }
        
        parent::display($tpl);  
    }


}