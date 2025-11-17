<?php
/**
 * @package     Tchooz\Repositories\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Actions;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Factories\Actions\ActionFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;
use function Symfony\Component\Translation\t;

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
		'description'
	]
)
]
class ActionRepository extends EmundusRepository implements RepositoryInterface
{
	private ActionFactory $factory;

	public function __construct($withRelations = true,$exceptRelations = [] )
	{
		parent::__construct($withRelations, $exceptRelations, 'action', self::class);

		$this->factory = new ActionFactory();
	}

	public function flush(ActionEntity $entity): bool
	{
		if (empty($entity->getId()))
		{
			$existing = $this->getByName($entity->getName());
			if(!empty($existing))
			{
				throw new \Exception('An action with the name "' . $entity->getName() . '" already exists');
			}

			$insert = (object) [
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
			];

			if(!$this->db->insertObject($this->tableName, $insert))
			{
				throw new \Exception('Failed to insert action "' . $entity->getName() . '"');
			}

			$entity->setId($this->db->insertid());
		}
		else
		{
			$update = (object) [
				'id'          => $entity->getId(),
				'name'        => $entity->getName(),
				'label'       => $entity->getLabel(),
				'multi'       => $entity->getCrud()->getMulti(),
				'c'           => $entity->getCrud()->getCreate(),
				'r'           => $entity->getCrud()->getRead(),
				'u'           => $entity->getCrud()->getUpdate(),
				'd'           => $entity->getCrud()->getDelete(),
				'ordering'    => $entity->getOrdering(),
				'status'      => $entity->isStatus(),
				'description' => $entity->getDescription(),
			];

			if(!$this->db->updateObject($this->tableName, $update, 'id'))
			{
				throw new \Exception('Failed to update action "' . $entity->getName() . '"');
			}
		}

		return true;
	}

	public function getByName(string $name): ?ActionEntity
	{
		$action_entity = null;

		$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus']);
		$cache_key = 'action_' . md5($name);

		if($cache->contains($cache_key))
		{
			return $cache->get($cache_key);
		}

		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->alias.'.name = ' . $this->db->quote($name));
		$this->db->setQuery($query);
		$action = $this->db->loadAssoc();

		if (!empty($action))
		{
			$action_entity = $this->factory->fromDbObject($action);

			$cache->store($action_entity, $cache_key);
		}

		return $action_entity;
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);

		return $this->db->execute();
	}

	public function getById(int $id): ?ActionEntity
	{
		$action_entity = null;

		$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus']);
		$cache_key = 'action_' . md5($id);

		if($cache->contains($cache_key))
		{
			return $cache->get($cache_key);
		}

		$query = $this->db->getQuery(true)
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->alias.'.id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$action = $this->db->loadAssoc();

		if (!empty($action))
		{
			$action_entity = $this->factory->fromDbObject($action);

			$cache->store($action_entity, $cache_key);
		}

		return $action_entity;
	}
}