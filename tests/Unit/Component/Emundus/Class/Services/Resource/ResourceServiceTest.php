<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Resource;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Entities\Resource\ResourceFolderEntity;
use Tchooz\Enums\List\ListSortEnum;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;
use Tchooz\Repositories\Resource\ResourceAccessRepository;
use Tchooz\Repositories\Resource\ResourceFolderRepository;
use Tchooz\Repositories\Resource\ResourceRepository;
use Tchooz\Services\Resource\ResourceService;

/**
 * @covers \Tchooz\Services\Resource\ResourceService
 */
class ResourceServiceTest extends UnitTestCase
{
	private ResourceService $service;

	private ResourceRepository $resourceRepository;

	private ResourceFolderRepository $folderRepository;

	private ResourceAccessRepository $accessRepository;

	private array $resourceIds = [];

	private array $folderIds = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initDataSet();

		$this->service            = new ResourceService();
		$this->resourceRepository = new ResourceRepository();
		$this->folderRepository   = new ResourceFolderRepository();
		$this->accessRepository   = new ResourceAccessRepository();
	}

	private function seedResource(string $name, string $format = 'pdf', ?int $folderId = null): ResourceEntity
	{
		$resource = new ResourceEntity(
			name: $name,
			format: $format,
			filename: 'images/emundus/resources/' . $name . '.' . $format,
			size: 1024,
			folderId: $folderId,
			createdBy: $this->dataset['coordinator']
		);
		$this->resourceRepository->flush($resource);
		$this->resourceIds[] = $resource->getId();

		return $resource;
	}

	private function seedFolder(string $name, ?int $parentId = null): ResourceFolderEntity
	{
		$folder = new ResourceFolderEntity(name: $name, parentId: $parentId, createdBy: $this->dataset['coordinator']);
		$this->folderRepository->flush($folder);
		$this->folderIds[] = $folder->getId();

		return $folder;
	}

	private function cleanup(): void
	{
		foreach ($this->resourceIds as $id)
		{
			$this->accessRepository->replaceForResource($id, []);
			$this->resourceRepository->delete($id);
		}
		$this->resourceIds = [];

		foreach (array_reverse($this->folderIds) as $id)
		{
			$this->folderRepository->delete($id);
		}
		$this->folderIds = [];
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::getResource
	 */
	public function testGetResourceThrowsWhenMissing(): void
	{
		$this->expectException(\DomainException::class);
		$this->service->getResource(999999999);
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::createFolder
	 */
	public function testCreateFolderStripsHtmlAndTrims(): void
	{
		$folder            = $this->service->createFolder('  <b>My Folder</b>  ', null, $this->dataset['coordinator']);
		$this->folderIds[] = $folder->getId();

		$this->assertEquals('My Folder', $folder->getName(), 'HTML stripped and value trimmed');
		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::createFolder
	 */
	public function testCreateFolderRejectsEmptyAfterSanitize(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->service->createFolder('   <br>  ', null, $this->dataset['coordinator']);
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::createFolder
	 */
	public function testCreateFolderRejectsTooLongName(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->service->createFolder(str_repeat('a', 256), null, $this->dataset['coordinator']);
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::rename
	 */
	public function testRenameSanitizesName(): void
	{
		$resource = $this->seedResource('Original');

		$renamed = $this->service->rename($resource->getId(), '<i>Renamed</i>');
		$this->assertEquals('Renamed', $renamed->getName());

		$reloaded = $this->resourceRepository->getById($resource->getId());
		$this->assertEquals('Renamed', $reloaded->getName(), 'Name persisted');

		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::renameFolder
	 */
	public function testRenameFolder(): void
	{
		$folder = $this->seedFolder('Folder Before');

		$this->service->renameFolder($folder->getId(), 'Folder After');
		$this->assertEquals('Folder After', $this->folderRepository->getById($folder->getId())->getName());

		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::move
	 */
	public function testMoveFileBetweenFolders(): void
	{
		$folder   = $this->seedFolder('Move Target');
		$resource = $this->seedResource('Movable', 'pdf', null);

		$this->service->move($resource->getId(), $folder->getId());
		$this->assertEquals($folder->getId(), $this->resourceRepository->getById($resource->getId())->getFolderId());

		$this->service->move($resource->getId(), null);
		$this->assertNull($this->resourceRepository->getById($resource->getId())->getFolderId(), 'Moved back to root');

		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::moveFolder
	 */
	public function testMoveFolderIntoItselfThrows(): void
	{
		$folder = $this->seedFolder('Self Move');

		try
		{
			$this->expectException(\InvalidArgumentException::class);
			$this->service->moveFolder($folder->getId(), $folder->getId());
		}
		finally
		{
			$this->cleanup();
		}
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::deleteFolder
	 */
	public function testDeleteFolderCascadesChildrenAndFiles(): void
	{
		$parent = $this->seedFolder('Parent');
		$child  = $this->seedFolder('Child', $parent->getId());
		$file   = $this->seedResource('Nested File', 'pdf', $child->getId());

		$this->assertTrue($this->service->deleteFolder($parent->getId()));

		$this->assertNull($this->folderRepository->getById($parent->getId()), 'Parent removed');
		$this->assertNull($this->folderRepository->getById($child->getId()), 'Child removed recursively');
		$this->assertNull($this->resourceRepository->getById($file->getId()), 'Nested file removed');

		// Already deleted — avoid double delete in cleanup.
		$this->resourceIds = [];
		$this->folderIds   = [];
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::grantAccess
	 * @covers \Tchooz\Services\Resource\ResourceService::revokeAccess
	 * @covers \Tchooz\Services\Resource\ResourceService::getUserPermissionRank
	 */
	public function testGrantThenRevokeAccess(): void
	{
		$resource = $this->seedResource('Access Managed');

		$this->service->grantAccess($resource->getId(), ResourceAccessTypeEnum::USER, $this->dataset['applicant'], ResourcePermissionEnum::EDIT);
		$this->assertEquals(2, $this->service->getUserPermissionRank($this->dataset['applicant'], $resource->getId()), 'EDIT rank');

		// Grant again updates in place (idempotent, no duplicate).
		$this->service->grantAccess($resource->getId(), ResourceAccessTypeEnum::USER, $this->dataset['applicant'], ResourcePermissionEnum::MANAGE);
		$this->assertEquals(3, $this->service->getUserPermissionRank($this->dataset['applicant'], $resource->getId()), 'Upgraded to MANAGE');
		$this->assertCount(1, $this->accessRepository->findByResource($resource->getId()), 'No duplicate access row');

		$this->service->revokeAccess($resource->getId(), ResourceAccessTypeEnum::USER, $this->dataset['applicant']);
		$this->assertEquals(0, $this->service->getUserPermissionRank($this->dataset['applicant'], $resource->getId()), 'Access revoked');

		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::saveAccess
	 */
	public function testSaveAccessReplacesSet(): void
	{
		$resource = $this->seedResource('Save Access');

		$this->service->saveAccess($resource->getId(), [
			['type' => ResourceAccessTypeEnum::USER->value, 'target_id' => $this->dataset['applicant'], 'permission' => ResourcePermissionEnum::MANAGE->value],
		]);

		$this->assertEquals(3, $this->service->getUserPermissionRank($this->dataset['applicant'], $resource->getId()));
		$this->cleanup();
	}

	/**
	 * Sharing a folder must grant its files (present and future) to the target and count as the
	 * user's permission rank on those files, on par with a direct file share.
	 *
	 * @covers \Tchooz\Services\Resource\ResourceService::saveFolderAccess
	 * @covers \Tchooz\Services\Resource\ResourceService::getFolderAccess
	 * @covers \Tchooz\Services\Resource\ResourceService::getUserPermissionRank
	 * @covers \Tchooz\Services\Resource\ResourceService::getAccessibleResources
	 */
	public function testFolderAccessGrantsFilesToUser(): void
	{
		$folder = $this->seedFolder('Shared Svc Folder');
		$file   = $this->seedResource('Svc Folder File', 'pdf', $folder->getId());

		$this->service->saveFolderAccess($folder->getId(), [
			['type' => ResourceAccessTypeEnum::USER->value, 'target_id' => $this->dataset['applicant'], 'permission' => ResourcePermissionEnum::EDIT->value],
		]);

		$access = $this->service->getFolderAccess($folder->getId());
		$this->assertCount(1, $access, 'Folder access list returns the saved entry');
		$this->assertEquals(ResourcePermissionEnum::EDIT, $access[0]->getPermission());

		$this->assertEquals(2, $this->service->getUserPermissionRank($this->dataset['applicant'], $file->getId()), 'Folder EDIT share grants rank 2 on the file');

		$rows = $this->service->getAccessibleResources($this->dataset['applicant'], $folder->getId());
		$ids  = array_map(static fn ($r) => (int) $r->id, $rows);
		$this->assertContains($file->getId(), $ids, 'File inside the shared folder is accessible');

		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::hasAccessibleResources
	 * @covers \Tchooz\Services\Resource\ResourceService::getAccessibleResources
	 */
	public function testAccessibleResourcesForSharedUser(): void
	{
		$resource = $this->seedResource('Shared To Applicant');
		$this->service->grantAccess($resource->getId(), ResourceAccessTypeEnum::USER, $this->dataset['applicant'], ResourcePermissionEnum::VIEW);

		$this->assertTrue($this->service->hasAccessibleResources($this->dataset['applicant']));

		$rows = $this->service->getAccessibleResources($this->dataset['applicant'], null);
		$ids  = array_map(static fn ($r) => (int) $r->id, $rows);
		$this->assertContains($resource->getId(), $ids, 'Shared resource is returned');

		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::getResources
	 */
	public function testGetResourcesTypeFilter(): void
	{
		$this->seedFolder('Svc Folder');
		$this->seedResource('Svc File', 'pdf', null);

		$filesOnly = $this->service->getResources(null, null, 0, 0, null, ListSortEnum::ASC, 'file');
		$this->assertNotContains('folder', array_map(static fn ($r) => $r->type, $filesOnly), 'type=file excludes folders');

		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::getFormatOptions
	 */
	public function testFormatOptionsManagerVsSharedUser(): void
	{
		$this->seedResource('Manager Only', 'xlsx', null);
		$sharedDoc = $this->seedResource('Shared Format', 'pdf', null);
		$this->service->grantAccess($sharedDoc->getId(), ResourceAccessTypeEnum::USER, $this->dataset['applicant'], ResourcePermissionEnum::VIEW);

		$managerFormats = array_column($this->service->getFormatOptions($this->dataset['coordinator'], true), 'value');
		$this->assertContains('xlsx', $managerFormats, 'Manager sees every format');
		$this->assertContains('pdf', $managerFormats);

		$userFormats = array_column($this->service->getFormatOptions($this->dataset['applicant'], false), 'value');
		$this->assertContains('pdf', $userFormats, 'Shared user sees accessible format');
		$this->assertNotContains('xlsx', $userFormats, 'Shared user does not see non-accessible format');

		$this->cleanup();
	}

	/**
	 * @covers \Tchooz\Services\Resource\ResourceService::getFolderOptions
	 */
	public function testGetFolderOptionsPrependsRoot(): void
	{
		$options = $this->service->getFolderOptions('ROOT_LABEL');

		$this->assertNotEmpty($options);
		$this->assertEquals('', $options[0]['value'], 'First option is the root sentinel');
		$this->assertEquals('ROOT_LABEL', $options[0]['label']);
	}
}
