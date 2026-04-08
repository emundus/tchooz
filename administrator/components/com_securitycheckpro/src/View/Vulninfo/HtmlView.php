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
use Joomla\CMS\Pagination\Pagination;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\VulninfoModel;

class HtmlView extends BaseHtmlView {
    
	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public $pagination = null;
	
	/**
	 * Datos de las vulnerabilidades
	 *
	 * @var mixed
	 */
	public $items = null;
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		  
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_VULN_DATABASE_TEXT'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common');
                        
        // Obtenemos el modelo de esta vista (Vulninfo)
		/** @var VulninfoModel $model */
       	$model= $this->getModel();
		
        $this->items = $this->get('Items');		
        $this->pagination = $this->get('Pagination');
        
        parent::display($tpl);  
    }


}