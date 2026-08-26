<?php
/**
 * @package     Tchooz\Repositories\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Resource;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Resource\ResourceFolderAccessEntity;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_resource_folder_access',
	alias: 'erfa',
	columns: [
		'id',
		'folder_id',
		'type',
		'target_id',
		'permission',
	]
)]
class ResourceFolderAccessRepository extends EmundusRepository implements RepositoryInterface
{
	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'resource_folder_access', self::class);
	}

	public function getFactory(): ?object
	{
		return null;
	}

	public function getById(int $id): ?ResourceFolderAccessEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? $this->hydrate($row) : null;
	}

	/**
	 * @return array<ResourceFolderAccessEntity>
	 */
	public function findByFolder(int $folderId): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('folder_id') . ' = ' . (int) $folderId);

		$rows = $this->db->setQuery($query)->loadObjectList();

		return array_map([$this, 'hydrate'], $rows ?: []);
	}

	/**
	 * Strongest permission the user holds on a given file through a share on the file's folder:
	 * 0 = none, 1 = view, 2 = edit, 3 = manage (direct, or via profile/group). Returns 0 when the
	 * file sits at the root (no folder) or when no folder share matches.
	 */
	public function getUserPermissionRankForFile(int $userId, int $resourceId): int
	{
		if ($userId <= 0 || $resourceId <= 0)
		{
			return 0;
		}

		$userId = (int) $userId;

		$query = $this->db->getQuery(true)
			->select(ResourceAccessRepository::permissionRankExpression($this->db, 'erfa'))
			->from($this->db->quoteName('#__emundus_resources', 'r'))
			->join('INNER', $this->db->quoteName($this->tableName, 'erfa')
				. ' ON ' . $this->db->quoteName('erfa.folder_id') . ' = ' . $this->db->quoteName('r.folder_id'))
			->where($this->db->quoteName('r.id') . ' = ' . (int) $resourceId)
			->where(ResourceAccessRepository::accessEligibilityCondition($this->db, $userId, 'erfa'));

		return (int) $this->db->setQuery($query)->loadResult();
	}

	/**
	 * Whether the user is granted access to at least one folder (directly or via profile/group).
	 * A folder share cascades to its files, so it counts as accessible resources on its own.
	 */
	public function hasAccessibleForUser(int $userId): bool
	{
		if ($userId <= 0)
		{
			return false;
		}

		$query = $this->db->getQuery(true)
			->select('1')
			->from($this->db->quoteName($this->tableName, 'erfa'))
			->where(ResourceAccessRepository::accessEligibilityCondition($this->db, (int) $userId, 'erfa'))
			->setLimit(1);

		return (bool) $this->db->setQuery($query)->loadResult();
	}

	/**
	 * Replace the whole access set of a folder atomically.
	 *
	 * @param   array<ResourceFolderAccessEntity>  $accesses
	 */
	public function replaceForFolder(int $folderId, array $accesses): bool
	{
		$this->db->transactionStart();

		try
		{
			$delete = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('folder_id') . ' = ' . (int) $folderId);

			if (!$this->db->setQuery($delete)->execute())
			{
				throw new \RuntimeException('Failed to clear access for folder ' . $folderId);
			}

			foreach ($accesses as $access)
			{
				$access->setFolderId($folderId);
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
			throw new \RuntimeException('Failed to delete resource folder access ' . $id);
		}

		return true;
	}

	private function insert(ResourceFolderAccessEntity $access): void
	{
		$data = (object) [
			'folder_id'  => $access->getFolderId(),
			'type'       => $access->getType()->value,
			'target_id'  => $access->getTargetId(),
			'permission' => $access->getPermission()->value,
		];

		if (!$this->db->insertObject($this->tableName, $data))
		{
			throw new \RuntimeException('Failed to insert resource folder access');
		}

		$access->setId($this->db->insertid());
	}

	private function hydrate(object $row): ResourceFolderAccessEntity
	{
		return new ResourceFolderAccessEntity(
			id: (int) $row->id,
			folderId: (int) $row->folder_id,
			type: ResourceAccessTypeEnum::from($row->type),
			targetId: (int) $row->target_id,
			permission: ResourcePermissionEnum::from($row->permission)
		);
	}
}
