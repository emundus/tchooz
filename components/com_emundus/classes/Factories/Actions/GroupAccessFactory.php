<?php
/**
 * @package     Tchooz\Factories\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Actions;

use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

class GroupAccessFactory extends AbstractFactory
{
	public const RELATION_GROUP = GroupRepository::NAME;
	public const RELATION_ACTION = ActionRepository::NAME;

	protected const RELATIONS = [
		self::RELATION_GROUP,
		self::RELATION_ACTION
	];

	private ?GroupRepository $groupRepository = null;
	private ?ActionRepository $actionRepository = null;

	public function buildEntity(object $dbObject, array $relations): GroupAccessEntity
	{
		$crud = new CrudEntity(
			0,
			(int) $dbObject->c,
			(int) $dbObject->r,
			(int) $dbObject->u,
			(int) $dbObject->d
		);

		return new GroupAccessEntity(
			(int) $dbObject->id,
			$relations[self::RELATION_GROUP] ?? null,
			$relations[self::RELATION_ACTION] ?? null,
			$crud
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		return match ($relation)
		{
			self::RELATION_GROUP => $this->getGroupRepository()->getById((int) $dbObject->group_id),
			self::RELATION_ACTION => $this->getActionRepository()->getById((int) $dbObject->action_id),
			default => null
		};
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return match ($relation)
		{
			self::RELATION_GROUP => (int) $dbObject->group_id,
			self::RELATION_ACTION => (int) $dbObject->action_id,
			default => ''
		};
	}

	public function getGroupRepository(): ?GroupRepository
	{
		if ($this->groupRepository === null)
		{
			$this->groupRepository = new GroupRepository();
		}

		return $this->groupRepository;
	}

	public function getActionRepository(): ?ActionRepository
	{
		if ($this->actionRepository === null)
		{
			$this->actionRepository = new ActionRepository();
		}

		return $this->actionRepository;
	}
}