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
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
/**
 * Modelo Vulninfo
 */
class VulninfoModel extends BaseModel
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

    function __construct()
    {
        parent::__construct();
    
    
        $mainframe = Factory::getApplication();
 
        // Obtenemos las variables de paginación de la petición
        $limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->get('list_limit'), 'uint');
        $input = Factory::getApplication()->input;		
		$limitstart = $input->get('limitstart', 0, 'uint');
       
        $this->setState('limit', $limit);
        $this->setState('limitstart', $limitstart);
    
    }


    /* 
    * Función para obtener el número de registros de la BBDD 'securitycheck_db'
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
    * Devuelve todos los componentes almacenados en la BBDD 'securitycheckpro_db'
    */
    function _buildQuery()
    {
		$local_joomla_branch = explode(".", JVERSION); 
		$joomla_version_db = $local_joomla_branch[0] . ".0.0";
		
		$db = Factory::getDbo();
		
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__securitycheckpro_db'))
			->where($db->quoteName('Joomlaversion') . ' = ' . $db->quote($joomla_version_db))
			->order('id DESC');
		$db->setQuery($query);
		
		return $query->__toString();		
    }

    /**
     * Método para cargar todas las vulnerabilidades de los componentes
     */
    function datos()
    {
        $db = Factory::getDBO();
        $query = 'SELECT * FROM #__securitycheckpro_db ORDER BY id DESC';
        $db->setQuery($query, $this->getState('limitstart'), $this->getState('limit'));
        $data = $db->loadAssocList();
        
        return $data;
    }
}
