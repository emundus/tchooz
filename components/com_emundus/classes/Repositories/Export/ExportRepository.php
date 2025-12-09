<?php
/**
 * @package     Tchooz\Repositories\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Export;

use http\Exception\InvalidArgumentException;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\List\ListResult;
use Tchooz\Factories\Export\ExportFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: '#__emundus_exports',
	alias: 'ee',
	columns: [
		'id'         => 'id',
		'created_at' => 'created_at',
		'created_by' => 'created_by',
		'filename'   => 'filename',
		'format'     => 'format',
		'expired_at' => 'expired_at',
		'task_id'    => 'task_id',
		'hits'       => 'hits',
		'progress'   => 'progress',
		'cancelled'  => 'cancelled',
		'failed'     => 'failed',
	]
)]
class ExportRepository extends EmundusRepository implements RepositoryInterface
{
	private ExportFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'export', self::class);

		$this->factory = new ExportFactory();
	}

	public function flush(ExportEntity $export): bool
	{
		$flushed = false;

		if (empty($export->getCreatedAt()))
		{
			$export->setCreatedAt(new \DateTime());
		}

		if (empty($export->getId()))
		{
			$insert = (object) [
				'created_at' => $export->getCreatedAt()->format('Y-m-d H:i:s'),
				'created_by' => $export->getCreatedBy()->id,
				'filename'   => $export->getFilename(),
				'format'     => $export->getFormat()->value,
				'expired_at' => $export->getExpiredAt()?->format('Y-m-d H:i:s'),
				'task_id'    => $export->getTask()?->getId(),
				'hits'       => $export->getHits(),
				'progress'   => $export->getProgress(),
				'cancelled'  => $export->isCancelled() ? 1 : 0,
			];

			if ($flushed = $this->db->insertObject($this->tableName, $insert))
			{
				$export->setId((int) $this->db->insertid());
			}
		}
		else
		{
			$updated = (object) [
				'id'         => $export->getId(),
				'filename'   => $export->getFilename(),
				'expired_at' => $export->getExpiredAt()?->format('Y-m-d H:i:s'),
				'task_id'    => $export->getTask()?->getId(),
				'hits'       => $export->getHits(),
				'progress'   => $export->getProgress(),
				'cancelled'  => $export->isCancelled() ? 1 : 0,
			];
			$flushed = $this->db->updateObject($this->tableName, $updated, 'id');
		}

		return $flushed;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			try
			{
				$query = $this->db->getQuery(true)
					->delete($this->db->qn($this->tableName, $this->alias))
					->where('id = :id')
					->bind(':id', $id, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error deleting export with ID ' . $id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.export.repository');
			}
		}

		return $deleted;
	}

	public function getById(int $id): ?ExportEntity
	{
		$exportEntity = null;

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->qn($this->tableName, $this->alias))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			$exportEntity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $exportEntity;
	}

	public function getByFilenameAndUser(string $filename, int $userId): ?ExportEntity
	{
		$exportEntity = null;

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->qn($this->tableName, $this->alias))
			->where('created_by = :created_by')
			->where('filename = :filename')
			->bind(':created_by', $userId, ParameterType::INTEGER)
			->bind(':filename', $filename);
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			$exportEntity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $exportEntity;
	}

	public function getExportByTask(int $task_id): ?ExportEntity
	{
		$exportEntity = null;

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->qn($this->tableName, $this->alias))
			->where('task_id = :task_id')
			->bind(':task_id', $task_id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			$exportEntity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $exportEntity;
	}

	public function isCancelled(int $id): bool
	{
		$query = $this->db->getQuery(true);

		$query->select('cancelled')
			->from($this->db->qn($this->tableName, $this->alias))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);

		return (bool) $this->db->loadResult();
	}

	public function getLastExportByUser(int $userId): ?ExportEntity
	{
		$exportEntity = null;

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->qn($this->tableName, $this->alias))
			->where('created_by = :created_by')
			->order('created_at DESC')
			->bind(':created_by', $userId, ParameterType::INTEGER);
		$this->db->setQuery($query, 0, 1);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			$exportEntity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $exportEntity;
	}

	/**
	 *
	 * @return array<ExportEntity>
	 *
	 */
	public function getExpiredExports(): array
	{
		$expiredExports = [];

		$query = $this->db->getQuery(true);

		// Get exports expired before now
		$now = (new \DateTime())->format('Y-m-d H:i:s');

		$query->select($this->columns)
			->from($this->db->qn($this->tableName, $this->alias))
			->where('expired_at <= :expired_at')
			->bind(':expired_at', $now);
		$this->db->setQuery($query);
		$dbObjects = $this->db->loadObjectList();

		if (!empty($dbObjects))
		{
			$expiredExports = $this->factory->fromDbObjects($dbObjects, $this->withRelations, $this->exceptRelations);
		}

		return $expiredExports;
	}

	public function getAll(
		int|string $limit = 25,
		           $page = 0,
		string     $sortDir = 'DESC',
		string     $status = 'all',
		int        $userId = 0
	): ListResult
	{
		$result = new ListResult([], 0);

		try
		{
			if (empty($limit) || $limit === 'all')
			{
				$limit = null;
			}

			if (empty($page) || empty($limit))
			{
				$offset = 0;
			}
			else
			{
				$offset = ($page - 1) * $limit;
			}

			$query = $this->buildQuery(0, '', $sortDir, $status, $userId);
			$this->db->setQuery($query);
			$total = sizeof($this->db->loadObjectList());

			$this->db->setQuery($query, $offset, $limit);
			$exports = $this->db->loadObjectList();

			foreach ($exports as $key => $export)
			{
				$exports[$key] = $this->factory->fromDbObject($export, $this->withRelations, []);
			}

			$result->setTotalItems($total);
			$result->setItems($exports);
		}
		catch (\Exception $e)
		{
			Log::add('Failed to get exports list: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $result;
	}

	public function buildQuery(
		int     $id = 0,
		?string $group_by = '',
		string  $sortDir = 'DESC',
		string  $status = 'all',
		int     $userId = 0
	): QueryInterface
	{
		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->qn($this->tableName, $this->alias));
		if (!empty($id))
		{
			$query->where('id = :id')
				->bind(':id', $id, ParameterType::INTEGER);
		}

		if (!empty($status))
		{
			if ($status == 'in_progress')
			{
				$query->where('progress < 100');
			}
			elseif ($status == 'completed')
			{
				$query->where('progress = 100');
			}
		}

		if (!empty($userId))
		{
			$query->where('created_by = :created_by')
				->bind(':created_by', $userId, ParameterType::INTEGER);
		}

		if (!empty($group_by) && in_array($group_by, ['created_by', 'hits']))
		{
			$query->group($group_by);
		}

		$query->order('created_at ' . $sortDir);

		return $query;
	}
}