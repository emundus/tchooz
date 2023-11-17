<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Vulninfo;

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
		  
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_VULN_DATABASE_TEXT'), 'securitycheckpro');
                        
        // Obtenemos los datos del modelo
        $model = $this->getModel();
        $vuln_details = $model->datos();                
        $pagination = $this->get('Pagination');
        $logs_pending = $model->LogsPending();
        $trackactions_plugin_exists = $model->PluginStatus(8);
        
                        
        // Ponemos los datos y la paginación en el template
        $this->vuln_details = $vuln_details;
        $this->pagination = $pagination;
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;
        
        parent::display($tpl);  
    }


}