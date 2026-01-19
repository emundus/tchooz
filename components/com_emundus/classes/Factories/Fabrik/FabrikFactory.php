<?php
/**
 * @package     Tchooz\Factories\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Fabrik;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Entities\Fabrik\FabrikFormEntity;
use Tchooz\Entities\Fabrik\FabrikGroupEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Fabrik\FabrikObjectsEnum;
use Tchooz\Repositories\Fabrik\FabrikRepository;

class FabrikFactory
{
	private FabrikRepository $fabrikRepository;

	public function __construct(FabrikRepository $fabrikRepository)
	{
		$this->fabrikRepository = $fabrikRepository;
	}

	public function fromDbObject(object $dbObject, $withRelations = true, FabrikObjectsEnum $object = FabrikObjectsEnum::FORM): ?object
	{
		$entity = null;

		switch ($object)
		{
			case FabrikObjectsEnum::FORM:
				$entity = $this->buildFormEntity($dbObject, $withRelations);
				break;
			case FabrikObjectsEnum::GROUP:
				$entity =$this->buildGroupEntity($dbObject, $withRelations);
				break;
			case FabrikObjectsEnum::ELEMENT:
				$entity = $this->buildElementEntity($dbObject, $withRelations);
				break;
			default:
				// Do nothing
				break;
		}

		return $entity;
	}

	public function fromDbObjects(array $dbObjects, $withRelations = true, FabrikObjectsEnum $object = FabrikObjectsEnum::FORM): array
	{
		$entities = [];

		foreach ($dbObjects as $dbObject)
		{
			switch ($object)
			{
				case FabrikObjectsEnum::FORM:
					$entities[] = $this->buildFormEntity($dbObject, $withRelations);
					break;
				case FabrikObjectsEnum::GROUP:
					$entities[] = $this->buildGroupEntity($dbObject, $withRelations);
					break;
				case FabrikObjectsEnum::ELEMENT:
					$entities[] = $this->buildElementEntity($dbObject, $withRelations);
					break;
				default:
					// Do nothing
					break;
			}
		}

		return $entities;
	}

	public function buildFormEntity(object $dbObject, $withRelations = true): FabrikFormEntity
	{
		$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->created_by);
		$created = new \DateTime($dbObject->created);

		$fabrikFormEntity = new FabrikFormEntity(
			$dbObject->id,
			$dbObject->label,
			$dbObject->intro,
			$created,
			$user,
		);

		if ($withRelations)
		{
			$groups = $this->fabrikRepository->getGroupsByFormId($fabrikFormEntity->getId());
			$fabrikFormEntity->setGroups($groups);
		}

		return $fabrikFormEntity;
	}

	private function buildGroupEntity(object $dbObject, $withRelations = true): FabrikGroupEntity
	{
		$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->created_by);
		$created = new \DateTime($dbObject->created);

		$fabrikGroupEntity = new FabrikGroupEntity(
			$dbObject->id,
			$dbObject->name,
			$dbObject->label,
			$created,
			$user,
			$dbObject->modified ? new \DateTime($dbObject->modified) : new \DateTime(),
			!empty($dbObject->modified_by) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->modified_by) : null,
			(bool) $dbObject->is_join,
			(int) $dbObject->private,
			$dbObject->params ?? '',
		);

		if ($withRelations)
		{
			$elements = $this->fabrikRepository->getElementsByGroupId($fabrikGroupEntity->getId());
			$fabrikGroupEntity->setElements($elements);
		}

		return $fabrikGroupEntity;
	}

	public function buildElementEntity(object $dbObject, $withRelations = true): ?FabrikElementEntity
	{
		if(empty($dbObject->plugin) || empty($dbObject->name) || empty($dbObject->group_id) || empty($dbObject->created_by))
		{
			return null;
		}

		$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->created_by);
		$created = $dbObject->created ? new \DateTime($dbObject->created) : new \DateTime();

		return new FabrikElementEntity(
			$dbObject->id,
			$dbObject->name,
			$dbObject->group_id,
			ElementPluginEnum::tryFrom($dbObject->plugin) ?? ElementPluginEnum::FIELD,
			$dbObject->label,
			$created,
			$user,
			$dbObject->params ?? '',
			$dbObject->db_table_name ?? '',
			$dbObject->table_join ?? '',
			$dbObject->group_params ?? '',
			$dbObject->alias ?? '',
			$dbObject->default ?? '',
			(int) ($dbObject->eval ?? 0)
		);
	}
}