<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Ruleslogs;
 
 // Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\RuleslogsModel;

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
	 * Los detalles de las entradas de confianza
	 *
	 * @var array<string,mixed>|null
	 */
    public $log_details = [];
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_RULES_VIEW_LOGS'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		  
		  ->useScript('com_securitycheckpro.Ruleslog');

        // Obtenemos los datos del modelo        
        $this->state= $this->get('State');
        $search = $this->state->get('filter.rules_search');
    
        // Obtenemos el modelo de esta vista (Ruleslogs)
		/** @var RuleslogsModel $model */
       	$model= $this->getModel();
        $this->log_details = $model->load_rules_logs();
            
        if (!empty($this->log_details)) {
            $this->pagination = $this->get('Pagination');    
        }
		
        
        parent::display($tpl);  
    }


}