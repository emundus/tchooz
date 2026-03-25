<?php
/**
 * @package     Tchooz\Factories\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Actions;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Groups\GroupRepository;

class GroupAccessFactory implements DBFactory
{
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		if(is_array($dbObject)) {
			$dbObject = (object)$dbObject;
		}

		return self::buildEntity($dbObject);
	}

	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true): array
	{
		$groupRepository = null;
		$actionRepository = null;
		if($withRelations)
		{
			$groupRepository = new GroupRepository();
			$actionRepository = new ActionRepository();
		}

		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entities[] = self::buildEntity($dbObject, $groupRepository, $actionRepository);
		}

		return $entities;
	}

	public static function buildEntity(object $dbObject, ?GroupRepository $groupRepository = null, ?ActionRepository $actionRepository = null): GroupAccessEntity
	{
		$group = null;
		if(!empty($groupRepository))
		{
			$group = $groupRepository->getById((int)$dbObject->group_id);
		}
		
		$action = null;
		if(!empty($actionRepository))
		{
			$action = $actionRepository->getById((int)$dbObject->action_id);
		}

		$crud = new CrudEntity(
			0,
			(int)$dbObject->c,
			(int)$dbObject->r,
			(int)$dbObject->u,
			(int)$dbObject->d
		);

		return new GroupAccessEntity(
			(int)$dbObject->id,
			$group,
			$action,
			$crud
		);
	}
}