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
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Form\Form;
use Joomla\Registry\Registry;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\LogsModel;

class HtmlView extends BaseHtmlView {
    
	/**
	 * The search tools form
	 *
	 * @var    Form|null	 
	 */
	public $filterForm;
	
	/**
	 * The model state
	 *
	 * @since  1.6
	 * @var    Registry
	 */
	public $state;
	
	/**
	 * Los items a mostrar
	 *
	 * @var array<int, mixed> $items 
	 */
    public $items = [];
	
	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public $pagination = null;
	
	/**
	 * indica si tenemos que grabar un log en cada ataque
	 *
	 * @var    int
	 */
	public $logs_attacks = 0;
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app     = Factory::getApplication();
		
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_FIREWALL_LOGS_TEXT'), 'securitycheckpro');
		if ($app->getIdentity()->authorise('logs.export', 'com_securitycheckpro')) {
			ToolbarHelper::custom('csv_export', 'out-2', 'out-2', 'COM_SECURITYCHECKPRO_EXPORT_LOGS_CSV', false);
		}
        ToolbarHelper::custom('mark_read', 'checkbox', 'checkbox', 'COM_SECURITYCHECKPRO_LOG_READ_CHANGE');
        ToolbarHelper::custom('mark_unread', 'checkbox-unchecked', 'checkbox-unchecked', 'COM_SECURITYCHECKPRO_LOG_NO_READ_CHANGE');
		if ($app->getIdentity()->authorise('logs.deleteall', 'com_securitycheckpro')) {
			ToolbarHelper::custom('delete', 'delete', 'delete', 'COM_SECURITYCHECKPRO_DELETE');
			ToolbarHelper::custom('delete_all', 'delete', 'delete', 'COM_SECURITYCHECKPRO_DELETE_ALL', false);
		}
        ToolbarHelper::custom('add_to_blacklist', 'plus_blacklist', 'plus', 'COM_SECURITYCHECKPRO_ADD_TO_BLACKLIST');
        ToolbarHelper::custom('add_to_whitelist', 'plus', 'plus', 'COM_SECURITYCHECKPRO_ADD_TO_WHITELIST');
		ToolbarHelper::custom('add_exception', 'plus', 'plus', 'COM_SECURITYCHECKPRO_ADD_EXCEPTION');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('com_securitycheckpro.Securitycheckpros')		  
		  ->useScript('list-view');

        // Obtenemos el modelo de esta vista (Logs)
		/** @var LogsModel $model */
       	$model               = $this->getModel();
		
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		$this->filterForm    = $model->getFilterForm();
                    
        // Obtenemos los parámetros del plugin...        
        $config= $model->getConfig();
        
		$this->logs_attacks = (int) ($config['logs_attacks'] ?? $this->logs_attacks);		            		
        
        parent::display($tpl);  
    }


}