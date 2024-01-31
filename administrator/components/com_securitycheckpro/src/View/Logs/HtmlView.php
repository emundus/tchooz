<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Logs;

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
	 * The search tools form
	 *
	 * @var    Form
	 * @since  1.6
	 */
	public $filterForm;
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_FIREWALL_LOGS_TEXT'), 'securitycheckpro');
        ToolBarHelper::custom('csv_export', 'out-2', 'out-2', 'COM_SECURITYCHECKPRO_EXPORT_LOGS_CSV', false);
        ToolBarHelper::custom('mark_read', 'checkbox', 'checkbox', 'COM_SECURITYCHECKPRO_LOG_READ_CHANGE');
        ToolBarHelper::custom('mark_unread', 'checkbox-unchecked', 'checkbox-unchecked', 'COM_SECURITYCHECKPRO_LOG_NO_READ_CHANGE');
        ToolBarHelper::custom('delete', 'delete', 'delete', 'COM_SECURITYCHECKPRO_DELETE');
        ToolBarHelper::custom('delete_all', 'delete', 'delete', 'COM_SECURITYCHECKPRO_DELETE_ALL', false);
        ToolBarHelper::custom('add_to_blacklist', 'plus_blacklist', 'plus', 'COM_SECURITYCHECKPRO_ADD_TO_BLACKLIST');
        ToolBarHelper::custom('add_to_whitelist', 'plus', 'plus', 'COM_SECURITYCHECKPRO_ADD_TO_WHITELIST');
		ToolBarHelper::custom('add_exception', 'plus', 'plus', 'COM_SECURITYCHECKPRO_ADD_EXCEPTION');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common');

        // Obtenemos los datos del modelo		
		$model               = $this->getModel();
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		$this->filterForm    = $model->getFilterForm();
                    
        // Obtenemos los parámetros del plugin...        
        $config= $model->getConfig();
                    
        $this->logs_attacks = 0;
        if (!is_null($config['logs_attacks'])) {
            $this->logs_attacks = $config['logs_attacks'];    
        }
		
		$common_model = new BaseModel();
        $this->logs_pending = $common_model->LogsPending();
		$this->trackactions_plugin_exists = $common_model->PluginStatus(8);                      		
        
        parent::display($tpl);  
    }


}