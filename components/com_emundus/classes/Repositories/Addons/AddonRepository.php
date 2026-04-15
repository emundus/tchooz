<?php
/**
 * @package     Tchooz\Repositories\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Addons;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Factories\Addons\AddonFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_setup_config',
	alias: 'esc',
	columns: [
		'namekey',
		'activated',
		'displayed',
		'suggested',
		'params',
		'default',
		'activated_at',
	]
)]
class AddonRepository extends EmundusRepository implements RepositoryInterface
{
	private AddonFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'addon', self::class);

		$this->factory = new AddonFactory();
		$this->primaryKey = 'namekey';
	}

	public function flush(AddonEntity $entity): bool
	{
		if (empty($entity->getNamekey()))
		{
			throw new \InvalidArgumentException('Addon namekey is required to flush AddonEntity');
		}

		$data = (object) [
			'namekey' => $entity->getNamekey(),
			'params' => json_encode($entity->getParams()),
			'default' => json_encode($entity->getDefault()),
			'activated' => $entity->isActivated() ? 1 : 0,
			'displayed' => $entity->isDisplayed() ? 1 : 0,
			'suggested' => $entity->isSuggested() ? 1 : 0,
			'activated_at' => $entity->getActivatedAt()?->format('Y-m-d H:i:s'),
		];

		$existing_addon = $this->getByName($entity->getNamekey());
		if ($existing_addon)
		{
			$result = $this->db->updateObject($this->tableName, $data, 'namekey');
		}
		else
		{
			$result = $this->db->insertObject($this->tableName, $data);
		}

		// Clear cache
		$cacheKey = 'addon_' . $entity->getNamekey();
		if ($this->cache->contains($cacheKey))
		{
			$this->cache->remove($cacheKey);
		}

		return $result;
	}

	public function delete(int $id): bool
	{
		// TODO: Implement delete() method.
		return false;
	}

	public function getById(int $id): null
	{
		// Addon n'a pas d'ID, utiliser getByName
		return null;
	}

	public function getByName(string $name): ?AddonEntity
	{
		$addon_entity = null;

		$cacheKey = 'addon_' . $name;
		if ($this->cache->contains($cacheKey))
		{
			$object = $this->cache->get($cacheKey);

			if (!empty($object))
			{
				return $this->factory->fromDbObject($object, $this->withRelations);
			}
		}

		$object = $this->getItemByField('namekey', $name);

		if (!empty($object))
		{
			$this->cache->store($object, $cacheKey);
			$addon_entity = $this->factory->fromDbObject($object, $this->withRelations);
		}

		return $addon_entity;
	}

	public function getFactory(): AddonFactory
	{
		return $this->factory;
	}
}

