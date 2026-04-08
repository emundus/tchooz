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
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\DbcheckModel;

class HtmlView extends BaseHtmlView {
	
	/**
	 * Indica si la opción está soportada
	 *
	 * @var bool
	 */
    public $supported;
	
	/**
	 * Contiene las tablas a optimizar
	 *
	 * @var array<int, object{
	 *     Name: string,
	 *     Engine?: string|null,
	 *     Version?: int,
	 *     Row_format?: string|null,
	 *     Rows?: int,
	 *     Avg_row_length?: int,
	 *     Data_length?: int,
	 *     Max_data_length?: int,
	 *     Index_length?: int,
	 *     Auto_increment?: int|null,
	 *     Create_time?: string|null,
	 *     Update_time?: string|null,
	 *     Check_time?: string|null,
	 *     Collation?: string,
	 *     Checksum?: int|null,
	 *     Create_options?: string|null,
	 *     Comment?: string
	 * }> 
	 */
    public $tables;
	
	/**
	 * Indica qué tablas se van a optimizar
	 *
	 * @var string
	 */
    public $show_tables;
	
	/**
	 * Indica la fecha de la última actualización de la BBDD
	 *
	 * @var string
	 */
    public $last_check_database;
	
    /**
     * Función que devuelve un valor en megas del argumento
     *
     * @param   int             $bytes    The number to convert
     *
     * @return  string
     *     
     */
    public function bytes_to_kbytes($bytes)
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
        $this->show_tables = $params->get('tables_to_check', 'All');

        // Extraemos la última optimización de la bbdd
		// Obtenemos el modelo de esta vista (Controlcenter)
		/** @var DbcheckModel $model */
        $model = $this->getModel();
        $this->last_check_database = $model->GetCampoFilemanager("last_check_database");

        $this->supported = $this->get('IsSupported');
        $this->tables      = $this->get('Tables');        
        				
		// Pass parameters to the cpanel.js script using Joomla's script options API
		$this->document->addScriptOptions('securitycheckpro.Dbcheck.tables', $this->tables);
        
        parent::display($tpl);  
    }


}