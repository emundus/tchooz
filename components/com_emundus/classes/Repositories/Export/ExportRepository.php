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
use Tchooz\Enums\Export\ExportFormatEnum;
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
		if (empty($export->getCreatedAt()))
		{
			$export->setCreatedAt(new \DateTime());
		}

		$object = (object) [
			'filename'   => $export->getFilename(),
			'expired_at' => $export->getExpiredAt()?->format('Y-m-d H:i:s'),
			'task_id'    => $export->getTask()?->getId(),
			'hits'       => $export->getHits(),
			'progress'   => $export->getProgress(),
			'cancelled'  => $export->isCancelled() ? 1 : 0,
			'result'     => !empty($export->getResult()) ? json_encode($export->getResult()) : null,
		];

		if (empty($export->getId()))
		{
			$object->created_at = $export->getCreatedAt()->format('Y-m-d H:i:s');
			$object->created_by = $export->getCreatedBy()->id;
			$object->format     = $export->getFormat()->value;

			if ($flushed = $this->db->insertObject($this->tableName, $object))
			{
				$export->setId((int) $this->db->insertid());
			}
		}
		else
		{
			$object->id = $export->getId();

			$flushed = $this->db->updateObject($this->tableName, $object, 'id');
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

	public function getAllExportTemplates(int $user_id): array
	{
		$query = $this->db->getQuery(true);

		$mode = 'export';

		$query->select('*')
			->from($this->db->qn('#__emundus_filters'))
			->where('user = :user_id')
			->where('mode = :mode')
			->bind(':user_id', $user_id, ParameterType::INTEGER)
			->bind(':mode', $mode)
			->order('time_date DESC');
		$this->db->setQuery($query);
		$templates = $this->db->loadObjectList();

		if (!empty($templates))
		{
			foreach ($templates as $key => $template)
			{
				// Decode constraints
				$constraints         = json_decode($template->constraints, true);
				$template->format    = $constraints['format'] ?? null;
				$template->elements  = $constraints['elements'] ? json_decode($constraints['elements'], true) : [];
				$template->headers   = $constraints['headers'] ?? [];
				$template->synthesis = $constraints['synthesis'] ?? [];
			}
		}

		return $templates ?: [];
	}

	public function getExportTemplate(int $id): ?object
	{
		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->qn('#__emundus_filters'))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$template = $this->db->loadObject();

		return $template ?: null;
	}

	public function saveExportTemplate(string $name, ExportFormatEnum $format, array $elements, array $headers, array $synthesis, array $attachments, int $user_id, int $id = 0): int
	{
		$constraints = [
			'format'    => $format->value,
			'elements'  => json_encode($elements),
			'headers'   => json_encode($headers),
			'synthesis' => json_encode($synthesis),
			'attachments' => json_encode($attachments),
		];

		$template = (object) [
			'name'        => $name,
			'constraints' => json_encode($constraints),
			'item_id'     => 0,
			'mode'        => 'export'
		];

		if (!empty($id))
		{
			$template->id = $id;
			if (!$this->db->updateObject('#__emundus_filters', $template, 'id'))
			{
				throw new InvalidArgumentException('Could not update export template with ID ' . $id);
			}
		}
		else
		{
			$template->time_date = new \DateTime();
			$template->user      = $user_id;
			if (!$this->db->insertObject('#__emundus_filters', $template))
			{
				throw new InvalidArgumentException('Could not insert new export template');
			}

			$id = (int) $this->db->insertid();
		}

		return $id;
	}

	public function deleteExportTemplate(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			try
			{
				$query = $this->db->getQuery(true)
					->delete($this->db->qn('#__emundus_filters'))
					->where('id = :id')
					->bind(':id', $id, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error deleting export template with ID ' . $id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.export.repository');
			}
		}

		return $deleted;
	}
}