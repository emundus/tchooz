<?php

namespace Tchooz\Repositories\Mapping;

use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Factories\Mapping\MappingFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute('#__emundus_connector_mapping', 'mapping', [
	'id',
	'label',
	'synchronizer_id',
	'target_object',
	'params',
])]
class MappingRepository extends EmundusRepository implements RepositoryInterface
{
	private MappingFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'mapping', self::class);
		$this->factory = new MappingFactory();
	}

	/**
	 * @param   int  $id
	 *
	 * @return bool
	 */
	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName($this->primaryKey) . ' = ' . $id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	/**
	 * @param   int  $id
	 *
	 * @return MappingEntity|null
	 */
	public function getById(int $id): ?MappingEntity
	{
		$mapping = null;

		if (!empty($id))
		{
			$query = $this->db->getQuery(true)
				->select($this->alias. '.*')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName($this->alias. '.' . $this->primaryKey) . ' = ' . $id);

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if (!empty($result))
			{
				$mapping = $this->factory->fromDbObjects([$result])[0];
			}
		}

		return $mapping;
	}

	/**
	 * @param   MappingEntity  $mappingEntity
	 *
	 * @return bool
	 */
	public function flush(MappingEntity $mappingEntity): bool
	{
		$flushed = false;

		if (empty($mappingEntity->getId()))
		{
			$object = (object)[
				'label'           => $mappingEntity->getLabel(),
				'synchronizer_id' => $mappingEntity->getSynchronizerId(),
				'target_object'   => $mappingEntity->getTargetObject(),
				'params'          => $mappingEntity->getParams() ? json_encode($mappingEntity->getParams()) : null,
			];

			$flushed = $this->db->insertObject($this->tableName, $object, $this->primaryKey);
			if ($flushed)
			{
				$mappingEntity->setId($this->db->insertid());
			}
		}
		else
		{
			$object = (object)[
				'id'              => $mappingEntity->getId(),
				'label'           => $mappingEntity->getLabel(),
				'synchronizer_id' => $mappingEntity->getSynchronizerId(),
				'target_object'   => $mappingEntity->getTargetObject(),
				'params'          => $mappingEntity->getParams() ? json_encode($mappingEntity->getParams()) : null,
			];

			$flushed = $this->db->updateObject($this->tableName, $object, $this->primaryKey);
		}

		if ($flushed)
		{
			$mappingRowRepository = new MappingRowRepository();
			$existingRows = $mappingRowRepository->getByMappingId($mappingEntity->getId());
			$existingRowIds = array_map(fn($row) => $row->getId(), $existingRows);
			$currentRowIds = array_map(fn($row) => $row->getId(), $mappingEntity->getRows());
			$rowsToDelete = array_diff($existingRowIds, $currentRowIds);

			foreach ($rowsToDelete as $rowId)
			{
				$mappingRowRepository->delete($rowId);
			}

			foreach ($mappingEntity->getRows() as $mappingRow)
			{
				$mappingRow->setMappingId($mappingEntity->getId());
				$mappingRowRepository->flush($mappingRow);
			}
		}

		return $flushed;
	}

	/**
	 * @param   array  $filters
	 *
	 * @return int
	 */
	public function count(array $filters = []): int
	{
		$count = 0;

		try {
			$query = $this->db->getQuery(true)
				->select('COUNT(id)')
				->from($this->db->quoteName($this->tableName));

			if (!empty($filters))
			{
				$this->applyFilters($query, $filters);
			}

			$this->db->setQuery($query);
			$count = (int) $this->db->loadResult();
		} catch (\Exception $e) {
			Log::add('Error counting mappings: ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $count;
	}

	/**
	 * @param   array  $filters
	 * @param   int    $limit
	 * @param   int    $page
	 *
	 * @return array<MappingEntity>
	 */
	public function getAll(array $filters = [], int $limit = 25, int $page = 1): array
	{
		$mappings = [];

		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName));

		if (!empty($filters))
		{
			$this->applyFilters($query, $filters);
		}

		if (!empty($limit))
		{
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();
		if ($results)
		{
			$mappings = $this->factory->fromDbObjects($results);
		}

		return $mappings;
	}

	/**
	 * @param   object  $query
	 * @param   array   $filters
	 *
	 * @return void
	 */
	public function applyFilters(object $query, array $filters): void
	{
		if (!empty($filters))
		{
			$query->where('1 = 1');

			foreach ($filters as $field => $value)
			{
				if (!empty($value) && in_array($field, $this->columns))
				{
					if (is_array($value))
					{
						$query->andWhere($this->db->quoteName($field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
					}
					else
					{
						$query->andWhere($this->db->quoteName($field) . ' = ' . $this->db->quote($value));
					}
				}
			}
		}
	}
}