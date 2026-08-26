<?php
/**
 * @package     Tchooz\Repositories\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Resource;

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Enums\List\ListSortEnum;
use Tchooz\Factories\Resource\ResourceFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_resources',
	alias: 'er',
	columns: [
		'id',
		'name',
		'format',
		'filename',
		'size',
		'download_count',
		'folder_id',
		'created_by',
		'created_at',
	]
)]
class ResourceRepository extends EmundusRepository implements RepositoryInterface
{
	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'resource', self::class);
	}

	public function getFactory(): ?object
	{
		return null;
	}

	public function getById(int $id): ?ResourceEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? ResourceFactory::fromDbObject($row, $this->withRelations) : null;
	}

	/**
	 * Resolve a stored relative path back to its resource id, so the download gateway can
	 * re-apply the per-file ACL on a request that only carries the filename.
	 */
	public function findIdByFilename(string $filename): ?int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('filename') . ' = :filename')
			->bind(':filename', $filename);

		$id = $this->db->setQuery($query)->loadResult();

		return $id ? (int) $id : null;
	}

	/**
	 * @param   int|null     $folderId  null returns the resources at the root.
	 * @param   string|null  $search    optional name filter.
	 *
	 * @return array<ResourceEntity>
	 */
	public function getAll(?int $folderId = null, ?string $search = null, int $limit = 0, int $offset = 0): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->order($this->db->quoteName('created_at') . ' DESC');

		if ($folderId === null)
		{
			$query->where($this->db->quoteName('folder_id') . ' IS NULL');
		}
		else
		{
			$query->where($this->db->quoteName('folder_id') . ' = ' . (int) $folderId);
		}

		if (!empty($search))
		{
			$query->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('%' . $this->db->escape($search, true) . '%'));
		}

		$this->db->setQuery($query, $offset, $limit > 0 ? $limit : 0);
		$rows = $this->db->loadObjectList();

		return ResourceFactory::fromDbObjects($rows ?: [], $this->withRelations);
	}

	/**
	 * List rows for the manager view: files of a folder (or the root) UNIONed with the sub-folders
	 * of the root level, each carrying {id, name, created_at, size, format, download_count, type}.
	 * At the root, folders are listed with their cumulated file size; inside a folder, only files.
	 *
	 * @return array<\stdClass>
	 */
	public function findListRows(?int $folderId, ?string $search, int $limit, int $page, ?string $orderBy, ListSortEnum $orderDir, ?string $typeFilter = null, array $formats = []): array
	{
		$db = $this->db;

		$formatCondition = $this->formatFilterCondition('format', $formats);
		// A format filter or an explicit "file" type restricts the list to files (folders have none).
		$filesOnly   = $typeFilter === 'file' || $formatCondition !== '';
		$foldersOnly = $typeFilter === 'folder';

		// Folders are only listed at the root; inside a folder there are no sub-folder rows.
		if ($foldersOnly)
		{
			return empty($folderId) ? $this->findFolderRows($search, $orderBy, $orderDir, $limit, $page) : [];
		}

		$columns         = [$db->quoteName('id'), $db->quoteName('name'), $db->quoteName('created_at')];
		$resourceColumns = array_merge($columns, [
			$db->quoteName('size'),
			$db->quoteName('format'),
			$db->quoteName('download_count'),
			$db->quote('file') . ' AS ' . $db->quoteName('type'),
		]);

		$queryResources = $db->getQuery(true);
		$queryResources->select($resourceColumns)
			->from($db->quoteName($this->tableName));

		if (!empty($folderId))
		{
			$queryResources->where($db->quoteName('folder_id') . ' = :folderId')
				->bind(':folderId', $folderId, ParameterType::INTEGER);
		}
		else
		{
			$queryResources->where($db->quoteName('folder_id') . ' IS NULL');
		}

		if (!empty($search))
		{
			$queryResources->where($db->quoteName('name') . ' LIKE ' . $db->quote('%' . $search . '%'));
		}

		if ($formatCondition !== '')
		{
			$queryResources->where($formatCondition);
		}

		// Folders (flat) are added at the root unless the view is restricted to files.
		if (empty($folderId) && !$filesOnly)
		{
			$queryResources->union($this->buildFolderUnionQuery($search));
		}

		// Default ordering: folders first, then files, each sorted by name.
		if (empty($orderBy))
		{
			$queryResources->order('(' . $db->quoteName('type') . ' = ' . $db->quote('folder') . ') DESC')
				->order($db->quoteName('name') . ' ASC');
		}
		else
		{
			$queryResources->order($db->quoteName($orderBy) . ' ' . $orderDir->value);
		}

		if (!empty($limit))
		{
			$offset = ($page - 1) * $limit;
			$queryResources->setLimit($limit, $offset);
		}

		return $db->setQuery($queryResources)->loadObjectList() ?: [];
	}

	/**
	 * Folder rows (flat, all folders) shaped like the file list rows, with the cumulated size of
	 * their files. Used when the list is filtered to folders only.
	 *
	 * @return array<\stdClass>
	 */
	private function findFolderRows(?string $search, ?string $orderBy, ListSortEnum $orderDir, int $limit, int $page): array
	{
		$db    = $this->db;
		$query = $this->buildFolderUnionQuery($search);

		if (empty($orderBy))
		{
			$query->order($db->quoteName('name') . ' ASC');
		}
		else
		{
			$query->order($db->quoteName($orderBy) . ' ' . $orderDir->value);
		}

		if (!empty($limit))
		{
			$query->setLimit($limit, ($page - 1) * $limit);
		}

		return $db->setQuery($query)->loadObjectList() ?: [];
	}

	/**
	 * Query producing folder rows in the same column shape as the file list rows
	 * ({id, name, created_at, size, format:'-', download_count:'-', type:'folder'}).
	 */
	private function buildFolderUnionQuery(?string $search): \Joomla\Database\QueryInterface
	{
		$db = $this->db;

		$folderSizeSubquery = '(SELECT COALESCE(SUM(' . $db->quoteName('r.size') . '), 0) FROM '
			. $db->quoteName($this->tableName, 'r')
			. ' WHERE ' . $db->quoteName('r.folder_id') . ' = ' . $db->quoteName('rf.id') . ')';

		$query = $db->getQuery(true);
		$query->select([
			$db->quoteName('rf.id'),
			$db->quoteName('rf.name'),
			$db->quoteName('rf.created_at'),
			$folderSizeSubquery . ' AS ' . $db->quoteName('size'),
			$db->quote('-') . ' AS ' . $db->quoteName('format'),
			$db->quote('-') . ' AS ' . $db->quoteName('download_count'),
			$db->quote('folder') . ' AS ' . $db->quoteName('type'),
		])
			->from($db->quoteName('#__emundus_resource_folders', 'rf'));

		if (!empty($search))
		{
			$query->where($db->quoteName('rf.name') . ' LIKE ' . $db->quote('%' . $search . '%'));
		}

		return $query;
	}

	/**
	 * WHERE fragment restricting a format column to a whitelist, or '' when there is no filter.
	 *
	 * @param   array<string>  $formats
	 */
	private function formatFilterCondition(string $column, array $formats): string
	{
		$formats = array_values(array_filter(array_map('trim', $formats), static fn ($f) => $f !== ''));

		if (empty($formats))
		{
			return '';
		}

		$quoted = array_map(fn ($format) => $this->db->quote($format), $formats);

		return $this->db->quoteName($column) . ' IN (' . implode(', ', $quoted) . ')';
	}

	/**
	 * Distinct non-empty file formats present in the library, ordered alphabetically.
	 *
	 * @return array<string>
	 */
	public function getDistinctFormats(): array
	{
		$query = $this->db->getQuery(true)
			->select('DISTINCT ' . $this->db->quoteName('format'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('format') . " <> ''")
			->where($this->db->quoteName('format') . ' IS NOT NULL')
			->order($this->db->quoteName('format') . ' ASC');

		return array_values(array_filter($this->db->setQuery($query)->loadColumn() ?: []));
	}

	/**
	 * Distinct non-empty formats among the files shared with the given user (directly on the file
	 * or through a share on its folder, via profile/group), ordered alphabetically. Used to scope
	 * the format filter for non-managers.
	 *
	 * @return array<string>
	 */
	public function getDistinctAccessibleFormats(int $userId): array
	{
		$userId = (int) $userId;
		if ($userId <= 0)
		{
			return [];
		}

		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('DISTINCT ' . $db->quoteName('r.format'))
			->from($db->quoteName($this->tableName, 'r'))
			->where($this->accessibleFilePredicate($userId))
			->where($db->quoteName('r.format') . " <> ''")
			->where($db->quoteName('r.format') . ' IS NOT NULL')
			->order($db->quoteName('r.format') . ' ASC');

		return array_values(array_filter($db->setQuery($query)->loadColumn() ?: []));
	}

	/**
	 * SQL predicate (for a resource query aliased 'r') matching files the user may access: either a
	 * direct file share (#__emundus_resource_access) or a share on the file's folder
	 * (#__emundus_resource_folder_access), each resolved directly / via profile / via group. Using
	 * EXISTS keeps one row per file, so downstream SUM/COUNT never double-count a file matched by
	 * both a file share and a folder share.
	 */
	private function accessibleFilePredicate(int $userId): string
	{
		$db = $this->db;

		$fileExists = 'EXISTS (' . (string) $db->getQuery(true)
			->select('1')
			->from($db->quoteName('#__emundus_resource_access', 'era'))
			->where($db->quoteName('era.resource_id') . ' = ' . $db->quoteName('r.id'))
			->where(ResourceAccessRepository::accessEligibilityCondition($db, $userId, 'era'))
			. ')';

		$folderExists = 'EXISTS (' . (string) $db->getQuery(true)
			->select('1')
			->from($db->quoteName('#__emundus_resource_folder_access', 'erfa'))
			->where($db->quoteName('erfa.folder_id') . ' = ' . $db->quoteName('r.folder_id'))
			->where(ResourceAccessRepository::accessEligibilityCondition($db, $userId, 'erfa'))
			. ')';

		return '(' . $fileExists . ' OR ' . $folderExists . ')';
	}

	/**
	 * SQL expression (for a resource query aliased 'r') giving the strongest permission rank (0..3)
	 * the user holds on the file: the greater of its direct file share and its folder share.
	 */
	private function accessiblePermissionRankExpression(int $userId): string
	{
		$db = $this->db;

		$fileRank = '(' . (string) $db->getQuery(true)
			->select(ResourceAccessRepository::permissionRankExpression($db, 'era'))
			->from($db->quoteName('#__emundus_resource_access', 'era'))
			->where($db->quoteName('era.resource_id') . ' = ' . $db->quoteName('r.id'))
			->where(ResourceAccessRepository::accessEligibilityCondition($db, $userId, 'era'))
			. ')';

		$folderRank = '(' . (string) $db->getQuery(true)
			->select(ResourceAccessRepository::permissionRankExpression($db, 'erfa'))
			->from($db->quoteName('#__emundus_resource_folder_access', 'erfa'))
			->where($db->quoteName('erfa.folder_id') . ' = ' . $db->quoteName('r.folder_id'))
			->where(ResourceAccessRepository::accessEligibilityCondition($db, $userId, 'erfa'))
			. ')';

		return 'GREATEST(COALESCE(' . $fileRank . ', 0), COALESCE(' . $folderRank . ', 0))';
	}

	/**
	 * File rows shared with a user (directly on the file or through a share on its folder, via
	 * profile/group), for a folder level or a flat search. Each row carries the manager-view columns
	 * plus permission_rank (0..3) and is_new (1 when the user has never opened the file). Same shape
	 * as findListRows() file rows.
	 *
	 * @return array<\stdClass>
	 */
	public function findAccessibleFileRows(int $userId, ?int $folderId, ?string $search, int $limit, int $page, array $formats = []): array
	{
		$userId = (int) $userId;
		if ($userId <= 0)
		{
			return [];
		}

		$db    = $this->db;
		$query = $db->getQuery(true);

		// EXISTS-based accessibility keeps one row per file, so the seen LEFT JOIN (unique per
		// user/file) never multiplies rows and permission_rank comes from correlated subqueries.
		$query->select(implode(', ', [
			$db->quoteName('r.id'),
			$db->quoteName('r.name'),
			$db->quoteName('r.created_at'),
			$db->quoteName('r.size'),
			$db->quoteName('r.format'),
			$db->quoteName('r.download_count'),
			$db->quote('file') . ' AS ' . $db->quoteName('type'),
			$this->accessiblePermissionRankExpression($userId) . ' AS ' . $db->quoteName('permission_rank'),
			'CASE WHEN ' . $db->quoteName('seen.id') . ' IS NULL THEN 1 ELSE 0 END AS ' . $db->quoteName('is_new'),
		]))
			->from($db->quoteName($this->tableName, 'r'))
			->join('LEFT', $db->quoteName('#__emundus_resource_seen', 'seen')
				. ' ON ' . $db->quoteName('seen.resource_id') . ' = ' . $db->quoteName('r.id')
				. ' AND ' . $db->quoteName('seen.user_id') . ' = ' . $userId)
			->where($this->accessibleFilePredicate($userId));

		if (!empty($search))
		{
			$like = $db->quote('%' . $db->escape($search, true) . '%', false);
			$query->where($db->quoteName('r.name') . ' LIKE ' . $like);
		}
		else
		{
			$query->where($db->quoteName('r.folder_id') . (!empty($folderId) ? ' = ' . (int) $folderId : ' IS NULL'));
		}

		$formatCondition = $this->formatFilterCondition('r.format', $formats);
		if ($formatCondition !== '')
		{
			$query->where($formatCondition);
		}

		$query->order($db->quoteName('r.name') . ' ASC');

		if (!empty($limit))
		{
			$db->setQuery($query, ($page - 1) * $limit, $limit);
		}
		else
		{
			$db->setQuery($query);
		}

		return $db->loadObjectList() ?: [];
	}

	/**
	 * For each folder directly holding a file shared with the user, the cumulated size of those
	 * files and the count not yet seen ({folder_id, shared_size, unseen_count}). Feeds the folder
	 * tree the shared user browses (size column + "recently shared" badge).
	 *
	 * @return array<\stdClass>
	 */
	public function getSharedSizeAndUnseenByFolder(int $userId): array
	{
		$userId = (int) $userId;
		if ($userId <= 0)
		{
			return [];
		}

		$db    = $this->db;
		$query = $db->getQuery(true);

		$query->select([
			$db->quoteName('r.folder_id'),
			'COALESCE(SUM(' . $db->quoteName('r.size') . '), 0) AS ' . $db->quoteName('shared_size'),
			'SUM(CASE WHEN ' . $db->quoteName('seen.id') . ' IS NULL THEN 1 ELSE 0 END) AS ' . $db->quoteName('unseen_count'),
		])
			->from($db->quoteName($this->tableName, 'r'))
			->join('LEFT', $db->quoteName('#__emundus_resource_seen', 'seen')
				. ' ON ' . $db->quoteName('seen.resource_id') . ' = ' . $db->quoteName('r.id')
				. ' AND ' . $db->quoteName('seen.user_id') . ' = ' . $userId)
			->where($this->accessibleFilePredicate($userId))
			->where($db->quoteName('r.folder_id') . ' IS NOT NULL')
			->group($db->quoteName('r.folder_id'));

		return $db->setQuery($query)->loadObjectList() ?: [];
	}

	/**
	 * Files directly inside a folder that the user may access, as rows carrying id, name, format
	 * and filename (stored relative path). A file share on the file or a share on the folder both
	 * grant access. Managers bypass the share filter and get every file.
	 *
	 * @return array<\stdClass>
	 */
	public function findAccessibleFilesInFolder(int $folderId, int $userId, bool $isManager): array
	{
		$db    = $this->db;
		$query = $db->getQuery(true);

		$query->select([
			$db->quoteName('r.id'),
			$db->quoteName('r.name'),
			$db->quoteName('r.format'),
			$db->quoteName('r.filename'),
		])
			->from($db->quoteName($this->tableName, 'r'))
			->where($db->quoteName('r.folder_id') . ' = ' . (int) $folderId);

		if (!$isManager)
		{
			$query->where($this->accessibleFilePredicate((int) $userId));
		}

		return $db->setQuery($query)->loadObjectList() ?: [];
	}

	/**
	 * Every file as raw references ({id, name, folder_id}), ordered by name. For callers that
	 * rebuild a folder-prefixed path in PHP (e.g. resource pickers).
	 *
	 * @return array<\stdClass>
	 */
	public function getAllFileRefs(): array
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('id'),
				$this->db->quoteName('name'),
				$this->db->quoteName('folder_id'),
			])
			->from($this->db->quoteName($this->tableName))
			->order($this->db->quoteName('name') . ' ASC');

		return $this->db->setQuery($query)->loadObjectList() ?: [];
	}

	public function flush(ResourceEntity $resource): bool
	{
		if (empty($resource->getName()))
		{
			throw new \InvalidArgumentException('Resource name cannot be empty');
		}

		if (empty($resource->getFilename()))
		{
			throw new \InvalidArgumentException('Resource filename cannot be empty');
		}

		$data = (object) [
			'name'           => $resource->getName(),
			'format'         => $resource->getFormat(),
			'filename'       => $resource->getFilename(),
			'size'           => $resource->getSize(),
			'download_count' => $resource->getDownloadCount(),
			'folder_id'      => $resource->getFolderId(),
			'created_by'     => $resource->getCreatedBy(),
		];

		if (empty($resource->getId()))
		{
			$data->created_at = Factory::getDate()->toSql();

			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Failed to insert resource');
			}

			$resource->setId($this->db->insertid());
		}
		else
		{
			$data->id = $resource->getId();

			// $updateNulls = true so moving a file back to the root (folder_id = null) is persisted.
			if (!$this->db->updateObject($this->tableName, $data, 'id', true))
			{
				throw new \RuntimeException('Failed to update resource');
			}
		}

		return true;
	}

	public function incrementDownloadCount(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName($this->tableName))
			->set($this->db->quoteName('download_count') . ' = ' . $this->db->quoteName('download_count') . ' + 1')
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		if (!$this->db->setQuery($query)->execute())
		{
			throw new \RuntimeException('Failed to increment download count for resource ' . $id);
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
			throw new \RuntimeException('Failed to delete resource ' . $id);
		}

		return true;
	}
}
