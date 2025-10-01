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
use Joomla\CMS\Version;
use Joomla\Filesystem\File;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

/**
 * Modelo Securitycheck
 */
class SecuritycheckproModel extends BaseModel
{
    /**
     Array de datos
     *
     @var array
     */
    var $_data;
    /**
     Total items
     *
     @var integer
     */
    var $_total = null;
    /**
     Objeto Pagination
     *
     @var object
     */
    var $_pagination = null;
    /**
     Columnas de #__securitycheck
     *
     @var integer
     */
    var $_dbrows = null;

    function __construct()
    {
        parent::__construct();

        global $mainframe, $option;
        
        $mainframe = Factory::getApplication();    
        $jinput = $mainframe->input;
 
        // Obtenemos las variables de paginación de la petición
        $limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');    
        $limitstart = $jinput->get('limitstart', 0, 'int');

        // En el caso de que los límites hayan cambiado, los volvemos a ajustar
        $limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

        $this->setState('limit', $limit);
        $this->setState('limitstart', $limitstart);
    }

    protected function populateState()
    {
        // Inicializamos las variables
        $app = Factory::getApplication();
    
        $extension_type = $app->getUserStateFromRequest('filter.extension_type', 'filter_extension_type');
        $this->setState('filter.extension_type', $extension_type);
        $vulnerable = $app->getUserStateFromRequest('filter.vulnerable', 'filter_vulnerable');
        $this->setState('filter.vulnerable', $vulnerable);
            
        parent::populateState();
    }

    /* 
    * Función para obtener todo los datos de la BBDD 'securitycheck' en forma de array 
    */
    function getTotal()
    {
        // Cargamos el contenido si es que no existe todavía
        if (empty($this->_total)) {
            $query = $this->_buildQuery();			
            $this->_total = $this->_getListCount($query);    
        }
        return $this->_total;
    }

    /* 
    * Función para obtener el número de registros de la BBDD 'securitycheckpro_logs' según la opción escogida por el usuario
    */
    function getFilterTotal()
    {
        // Cargamos el contenido si es que no existe todavía
        if (empty($this->_total)) {
            $query = $this->_buildFilterQuery();
            $this->_total = $this->_getListCount($query);
        }
        return $this->_total;
    }

    /* 
    * Función para la paginación 
    */
    function getPagination()
    {
        // Cargamos el contenido si es que no existe todavía
        if (empty($this->_pagination)) {            
            $this->_pagination = new Pagination($this->getTotal(), $this->getState('limitstart'), $this->getState('limit'));
        }
        return $this->_pagination;
    }

    /* 
    * Función para la paginación filtrada según la opción escogida por el usuario
    */
    function getFilterPagination()
    {
        // Cargamos el contenido si es que no existe todavía
        if (empty($this->_pagination)) {            
            $this->_pagination = new Pagination($this->getFilterTotal(), $this->getState('limitstart'), $this->getState('limit'));
        }
        return $this->_pagination;
    }

    /*
    * Devuelve todos los componentes almacenados en la BBDD 'securitycheck'
    */
    function _buildQuery()
    {
        $query = 'SELECT * FROM #__securitycheckpro as a ORDER BY a.id ASC';
        return $query;
    }

    /*
    * Devuelve todos los componentes almacenados en la BBDD 'securitycheckpro_logs' filtrados según las opciones establecidas por el usuario
    */
    function _buildFilterQuery()
    {
		$config = Factory::getConfig();
		$dbtype = $config->get('dbtype');
		
        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
    
        $query->select('*');
        $query->from('#__securitycheckpro AS a');
    
        // Filtramos el tipo
        if ($extension_type = $this->getState('filter.extension_type')) {
            $query->where('a.sc_type = '.$db->quote(strtolower($extension_type)));
        }
    
        // Filtramos si el componente es vulnerable
        if ($vulnerable = $this->getState('filter.vulnerable')) {
			if (strstr($dbtype,"mysql")) {
				$query->where('a.Vulnerable = '.$db->quote($vulnerable));
			} else if (strstr($dbtype,"pgsql")) {
				$query->where('a."Vulnerable" = '.$db->quote($vulnerable));
			}
            
        }
        
        // Ordenamos el resultado
        $query = $query . ' ORDER BY a.id ASC';
		
        return $query;
    }

