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
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_actions')]
readonly class ActionRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.action.php'], Log::ALL, ['com_emundus.repository.action']);
		$this->db   = Factory::getContainer()->get('DatabaseDriver');
	}

	public function flush(ActionEntity $entity): bool
	{
		if (empty($entity->getId()))
		{
			// Cannot flush an action with the same name as an existing one
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

			$this->db->insertObject($this->getTableName(self::class), $insert);

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

			$this->db->updateObject($this->getTableName(self::class), $update, 'id');
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
			->select('*')
			->from($this->getTableName(self::class))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($name));

		$this->db->setQuery($query);
		$action = $this->db->loadAssoc();

		if (!empty($action))
		{
			$action_entity = $this->loadEntity($action);

			$cache->store($action_entity, $cache_key);
		}

		return $action_entity;
	}

	private function loadEntity(object|array $action): ActionEntity
	{
		if(is_object($action))
		{
			$action = (array) $action;
		}

		return new ActionEntity(
			id: (int) $action['id'],
			name: $action['name'],
			label: $action['label'],
			crud: new CrudEntity($action['multi'], $action['c'], $action['r'], $action['u'], $action['d']),
			ordering: (int) $action['ordering'],
			status: (bool) $action['status'],
			description: $action['description'] ?? null
		);
	}
}