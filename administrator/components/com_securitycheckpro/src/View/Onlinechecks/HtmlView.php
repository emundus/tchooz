<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Onlinechecks;

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
		  
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_ONLINE_CHECK_LOGS'), 'securitycheckpro');
		ToolBarHelper::custom('download_log_file', 'out-2', 'out-2', 'COM_SECURITYCHECKPRO_DOWNLOAD_LOG', false);
        ToolBarHelper::custom('delete_files', 'remove', 'remove', 'COM_SECURITYCHECKPRO_DELETE_FILE');
        ToolBarHelper::custom('view_log', 'eye', 'eye', 'COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_MESSAGE');
                        
        // Obtenemos los datos del modelo
        $model = $this->getModel();
		$this->items = $model->load();
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		
		// Filtro
        $managedevices_search = $this->state->get('filter.onlinechecks_search');

        $common_model = new BaseModel();
        $this->logs_pending = $common_model->LogsPending();
		$this->trackactions_plugin_exists = $common_model->PluginStatus(8);   
        
        parent::display($tpl);  
    }


}