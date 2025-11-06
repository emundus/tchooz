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
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_config')]
class AddonRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	const COLUMNS = [
		't.namekey',
		't.value',
	];

	private AddonFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'addon');

		$this->factory = new AddonFactory();
	}

	public function flush(AddonEntity $entity): bool
	{
		if(empty($entity->getNamekey()))
		{
			throw new \InvalidArgumentException('Addon namekey is required to flush AddonEntity');
		}

		$data = (object) [
			'namekey' => $entity->getNamekey(),
			'value' => json_encode([
				'enabled' => $entity->getValue()->isEnabled(),
				'displayed' => $entity->getValue()->isDisplayed(),
				'params' => $entity->getValue()->getParams(),
			]),
		];

		$existing_addon = $this->getByName($entity->getNamekey());
		if ($existing_addon) {
			return $this->db->updateObject(
				$this->getTableName(self::class),
				$data,
				'namekey'
			);
		} else {
			return $this->db->insertObject(
				$this->getTableName(self::class),
				$data
			);
		}
	}

	public function delete(int $id): bool
	{
		// TODO: Implement delete() method.
	}

	public function getById(int $id): mixed
	{
		// TODO: Implement getById() method.
	}

	public function getByName(string $name): ?AddonEntity
	{
		$addon_entity = null;

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where('t.namekey = ' . $this->db->quote($name));
		$this->db->setQuery($query);
		$addon = $this->db->loadAssoc();

		if (!empty($addon)) {
			$addon_entity = $this->factory->fromDbObject($addon, $this->withRelations);
		}

		return $addon_entity;
	}
}