<?php
/**
 * @package     Tchooz\Repositories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\ApplicationFile;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Factories\ApplicationFile\StatusFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: 'jos_emundus_setup_status', alias: 'ess', columns: [
	'id',
	'step',
	'value',
	'ordering',
	'class'
])]
class StatusRepository extends EmundusRepository implements RepositoryInterface
{
	private StatusFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'status', self::class);

		$this->factory = new StatusFactory();
	}

	public function delete(int $id): bool
	{
		// TODO: Implement delete() method.
		return false;
	}

	/**
	 *
	 * @return array<StatusEntity>
	 */
	public function getAll(): array
	{
		$statuses = [];

		$cacheKey = 'all_statuses';
		if ($this->cache->contains($cacheKey)) {
			$results = $this->cache->get($cacheKey);
		}

		if(empty($results))
		{
			$query = $this->db->createQuery()
				->select($this->columns)
				->from($this->db->quoteName($this->tableName, $this->alias))
				->order($this->db->quoteName($this->alias . '.ordering') . ' ASC');
			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if(!empty($results))
			{
				$this->cache->store($results, $cacheKey);
			}
		}

		foreach ($results as $status) {
			$statuses[] = $this->factory->fromDbObject($status, $this->withRelations);
		}

		return $statuses;
	}

	public function getById(int $id): ?StatusEntity
	{
		$status_entity = null;

		$cacheKey = 'status_id_' . $id;
		if ($this->cache->contains($cacheKey)) {
			$status = $this->cache->get($cacheKey);
		}

		if(empty($status))
		{
			$query = $this->db->createQuery()
				->select($this->columns)
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName($this->alias. '.id') . ' = ' . $id);
			$this->db->setQuery($query);
			$status = $this->db->loadObject();
		}

		if (!empty($status)) {
			$this->cache->store($status, $cacheKey);

			$status_entity = $this->factory->fromDbObject($status, $this->withRelations);
		}

		return $status_entity;
	}

	public function getByStep(int $step): ?StatusEntity
	{
		$status_entity = null;

		$cacheKey = 'status_step_' . $step;
		if ($this->cache->contains($cacheKey))
		{
			$status = $this->cache->get($cacheKey);
		}

		if (empty($status))
		{
			$query = $this->db->getQuery(true)
				->select($this->columns)
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName($this->alias . '.step') . ' = ' . $step);
			$this->db->setQuery($query);
			$status = $this->db->loadObject();
		}

		if (!empty($status))
		{
			$this->cache->store($status, $cacheKey);

			$status_entity = $this->factory->fromDbObject($status, $this->withRelations);
		}

		return $status_entity;
	}

	public function getFactory(): StatusFactory
	{
		return $this->factory;
	}
}