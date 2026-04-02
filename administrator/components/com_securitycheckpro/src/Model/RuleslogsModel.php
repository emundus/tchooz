<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

// @codeCoverageIgnoreStart
defined('_JEXEC') or die;
// @codeCoverageIgnoreEnd

use Joomla\CMS\Factory;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;

class RuleslogsModel extends BaseDatabaseModel
{

    /**
     * Objeto Pagination 
	 * @var Pagination 
     */
    var $_pagination = null;

    /**
     * @var int Total number of files of Pagination 
     */
    var $total = 0;

    function __construct()
    {
        parent::__construct();  
		
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();
    
        // Obtenemos las variables de paginación de la petición
		if ( $mainframe instanceof \Joomla\CMS\Application\CMSWebApplicationInterface ) {
			$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getConfig()->get('list_limit',20), 'int');
			$limitstart = $mainframe->getInput()->getInt('limitstart', 0);

			// En el caso de que los límites hayan cambiado, los volvemos a ajustar
			$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

			$this->setState('limit', $limit);
			$this->setState('limitstart', $limitstart);
		}
    }

    protected function populateState()
    {
        // Inicializamos las variables
        $app = Factory::getApplication();
		
		if ( $app instanceof \Joomla\CMS\Application\CMSWebApplicationInterface ) {		
			$search = $app->getUserStateFromRequest('filter.rules_search', 'filter_rules_search');
			$this->setState('filter.rules_search', $search);
		
			parent::populateState();
		}
    }

   	/**
     * Función para la paginación
     *
     *
     * @return  Pagination
     *     
     */
    function getPagination()
    {
		// Cargamos el contenido si es que no existe todavía
        if ($this->_pagination === null) {        
            $this->_pagination = new Pagination($this->total, $this->getState('limitstart'), $this->getState('limit'));
        }
        return $this->_pagination;
    }

    /**
     * Función para cargar los logs de confianza
     *
     *
     * @return  array<string>
     *     
     */
    function load_rules_logs(): array
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		// ACL mínima (ajusta al permiso real de tu componente)
		if (!$app->getIdentity()->authorise('core.manage', 'com_securitycheckpro')) {
			// Lanza excepción o devuelve vacío según tu política
			throw new \RuntimeException('Not authorised', 403);
		}

		/** @var DatabaseInterface $db */
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('a') . '.*')
			->from($db->quoteName('#__securitycheckpro_rules_logs') . ' AS a');

		// Filtro de búsqueda (no usar empty() por el caso "0")
		$search = (string) $this->getState('filter.rules_search', '');
		if ($search !== '') {
			// Escapa para LIKE (true -> escapa % y _), y añade comodines controlados
			$pattern = '%' . $db->escape($search, true) . '%';
			$quoted  = $db->quote($pattern);

			$query->where(
				'('
				. $db->quoteName('a.ip')        . " LIKE $quoted OR "
				. $db->quoteName('a.username')  . " LIKE $quoted OR "
				. $db->quoteName('a.last_entry'). " LIKE $quoted OR "
				. $db->quoteName('a.reason')    . " LIKE $quoted"
				. ')'
			);
		}

		// Orden consistente para paginar de forma fiable
		$query->order($db->quoteName('a.id') . ' DESC');

		// --------- Conteo total (para paginación) ----------
		$countQuery = clone $query;
		$countQuery->clear('select')
				   ->clear('order')
				   ->select('COUNT(*)');

		$db->setQuery($countQuery);
		$this->total = (int) $db->loadResult();

		// --------- Paginación en SQL ----------
		$offset = (int) $this->getState('limitstart', 0);
		$limit  = (int) $this->getState('limit', 20); // valor por defecto prudente

		// Si limit==0, devuelve todo (pero desde SQL, no en memoria)
		if ($limit > 0) {
			$db->setQuery($query, $offset, $limit);
		} else {
			$db->setQuery($query);
		}

		$items = (array) $db->loadObjectList();

		return $items;
	}

}
