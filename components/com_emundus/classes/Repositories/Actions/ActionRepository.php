<?php
/**
 * @package     Tchooz\Repositories\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Actions;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Factories\Actions\ActionFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: '#__emundus_setup_actions',
	alias: 'esa',
	columns: [
		'id',
		'name',
		'label',
		'multi',
		'c',
		'r',
		'u',
		'd',
		'ordering',
		'status',
		'description',
		'type'
	]
)
]
class ActionRepository extends EmundusRepository implements RepositoryInterface
{
	private ActionFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'action', self::class);

		$this->factory = new ActionFactory();
	}

	public function flush(ActionEntity $entity): bool
	{
		$data = (object) [
			'name'        => $entity->getName(),
			'label'       => $entity->getLabel(),
			'multi'       => $entity->getCrud()->getMulti(),
			'c'           => $entity->getCrud()->getCreate(),
			'r'           => $entity->getCrud()->getRead(),
			'u'           => $entity->getCrud()->getUpdate(),
			'd'           => $entity->getCrud()->getDelete(),
			'ordering'    => $entity->getOrdering(),
			'status'      => $entity->isStatus() ? 1 : 0,
			'description' => $entity->getDescription(),
			'type'        => $entity->getType()->value,
		];

		if (empty($entity->getId()))
		{
			$existing = $this->getByName($entity->getName());
			if (!empty($existing))
			{
				throw new \Exception('An action with the name "' . $entity->getName() . '" already exists');
			}

			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \Exception('Failed to insert action "' . $entity->getName() . '"');
			}

			$entity->setId($this->db->insertid());
		}
		else
		{
			$data->id = $entity->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \Exception('Failed to update action "' . $entity->getName() . '"');
			}
		}

		// Clear cache
		$cache_key = 'action_' . md5($entity->getId());
		$this->cache->store(null, $cache_key);

		$cache_key = 'action_' . md5($entity->getName());
		$this->cache->store(null, $cache_key);
		//

		return true;
	}

	public function getByName(string $name): ?ActionEntity
	{
		$action_entity = null;

		$cache_key = 'action_' . md5($name);
		if ($this->cache->contains($cache_key))
		{
			$action = $this->cache->get($cache_key);
		}

		if(empty($action))
		{
			$query = $this->db->getQuery(true)
				->select($this->columns)
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->alias . '.name = ' . $this->db->quote($name));
			$this->db->setQuery($query);
			$action = $this->db->loadObject();
		}

		if (!empty($action))
		{
			$this->cache->store($action, $cache_key);

			$action_entity = $this->factory->fromDbObject($action);
		}

		return $action_entity;
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true);

		$query->select('name')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);
		$this->db->setQuery($query);
		$actionName = $this->db->loadResult();

		$query->clear()
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);

		if($deleted = $this->db->execute())
		{
			// Clear cache
			$cache_key = 'action_' . md5($id);
			$this->cache->store(null, $cache_key);

			if(!empty($actionName))
			{
				$cache_key = 'action_' . md5($actionName);
				$this->cache->store(null, $cache_key);
			}
			//
		}

		return $deleted;
	}

	public function getById(int $id): ?ActionEntity
	{
		$action_entity = null;

		$cache_key = 'action_' . md5($id);

		if ($this->cache->contains($cache_key))
		{
			$action = $this->cache->get($cache_key);
		}

		if(empty($action))
		{
			$query = $this->db->getQuery(true)
				->select($this->columns)
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->alias . '.id = ' . $this->db->quote($id));
			$this->db->setQuery($query);
			$action = $this->db->loadObject();
		}

		if (!empty($action))
		{
			$this->cache->store($action, $cache_key);

			$action_entity = $this->factory->fromDbObject($action);
		}

		return $action_entity;
	}

	public function getFactory(): ActionFactory
	{
		return $this->factory;
	}
}