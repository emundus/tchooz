<?php

namespace Tchooz\Repositories\ExternalReference;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\ExternalReference\ExternalReferenceEntity;
use Tchooz\Factories\ExternalReference\ExternalReferenceFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: '#__emundus_external_reference', alias: 'reference',
	columns: [
		'id',
		'column',
		'intern_id',
		'reference',
		'sync_id',
		'reference_object',
		'reference_attribute',
	]
)]
class ExternalReferenceRepository extends EmundusRepository implements RepositoryInterface
{
	private ExternalReferenceFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'external_reference', self::class);
		$this->factory = new ExternalReferenceFactory();
	}

	public function getFactory(): ExternalReferenceFactory
	{
		return $this->factory;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->createQuery()
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

			$this->db->setQuery($query);
			$deleted = (bool) $this->db->execute();
		}

		return $deleted;
	}

	public function getById(int $id): ?ExternalReferenceEntity
	{
		$externalReference = null;

		if (!empty($id))
		{
			$query = $this->db->createQuery()
				->select($this->alias . '.*')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName($this->alias . '.id') . ' = ' . $this->db->quote($id));

			$this->db->setQuery($query);
			$dbObject = $this->db->loadObject();

			if ($dbObject)
			{
				$externalReferences = $this->factory::fromDbObjects([$dbObject]);
				$externalReference = $externalReferences[0];
			}
		}

		return $externalReference;
	}

	public function flush(ExternalReferenceEntity $externalReference): bool
	{
		$query = $this->db->createQuery();

		if (empty($externalReference->getId()))
		{
			// can t have two references for the same column + intern id+ synchronizer
			$query->select('id')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName('column') . ' = ' . $this->db->quote($externalReference->getColumn()))
				->where($this->db->quoteName('intern_id') . ' = ' . $this->db->quote($externalReference->getInternId()));

			if (!empty($externalReference->getSynchronizerId()))
			{
				$query->where($this->db->quoteName('sync_id') . ' = ' . $this->db->quote($externalReference->getSynchronizerId()));
			}
			else
			{
				$query->where($this->db->quoteName('sync_id') . ' IS NULL');
			}

			$this->db->setQuery($query);
			$referenceId = $this->db->loadResult();

			if ($referenceId > 0)
			{
				$externalReference->setId($referenceId);
			}
		}

		if (empty($externalReference->getId()))
		{
			$object = (object) [
				'column'    => $externalReference->getColumn(),
				'intern_id' => $externalReference->getInternId(),
				'reference' => $externalReference->getReference(),
				'sync_id' => $externalReference->getSynchronizerId(),
				'reference_object' => $externalReference->getReferenceObject(),
				'reference_attribute' => $externalReference->getReferenceAttribute(),
			];
			$saved  = $this->db->insertObject($this->tableName, $object);

			if ($saved)
			{
				$externalReference->setId((int) $this->db->insertid());
			}
		}
		else
		{
			$object = (object) [
				'id'        => $externalReference->getId(),
				'column'    => $externalReference->getColumn(),
				'intern_id' => $externalReference->getInternId(),
				'reference' => $externalReference->getReference(),
				'sync_id' => $externalReference->getSynchronizerId(),
				'reference_object' => $externalReference->getReferenceObject(),
				'reference_attribute' => $externalReference->getReferenceAttribute(),
			];
			$saved = $this->db->updateObject($this->tableName, $object, 'id');
		}

		return $saved;
	}
}