<?php
/**
 * @package     Tchooz\Services\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Resource;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Resource\ResourceAccessEntity;
use Tchooz\Entities\Resource\ResourceDisplaySpaceEntity;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Entities\Resource\ResourceFolderAccessEntity;
use Tchooz\Entities\Resource\ResourceFolderEntity;
use Tchooz\Enums\List\ListSortEnum;
use Tchooz\Enums\Resource\DisplaySpaceTypeEnum;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;
use Tchooz\Factories\Resource\ResourceFactory;
use Tchooz\Repositories\Resource\ResourceAccessRepository;
use Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository;
use Tchooz\Repositories\Resource\ResourceFolderAccessRepository;
use Tchooz\Repositories\Resource\ResourceFolderRepository;
use Tchooz\Repositories\Resource\ResourceRepository;
use Tchooz\Repositories\Resource\ResourceSeenRepository;
use Tchooz\Services\FilePreviewService;
use Tchooz\Services\UploadService;

class ResourceService
{
	public const UPLOAD_DIR = 'images/emundus/resources/';
	private const MAX_FILESIZE_MB = 20;
	// Matches the varchar(255) `name` column on resources and folders.
	private const MAX_NAME_LENGTH = 180;
	private const VALID_MIME_TYPES = [
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.ms-powerpoint',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'text/csv',
		'text/plain',
		'image/jpeg',
		'image/png',
	];

	public function __construct(
		private ResourceRepository             $resourceRepository = new ResourceRepository(),
		private ResourceFolderRepository       $folderRepository = new ResourceFolderRepository(),
		private ResourceAccessRepository       $accessRepository = new ResourceAccessRepository(),
		private ResourceFolderAccessRepository $folderAccessRepository = new ResourceFolderAccessRepository(),
		private ResourceDisplaySpaceRepository $displayRepository = new ResourceDisplaySpaceRepository(),
		private ResourceSeenRepository         $seenRepository = new ResourceSeenRepository(),
		private ?UploadService                 $uploadService = null,
		private ResourceArchiveService         $archiveService = new ResourceArchiveService()
	)
	{
		$this->uploadService ??= new UploadService(self::UPLOAD_DIR, self::MAX_FILESIZE_MB, self::VALID_MIME_TYPES);

		Log::addLogger(['text_file' => 'com_emundus.service.resource.php'], Log::ALL, ['com_emundus.service.resource']);
	}

	/* ---------------------------------------------------------------- Reads */

	public function getResource(int $id): ResourceEntity
	{
		$resource = $this->resourceRepository->getById($id);
		if ($resource === null)
		{
			throw new \DomainException('Resource ' . $id . ' not found');
		}

		return $resource;
	}

	/**
	 * @return array<\stdClass>
	 */
	/**
	 * @param   string|null     $typeFilter  'file' or 'folder' to restrict the list, null for both.
	 * @param   array<string>   $formats     Restrict to these file formats (implies files only).
	 */
	public function getResources(?int $folderId = null, ?string $search = null, int $limit = 0, int $offset = 0, ?string $orderBy = null, ListSortEnum $orderDir = ListSortEnum::ASC, ?string $typeFilter = null, array $formats = []): array
	{
		return $this->resourceRepository->findListRows($folderId, $search, $limit, $offset, $orderBy, $orderDir, $typeFilter, $formats);
	}

	/**
	 * File format filter options as {value, label}. Managers see every format in the library;
	 * everyone else only sees the formats among the files shared with them.
	 *
	 * @return array<array{value:string,label:string}>
	 */
	public function getFormatOptions(int $userId, bool $isManager): array
	{
		$formats = $isManager
			? $this->resourceRepository->getDistinctFormats()
			: $this->resourceRepository->getDistinctAccessibleFormats($userId);

		return array_map(
			static fn (string $format) => ['value' => $format, 'label' => strtoupper($format)],
			$formats
		);
	}

	/**
	 * Every file resource as {id, name, folder_path, label}. `folder_path` is the slash-separated
	 * folder chain ("" for a root file); `label` is that path prefixed to the file name
	 * ("Parent/Child/File name"). Folders themselves are not returned. Used to populate resource
	 * pickers where the folder context matters (grouping or a flat prefixed label).
	 *
	 * @return array<\stdClass>
	 */
	public function getFilesWithFolderPath(): array
	{
		$files = $this->resourceRepository->getAllFileRefs();

		// Whole folder table (small): id => {parent_id, name}, used to rebuild each file's path.
		$folders = [];
		foreach ($this->folderRepository->getAllRows() as $row)
		{
			$folders[(int) $row->id] = $row;
		}

		$options = [];
		foreach ($files as $file)
		{
			$folderId = $file->folder_id !== null ? (int) $file->folder_id : 0;
			$path     = $this->buildFolderPath($folderId, $folders);

			$options[] = (object) [
				'id'          => (int) $file->id,
				'name'        => $file->name,
				'folder_path' => $path,
				'label'       => $path !== '' ? $path . '/' . $file->name : $file->name,
			];
		}

		return $options;
	}

	/**
	 * Slash-separated names of the folder chain leading to (and including) $folderId.
	 * Returns an empty string for a root file (folderId <= 0).
	 *
	 * @param   array<int,object>  $folders  id => {parent_id, name}
	 */
	private function buildFolderPath(int $folderId, array $folders): string
	{
		return implode('/', $this->folderPathSegments($folderId, $folders));
	}

	/**
	 * Ordered folder names from the root down to (and including) $folderId.
	 * Returns an empty array for a root file (folderId <= 0).
	 *
	 * @param   array<int,object>  $folders  id => {parent_id, name}
	 *
	 * @return array<string>
	 */
	private function folderPathSegments(int $folderId, array $folders): array
	{
		$segments = [];
		$current  = $folderId;

		while ($current > 0 && isset($folders[$current]))
		{
			array_unshift($segments, $folders[$current]->name);
			$current = $folders[$current]->parent_id !== null ? (int) $folders[$current]->parent_id : 0;
		}

		return $segments;
	}

	/**
	 * Resources shared with a given user, either directly (type=user) or through one of
	 * their profiles (type=role) or groups (type=group). The folder tree leading to the
	 * shared files is preserved: at each level the user sees the folders that contain
	 * (directly or deeper) a shared file, plus the shared files of the current folder.
	 * Returns the same row shape as getResources() so ResourceListItemTransformer can consume it.
	 *
	 * @return array<object>
	 */
	/**
	 * @param   string|null    $typeFilter  'file' or 'folder' to restrict the list, null for both.
	 * @param   array<string>  $formats     Restrict to these file formats (implies files only).
	 */
	public function getAccessibleResources(int $userId, ?int $folderId = null, ?string $search = null, int $limit = 0, int $page = 0, ?string $orderBy = null, ListSortEnum $orderDir = ListSortEnum::ASC, ?string $typeFilter = null, array $formats = []): array
	{
		if ($userId <= 0)
		{
			return [];
		}

		$userId = (int) $userId;

		// Folders are listed only while browsing without a search and when the view is not restricted
		// to files (an explicit "file" type or a format filter both exclude folders).
		$showFolders = empty($search) && $typeFilter !== 'file' && empty($formats);
		$folderRows  = $showFolders ? $this->getAccessibleFolderRows($userId, $folderId) : [];

		// A "folder" type filter drops the files entirely.
		$fileRows = $typeFilter === 'folder'
			? []
			: $this->resourceRepository->findAccessibleFileRows($userId, $folderId, $search, $limit, $page, $formats);

		// Folders first, then files (mirrors getResources default ordering).
		return array_merge($folderRows, $fileRows);
	}

	/**
	 * Folder rows to display at the given level for a shared user: the folders whose parent
	 * is $folderId and which lead (directly or through descendants) to at least one shared file.
	 *
	 * @return array<object>
	 */
	private function getAccessibleFolderRows(int $userId, ?int $folderId): array
	{
		// Folders directly holding a shared file, with the cumulated size of those files and the
		// count of files not yet seen by the user (drives the "recently shared" folder badge).
		$sizeByFolder   = [];
		$unseenByFolder = [];
		foreach ($this->resourceRepository->getSharedSizeAndUnseenByFolder($userId) as $row)
		{
			$sizeByFolder[(int) $row->folder_id]   = (int) $row->shared_size;
			$unseenByFolder[(int) $row->folder_id] = (int) $row->unseen_count;
		}

		if (empty($sizeByFolder))
		{
			return [];
		}

		// Whole folder table (small): id => {parent_id, name, created_at}.
		$folders = [];
		foreach ($this->folderRepository->getAllRows() as $row)
		{
			$folders[(int) $row->id] = $row;
		}

		// Path set: every folder holding a shared file, plus all its ancestors.
		$pathIds = [];
		foreach (array_keys($sizeByFolder) as $leafId)
		{
			$current = $leafId;
			while ($current !== null && isset($folders[$current]) && !isset($pathIds[$current]))
			{
				$pathIds[$current] = true;
				$parent            = $folders[$current]->parent_id;
				$current           = $parent !== null ? (int) $parent : null;
			}
		}

		// "New" set: any folder holding an unseen shared file, plus all its ancestors, so the
		// badge bubbles up to every parent leading to a not-yet-seen resource.
		$newPathIds = [];
		foreach ($unseenByFolder as $leafId => $unseenCount)
		{
			if ($unseenCount <= 0)
			{
				continue;
			}

			$current = $leafId;
			while ($current !== null && isset($folders[$current]) && !isset($newPathIds[$current]))
			{
				$newPathIds[$current] = true;
				$parent               = $folders[$current]->parent_id;
				$current              = $parent !== null ? (int) $parent : null;
			}
		}

		// Folders whose parent matches the current level.
		$rows = [];
		foreach ($pathIds as $id => $unused)
		{
			$folder      = $folders[$id];
			$parentId    = $folder->parent_id !== null ? (int) $folder->parent_id : null;
			$currentBase = !empty($folderId) ? (int) $folderId : null;

			if ($parentId !== $currentBase)
			{
				continue;
			}

			$rows[] = (object) [
				'id'             => (int) $id,
				'name'           => $folder->name,
				'created_at'     => $folder->created_at,
				'size'           => $sizeByFolder[$id] ?? 0,
				'format'         => '-',
				'download_count' => '-',
				'type'           => 'folder',
				'is_new'         => isset($newPathIds[$id]) ? 1 : 0,
			];
		}

		usort($rows, static fn($a, $b) => strcasecmp($a->name, $b->name));

		return $rows;
	}

	/**
	 * Strongest permission the user holds on a given file: the greater of a direct file share and
	 * a share on the file's folder. 0 = none, 1 = view, 2 = edit, 3 = manage (direct/profile/group).
	 */
	public function getUserPermissionRank(int $userId, int $resourceId): int
	{
		return max(
			$this->accessRepository->getUserPermissionRank($userId, $resourceId),
			$this->folderAccessRepository->getUserPermissionRankForFile($userId, $resourceId)
		);
	}

	public function getFolder(int $id): ResourceFolderEntity
	{
		$folder = $this->folderRepository->getById($id);
		if ($folder === null)
		{
			throw new \DomainException('Folder ' . $id . ' not found');
		}

		return $folder;
	}

	/**
	 * @return array<ResourceFolderEntity>
	 */
	public function getFolders(?int $parentId = null): array
	{
		return $this->folderRepository->getAll($parentId);
	}

	/**
	 * Flat list of every folder as {value, label} options, prefixed with a "root" option.
	 * Each label is the full breadcrumb (root included) so folders sharing a name stay
	 * distinguishable. Used by the "move" and "import" action selects.
	 *
	 * @return array<array{value:string,label:string}>
	 */
	public function getFolderOptions(string $rootLabel): array
	{
		// Whole folder table (small): id => {parent_id, name}, used to rebuild each breadcrumb.
		$folders = [];
		foreach ($this->folderRepository->getAllRows() as $row)
		{
			$folders[(int) $row->id] = $row;
		}

		$options = [];
		foreach ($folders as $id => $row)
		{
			$segments = array_merge([$rootLabel], $this->folderPathSegments($id, $folders));
			$options[] = ['value' => (string) $id, 'label' => implode(' / ', $segments)];
		}

		// Alphabetical breadcrumb ordering keeps each parent right before its descendants.
		usort($options, static fn ($a, $b) => strcasecmp($a['label'], $b['label']));

		// Root entry always first.
		array_unshift($options, ['value' => '', 'label' => $rootLabel]);

		return $options;
	}

	/* ------------------------------------------------------------- Mutations */

	/**
	 * Import an uploaded document into the resource library.
	 *
	 * @param   array  $file  a single $_FILES entry.
	 */
	public function importDocument(array $file, ?int $folderId, int $userId): ResourceEntity
	{
		if ($userId <= 0)
		{
			throw new \InvalidArgumentException('A valid user is required to import a document');
		}

		$relativePath = $this->uploadService->upload($file);

		$resource = new ResourceEntity(
			name: $this->normalizeName(pathinfo($file['name'], PATHINFO_FILENAME)),
			format: strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)),
			filename: $relativePath,
			size: (int) $file['size'],
			folderId: $folderId,
			createdBy: $userId
		);

		$this->resourceRepository->flush($resource);

		return $resource;
	}

	public function createFolder(string $name, ?int $parentId, int $userId): ResourceFolderEntity
	{
		$folder = new ResourceFolderEntity(
			name: $this->normalizeName($name),
			parentId: $parentId,
			createdBy: $userId
		);

		$this->folderRepository->flush($folder);

		return $folder;
	}

	public function rename(int $id, string $name): ResourceEntity
	{
		$resource = $this->getResource($id);
		$resource->setName($this->normalizeName($name));
		$this->resourceRepository->flush($resource);

		return $resource;
	}

	public function renameFolder(int $id, string $name): ResourceFolderEntity
	{
		$folder = $this->getFolder($id);
		$folder->setName($this->normalizeName($name));
		$this->folderRepository->flush($folder);

		return $folder;
	}

	/**
	 * Move a file to another folder (null moves it back to the root).
	 */
	public function move(int $id, ?int $folderId): ResourceEntity
	{
		$resource = $this->getResource($id);
		$resource->setFolderId($folderId);
		$this->resourceRepository->flush($resource);

		return $resource;
	}

	/**
	 * Move a folder under another folder (null moves it back to the root).
	 * A folder cannot be moved into itself.
	 */
	public function moveFolder(int $id, ?int $parentId): ResourceFolderEntity
	{
		if ($parentId !== null && $parentId === $id)
		{
			throw new \InvalidArgumentException('A folder cannot be moved into itself');
		}

		$folder = $this->getFolder($id);
		$folder->setParentId($parentId);
		$this->folderRepository->flush($folder);

		return $folder;
	}

	/**
	 * Delete resources, their stored files and (via FK cascade) their relations.
	 *
	 * @param   array<int>  $ids
	 */
	public function deleteResources(array $ids): bool
	{
		foreach ($ids as $id)
		{
			$id = (int) $id;
			if ($id <= 0)
			{
				continue;
			}

			$resource = $this->resourceRepository->getById($id);
			if ($resource === null)
			{
				continue;
			}

			$this->deleteStoredFile($resource);
			$this->resourceRepository->delete($id);
		}

		return true;
	}

	/**
	 * Delete a folder, its files (with stored files) and its sub-folders recursively.
	 */
	public function deleteFolder(int $id): bool
	{
		$id = (int) $id;
		if ($id <= 0)
		{
			return false;
		}

		if ($this->folderRepository->getById($id) === null)
		{
			return false;
		}

		// Sub-folders first, depth-first.
		foreach ($this->folderRepository->getAll($id) as $child)
		{
			$this->deleteFolder($child->getId());
		}

		// Files directly contained in this folder.
		$fileIds = array_map(
			static fn($resource) => $resource->getId(),
			$this->resourceRepository->getAll($id)
		);
		if (!empty($fileIds))
		{
			$this->deleteResources($fileIds);
		}

		$this->folderRepository->delete($id);

		return true;
	}

	/**
	 * Whether at least one resource is shared with the given user
	 * (directly, or through one of their profiles or groups).
	 */
	public function hasAccessibleResources(int $userId): bool
	{
		// A resource can be shared directly (resource_access) or through its folder
		// (resource_folder_access); either grants the user accessible resources.
		return $this->accessRepository->hasAccessibleForUser($userId)
			|| $this->folderAccessRepository->hasAccessibleForUser($userId);
	}

	/**
	 * Replace the access set of a resource.
	 *
	 * @param   array<array{type:string,target_id:int,permission:string}>  $accessItems
	 */
	public function saveAccess(int $resourceId, array $accessItems): bool
	{
		$this->getResource($resourceId);

		$entities = [];
		foreach ($accessItems as $item)
		{
			$entities[] = new ResourceAccessEntity(
				resourceId: $resourceId,
				type: ResourceAccessTypeEnum::from((string) ($item['type'] ?? '')),
				targetId: (int) ($item['target_id'] ?? 0),
				permission: ResourcePermissionEnum::from((string) ($item['permission'] ?? ResourcePermissionEnum::VIEW->value))
			);
		}

		return $this->accessRepository->replaceForResource($resourceId, $entities);
	}

	/**
	 * Access set of a folder (roles/groups/users), each entry carrying its resolved target label.
	 * Sharing a folder grants its target every file directly inside it, present or added later.
	 *
	 * @return array<ResourceFolderAccessEntity>
	 */
	public function getFolderAccess(int $folderId): array
	{
		$this->getFolder($folderId);

		$access = $this->folderAccessRepository->findByFolder($folderId);
		foreach ($access as $entry)
		{
			$entry->setTargetLabel(ResourceFactory::resolveTargetLabel($entry->getType(), $entry->getTargetId()));
			$entry->setTargetEmail(ResourceFactory::resolveTargetEmail($entry->getType(), $entry->getTargetId()));
		}

		return $access;
	}

	/**
	 * Replace the access set of a folder.
	 *
	 * @param   array<array{type:string,target_id:int,permission:string}>  $accessItems
	 */
	public function saveFolderAccess(int $folderId, array $accessItems): bool
	{
		$this->getFolder($folderId);

		$entities = [];
		foreach ($accessItems as $item)
		{
			$entities[] = new ResourceFolderAccessEntity(
				folderId: $folderId,
				type: ResourceAccessTypeEnum::from((string) ($item['type'] ?? '')),
				targetId: (int) ($item['target_id'] ?? 0),
				permission: ResourcePermissionEnum::from((string) ($item['permission'] ?? ResourcePermissionEnum::VIEW->value))
			);
		}

		return $this->folderAccessRepository->replaceForFolder($folderId, $entities);
	}

	/**
	 * Grant a permission to a single target on a resource without disturbing its other shares.
	 * Idempotent: if the target already has a share, its permission is updated in place.
	 */
	public function grantAccess(int $resourceId, ResourceAccessTypeEnum $type, int $targetId, ResourcePermissionEnum $permission): bool
	{
		$this->getResource($resourceId);

		$accesses = $this->accessRepository->findByResource($resourceId);

		$found = false;
		foreach ($accesses as $access)
		{
			if ($access->getType() === $type && $access->getTargetId() === $targetId)
			{
				$access->setPermission($permission);
				$found = true;
			}
		}

		if (!$found)
		{
			$accesses[] = new ResourceAccessEntity(
				resourceId: $resourceId,
				type: $type,
				targetId: $targetId,
				permission: $permission
			);
		}

		return $this->accessRepository->replaceForResource($resourceId, $accesses);
	}

	/**
	 * Revoke a single target's share on a resource, leaving every other share untouched.
	 * A no-op (returns true) when the target holds no direct share.
	 */
	public function revokeAccess(int $resourceId, ResourceAccessTypeEnum $type, int $targetId): bool
	{
		$this->getResource($resourceId);

		$remaining = array_values(array_filter(
			$this->accessRepository->findByResource($resourceId),
			static fn(ResourceAccessEntity $access) => !($access->getType() === $type && $access->getTargetId() === $targetId)
		));

		return $this->accessRepository->replaceForResource($resourceId, $remaining);
	}

	/**
	 * Grant a permission to a single target on a folder without disturbing its other shares.
	 * Idempotent: if the target already has a share, its permission is updated in place.
	 */
	public function grantFolderAccess(int $folderId, ResourceAccessTypeEnum $type, int $targetId, ResourcePermissionEnum $permission): bool
	{
		$this->getFolder($folderId);

		$accesses = $this->folderAccessRepository->findByFolder($folderId);

		$found = false;
		foreach ($accesses as $access)
		{
			if ($access->getType() === $type && $access->getTargetId() === $targetId)
			{
				$access->setPermission($permission);
				$found = true;
			}
		}

		if (!$found)
		{
			$accesses[] = new ResourceFolderAccessEntity(
				folderId: $folderId,
				type: $type,
				targetId: $targetId,
				permission: $permission
			);
		}

		return $this->folderAccessRepository->replaceForFolder($folderId, $accesses);
	}

	/**
	 * Revoke a single target's share on a folder, leaving every other share untouched.
	 * A no-op (returns true) when the target holds no direct share.
	 */
	public function revokeFolderAccess(int $folderId, ResourceAccessTypeEnum $type, int $targetId): bool
	{
		$this->getFolder($folderId);

		$remaining = array_values(array_filter(
			$this->folderAccessRepository->findByFolder($folderId),
			static fn(ResourceFolderAccessEntity $access) => !($access->getType() === $type && $access->getTargetId() === $targetId)
		));

		return $this->folderAccessRepository->replaceForFolder($folderId, $remaining);
	}

	/**
	 * Replace the display-space set of a resource.
	 *
	 * @param   array<array{type:string,target_id:?int}>  $spaceItems
	 */
	public function saveDisplaySpaces(int $resourceId, array $spaceItems): bool
	{
		$this->getResource($resourceId);

		$entities = [];
		foreach ($spaceItems as $item)
		{
			$targetId   = $item['target_id'] ?? null;
			$entities[] = new ResourceDisplaySpaceEntity(
				resourceId: $resourceId,
				type: DisplaySpaceTypeEnum::from((string) ($item['type'] ?? '')),
				targetId: $targetId !== null ? (int) $targetId : null
			);
		}

		return $this->displayRepository->replaceForResource($resourceId, $entities);
	}

	/**
	 * Acknowledge that a user has opened a resource, clearing its "recently shared" flag.
	 * Idempotent — safe to call on every view/preview/download.
	 */
	public function markResourceSeen(int $userId, int $resourceId): void
	{
		$this->seenRepository->markSeen($userId, $resourceId);
	}

	/**
	 * Return the URL of the stored file without any side effect.
	 * Used for in-app preview, where counting a download would be misleading.
	 */
	public function getViewUrl(int $id): string
	{
		$resource = $this->getResource($id);

		if (!is_file($this->getAbsolutePath($resource)))
		{
			throw new \RuntimeException('Stored file is missing for resource ' . $id);
		}

		return $this->buildGatewayUrl($resource);
	}

	/**
	 * Build an in-app HTML preview of a stored document (PDF, image, Word, spreadsheet…).
	 *
	 * @return array{status: bool, content: string, style: string, overflowX: bool, overflowY: bool, msg: string, error: string}
	 */
	public function getPreview(int $id): array
	{
		$resource = $this->getResource($id);

		$absolutePath = $this->getAbsolutePath($resource);
		$publicUrl    = $this->buildGatewayUrl($resource);

		return (new FilePreviewService())->build($absolutePath, $publicUrl);
	}

	/**
	 * Register a download and return the public URL of the stored file.
	 */
	public function download(int $id): string
	{
		$url = $this->getViewUrl($id);

		$this->resourceRepository->incrementDownloadCount($id);

		return $url;
	}

	/**
	 * Build a zip archive of the files a user may access inside a folder and return its public URL.
	 * Managers holding the "resource" read action get every file in the folder; anyone else only
	 * gets the files explicitly shared with them (directly or via profile/group).
	 */
	public function downloadFolderArchive(int $folderId, int $userId, bool $isManager): string
	{
		$folder = $this->getFolder($folderId);
		$files  = $this->resourceRepository->findAccessibleFilesInFolder($folderId, $userId, $isManager);

		if (empty($files))
		{
			throw new \RuntimeException('No accessible file to archive in folder ' . $folderId);
		}

		$entries = array_map(static fn(object $file): array => [
			'id'        => (int) $file->id,
			'path'      => JPATH_SITE . DIRECTORY_SEPARATOR . ltrim($file->filename, '/'),
			'label'     => $file->name !== '' ? $file->name : 'file-' . $file->id,
			'extension' => (string) ($file->format ?? ''),
		], $files);

		$result = $this->archiveService->create($folder->getName(), $entries, $userId);

		// The download count is a resource-domain fact, so it stays here: bump it once per file
		// the archive service actually stored (missing files are skipped, not counted).
		foreach ($result['archived_ids'] as $id)
		{
			$this->resourceRepository->incrementDownloadCount((int) $id);
		}

		return $result['url'];
	}

	/* --------------------------------------------------------------- Helpers */

	/**
	 * Normalize and validate a user-supplied resource/folder name before it is stored:
	 * strip HTML tags (the list view renders names with v-html, so a stored tag would be a
	 * stored-XSS vector) and control characters, trim, then enforce a non-empty name within
	 * the varchar(255) column. Accents, spaces and ordinary punctuation are intentionally kept.
	 */
	private function normalizeName(string $name): string
	{
		$clean = strip_tags($name);
		$clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
		$clean = trim($clean);

		if ($clean === '')
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_NAME_REQUIRED'));
		}

		if (mb_strlen($clean) > self::MAX_NAME_LENGTH)
		{
			throw new \InvalidArgumentException(Text::sprintf('COM_EMUNDUS_RESOURCE_NAME_TOO_LONG', self::MAX_NAME_LENGTH));
		}

		return $clean;
	}

	/**
	 * Resources are never served as static files: the stored filename is only a uniqid(), so a
	 * direct URL would hand out the file to anyone holding it, forever, bypassing the per-file
	 * ACL and outliving share revocation. Every read goes through getfile, which re-checks access.
	 */
	private function buildGatewayUrl(ResourceEntity $resource): string
	{
		return Uri::root() . 'index.php?option=com_emundus&task=getfile&u=' . ltrim($resource->getFilename(), '/');
	}

	private function getAbsolutePath(ResourceEntity $resource): string
	{
		return JPATH_SITE . DIRECTORY_SEPARATOR . ltrim($resource->getFilename(), '/');
	}

	private function deleteStoredFile(ResourceEntity $resource): void
	{
		$absolutePath = $this->getAbsolutePath($resource);
		if (!is_file($absolutePath))
		{
			// File already gone — nothing to remove, record it and continue.
			Log::add('Stored file already missing for resource ' . $resource->getId() . ' (' . $resource->getFilename() . ')', Log::WARNING, 'com_emundus.service.resource');

			return;
		}

		$this->uploadService->deleteFile($resource->getFilename());
	}
}
