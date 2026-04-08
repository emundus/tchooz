<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Sysinfo;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\SysinfoModel;


class HtmlView extends BaseHtmlView {
    
	/**
	 * Los items a mostrar
	 *
	 * @var array<string, mixed>
	 */
    public $system_info = [];
	
	/**
	 * El modelo
	 *
	 * @var SysinfoModel
	 */
    public $model = null;
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		   
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | '.Text::_('COM_SECURITYCHECKPRO_SYSTEM_INFORMATION'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('bootstrap.tab')		  
		  ->useScript('com_securitycheckpro.Sysinfo');
                        
        // Obtenemos el modelo de esta vista (Sysinfo)
		/** @var SysinfoModel $model */
       	$this->model= $this->getModel();
        
		$this->system_info = $this->model->getInfo(); 
                            
        parent::display($tpl);
    }


}