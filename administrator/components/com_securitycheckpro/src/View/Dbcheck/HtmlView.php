<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Dbcheck;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
 
// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;


/**
 * Main Admin View
 */
class HtmlView extends BaseHtmlView {
	
	// Iniciliazamos las variables
    protected $supported;
    protected $tables;

    /* Función que devuelve un valor en megas del argumento*/
    protected function bytes_to_kbytes($bytes)
    {
        if ($bytes < 1) {
            return '0.00';
        }
        
        return number_format($bytes/1024, 2, '.', ' ');
    }
    
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		  
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_DB_OPTIMIZATION'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('com_securitycheckpro.Dbcheck');

        // Extraemos el tipo de tablas que serán mostradas
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $show_tables = $params->get('tables_to_check', 'All');

        // Extraemos la última optimización de la bbdd
        $model = $this->getModel("dbcheck");
        $last_check_database = $model->get_campo_filemanager("last_check_database");

        $logs_pending = $model->LogsPending();
        $trackactions_plugin_exists = $model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;

        $this->supported = $this->get('IsSupported');
        $this->tables      = $this->get('Tables');
        $this->show_tables = $show_tables;
        $this->last_check_database = $last_check_database;
				
		// Pass parameters to the cpanel.js script using Joomla's script options API
		$this->document->addScriptOptions('securitycheckpro.Dbcheck.tables', $this->tables);
        
        parent::display($tpl);  
    }


}