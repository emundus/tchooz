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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use Joomla\CMS\Application\CMSApplication;

class HtmlView extends BaseHtmlView {
    
	/**
	 * The model state
	 *
	 * @since  1.6
	 * @var    Registry
	 */
	public $state;
	
	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public $pagination = null;
	
	/**
	 * The search tools form
	 *
	 * @var    Form|null	 
	 */
	public $filterForm;
	
	/**
	 * Los items a mostrar
	 *
	 * @var array<string,mixed>|null
	 */
    public $items = [];
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app     = Factory::getApplication();
		
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_TRACKACTIONS_LOGS_TEXT'), 'securitycheckpro');
        if ($app->getIdentity()->authorise('logs.deleteall', 'com_securitycheckpro')) {
            ToolbarHelper::custom('delete', 'delete', 'delete', 'COM_SECURITYCHECKPRO_DELETE');
            ToolbarHelper::custom('delete_all', 'delete', 'delete', 'COM_SECURITYCHECKPRO_DELETE_ALL', false);
        }
        if ($app->getIdentity()->authorise('logs.export', 'com_securitycheckpro')) {
            ToolbarHelper::custom('exportLogs', 'out-2', 'out-2', 'COM_SECURITYCHECKPRO_EXPORT_LOGS_CSV', false);
        }
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('com_securitycheckpro.Trackactions');
            
        $this->state= $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $search = $this->state->get('filter.search');
        $user = $this->state->get('filter.user');
        $extension= $this->state->get('filter.extension');
        $ip_address = $this->state->get('filter.ip_address');
        $daterange = $this->state->get('daterange');
            
		$search = $app->getUserState('filter.search', '');
        $listDirn = $this->state->get('list.direction');
        $listOrder = $this->state->get('list.ordering');
  

        //  Parámetros del componente
        $this->items = $this->get('Items');
       
        if (!empty($this->items)) {
            $this->pagination = $this->get('Pagination');            
        }
        
        parent::display($tpl);  
    }


}