<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Rules;
 
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
		 
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' . Text::_('COM_SECURITYCHECKPRO_CPANEL_RULES_TEXT'), 'securitycheckpro');
        ToolBarHelper::publish('apply_rules', Text::_('COM_SECURITYCHECKPRO_RULES_APPLY'), true);
        ToolBarHelper::unpublish('not_apply_rules', Text::_('COM_SECURITYCHECKPRO_RULES_NOT_APPLY'), true);
        ToolBarHelper::custom('rules_logs', 'users', 'users', 'COM_SECURITYCHECKPRO_RULES_VIEW_LOGS', false);
		
		// Filtro por tipo de extensión
        $this->state= $this->get('State');
        $acl_search = $this->state->get('filter.acl_search');

        $model = $this->getModel("rules");
        $items = $model->load();

        // Información para la barra de navegación        
        $common_model = new BaseModel();

        $logs_pending = $common_model->LogsPending();
        $trackactions_plugin_exists = $common_model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;

        // Ponemos los datos en el template
        $this->items = $items;

        if (!empty($items)) {
            $this->pagination = $this->get('Pagination');    
        }
        
        parent::display($tpl);  
    }


}