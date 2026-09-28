<?php
/**
 * @package     Tchooz\Repositories\Logs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Logs;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Logs\LogEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: '#__emundus_logs',
	alias: 'el',
	columns: [
		'id',
		'timestamp',
		'user_id_from',
		'user_id_to',
		'fnum_to',
		'action_id',
		'verb',
		'message',
		'params',
		'ip_from'
	]
)
]
class LogRepository extends EmundusRepository implements RepositoryInterface
{
	const NAME = 'log';

	private ?ActionRepository $actionRepository = null;

	/**
	 * Insert order, derived from the table attribute so a new column cannot be declared twice.
	 *
	 * @var string[]
	 */
	private array $insertColumns;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, self::NAME, self::class);

		$this->insertColumns = array_values(array_diff($this->columnsNoAlias, [$this->primaryKey]));
	}

	/**
	 * Writes one history entry.
	 *
	 * Journaling is auxiliary to the operation it records: a failure here must never abort the
	 * business flow, so this returns false instead of throwing. That is also what the ~100
	 * EmundusModelLogs::log() call sites expect.
	 */
	public function add(LogEntity $log): bool
	{
		$added    = false;
		$actionId = $this->resolveActionId($log->getAction());

		if (!$this->isLoggable($log, $actionId))
		{
			return false;
		}

		$query = $this->db->getQuery(true)
			->insert($this->db->quoteName($this->tableName))
			->columns($this->db->quoteName($this->insertColumns))
			->values($this->buildValues($log, $actionId));

		try
		{
			$this->db->setQuery($query);
			$added = (bool) $this->db->execute();

			if ($added)
			{
				$log->setId((int) $this->db->insertid());
			}
		}
		catch (\Exception $e)
		{
			Log::add('Failed to write log entry for fnum ' . $log->getFnum() . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.' . self::NAME);
		}

		return $added;
	}

	/**
	 * Writes several history entries in a single query.
	 *
	 * @param   LogEntity[]  $logs
	 */
	public function addMany(array $logs): bool
	{
		$added = false;

		$loggables = [];
		foreach ($logs as $log)
		{
			$actionId = $this->resolveActionId($log->getAction());

			if ($this->isLoggable($log, $actionId))
			{
				$loggables[] = [$log, $actionId];
			}
		}

		if (empty($loggables))
		{
			return false;
		}

		$query = $this->db->getQuery(true)
			->insert($this->db->quoteName($this->tableName))
			->columns($this->db->quoteName($this->insertColumns));

		foreach ($loggables as [$log, $actionId])
		{
			$query->values($this->buildValues($log, $actionId));
		}

		try
		{
			$this->db->setQuery($query);
			$added = (bool) $this->db->execute();
		}
		catch (\Exception $e)
		{
			Log::add('Failed to write ' . count($loggables) . ' log entries : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.' . self::NAME);
		}

		return $added;
	}

	public function getById(int $id): ?LogEntity
	{
		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName($this->alias . '.id') . ' = ' . (int) $id);

		$this->db->setQuery($query);
		$row = $this->db->loadObject();

		return !empty($row) ? $this->fromDbObject($row) : null;
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$this->db->setQuery($query);

		return (bool) $this->db->execute();
	}

	/**
	 * Every user is logged: the only configurable gate is the excluded actions.
	 */
	private function isLoggable(LogEntity $log, int $actionId): bool
	{
		if (empty($log->getUserFrom()))
		{
			Log::add('Cannot log action [' . $actionId . '] : user_id_from cannot be empty', Log::WARNING, 'com_emundus.repository.' . self::NAME);

			return false;
		}

		$excludedActions = ComponentHelper::getParams('com_emundus')->get('log_actions_exclude', '');
		$excludedActions = empty($excludedActions) ? [] : explode(',', $excludedActions);

		return !in_array($actionId, $excludedActions);
	}

	private function buildValues(LogEntity $log, int $actionId): string
	{
		if (!class_exists('EmundusHelperDate'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/date.php';
		}

		$timestamp = $log->getTimestamp() ?? \EmundusHelperDate::getNow();
		$ip        = $log->getIp() ?? Factory::getApplication()->input->server->get('REMOTE_ADDR', '');
		$params    = empty($log->getParams()) ? '' : json_encode($log->getParams(), JSON_UNESCAPED_UNICODE);

		return implode(',', [
			$this->db->quote($timestamp),
			$this->db->quote($log->getUserFrom()),
			// user_id_to is a nullable int: an absent recipient is NULL, never an empty string.
			empty($log->getUserTo()) ? 'NULL' : $this->db->quote($log->getUserTo()),
			$this->db->quote($log->getFnum() ?? ''),
			$actionId,
			$this->db->quote($log->getCrud()?->value ?? ''),
			$this->db->quote($log->getMessage()),
			$this->db->quote($params),
			$this->db->quote($ip)
		]);
	}

	/**
	 * Action ids are environment-dependent above the seeded range, so a name is always resolved
	 * through the actions referential rather than hardcoded.
	 */
	private function resolveActionId(ActionEnum|string|int $action): int
	{
		if ($action instanceof ActionEnum)
		{
			$action = $action->value;
		}

		if (is_numeric($action))
		{
			return (int) $action;
		}

		if ($this->actionRepository === null)
		{
			$this->actionRepository = new ActionRepository();
		}

		$actionId = $this->actionRepository->getByName($action)?->getId();

		if (empty($actionId))
		{
			// action_id 0 matches no row of the referential: the entry would render as blank.
			Log::add('Unknown action name "' . $action . '", the entry will not be attached to any action', Log::WARNING, 'com_emundus.repository.' . self::NAME);
		}

		return (int) $actionId;
	}

	private function fromDbObject(object $row): LogEntity
	{
		return new LogEntity(
			userFrom: (int) $row->user_id_from,
			action: (int) $row->action_id,
			crud: CrudEnum::tryFrom($row->verb ?? ''),
			message: $row->message ?? '',
			params: json_decode($row->params ?? '', true) ?? [],
			fnum: $row->fnum_to ?: null,
			userTo: $row->user_id_to !== null ? (int) $row->user_id_to : null,
			timestamp: $row->timestamp,
			ip: $row->ip_from,
			id: (int) $row->id
		);
	}
}