    /*
    Obtiene la versión de un determinado componente en una de las BBDD. Pasamos como parámetro la BBDD donde buscar, el campo de la tabla sobre el que hacerlo y el nombre que buscamos.
    */
    function version_componente($nombre,$database,$campo)
    {

        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        
        // Sanitizamos las entradas
        $database = htmlspecialchars($database);
        $campo = htmlspecialchars($campo);
        $nombre = htmlspecialchars($nombre);
        $database = $db->escape($database);
        $campo = $db->escape($campo);
        $nombre = $db->Quote($db->escape($nombre));

        // Construimos la consulta
        $query->select('Installedversion');
        $query->from('#__' .$database);
        $query->where($campo .'=' .$nombre);
		
		try {
			$db->setQuery($query);
			$result = $db->loadResult();
		} catch (Exception $e)
        {    			
            $result = "0.0.0";
        }     

        
        return $result;
    }

    
    /*
    * Compara los componentes de la BBDD de 'securitycheck' con los de 'securitycheck_db" y actualiza los componentes que sean vulnerables 
    */
    function chequear_vulnerabilidades()
    {
        // Extraemos los componentes instalados
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		
        $query = $this->_buildQuery();
        $db->setQuery($query);
        $components = $db->loadAssocList();
		
		// Versión de Joomla instalada
		$local_joomla_branch = explode(".", JVERSION);
		if ( (is_array($local_joomla_branch)) && (array_key_exists('0',$local_joomla_branch)) ) {
			$local_joomla_branch = $local_joomla_branch[0];
		} else {
			$local_joomla_branch = 5;
		}
		
        // Extraemos los componentes vulnerables para nuestra versión de Joomla
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__securitycheckpro_db'))
            ->where($db->quoteName('Joomlaversion').' = '.$db->quote($local_joomla_branch));
        $db->setQuery($query);
		$vuln_components = $db->loadAssocList();		
		
		foreach ($vuln_components as $vulnerable_product)
        {
			$valor_campo_vulnerable = "Si"; // Valor que tendrá el campo 'Vulnerable' cuando se actualice. También puede tener el valor 'Indefinido'.
			$components_key = array_search($vulnerable_product['Product'], array_column($components, 'Product'));
			
			if ($components_key === false) {
				// El producto vulnerable no está instalado
			} else {
				if ( $components[$components_key]['sc_type'] == $vulnerable_product['vuln_type'] ) {
					$modvulnversion = $vulnerable_product['modvulnversion']; //Modificador sobre la versión de la extensión
                    $db_version = $components[$components_key]['Installedversion']; // Versión de la extensión instalada
                    $vuln_version = $vulnerable_product['Vulnerableversion']; // Versión de la extensión vulnerable
					                
                    // Usamos la funcion 'version_compare' de php para comparar las versiones del producto instalado y la del componente vulnerable					
                    $version_compare = version_compare($db_version, $vuln_version, $modvulnversion);
					if ( ($version_compare) || ($vuln_version == '---') ) {
						// El producto es vulnerable
						$query = $db->getQuery(true)
                            ->select(array('id'))
                            ->from($db->quoteName('#__securitycheckpro_vuln_components'))
                            ->where($db->quoteName('Product').' = '.$db->quote($vulnerable_product['Product']))
							->where($db->quoteName('vuln_id').' = '.$db->quote($vulnerable_product['id']));
                        $db->setQuery($query);
                        $exists = $db->loadResult();
						
						$valor = (object) array(
							'Product' => $vulnerable_product['Product'],
                            'vuln_id' => $vulnerable_product['id'],
                        );
                                                                    
                        try
                        {
							// Añadimos los datos a la tabla 'securitycheck_vuln_components' si no existen ya   
							if (is_null($exists)) {
								$result = $db->insertObject('#__securitycheckpro_vuln_components', $valor);        
							}
							
							$res_actualizar = $this->actualizar_registro($vulnerable_product['Product'], 'securitycheckpro', 'Product', $valor_campo_vulnerable, 'Vulnerable');
							if ( $res_actualizar ) { // Se ha actualizado la BBDD correctamente                            
							} else {                            
								Factory::getApplication()->enqueueMessage('COM_SECURITYCHECKPRO_UPDATE_VULNERABLE_FAILED' ."'" . $vulnerable_product['Product'] ."'", 'error');
							}
                        } catch (Exception $e)
                        {    
                            Factory::getApplication()->enqueueMessage('COM_SECURITYCHECKPRO_UPDATE_VULNERABLE_FAILED' ."'" . $vulnerable_product['Product'] ."'", 'error');                         
                        }						
					} else {
						// El producto NO es vulnerable. Borramos la entrada de la tabla 'securitycheck_vuln_components' si existe
						$query2 = $db->getQuery(true);
						$conditions = array(
							$db->quoteName('Product') . ' = ' . $db->quote($vulnerable_product['Product']), 
							$db->quoteName('vuln_id') . ' = ' . $db->quote($vulnerable_product['id'])
						);

						$query2->delete($db->quoteName('#__securitycheckpro_vuln_components'));
						$query2->where($conditions);
						
                        $db->setQuery($query2);						
                        $db->execute();						
					}
					
				}
			}
		}			
    }


