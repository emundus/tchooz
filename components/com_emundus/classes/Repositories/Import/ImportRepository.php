<?php
/**
 * @package     Tchooz\Repositories\Import
 * @subpackage
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Repositories\Import;

use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Import\ImportEntity;
use Tchooz\Entities\List\ListResult;
use Tchooz\Enums\Import\ImportStatusEnum;
use Tchooz\Factories\Import\ImportFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: '#__emundus_import',
	alias: 'ei',
	columns: [
		'id'                 => 'id',
		'created_at'         => 'created_at',
		'created_by'         => 'created_by',
		'type'               => 'type',
		'filename'           => 'filename',
		'original_filename'  => 'original_filename',
		'format'             => 'format',
		'conflict_mode'      => 'conflict_mode',
		'progress'           => 'progress',
		'total_rows'         => 'total_rows',
		'last_processed_row' => 'last_processed_row',
		'report'             => 'report',
		'task_id'            => 'task_id',
		'cancelled'          => 'cancelled',
		'failed'             => 'failed',
	]
)]
class ImportRepository extends EmundusRepository implements RepositoryInterface
{
	private ImportFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'import', self::class);

		$this->factory = new ImportFactory();
	}

	public function flush(ImportEntity $import): bool
	{
		if (empty($import->getCreatedAt()))
		{
			$import->setCreatedAt(new \DateTime());
		}

		$object = (object) [
			'filename'           => $import->getFilename(),
			'original_filename'  => $import->getOriginalFilename(),
			'task_id'            => $import->getTask()?->getId(),
			'progress'           => $import->getProgress(),
			'total_rows'         => $import->getTotalRows(),
			'last_processed_row' => $import->getLastProcessedRow(),
			'report'             => !empty($import->getReport()) ? json_encode($import->getReport()) : null,
			'cancelled'          => $import->isCancelled() ? 1 : 0,
			'failed'             => $import->isFailed() ? 1 : 0,
		];

		if (empty($import->getId()))
		{
			// Immutable facts set once at creation.
			$object->created_at    = $import->getCreatedAt()->format('Y-m-d H:i:s');
			$object->created_by    = $import->getCreatedBy()->id;
			$object->type          = $import->getType();
			$object->format        = $import->getFormat();
			$object->conflict_mode = $import->getConflictMode()->value;

			if ($flushed = $this->db->insertObject($this->tableName, $object))
			{
				$import->setId((int) $this->db->insertid());
			}
		}
		else
		{
			$object->id = $import->getId();

			$flushed = $this->db->updateObject($this->tableName, $object, 'id');
		}

		return $flushed;
	}

	public function getById(int $id): ?ImportEntity
	{
		$importEntity = null;

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			$importEntity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $importEntity;
	}

	public function getByTask(int $taskId): ?ImportEntity
	{
		$importEntity = null;

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('task_id = :task_id')
			->bind(':task_id', $taskId, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			$importEntity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $importEntity;
	}

	public function isCancelled(int $id): bool
	{
		$query = $this->db->getQuery(true);

		$query->select('cancelled')
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);

		return (bool) $this->db->loadResult();
	}

	/**
	 * Terminal imports (completed or failed) created before $olderThan. The
	 * purgeimports task uses this to reclaim their durable source files + rows.
	 * In-progress imports are never returned, so a long-running job is never
	 * purged from under the scheduler.
	 *
	 * @return array<ImportEntity>
	 */
	public function getExpiredImports(\DateTime $olderThan): array
	{
		$expired = [];

		$query = $this->db->getQuery(true);
		$date  = $olderThan->format('Y-m-d H:i:s');

		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('created_at <= :older_than')
			->where('(progress >= 100 OR failed = 1)')
			->bind(':older_than', $date);
		$this->db->setQuery($query);
		$dbObjects = $this->db->loadObjectList();

		if (!empty($dbObjects))
		{
			$expired = $this->factory->fromDbObjects($dbObjects, $this->withRelations, $this->exceptRelations);
		}

		return $expired;
	}

	public function getAll(
		?int                    $limit = 25,
		int                     $page = 0,
		string                  $sortDir = 'DESC',
		?ImportStatusEnum $status = null,
		int                     $userId = 0
	): ListResult
	{
		$result = new ListResult([], 0);

		try
		{
			if (empty($limit))
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

			$countQuery = clone $query;
			$countQuery->clear('select')->clear('order')->select('COUNT(id)');
			$this->db->setQuery($countQuery);
			$total = (int) $this->db->loadResult();

			$this->db->setQuery($query, $offset, $limit);
			$imports = $this->db->loadObjectList();

			foreach ($imports as $key => $import)
			{
				$imports[$key] = $this->factory->fromDbObject($import, $this->withRelations, []);
			}

			$result->setTotalItems($total);
			$result->setItems($imports);
		}
		catch (\Exception $e)
		{
			Log::add('Failed to get imports list: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $result;
	}

	public function buildQuery(
		int                     $id = 0,
		?string                 $group_by = '',
		string                  $sortDir = 'DESC',
		?ImportStatusEnum $status = null,
		int                     $userId = 0
	): QueryInterface
	{
		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias));

		if (!empty($id))
		{
			$query->where('id = :id')
				->bind(':id', $id, ParameterType::INTEGER);
		}

		if ($status !== null)
		{
			$query->where(match ($status)
			{
				ImportStatusEnum::FAILED     => 'failed = 1',
				ImportStatusEnum::COMPLETED  => 'failed = 0 AND progress >= 100',
				ImportStatusEnum::CANCELLED  => 'failed = 0 AND progress < 100 AND cancelled = 1',
				ImportStatusEnum::PROCESSING => 'failed = 0 AND cancelled = 0 AND progress < 100',
			});
		}

		if (!empty($userId))
		{
			$query->where('created_by = :created_by')
				->bind(':created_by', $userId, ParameterType::INTEGER);
		}

		if (!empty($group_by) && in_array($group_by, ['created_by', 'type']))
		{
			$query->group($group_by);
		}

		$query->order('created_at ' . $sortDir);

		return $query;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			try
			{
				$importEntity = $this->getById($id);

				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName($this->tableName, $this->alias))
					->where('id = :id')
					->bind(':id', $id, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$deleted = $this->db->execute();

				// Remove the durable source file we persisted at queue time.
				if ($deleted && !empty($importEntity) && !empty($importEntity->getFilename()) && str_starts_with($importEntity->getFilename(), 'tmp/imports/'))
				{
					$importFilePath = JPATH_SITE . '/' . $importEntity->getFilename();
					if (file_exists($importFilePath))
					{
						unlink($importFilePath);
					}
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error deleting import with ID ' . $id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.import.repository');
			}
		}

		return $deleted;
	}
}