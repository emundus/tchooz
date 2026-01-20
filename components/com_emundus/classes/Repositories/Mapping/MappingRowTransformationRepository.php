<?php

namespace Tchooz\Repositories\Mapping;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Mapping\MappingTransformEntity;
use Tchooz\Factories\Mapping\MappingRowTransformationFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: 'jos_emundus_connector_mapping_row_transformation', alias: 'mapping_row_transformation',
	columns: [
		'id',
		'mapping_row_id',
		'order',
		'type',
		'parameters',
	]
)]
class MappingRowTransformationRepository extends EmundusRepository implements RepositoryInterface
{
	private MappingRowTransformationFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'mapping_row_transformation', self::class);
		$this->factory = new MappingRowTransformationFactory();
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName($this->alias . '.' . $this->primaryKey) . ' = ' . $id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function flush(MappingTransformEntity $transformEntity): bool
	{
		$flushed = false;

		if (!empty($transformEntity->getId()))
		{
			$existingEntity = $this->getById($transformEntity->getId());
			if (!$existingEntity) {
				$transformEntity->setId(0);
			}
		}

		if (!empty($transformEntity->getId()))
		{
			$object = (object) [
				'id' => $transformEntity->getId(),
				'mapping_row_id' => $transformEntity->getMappingRowId(),
				'order' => $transformEntity->getOrder(),
				'type' => $transformEntity->getType()->value,
				'parameters' => json_encode($transformEntity->getParameters()),
			];

			$flushed = $this->db->updateObject($this->tableName, $object, 'id');
		}
		else
		{
			$object = (object) [
				'mapping_row_id' => $transformEntity->getMappingRowId(),
				'order' => $transformEntity->getOrder(),
				'type' => $transformEntity->getType()->value,
				'parameters' => json_encode($transformEntity->getParameters()),
			];
			$flushed = $this->db->insertObject($this->tableName, $object);

			if ($flushed)
			{
				$transformEntity->setId((int) $this->db->insertid());
			}
		}

		return $flushed;
	}

	public function getById(int $id): ?MappingTransformEntity
	{
		$transformation = null;

		if (!empty($id))
		{
			$query = $this->db->getQuery(true)
				->select('*')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName($this->alias . '.' . $this->primaryKey) . ' = ' . $id);

			$this->db->setQuery($query);
			$dbObject = $this->db->loadObject();

			if ($dbObject)
			{
				$transformations = $this->factory->fromDbObjects([$dbObject]);
				$transformation = $transformations[0] ?? null;
			}
		}

		return $transformation;
	}

	/**
	 * @param   int  $mappingRowId
	 *
	 * @return array<MappingTransformEntity>
	 */
	public function getByMappingRowId(int $mappingRowId): array
	{
		$transformations = [];

		if (!empty($mappingRowId))
		{
			$query = $this->db->getQuery(true)
				->select('*')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName('mapping_row_id') . ' = ' . $mappingRowId)
				->order($this->db->quoteName('order') . ' ASC');

			$this->db->setQuery($query);
			$dbObjects = $this->db->loadObjectList();

			if (!empty($dbObjects))
			{
				$transformations = $this->factory->fromDbObjects($dbObjects);
			}
		}

		return $transformations;
	}
}