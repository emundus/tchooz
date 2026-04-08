<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Session\Session;
use Joomla\Database\ParameterType;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;


class Trackactions_logsModel extends ListModel
{
	/**
     * Constructor
     *
     * @param   array<string>                $config   An array of configuration options (name, state, dbo, table_path, ignore_request).     
     *
     * @since   3.0
     * @throws  \Exception
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
				'a.id', 'id',
				'a.extension', 'extension',
				'a.user_id', 'user',
				'a.message', 'message',
				'a.log_date', 'log_date',
				'a.ip_address', 'ip_address'
            ];
        }
    
        parent::__construct($config);
    
    }

    protected function populateState($ordering = null,$direction = null)
    {
        // Inicializamos las variables
        $app        = Factory::getApplication();
    
		if ( $app instanceof \Joomla\CMS\Application\CMSWebApplicationInterface ) {		
			$search = $app->getUserStateFromRequest('filter.search', 'filter_search');
			$this->setState('filter.search', $search);
			$user = $app->getUserStateFromRequest('filter.user', 'filter_user');
			$this->setState('filter.user', $user);
			$extension = $app->getUserStateFromRequest('filter.extension', 'filter_extension');
			$this->setState('filter.extension', $extension);
			$ip_address = $app->getUserStateFromRequest('filter.ip_address', 'filter_ip_address');
			$this->setState('filter.ip_address', $ip_address);
			$daterange = $app->getUserStateFromRequest('daterange', 'daterange');
			$this->setState('filter.dateRange', $daterange);
		
			parent::populateState('id', 'DESC');
		}
    }

	/**
     * Función para cargar los datos
     *
     *
     * @return  string
     *     
     */
    public function getListQuery()
    {
        
        // Chequeamos el rango para borrar logs
        $this->checkIn();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('a.*')
            ->from($db->quoteName('#__securitycheckpro_trackactions', 'a'));

        // Get ordering
        $fullorderCol = $this->state->get('list.fullordering', 'a.id DESC');

        // Apply ordering
        if (!empty($fullorderCol)) {
            $query->order($db->escape($fullorderCol));
        }

        // Get filter by user
        $user = $this->getState('filter.user');

        // Apply filter by user
        if (!empty($user)) {
            $query->where($db->quoteName('a.user_id') . ' = ' . (int) $user);
        }

        // Get filter by extension
        $extension = $this->getState('filter.extension');

        // Apply filter by extension
        if (!empty($extension)) {
            $query->where($db->quoteName('a.extension') . ' = ' . $db->quote($extension));
        }

        // Get filter by date range
        $dateRange = $this->getState('filter.dateRange');

        // Apply filter by date range
        if (!empty($dateRange)) {
            $date = $this->buildDateRange($dateRange);
			
            // If the chosen range is not more than a year ago
            if ($date['dNow'] != false) {
                $query->where(
                    $db->qn('a.log_date') . ' >= ' . $db->quote($date['dStart']->format('Y-m-d H:i:s')) .
                    ' AND ' . $db->qn('a.log_date') . ' <= ' . $db->quote($date['dNow']->format('Y-m-d H:i:s'))
                );
            }
        }

        // Filter the items over the search string if set.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true)) . '%');
            $query->where('(a.message LIKE ' . $search . ')');
        }

