<?php
/**
 * @package     Tchooz\Repositories\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Resource;

use Joomla\Database\DatabaseInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Resource\ResourceAccessEntity;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_resource_access',
	alias: 'era',
	columns: [
		'id',
		'resource_id',
		'type',
		'target_id',
		'permission',
	]
)]
class ResourceAccessRepository extends EmundusRepository implements RepositoryInterface
{
	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'resource_access', self::class);
	}

	public function getFactory(): ?object
	{
		return null;
	}

	public function getById(int $id): ?ResourceAccessEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? $this->hydrate($row) : null;
	}

	/**
	 * @return array<ResourceAccessEntity>
	 */
	public function findByResource(int $resourceId): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('resource_id') . ' = ' . (int) $resourceId);

		$rows = $this->db->setQuery($query)->loadObjectList();

		return array_map([$this, 'hydrate'], $rows ?: []);
	}

	/**
	 * Whether at least one resource is shared with the given user, either directly
	 * (type=user) or through one of their profiles (type=role) or groups (type=group).
	 */
	public function hasAccessibleForUser(int $userId): bool
	{
		if ($userId <= 0)
		{
			return false;
		}

		$query = $this->db->getQuery(true)
			->select('1')
			->from($this->db->quoteName($this->tableName, 'era'))
			->where(self::accessEligibilityCondition($this->db, (int) $userId))
			->setLimit(1);

		return (bool) $this->db->setQuery($query)->loadResult();
	}

	/**
	 * Strongest permission the user holds on a given resource through sharing:
	 * 0 = none, 1 = view, 2 = edit, 3 = manage (direct, or via profile/group).
	 */
	public function getUserPermissionRank(int $userId, int $resourceId): int
	{
		if ($userId <= 0 || $resourceId <= 0)
		{
			return 0;
		}

		$userId = (int) $userId;

		$query = $this->db->getQuery(true)
			->select(self::permissionRankExpression($this->db))
			->from($this->db->quoteName($this->tableName, 'era'))
			->where($this->db->quoteName('era.resource_id') . ' = ' . (int) $resourceId)
			->where(self::accessEligibilityCondition($this->db, $userId));

		return (int) $this->db->setQuery($query)->loadResult();
	}

	/**
	 * SQL expression ranking the resource_access.permission column into 0..3
	 * (none/view/edit/manage). Wrapped in MAX() so it collapses several shares to the strongest.
	 *
	 * @param   string  $alias  Table alias the access rows are exposed under (defaults to 'era').
	 */
	public static function permissionRankExpression(DatabaseInterface $db, string $alias = 'era'): string
	{
		$permission = $db->quoteName($alias . '.permission');

		return 'MAX(CASE ' . $permission
			. ' WHEN ' . $db->quote(ResourcePermissionEnum::MANAGE->value) . ' THEN 3'
			. ' WHEN ' . $db->quote(ResourcePermissionEnum::EDIT->value) . ' THEN 2'
			. ' WHEN ' . $db->quote(ResourcePermissionEnum::VIEW->value) . ' THEN 1'
			. ' ELSE 0 END)';
	}

	/**
	 * SQL condition (single outer-grouped OR block) selecting resource_access rows targeting the
	 * given user directly (type=user), via one of their profiles (type=role), or via one of their
	 * groups (type=group). The outer group lets callers safely AND further conditions.
	 *
	 * Single source of truth: both ResourceAccessRepository::hasAccessibleForUser() and
	 * ResourceService's accessible-resource queries build the eligibility filter from here.
	 *
	 * @param   string  $alias  Table alias the access rows are exposed under (defaults to 'era').
	 */
	public static function accessEligibilityCondition(DatabaseInterface $db, int $userId, string $alias = 'era'): string
	{
		$userId = (int) $userId;
		$type   = $db->quoteName($alias . '.type');
		$target = $db->quoteName($alias . '.target_id');

		return '((' . $type . ' = ' . $db->quote(ResourceAccessTypeEnum::USER->value)
			. ' AND ' . $target . ' = ' . $userId . ')'
			. ' OR (' . $type . ' = ' . $db->quote(ResourceAccessTypeEnum::ROLE->value)
			. ' AND ' . $target . ' IN (SELECT ' . $db->quoteName('profile_id')
			. ' FROM ' . $db->quoteName('#__emundus_users_profiles')
			. ' WHERE ' . $db->quoteName('user_id') . ' = ' . $userId . '))'
			. ' OR (' . $type . ' = ' . $db->quote(ResourceAccessTypeEnum::GROUP->value)
			. ' AND ' . $target . ' IN (SELECT ' . $db->quoteName('group_id')
			. ' FROM ' . $db->quoteName('#__emundus_groups')
			. ' WHERE ' . $db->quoteName('user_id') . ' = ' . $userId . ')))';
	}

	/**
	 * Replace the whole access set of a resource atomically.
	 *
	 * @param   array<ResourceAccessEntity>  $accesses
	 */
	public function replaceForResource(int $resourceId, array $accesses): bool
	{
		$this->db->transactionStart();

		try
		{
			$delete = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('resource_id') . ' = ' . (int) $resourceId);

			if (!$this->db->setQuery($delete)->execute())
			{
				throw new \RuntimeException('Failed to clear access for resource ' . $resourceId);
			}

			foreach ($accesses as $access)
			{
				$access->setResourceId($resourceId);
				$this->insert($access);
			}

			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();

			throw $e;
		}

		return true;
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		if (!$this->db->setQuery($query)->execute())
		{
			throw new \RuntimeException('Failed to delete resource access ' . $id);
		}

		return true;
	}

	private function insert(ResourceAccessEntity $access): void
	{
		$data = (object) [
			'resource_id' => $access->getResourceId(),
			'type'        => $access->getType()->value,
			'target_id'   => $access->getTargetId(),
			'permission'  => $access->getPermission()->value,
		];

		if (!$this->db->insertObject($this->tableName, $data))
		{
			throw new \RuntimeException('Failed to insert resource access');
		}

		$access->setId($this->db->insertid());
	}

	private function hydrate(object $row): ResourceAccessEntity
	{
		return new ResourceAccessEntity(
			id: (int) $row->id,
			resourceId: (int) $row->resource_id,
			type: ResourceAccessTypeEnum::from($row->type),
			targetId: (int) $row->target_id,
			permission: ResourcePermissionEnum::from($row->permission)
		);
	}
}
