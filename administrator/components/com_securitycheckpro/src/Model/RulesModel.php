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

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Application\CMSApplication;

class RulesModel extends BaseDatabaseModel
{

    /**
     * Objeto Pagination 
	 * @var Pagination 
     */
    var $_pagination = null;

    /**
	 * Total number of files of Pagination 
     * @var int $total
     */
    public int $total = 0;
	
	public BaseModel $basemodel;
	
    function __construct()
    {
        parent::__construct();    
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();	
		
		if ( $mainframe instanceof \Joomla\CMS\Application\CMSWebApplicationInterface ) {		    
			// Obtenemos las variables de paginación de la petición
			$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getConfig()->get('list_limit',20), 'int');
			$limitstart = $mainframe->getInput()->getInt('limitstart', 0);

			// En el caso de que los límites hayan cambiado, los volvemos a ajustar
			$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

			$this->setState('limit', $limit);
			$this->setState('limitstart', $limitstart);
			
			$this->basemodel = new BaseModel();
		}
    }

    protected function populateState(): void
	{
		$app = Factory::getApplication();

		if ($app instanceof \Joomla\CMS\Application\CMSWebApplicationInterface) {

			// Usa una clave de contexto para no mezclar estados con otras vistas
			$context = 'com_securitycheckpro.rules';

			// Lee el valor anterior SIN getState() para evitar recursión
			$prevSearch = (string) $app->getUserState($context . '.filter.acl_search', '');

			// Carga/actualiza el filtro desde la request
			$search = (string) $app->getUserStateFromRequest(
				$context . '.filter.acl_search',
				'filter_acl_search',
				''
			);

			// Guarda en el estado del modelo (esto es seguro)
			$this->setState('filter.acl_search', $search);

			// Inicializa el resto de estados (limit, ordering, etc.)
			parent::populateState();

			// Si cambió el filtro, resetea la primera página
			if ($search !== $prevSearch) {
				$this->setState('limitstart', 0);
			}
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

   	/** @return array<object> */
    public function load(?string $data = null): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // --- Sincroniza reglas de forma atómica ---
        $this->syncRulesAtomically($db);

        // --- Filtros y orden ---
        $search    = (string) ($this->getState('filter.acl_search') ?? '');
        $ordering  = (string) ($this->getState('list.ordering') ?? 'a.lft');
        $direction = strtoupper((string) ($this->getState('list.direction') ?? 'ASC'));
        $offset    = (int) ($this->getState('limitstart') ?? 0);
        $limit     = (int) ($this->getState('limit') ?? 20);

        // Whitelist de columnas permitidas
        $allowedOrder = [
            'a.id' => $db->quoteName('a.id'),
            'a.lft' => $db->quoteName('a.lft'),
            'a.rgt' => $db->quoteName('a.rgt'),
            'a.parent_id' => $db->quoteName('a.parent_id'),
            'a.title' => $db->quoteName('a.title'),
            'b.rules_applied' => $db->quoteName('b.rules_applied'),
            'b.last_change' => $db->quoteName('b.last_change'),
        ];
        $orderCol = $allowedOrder[$ordering] ?? $db->quoteName('a.lft');
        $dir = $direction === 'DESC' ? 'DESC' : 'ASC';

        // --- Query principal ---
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.lft'),
                $db->quoteName('a.rgt'),
                $db->quoteName('a.parent_id'),
                $db->quoteName('a.title'),
                $db->quoteName('b.group_id'),
                $db->quoteName('b.rules_applied'),
                $db->quoteName('b.last_change'),
                'COUNT(DISTINCT ' . $db->quoteName('c2.id') . ') AS ' . $db->quoteName('level'),
            ])
            ->from($db->quoteName('#__usergroups') . ' AS ' . $db->quoteName('a'))
            ->join(
                'LEFT OUTER',
                $db->quoteName('#__usergroups') . ' AS ' . $db->quoteName('c2')
                . ' ON ' . $db->quoteName('a.lft') . ' > ' . $db->quoteName('c2.lft')
                . ' AND ' . $db->quoteName('a.rgt') . ' < ' . $db->quoteName('c2.rgt')
            )
            ->join(
                'LEFT OUTER',
                $db->quoteName('#__securitycheckpro_rules') . ' AS ' . $db->quoteName('b')
                . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.group_id')
            )
            ->group(
                implode(', ', [
                    $db->quoteName('a.id'),
                    $db->quoteName('b.group_id'),
                    $db->quoteName('a.lft'),
                    $db->quoteName('a.rgt'),
                    $db->quoteName('a.parent_id'),
                    $db->quoteName('a.title'),
                    $db->quoteName('b.rules_applied'),
                    $db->quoteName('b.last_change'),
                ])
            )
            ->order($orderCol . ' ' . $dir);
     
		if ($search !== '') {
			if (stripos($search, 'id:') === 0) {
				$id = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :gid')
					  ->bind(':gid', $id, ParameterType::INTEGER); 
			} else {
				$like = '%' . $search . '%';
				$query->where($db->quoteName('a.title') . ' LIKE :ttl')
					  ->bind(':ttl', $like, ParameterType::STRING); 
			}
		}


        // --- Total alineado con filtros ---
		$countQuery = clone $query;
		$countQuery->clear('select')->clear('order')->clear('limit')->clear('offset')->clear('group');
		// OJO: DISTINCT por el join con c2
		$countQuery->select('COUNT(DISTINCT ' . $db->quoteName('a.id') . ')');
		$db->setQuery($countQuery);
		$this->total = (int) $db->loadResult();


        // Después de calcular $this->total:
		$offset = (int) ($this->getState('limitstart') ?? 0);
		$limit  = (int) ($this->getState('limit') ?? 20);

		if ($limit > 0 && $offset >= $this->total) {
			$offset = max(0, (int) (floor(($this->total - 1) / $limit) * $limit));
			$this->setState('limitstart', $offset);
		}

		// Paginación en SQL (con offset quizá reajustado)
		$db->setQuery($query, $offset, $limit > 0 ? $limit : 0);
		/** @var array<object> $rows */
		$rows = (array) $db->loadObjectList();

        return $rows;
    }

    private function syncRulesAtomically(DatabaseInterface $db): void
	{
		$db->transactionStart();

		try {
			$timestamp = (string) $this->basemodel->get_Joomla_timestamp();

			$q = $db->getQuery(true)
				->select([$db->quoteName('id'), $db->quoteName('title')])
				->from($db->quoteName('#__usergroups'));
			$db->setQuery($q);

			/** @var array<int, object> $groups */
			$groups = (array) $db->loadObjectList();

			$columns = array_map(
				static fn(string $col): string => (string) $db->quoteName($col),
				['group_id', 'rules_applied', 'last_change']
			);

			foreach ($groups as $g) {
				$gid = (int) ($g->id ?? 0);
				if ($gid <= 0) {
					continue;
				}

				$sql = sprintf(
					'INSERT INTO %s (%s) VALUES (%d, %d, %s) ' .
					'ON DUPLICATE KEY UPDATE %s = VALUES(%s)',
					$db->quoteName('#__securitycheckpro_rules'),
					implode(', ', $columns),
					$gid,
					0,
					$db->quote($timestamp),
					$db->quoteName('last_change'),
					$db->quoteName('last_change')
				);

				$db->setQuery($sql);
				$db->execute();
			}

			$db->transactionCommit();
		} catch (\Throwable $e) {
			$db->transactionRollback();
			Log::add('RulesModel. syncRulesAtomically error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
		}
	}
    
	/**
     * Cambia el estado de reglas para los grupos recibidos en la request (cid[]).
     * @return bool True si se actualiza sin errores (0 cambios también cuenta como éxito).
     */
    public function setRulesAppliedFromRequest(bool $apply): bool
    {
        $app = Factory::getApplication();

        // CSRF + método
        if (!Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }
        if ($app->getInput()->getMethod() !== 'POST') {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 405);
        }

        // ACL
        $user = $app->getIdentity();
        if (!$user->authorise('core.edit.state', 'com_securitycheckpro')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        // IDs
        $ids = (array) $app->getInput()->get('cid', [], 'array');
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn (int $v): bool => $v > 0);
        if ($ids === []) {
            return true; // Nada que hacer
        }

        // Timestamp (si prefieres DB NOW() ajusta set() en consecuencia)
        $timestamp = $this->basemodel->get_Joomla_timestamp(); // string seguro/controlado

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        // Placeholders nombrados por id
        $ph = [];
        foreach ($ids as $i => $_) {
            $ph[] = ':id' . $i;
        }

        $applied = $apply ? 1 : 0;    
		$ts      = (string) $timestamp; 

		$query
			->update($db->quoteName('#__securitycheckpro_rules'))
			->set($db->quoteName('rules_applied') . ' = :applied')
			->set($db->quoteName('last_change')   . ' = :ts')
			->where($db->quoteName('group_id') . ' IN (' . implode(',', $ph) . ')');

		$query->bind(':applied', $applied, ParameterType::INTEGER);
		$query->bind(':ts', $ts, ParameterType::STRING);

		foreach ($ids as $i => $val) {
			$idVal = (int) $val; // por referencia también
			$query->bind(':id' . $i, $idVal, ParameterType::INTEGER);
		}

        try {
            $db->transactionStart();
            $db->setQuery($query)->execute();
            $db->transactionCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transactionRollback();
            Log::add('RulesModel. setRulesAppliedFromRequest error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
            return false;
        }
    }

    // Mantén estas dos para compatibilidad con tus tareas/acciones
    public function apply_rules(): bool
    {
        return $this->setRulesAppliedFromRequest(true);
    }

    public function not_apply_rules(): bool
    {
        return $this->setRulesAppliedFromRequest(false);
    }

}