        return $query;
    }
        
    /**
     * Check for old logs that needs to be deleted_comment
     *
     * @return void
     *    
     */
    protected function checkIn()
    {
        $model = new BaseModel();
        
        //  Parámetros del componente
        $items= $model->getConfig();
        $daysToDeleteAfter = (int) $items['delete_period'];
        
        if ($daysToDeleteAfter > 0) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $conditions = array($db->quoteName('log_date') . ' < DATE_SUB(NOW(), INTERVAL ' . $daysToDeleteAfter . ' DAY)');

            $query->delete($db->quoteName('#__securitycheckpro_trackactions'))->where($conditions);
            $db->setQuery($query);

            try
            {
                $db->execute();
            }
            catch (\RuntimeException $e)
            {
                Factory::getApplication()->enqueueMessage($db->getMessage(), 'warning');
                return;
            }
        }
        
    }        
    
    /**
	 * @return array{dNow: Date|false, dStart: Date|string}
	 */
	private function buildDateRange(string $range): array
	{
		// Siempre empezamos en UTC
		$dNow   = new Date('now', new \DateTimeZone('UTC'));
		$dStart = clone $dNow;

		switch ($range) {
			case 'past_week':
				$dStart->modify('-7 day');
				break;

			case 'past_1month':
				$dStart->modify('-1 month');
				break;

			case 'past_3month':
				$dStart->modify('-3 month');
				break;

			case 'past_6month':
				$dStart->modify('-6 month');
				break;			
			case 'past_year':
				$dStart->modify('-1 year');
				break;

			case 'today':
				// Alinear a medianoche local y volver a UTC
				/** @var \Joomla\CMS\Application\CMSApplication $app */
				$app = Factory::getApplication();
				$tz  = new \DateTimeZone((string) $app->getConfig()->get('offset', 'UTC'));

				$dStart = new Date('now', $tz);
				$dStart->setTime(0, 0, 0);
				$dStart->setTimezone(new \DateTimeZone('UTC'));
				break;

			case 'never':
				$dNow = false;

				/** @var DatabaseInterface $db */
				$db = $this->getDatabase(); // o Factory::getContainer()->get(DatabaseInterface::class)
				$dStart = $db->getNullDate(); // string tipo '0000-00-00 00:00:00'
				break;

			default:
				// Mantén los valores por defecto (rango “ahora ? ahora”)
				break;
		}

		return ['dNow' => $dNow, 'dStart' => $dStart];
	}
    
	/**
	 * Borra logs seleccionados por id (backend).
	 */
	function delete(): void
	{
		// CSRF
		if (!Session::checkToken('request')) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		// ACL
		if (!$user->authorise('core.delete', 'com_securitycheckpro')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$uids = $app->getInput()->get('cid', [], 'array');
		ArrayHelper::toInteger($uids);
		
		if ($uids === []){
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_ELEMENTS_SELECTED'), 'warning');
			return;
		}

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Por seguridad/control de tamaño
		$uids = array_values(
			array_unique(
				array_filter(
					array_map(
						static fn($v): int => (int) $v,
						$uids
					),
					static fn(int $v): bool => $v > 0
				)
			)
		);

		if ($uids === []) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_VALID_IDS'), 'warning');
			return;
		}

		// Borrado por lotes con transacción
		$db->transactionStart();

		try {
			$chunks = array_chunk($uids, 500);
			foreach ($chunks as $batch) {
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__securitycheckpro_trackactions'))
					->whereIn($db->quoteName('id'), $batch, ParameterType::INTEGER);

				$db->setQuery($query);
				$db->execute();
			}

			$db->transactionCommit();
			$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_N_ITEMS_DELETED', count($uids)), 'message');
		} catch (\Throwable $e) {
			$db->transactionRollback();
			Log::add('Trackactions_logsModel. delete function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');			
			throw new \RuntimeException(Text::_('COM_SECURITYCHECKPRO_DELETE_FAILED') . ': ' . $e->getMessage(), 500, $e);
		}
	}

	/**
	 * Vacía toda la tabla de logs (backend).
	 * Requiere permiso fuerte específico.
	 */
	function delete_all(): void
	{
		if (!Session::checkToken('request')) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if (!$user->authorise('logs.deleteall', 'com_securitycheckpro')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__securitycheckpro_trackactions'));
			$db->setQuery($query);
			$db->execute();

			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_TABLE_CLEARED'), 'message');
		} catch (\Throwable $e) {
			throw new \RuntimeException(Text::_('COM_SECURITYCHECKPRO_TRUNCATE_FAILED') . ': ' . $e->getMessage(), 500, $e);
		}
	}

    /**
     * Get logs data into Table object
     *
	 * @param   array<string>|null          $pks    Array with the packages
     * @return list<object{id: mixed, message: mixed, log_date: mixed, extension: mixed, user_id: mixed, ip_address: mixed}>
	 *     
     */
    public function getLogsData($pks = null)
    {
        if ($pks == null) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('a.*')
                ->from($db->quoteName('#__securitycheckpro_trackactions', 'a'));
            $db->setQuery($query);

            return $db->loadObjectList();
        }
        else
        {
            $items = [];
            Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/tables');
            $table = $this->getTable('TrackActions', 'Table');

            foreach ($pks as $i => $pk)
            {
                $table->load($pk);
                $items[] = (object) array(
                'id'         => $table->id,
                'message'    => $table->message,
                'log_date'   => $table->log_date,
                'extension'  => $table->extension,
                'user_id'    => $table->user_id,
                'ip_address' => $table->ip_address,
                );
            }

            return $items;
        }
    }
}
