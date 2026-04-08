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
use Joomla\Registry\Registry;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\User\User;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\RulesModel;

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
	public ?Pagination $pagination = null;
	
	/**
	 * Los items a mostrar
	 *
	 * @var array<string,mixed>|null
	 */
    public $items = [];
	
	/** @var User */
    public User $user;
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		 
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' . Text::_('COM_SECURITYCHECKPRO_CPANEL_RULES_TEXT'), 'securitycheckpro');
        ToolbarHelper::publish('rules.apply_rules', Text::_('COM_SECURITYCHECKPRO_RULES_APPLY'), true);
        ToolbarHelper::unpublish('rules.not_apply_rules', Text::_('COM_SECURITYCHECKPRO_RULES_NOT_APPLY'), true);
        ToolbarHelper::custom('rules.rules_logs', 'users', 'users', 'COM_SECURITYCHECKPRO_RULES_VIEW_LOGS', false);
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('com_securitycheckpro.Rules');
		
		// Filtro por tipo de extensión
        $this->state= $this->get('State');
        $acl_search = $this->state->get('filter.acl_search');

        // Obtenemos el modelo de esta vista (Rules)
		/** @var RulesModel $model */
       	$model= $this->getModel();
		
        $this->items = $model->load();

        if (!empty($this->items)) {
            $this->pagination = $model->getPagination();    
        }
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app     = Factory::getApplication();
		
		$this->user = $app->getIdentity();
		
        
        parent::display($tpl);  
    }


}