    /*
    Actualiza el campo '$campo_set'  de un registro en la BBDD pasada como parámetro.
    */
    function actualizar_registro($nombre,$database,$campo,$nuevo_valor,$campo_set,$tipo=null)
    {
        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        
        // Sanitizamos las entradas
        $nombre = $db->Quote($nombre);
        $campo = $db->quoteName($campo);
        $nuevo_valor = $db->Quote($nuevo_valor);
        $campo_set = $db->quoteName($campo_set);
		$product = $db->quoteName("Product");
        if (!is_null($tipo)) {
            $tipo = $db->Quote($tipo);
			$sc_type = $db->quoteName("sc_type");
        }

        // Construimos la consulta
        if (is_null($tipo)) {
            $query = 'UPDATE #__' .$database . ' SET ' . $campo_set .'=' .$nuevo_valor .' WHERE ' . $product . '=' . $nombre;    
        } else 
        {
			$query = 'UPDATE #__' .$database . ' SET ' . $campo_set .'=' .$nuevo_valor .' WHERE ' . $product . '=' . $nombre . ' and ' . $sc_type .'=' . $tipo;    
        }
				
		$db->setQuery($query);
        $result = $db->execute();
        return $result;

    }


    /*
    Busca el nombre de un registro en la BBDD pasada como parámetro. Devuelve true si existe y false en caso contrario.
    */
    function buscar_registro($nombre,$database,$campo)
    {
        $encontrado = false;

        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        
        // Sanitizamos las entradas
        $database = $db->escape($database);
        $campo = $db->escape($campo);
        $nombre = $db->Quote($db->escape($nombre));

        // Construimos la consulta
        $query->select('*');
        $query->from('#__' .$database);
        $query->where($campo .'=' .$nombre);
		
		try {
			$db->setQuery($query);
			$result = $db->loadAssocList();
		} catch (Exception $e)
        {    
			$result = false;
            $encontrado = false;
        }             

        if ($result) {
            $encontrado = true;
        }

        return $encontrado;
    }

    /*
    Inserta un registro en la BBDD. Devuelve true si ha tenido éxito y false en caso contrario.
    */
    function insertar_registro($nombre,$version,$tipo)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Sanitizamos las entradas
        $nombre = $db->escape($nombre);
        $version = $db->escape($version);
        $tipo = $db->escape($tipo);

