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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;

class VulninfoModel extends BaseDatabaseModel
{
    /**
     Array de datos
     *
     @var array<string>
     */
    var $_data;
	
    /**
     Total items
     *
     @var integer
     */
	 
    var $total = null;
	
    /**
     Objeto Pagination
     *
     @var object
     */	 
    var $_pagination = null;
	
	protected function populateState(): void
    {
        parent::populateState();
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app   = Factory::getApplication();
        $limit = $app->getUserStateFromRequest('global.limit', 'limit', $app->getConfig()->get('list_limit',20), 'int');
		$limitstart = $app->getInput()->get('limitstart', 0);
		
		// En el caso de que los límites hayan cambiado, los volvemos a ajustar
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);
		
		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);         

        // Filtro fijo por rama de Joomla (no hay buscador)
        $major = (int) explode('.', JVERSION)[0];
        $this->setState('filter.branch', $major);
    }

    /**
     * Obtiene items paginados de la tabla de vulnerabilidades.
     *
     * @return array<int, array<string, string|null>>  Lista indexada de filas asociativas.
     */
    public function getItems(): array
    {
        $db   = Factory::getContainer()->get(DatabaseInterface::class);
        $qry  = $db->getQuery(true);

        $major             = (int) $this->getState('filter.branch');
		
        // --- total ---
        if ($this->total === null) {
            $count = clone $qry;
            $count
                ->select('COUNT(*)')
                ->from($db->quoteName('#__securitycheckpro_db'))
                ->where($db->quoteName('Joomlaversion') . ' = ' . $db->quote($major));
            $db->setQuery($count);
            $this->total = (int) $db->loadResult();
        }
		
        // Corrige start fuera de rango si cambiaron los límites
        $limit = (int) $this->getState('limit');
        $start = (int) $this->getState('limitstart');
        if ($start >= $this->total && $this->total > 0) {
            $pages = (int) floor(($this->total - 1) / max($limit, 1));
            $start = $pages * $limit;
            $this->setState('limitstart', $start);
        }

        // --- datos paginados ---
        $qry = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__securitycheckpro_db'))
            ->where($db->quoteName('Joomlaversion') . ' = ' . $db->quote($major))
            ->order($db->quoteName('id') . ' DESC');

        $db->setQuery($qry, $start, $limit);
        return (array) $db->loadAssocList();
    }

    public function getTotal(): int
    {
        if ($this->total === null) {
            // Asegura cálculo del total si alguien llama antes a getItems()
            $this->getItems();
        }
        return $this->total;
    }

    public function getPagination(): Pagination
    {
        return new Pagination($this->getTotal(), (int) $this->getState('limitstart'), (int) $this->getState('limit'));
    }
}
