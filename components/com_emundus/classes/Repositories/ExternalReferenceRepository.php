<?php
/**
 * @package     Tchooz\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\ExternalReferenceEntity;
use Tchooz\Factories\ExternalReferenceFactory;

#[TableAttribute(
	table: '#__emundus_external_reference',
	alias: 'eer',
	columns: [
		'id',
		'column',
		'intern_id',
		'reference'
	]
)]
class ExternalReferenceRepository extends EmundusRepository implements RepositoryInterface
{
	private ExternalReferenceFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'application_choices', self::class);

		$this->factory = new ExternalReferenceFactory();
	}

	public function flush(ExternalReferenceEntity $entity): bool
	{
		if(empty($entity->getId()))
		{
			$insert = (object)[
				'column'    => $entity->getColumn(),
				'intern_id' => $entity->getInternId(),
				'reference' => $entity->getReference()
			];

			if(!$flushed = $this->db->insertObject($this->tableName, $insert))
			{
				throw new \Exception('Failed to insert an external reference');
			}
			$entity->setId((int)$this->db->insertid());
		}
		else {
			$update = (object)[
				'id'        => $entity->getId(),
				'column'    => $entity->getColumn(),
				'intern_id' => $entity->getInternId(),
				'reference' => $entity->getReference()
			];

			if(!$flushed = $this->db->updateObject($this->tableName, $update, 'id'))
			{
				throw new \Exception('Failed to update an external reference');
			}
		}

		return $flushed;
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . $id);
		$this->db->setQuery($query);
		return (bool)$this->db->execute();
	}

	public function getById(int $id): ?ExternalReferenceEntity
	{
		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName('id') . ' = ' . $id);
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			return $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return null;
	}

	public function getReferenceByInternId(string $column, string|int $intern_id): ?ExternalReferenceEntity
	{
		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName('column') . ' = ' . $this->db->quote($column))
			->where($this->db->quoteName('intern_id') . ' = ' . $this->db->quote($intern_id));
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			return $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return null;
	}

	public function getReferenceByExternal(string $column, string|int $reference): ?ExternalReferenceEntity
	{
		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName('column') . ' = ' . $this->db->quote($column))
			->where($this->db->quoteName('reference') . ' = ' . $this->db->quote($reference));
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			return $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return null;
	}
}