<?php

namespace Tchooz\Repositories\Mapping;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Factories\Mapping\MappingRowFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: '#__emundus_connector_mapping_row', alias: 'mapping_row',
	columns: [
		'id',
		'mapping_id',
		'source_type',
		'source_field',
		'target_field',
	]
)]
class MappingRowRepository extends EmundusRepository implements RepositoryInterface
{
	private MappingRowFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'mapping_row', self::class);
		$this->factory = new MappingRowFactory();
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
				->delete($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName($this->alias . '.' . $this->primaryKey) . ' = ' . $id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	/**
	 * @param   int  $mappingId
	 *
	 * @return bool
	 */
	public function deleteByMappingId(int $mappingId): bool
	{
		$deleted = false;

		if (!empty($mappingId))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('mapping_id') . ' = ' . $mappingId);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function flush(MappingRowEntity $mappingRowEntity): bool
	{
		$flushed = false;

		if (!empty($mappingRowEntity->getId()))
		{
			$existingEntity = $this->getById($mappingRowEntity->getId());
			if (!$existingEntity)
			{
				$mappingRowEntity->setId(0);
			}
		}

		if (!empty($mappingRowEntity->getId()))
		{
			$object = (object)[
				'id'           => $mappingRowEntity->getId(),
				'mapping_id'   => $mappingRowEntity->getMappingId(),
				'source_type'  => $mappingRowEntity->getSourceType()->value,
				'source_field' => $mappingRowEntity->getSourceField(),
				'target_field' => $mappingRowEntity->getTargetField(),
			];

			$flushed = $this->db->updateObject($this->tableName, $object, 'id');
		}
		else
		{
			$object = (object)[
				'mapping_id'   => $mappingRowEntity->getMappingId(),
				'source_type'  => $mappingRowEntity->getSourceType()->value,
				'source_field' => $mappingRowEntity->getSourceField(),
				'target_field' => $mappingRowEntity->getTargetField(),
			];

			$flushed = $this->db->insertObject($this->tableName, $object);

			if ($flushed)
			{
				$mappingRowEntity->setId((int) $this->db->insertid());
			}
		}

		if ($flushed)
		{
			$transformationsRepository = new MappingRowTransformationRepository();
			$existingTransformations = $transformationsRepository->getByMappingRowId($mappingRowEntity->getId());
			$existingTransformationIds = array_map(fn($transformation) => $transformation->getId(), $existingTransformations);
			$currentTransformationIds = array_map(fn($transformation) => $transformation->getId(), $mappingRowEntity->getTransformations());
			$transformationsToDelete = array_diff($existingTransformationIds, $currentTransformationIds);

			foreach ($transformationsToDelete as $transformationId) {
				$transformationsRepository->delete($transformationId);
			}

			foreach ($mappingRowEntity->getTransformations() as $transformation) {
				$transformation->setMappingRowId($mappingRowEntity->getId());
				$transformationsRepository->flush($transformation);
			}
		}

		return $flushed;
	}

	/**
	 * @param   int  $id
	 *
	 * @return MappingRowEntity|null
	 */
	public function getById(int $id): ?MappingRowEntity
	{
		$entity = null;

		if (!empty($id))
		{
			$query = $this->db->getQuery(true)
				->select($this->alias . '.*')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName($this->alias . '.' . $this->primaryKey) . ' = ' . $id);

			$this->db->setQuery($query);
			$dbObject = $this->db->loadObject();

			if ($dbObject) {
				$entities = $this->factory->fromDbObjects([$dbObject]);
				$entity = $entities[0];
			}
		}

		return $entity;
	}

	/**
	 * @param   int  $mappingId
	 *
	 * @return MappingRowEntity[]
	 */
	public function getByMappingId(int $mappingId): array
	{
		$entities = [];

		if (!empty($mappingId))
		{
			$query = $this->db->getQuery(true)
				->select('*')
				->from($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('mapping_id') . ' = ' . $mappingId)
				->order($this->db->quoteName('order') . ' ASC');

			$this->db->setQuery($query);
			$dbObjects = $this->db->loadObjectList();

			if ($dbObjects) {
				$entities = $this->factory->fromDbObjects($dbObjects);
			}
		}

		return $entities;
	}
}