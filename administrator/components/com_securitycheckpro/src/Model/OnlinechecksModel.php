<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

// Chequeamos si el archivo está incluído en Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Utilities\ArrayHelper;
use Joomla\Filesystem\File;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Modelo Securitycheck
 */
class OnlineChecksModel extends ListModel
{

    /**
     * @var int Total number of files of Pagination 
     */
    var $total = 0;

    protected function populateState($ordering = null,$direction = null)
    {
        // Inicializamos las variables
        $app        = Factory::getApplication();
    
        $managewebsites_search = $app->getUserStateFromRequest('filter.onlinechecks_search', 'filter_onlinechecks_search');
        $this->setState('filter.onlinechecks_search', $managewebsites_search);
		                
        parent::populateState();
    }

    
    /* Ver un fichero de log */
    public function view_log()
    {

        // Creamos el objeto JInput para obtener las variables del formulario
        $jinput = Factory::getApplication()->input;
    
        // Obtenemos las rutas de los ficheros a analizar
        $filename = $jinput->get('onlinechecks_logs_table', null, 'array');
        
        $mainframe = Factory::getApplication();
    
        if (!empty($filename) && (count($filename) == 1)) {    
            // Establecemos la ruta donde están almacenados los escaneos
            $folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
            $file_content = file_get_contents($folder_path.$filename[0]);
            $contenido = $mainframe->setUserState('contenido', $file_content);
        
        }else
        {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_SELECT_ONLY_A_FILE'), 'error');
            $contenido = $mainframe->setUserState('contenido', "vacio");
        }

    }

    /* Borra ficheros de logs */
    public function delete_files()
    {

        // Inicializamos las variables
        $query = null;
        $deleted_elements = 0;
        
        $db = Factory::getDBO();

        // Creamos el objeto JInput para obtener las variables del formulario
        $jinput = Factory::getApplication()->input;
    
        // Obtenemos las rutas de los ficheros a analizar
        $filenames = $jinput->get('onlinechecks_logs_table', null, 'array');
    
        // Establecemos la ruta donde están almacenados los escaneos
        $folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
    
        if (!empty($filenames)) {    
            foreach($filenames as $filename)
            {
				if (file_exists($folder_path.$filename)) {					
					$delete_result = File::delete($folder_path.$filename);
					if ($delete_result) {
						$sql = "DELETE FROM #__securitycheckpro_online_checks WHERE filename='{$filename}'";
						$db->setQuery($sql);
						$result = $db->execute();
					
						if ($result) {
							$deleted_elements++;
						}
					}else
					{
						Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_FILE_ERROR', $folder_path.$filename), 'error');    
					}
				}
            }
        
            if ($deleted_elements > 0) {
                Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_DELETED_FROM_LIST', $deleted_elements));
            }
        
        }else
        {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_FILES_SELECTED'), 'error');    
        }

        // Inicializamos las variables
        $query = null;
        $deleted_elements = 0;
        
        $db = Factory::getDBO();
    
        // Obtenemos los valores de las webs que serán borradas de la BBDD
		$input = Factory::getApplication()->input;
		$uids = $input->get('cid', null, 'array');	
		if (!is_null($uids)) {
			ArrayHelper::toInteger($uids, array());
		
			foreach($uids as $uid)
			{                
				$sql = "DELETE FROM #__securitycheckprocontrolcenter_websites WHERE id='{$uid}'";
				$db->setQuery($sql);
				$result = $db->execute();
			
				if ($result) {
					$deleted_elements++;
				}
			}
			
			if ($deleted_elements > 0) {
				Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_CONTROL_CENTER_DELETED_ELEMENTS', $deleted_elements));
			}
		}        
    }

    /* Extrae los datos de la tabla  '#__securitycheckpro_online_checks' */
    public function load($key_name = null)
    {
        
        $db = Factory::getDBO();
        
        $query = $db->getQuery(true)
            ->select(array('*'))
            ->from($db->quoteName('#__securitycheckpro_online_checks'))
            ->order('scan_date DESC');
        $db->setQuery($query);
        $websites = $db->loadRowList();
    
        // Obtenemos el número de registros del array que hemos de mostrar. Si el límite superior es '0', entonces devolvemos todo el array
        $upper_limit = $this->getState('limitstart');
        $lower_limit = $this->getState('limit', 0 , 'integer');
		
       // Obtenemos los valores de los filtros
        $filter_onlinechecks_search = $this->state->get('filter.onlinechecks_search');
		    
        // Si el campo 'search' no está vacío, buscamos en todos los campos del array           
        if (!empty($search)) {
			$search = htmlentities($filter_onlinechecks_search);
            // Inicializamos el array
            $filtered_array = array();
            $filtered_array = array_values(
                array_filter(
                    $websites, function ($element) use ($search) {
                        return ((strstr($element[1], $search)) || (strstr($element[2], $search)) || (strstr($element[3], $search)));
                    }
                )
            );
    
            $websites = $filtered_array;        
        } 
    
        // Número total de elementos del array
        $this->total = count($websites);
        
        /* Cortamos el array para mostrar sólo los valores mostrados por la paginación */
        $websites = array_splice($websites, $lower_limit, $upper_limit,);
    
        return $websites;
    }

    /* Función para descargar el fichero de logs de archivos sospechosos */
    function download_log_file()
    {

        // Creamos el objeto JInput para obtener las variables del formulario
        $jinput = Factory::getApplication()->input;
    
        // Obtenemos las rutas de los ficheros a analizar
        $filename = $jinput->get('onlinechecks_logs_table', null, 'array');
    
        if (!empty($filename) && (count($filename) == 1)) {        
            // Establecemos la ruta donde se almacenarán los escaneos
            $folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment;filename=' . $filename[0]);
            header('Expirer: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Lenght: ' . filesize($folder_path.$filename[0]));
            ob_clean();
            flush();
            readfile($folder_path.$filename[0]);
            exit;
        }else
        {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_SELECT_ONLY_A_FILE'), 'error');    
        }

    }

}
