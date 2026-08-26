<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Resource;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Resource\ResourceAccessEntity;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Entities\Resource\ResourceFolderAccessEntity;
use Tchooz\Entities\Resource\ResourceFolderEntity;
use Tchooz\Enums\List\ListSortEnum;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;
use Tchooz\Repositories\Resource\ResourceAccessRepository;
use Tchooz\Repositories\Resource\ResourceFolderAccessRepository;
use Tchooz\Repositories\Resource\ResourceFolderRepository;
use Tchooz\Repositories\Resource\ResourceRepository;

/**
 * @covers \Tchooz\Repositories\Resource\ResourceRepository
 */
class ResourceRepositoryTest extends UnitTestCase
{
	private ResourceRepository $repository;

	private ResourceFolderRepository $folderRepository;

	private ResourceAccessRepository $accessRepository;

	private ResourceFolderAccessRepository $folderAccessRepository;

	private array $resourceFixtures = [];

	private array $folderFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initDataSet();

		$this->repository             = new ResourceRepository();
		$this->folderRepository       = new ResourceFolderRepository();
		$this->accessRepository       = new ResourceAccessRepository();
		$this->folderAccessRepository = new ResourceFolderAccessRepository();
	}

	private function makeFolder(string $name, ?int $parentId = null): ResourceFolderEntity
	{
		$folder = new ResourceFolderEntity(name: $name, parentId: $parentId, createdBy: $this->dataset['coordinator']);
		$this->folderRepository->flush($folder);
		$this->folderFixtures[] = $folder;

		return $folder;
	}

	private function makeResource(string $name, string $format = 'pdf', ?int $folderId = null): ResourceEntity
	{
		$resource = new ResourceEntity(
			name: $name,
			format: $format,
			filename: 'images/emundus/resources/' . $name . '.' . $format,
			size: 1024,
			folderId: $folderId,
			createdBy: $this->dataset['coordinator']
		);
		$this->repository->flush($resource);
		$this->resourceFixtures[] = $resource;

		return $resource;
	}

	private function grant(int $resourceId, int $userId, ResourcePermissionEnum $permission): void
	{
		$this->accessRepository->replaceForResource($resourceId, [
			new ResourceAccessEntity(
				resourceId: $resourceId,
				type: ResourceAccessTypeEnum::USER,
				targetId: $userId,
				permission: $permission
			),
		]);
	}

	private function grantFolder(int $folderId, int $userId, ResourcePermissionEnum $permission): void
	{
		$this->folderAccessRepository->replaceForFolder($folderId, [
			new ResourceFolderAccessEntity(
				folderId: $folderId,
				type: ResourceAccessTypeEnum::USER,
				targetId: $userId,
				permission: $permission
			),
		]);
	}

	private function clearFixtures(): void
	{
		foreach ($this->resourceFixtures as $resource)
		{
			$this->repository->delete($resource->getId());
		}
		$this->resourceFixtures = [];

		foreach (array_reverse($this->folderFixtures) as $folder)
		{
			$this->folderRepository->delete($folder->getId());
		}
		$this->folderFixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::flush
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::getById
	 */
	public function testFlushInsertsThenUpdates(): void
	{
		$resource = $this->makeResource('Insert Doc', 'pdf');
		$this->assertGreaterThan(0, $resource->getId());

		$resource->setName('Updated Doc');
		$this->assertTrue($this->repository->flush($resource));

		$reloaded = $this->repository->getById($resource->getId());
		$this->assertInstanceOf(ResourceEntity::class, $reloaded);
		$this->assertEquals('Updated Doc', $reloaded->getName());
		$this->assertEquals('pdf', $reloaded->getFormat());

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::flush
	 */
	public function testFlushRejectsEmptyNameOrFilename(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->flush(new ResourceEntity(name: '', filename: 'x.pdf', createdBy: $this->dataset['coordinator']));
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::incrementDownloadCount
	 */
	public function testIncrementDownloadCount(): void
	{
		$resource = $this->makeResource('Counted Doc');
		$this->assertEquals(0, $resource->getDownloadCount());

		$this->assertTrue($this->repository->incrementDownloadCount($resource->getId()));
		$this->assertTrue($this->repository->incrementDownloadCount($resource->getId()));

		$reloaded = $this->repository->getById($resource->getId());
		$this->assertEquals(2, $reloaded->getDownloadCount(), 'Download count incremented twice');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::findListRows
	 */
	public function testFindListRowsUnionsFoldersAtRoot(): void
	{
		$this->makeFolder('List Folder');
		$this->makeResource('Root File', 'pdf', null);

		$rows  = $this->repository->findListRows(null, null, 0, 0, null, ListSortEnum::ASC);
		$types = array_map(static fn ($r) => $r->type, $rows);

		$this->assertContains('folder', $types, 'Root listing includes folder rows');
		$this->assertContains('file', $types, 'Root listing includes file rows');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::findListRows
	 */
	public function testFindListRowsTypeFilterFileExcludesFolders(): void
	{
		$this->makeFolder('Filtered Folder');
		$this->makeResource('Filtered File', 'pdf', null);

		$rows  = $this->repository->findListRows(null, null, 0, 0, null, ListSortEnum::ASC, 'file');
		$types = array_map(static fn ($r) => $r->type, $rows);

		$this->assertNotContains('folder', $types, 'type=file excludes folders');
		$this->assertContains('file', $types, 'type=file keeps files');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::findListRows
	 */
	public function testFindListRowsTypeFilterFolderExcludesFiles(): void
	{
		$this->makeFolder('Only Folder');
		$this->makeResource('Hidden File', 'pdf', null);

		$rows  = $this->repository->findListRows(null, null, 0, 0, null, ListSortEnum::ASC, 'folder');
		$types = array_map(static fn ($r) => $r->type, $rows);

		$this->assertNotContains('file', $types, 'type=folder excludes files');
		$this->assertContains('folder', $types, 'type=folder keeps folders');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::findListRows
	 */
	public function testFindListRowsFormatFilterRestrictsToFiles(): void
	{
		$this->makeFolder('Format Folder');
		$this->makeResource('Pdf Doc', 'pdf', null);
		$this->makeResource('Docx Doc', 'docx', null);

		$rows    = $this->repository->findListRows(null, null, 0, 0, null, ListSortEnum::ASC, null, ['pdf']);
		$formats = array_map(static fn ($r) => $r->format, $rows);
		$types   = array_map(static fn ($r) => $r->type, $rows);

		$this->assertNotContains('folder', $types, 'Format filter implies files only');
		$this->assertContains('pdf', $formats, 'pdf files kept');
		$this->assertNotContains('docx', $formats, 'docx files filtered out');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::getDistinctFormats
	 */
	public function testGetDistinctFormats(): void
	{
		$this->makeResource('Fmt A', 'pdf', null);
		$this->makeResource('Fmt B', 'docx', null);

		$formats = $this->repository->getDistinctFormats();
		$this->assertContains('pdf', $formats);
		$this->assertContains('docx', $formats);
		$this->assertEquals(array_values(array_unique($formats)), $formats, 'Formats are distinct');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::findAccessibleFileRows
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::getDistinctAccessibleFormats
	 */
	public function testAccessibleRowsAndFormatsRespectSharing(): void
	{
		$shared    = $this->makeResource('Shared Doc', 'pdf', null);
		$notShared = $this->makeResource('Private Doc', 'xlsx', null);

		$this->grant($shared->getId(), $this->dataset['applicant'], ResourcePermissionEnum::EDIT);

		$rows = $this->repository->findAccessibleFileRows($this->dataset['applicant'], null, null, 0, 0);
		$ids  = array_map(static fn ($r) => (int) $r->id, $rows);
		$this->assertContains($shared->getId(), $ids, 'Shared file is visible');
		$this->assertNotContains($notShared->getId(), $ids, 'Non-shared file is hidden');

		$sharedRow = null;
		foreach ($rows as $row)
		{
			if ((int) $row->id === $shared->getId())
			{
				$sharedRow = $row;
			}
		}
		$this->assertNotNull($sharedRow);
		$this->assertEquals(2, (int) $sharedRow->permission_rank, 'EDIT maps to rank 2');

		$formats = $this->repository->getDistinctAccessibleFormats($this->dataset['applicant']);
		$this->assertContains('pdf', $formats, 'Shared format is listed');
		$this->assertNotContains('xlsx', $formats, 'Non-shared format is excluded');

		$this->clearFixtures();
	}

	/**
	 * Sharing a whole folder must expose every file directly inside it, including files added
	 * after the share was created, through both the flat listing and the folder archive query.
	 *
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::findAccessibleFileRows
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::findAccessibleFilesInFolder
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::getSharedSizeAndUnseenByFolder
	 */
	public function testFolderShareExposesItsFilesIncludingLaterOnes(): void
	{
		$folder      = $this->makeFolder('Shared Folder');
		$fileBefore  = $this->makeResource('Before Share', 'pdf', $folder->getId());

		$this->grantFolder($folder->getId(), $this->dataset['applicant'], ResourcePermissionEnum::VIEW);

		// A file dropped into the folder AFTER the share is granted must still be accessible.
		$fileAfter = $this->makeResource('After Share', 'pdf', $folder->getId());

		$rows = $this->repository->findAccessibleFileRows($this->dataset['applicant'], $folder->getId(), null, 0, 0);
		$ids  = array_map(static fn ($r) => (int) $r->id, $rows);
		$this->assertContains($fileBefore->getId(), $ids, 'File present at share time is visible');
		$this->assertContains($fileAfter->getId(), $ids, 'File added after the share is visible');

		foreach ($rows as $row)
		{
			if ((int) $row->id === $fileBefore->getId())
			{
				$this->assertEquals(1, (int) $row->permission_rank, 'Folder VIEW share maps to rank 1');
			}
		}

		$archiveRows = $this->repository->findAccessibleFilesInFolder($folder->getId(), $this->dataset['applicant'], false);
		$this->assertCount(2, $archiveRows, 'Both folder files are archivable by the shared user');

		$sizeRows = $this->repository->getSharedSizeAndUnseenByFolder($this->dataset['applicant']);
		$folderRow = null;
		foreach ($sizeRows as $row)
		{
			if ((int) $row->folder_id === $folder->getId())
			{
				$folderRow = $row;
			}
		}
		$this->assertNotNull($folderRow, 'Shared folder appears in the folder tree');
		// Two files of 1024 bytes each, counted once (no file/folder share double-count).
		$this->assertEquals(2048, (int) $folderRow->shared_size, 'Folder size sums each file once');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::findAccessibleFilesInFolder
	 */
	public function testFindAccessibleFilesInFolderManagerBypassesShare(): void
	{
		$folder = $this->makeFolder('Archive Folder');
		$file   = $this->makeResource('Folder File', 'pdf', $folder->getId());

		$managerRows = $this->repository->findAccessibleFilesInFolder($folder->getId(), 0, true);
		$this->assertContains($file->getId(), array_map(static fn ($r) => (int) $r->id, $managerRows), 'Manager sees every file');

		$nonManagerRows = $this->repository->findAccessibleFilesInFolder($folder->getId(), $this->dataset['applicant'], false);
		$this->assertEmpty($nonManagerRows, 'Non-manager without share sees nothing');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::getAllFileRefs
	 */
	public function testGetAllFileRefs(): void
	{
		$file = $this->makeResource('Ref Doc', 'pdf', null);

		$refs  = $this->repository->getAllFileRefs();
		$match = null;
		foreach ($refs as $ref)
		{
			if ((int) $ref->id === $file->getId())
			{
				$match = $ref;
			}
		}
		$this->assertNotNull($match, 'File reference is returned');
		$this->assertObjectHasProperty('folder_id', $match);
		$this->assertEquals('Ref Doc', $match->name);

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceRepository::delete
	 */
	public function testDelete(): void
	{
		$resource = new ResourceEntity(name: 'Delete Doc', format: 'pdf', filename: 'x.pdf', createdBy: $this->dataset['coordinator']);
		$this->repository->flush($resource);

		$this->assertTrue($this->repository->delete($resource->getId()));
		$this->assertNull($this->repository->getById($resource->getId()));
	}
}