        $valor = (object) array(
        'Product' => $nombre,
        'Installedversion' => $version,
        'sc_type' => $tipo
        );
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $result = $db->insertObject('#__securitycheckpro', $valor, 'id');
        return $result;
    }

    /*
    Compara la BBDD #_securitycheckpro con #_extensions para eliminar componentes desinstalados del sistema y que figuran en dicha BBDD. Los componentes que 
    figuran en #_securitycheckpro se pasan como variable */
    function eliminar_componentes_desinstalados()
    {
        $mainframe = Factory::getApplication();    
        $jinput = $mainframe->input;
        
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = "SELECT * FROM #__securitycheckpro";
        $db->setQuery($query);
        $db->execute();
        $regs_securitycheck = $db->loadAssocList();
        $i = 0;
        $comp_eliminados = 0;
        foreach ($regs_securitycheck as $indice)
        {
            $nombre = $regs_securitycheck[$i]['Product'];
            $database = 'extensions';
            $buscar_componente = $this->buscar_registro($nombre, $database, 'element');
            if (!($buscar_componente)) { /*Si el componente no existe en #_extensions, lo eliminamos  de #_securitycheckpro */
                if ($nombre != 'Joomla!') { /* Este componente no existe como extensión*/
                    $db = Factory::getContainer()->get(DatabaseInterface::class);
                    // Sanitizamos las entradas
                    $nombre = $db->Quote($db->escape($nombre));
                    $query = 'DELETE FROM #__securitycheckpro WHERE Product=' .$nombre;
                    $db->setQuery($query);
                    $db->execute();
                    $comp_eliminados++;            
                }
            }    
            $i++;
        } 
        if ($comp_eliminados > 0) {
            $mensaje_eliminados = Text::_('COM_SECURITYCHECKPRO_DELETED_COMPONENTS');
            $jinput->set('comp_eliminados', $mensaje_eliminados .$comp_eliminados);
        
        }
    }

    /*
    Extrae los nombres de los componentes instalados y actualiza la BBDD de nuestro componente con dichos nombres.
    Un ejemplo de cómo almacena Joomla esta información es el siguiente:

    {"legacy":false,"name":"securitycheckpro","type":"component","creationDate":"2011-04-12","author":"Jose A. Luque","copyright":"Copyright Info",
    "authorEmail":"contacto@protegetuordenador.com","authorUrl":"http:\/\/www.protegetuordenador.com","version":"1.00",
    "description":"COM_SECURITYCHECKPRO_DESCRIPTION","group":""} 

    Esta función debe extraer la información convirtiendo el string json a array y extrayendo los valores que necesitamos
    */
    function actualizarbbdd($registros)
    {
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		$scan_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
		
		$query = "SELECT COUNT(*) FROM #__securitycheckpro";
		try {
			$db->setQuery($query);
			$db->execute();
			$vulnerabilities_table_entries = $db->loadResult();
		} catch (Exception $e)
        {    			
            $vulnerabilities_table_entries = '0';
        } 
		
		// Sólo actualizamos la bbdd si se ha instalado/desinstalado/actualizado una extensión, se ha añadido una nueva entrada a la bbdd de vulnerabilidades por el plugin 'database update' o estamos en una instalación nueva de nuestra extensión
		if ( (file_exists($scan_path."update_vuln_table.php")) || ($vulnerabilities_table_entries == '0') ) {
			
			$config = Factory::getConfig();
			$dbtype = $config->get('dbtype');
									
			$registros_map = array_map(function ($element) {
				$new_array = array();
				$tipo = 'Notdefined';
				$version = '0.0.0';
				$decode = json_decode($element->manifest_cache);
				// Algunos componentes devuelven un valor nulo en el manifest_cache, así que hemos de controlar esto
				if (is_object($decode)) {
					if (property_exists($decode, 'version')) {
						$version = $decode->version;
					}
					if (property_exists($decode, 'type')) {
						$tipo = $decode->type;
					} 
				
				}    
				$new_array['Product'] = $element->element;
				$new_array['Installedversion'] = $version;
				$new_array['sc_type'] = $tipo;
				return $new_array;
			}, $registros);
			
			
			if (strstr($dbtype,"mysql")) {
				$query = "TRUNCATE TABLE #__securitycheckpro";
			} else if (strstr($dbtype,"pgsql")) {
				$query = "TRUNCATE TABLE #__securitycheckpro RESTART IDENTITY";
			}
			$db->setQuery($query);
			$db->execute();
			
			/* Obtenemos y guardamos la versión de Joomla */
			$Version = new Version();
			$joomla_version = $Version->getShortVersion();
			
			$object = new \StdClass();                    
			$object->Product = 'Joomla!';
			$object->Installedversion = $joomla_version;
			$object->sc_type = 'core';
			$db->insertObject('#__securitycheckpro', $object);
			
			foreach ($registros_map as $extension)
			{
				$object = new \StdClass();                    
				$object->Product = $extension['Product'];
				$object->Installedversion = (strlen($extension['Installedversion']) > 20) ? substr($extension['Installedversion'],0,20): $extension['Installedversion'];
				$object->sc_type = $extension['sc_type'];
				$db->insertObject('#__securitycheckpro', $object);
			}	

			// Chequeamos los componentes instalados con la lista de vulnerabilidades conocidas y actualizamos los componentes vulnerables 
			$this->chequear_vulnerabilidades();
			
			// Delete the file used as witness
			if (file_exists($scan_path."update_vuln_table.php")) {				
				File::delete($scan_path."update_vuln_table.php");
			}
		}
    }

    /*
    Busca los componentes instaladas en el equipo. 
    */
    function buscar()
    {
        $jinput = Factory::getApplication()->input;

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = "SELECT * FROM #__extensions WHERE (state=0) AND ((type='component') OR (type='module') OR (type='plugin'))";
        $db->setQuery($query);
        $db->execute();
        $num_rows = $db->getNumRows();
        $result = $db->loadObjectList();	
		       		
        $this->actualizarbbdd($result);
        $eliminados = $jinput->get('comp_eliminados', 0, 'int');
        $jinput->set('eliminados', $eliminados);
        $core_actualizado = $jinput->get('core_actualizado', 0, 'int');
        $jinput->set('core_actualizado', $core_actualizado);
        $comps_actualizados = $jinput->get('componentes_actualizados', 0, 'int');
        $jinput->set('comps_actualizados', $comps_actualizados);
        $comp_ok = Text::_('COM_SECURITYCHECKPRO_CHECK_OK');
        $jinput->set('comp_ok', $comp_ok);
        return true;
    }

    /*
    * Obtiene los datos de la BBDD 'securitycheckpro'
    */
    function getData()
    {
        // Cargamos el contenido si es que no existe todavía
        if (empty($this->_data)) {
			$this-> buscar();			
            $query = $this->_buildQuery();
            $this->_data = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
        }
        return $this->_data;
    }

    /**
     * Obtiene los datos de la BBDD 'securitycheckpro' por tipo de extensión
     */
    function getFilterData()
    {
        // Cargamos los datos
        if (empty($this->_data)) {
            $this-> buscar();			
            $query = $this->_buildFilterQuery();
            $this->_data = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
        }
            
        return $this->_data;
    }

    /* Función que obtiene el id del plugin de: '1' -> Securitycheck Pro Update Database  */
    function get_plugin_id($opcion)
    {

        $db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
        if ($opcion == 1) {
			$query->select($db->quoteName('extension_id'));
            $query->from($db->quoteName('#__extensions'));
            $query->where($db->quoteName('name').' = '.$db->quote('System - Securitycheck Pro Update Database'));
			$query->where($db->quoteName('type').' = '.$db->quote('plugin'));
        } 
		try {			
			$db->setQuery($query);
			$db->execute();
			$id = $db->loadResult();
		} catch (Exception $e)
		{    
			$id = 0;
		}	
    
        return $id;
    }

    /* Función que obtiene la fecha de actualización del último componente añadido a la bbdd por el plugin 'Update Database'  */
    function get_last_update()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		try {
			$query = 'SELECT published FROM #__securitycheckpro_db ORDER BY id DESC LIMIT 1';
			$db->setQuery($query);
			$db->execute();
			$last_date = $db->loadResult();
		} catch (Exception $e)
		{    
			$last_date = "";
		}		       
    
        return $last_date;
    }

    /* Método para cargar todas las vulnerabilidades de un componente pasado en la url */
    function filter_vulnerable_extension($product)
    {
        $data = null;
        $content = "";
        $db = Factory::getContainer()->get(DatabaseInterface::class);
    
        // Cargamos los datos
        if (empty($data)) {
            $product = $db->Quote(htmlspecialchars($product));
			$product_query = $db->quoteName("Product");
            $query = 'SELECT * FROM #__securitycheckpro_db  WHERE id IN (SELECT vuln_id FROM #__securitycheckpro_vuln_components WHERE ' . $product_query .' = '.$product .')';
			$db->setQuery($query);
            $data = $db->loadAssocList();			
        }    
    
        $content = '<table class="table table-bordered table-hover">
					<thead>
						<tr>
							<th class="alert alert-warning text-center" align="center">' . Text::_("COM_SECURITYCHECKPRO_VULNERABILITY_DETAILS") . '
							</th>
							<th class="alert alert-warning text-center" align="center">' . Text::_("COM_SECURITYCHECKPRO_VULNERABILITY_CLASS") . '
							</th>
							<th class="alert alert-warning text-center" align="center">' . Text::_("COM_SECURITYCHECKPRO_VULNERABILITY_PUBLISHED") . '
							</th>
							<th class="alert alert-warning text-center" align="center">' . Text::_("COM_SECURITYCHECKPRO_VULNERABILITY_VULNERABLE") . '
							</th>
							<th class="alert alert-warning text-center" align="center">' . Text::_("COM_SECURITYCHECKPRO_VULNERABILITY_SOLUTION") . '
							</th>
						</tr>
					</thead>';
        foreach ($data as $element)
        {
            $description_sanitized = htmlspecialchars($element['description']);
            $class_sanitized = htmlspecialchars($element['vuln_class']);
            $published_sanitized = htmlspecialchars($element['published']);
            $vulnerable_sanitized = htmlspecialchars($element['vulnerable']);
            $solution_type = htmlspecialchars($element['solution_type']);
            $solution = htmlspecialchars($element['solution']);
            if ($solution_type == 'update') {
                $solution = Text::_('COM_SECURITYCHECKPRO_SOLUTION_TYPE_UPDATE') . ' ' . $solution;                
            } else if ($solution_type == 'none') {
                $solution = Text::_('COM_SECURITYCHECKPRO_SOLUTION_TYPE_NONE');
            }
        
            $content .= '<tr>
						<td class="text-center">' . $description_sanitized . '
						</td>
						<td class="text-center">' . $class_sanitized . '
						</td>
						<td class="text-center">' . $published_sanitized . '
						</td>
						<td class="text-center">' . $vulnerable_sanitized . '
						</td>
						<td class="text-center">' . $solution . '
						</td>
					</tr>';        
        }
    
        $content .= '</table>';    
        return $content;
    }

}
