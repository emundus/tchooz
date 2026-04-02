<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Protection;
 
// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\ProtectionModel;
use Joomla\CMS\Application\CMSApplication;

class HtmlView extends BaseHtmlView {
    
	/**
	 * Contiene la configuración
	 *
	 * @var array<string, string>|null
	 */
    public $protection_config = [];
	
	/**
	 * Contiene la configuración aplicada
	 *
	 * @var array<string, int|list<string>|string>
	 */
    public $config_applied = [];
	
	/**
	 * Indica si existe al archivo .htaccess
	 *
	 * @var bool
	 */
    public $ExistsHtaccess = false;
	
	/**
	 * Tipo de servidor
	 *
	 * @var string
	 */
    public $server = '';
	
	/**
	 * @var BaseModel
	 */
	public $basemodel;
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_HTACCESS_PROTECTION_TEXT'), 'securitycheckpro');		
				  
        // Si existe el fichero .htaccess, mostramos la opción para borrarlo.
        // Obtenemos el modelo de esta vista (Protection)
		/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\ProtectionModel $model */
       	$model= $this->getModel();
		
		// BaseModel
        $this->basemodel = new BaseModel();
		
        // ... y el tipo de servidor web
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();
        $this->server = $mainframe->getUserState("server", 'apache');

        if (($this->server == 'apache') || ($this->server == 'iis')) {
            if ($model->ExistsFile('.htaccess.original')) {
                  ToolbarHelper::custom('restore_htaccess', 'redo-2', 'redo-2', 'COM_SECURITYCHECKPRO_RESTORE_HTACCESS', false);
            }
            if ($model->ExistsFile('.htaccess')) {
                ToolbarHelper::custom('delete_htaccess', 'file-remove', 'file-remove', 'COM_SECURITYCHECKPRO_DELETE_HTACCESS', false);
            }
            ToolbarHelper::custom('protect', 'key', 'key', 'COM_SECURITYCHECKPRO_PROTECT', false);
        } else if ($this->server == 'nginx') {
            ToolbarHelper::custom('generate_rules', 'key', 'key', 'COM_SECURITYCHECKPRO_GENERATE_RULES', false);
        }

        ToolbarHelper::apply();
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('bootstrap.tab')		 
		  ->useScript('com_securitycheckpro.Protection');

        // Obtenemos la configuración actual...
        $this->protection_config = $model->getConfig();
		
        // ... y la que hemos aplicado en el fichero .htaccess existente
        $this->config_applied = $model->GetconfigApplied();
        $this->ExistsHtaccess = $model->ExistsFile('.htaccess');
        		
		$params = ComponentHelper::getParams('com_securitycheckpro');
		$size = $params->get('secret_key_length', 20); 
		
		// Also comes common data from SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController
		
		// Pass parameters to the cpanel.js script using Joomla's script options API
		$this->document->addScriptOptions('securitycheckpro.Protection.blockedaccessText', (int) $size);
        
        parent::display($tpl);  
    }


}