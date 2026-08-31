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
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Entities\Resource\ResourceFolderAccessEntity;
use Tchooz\Entities\Resource\ResourceFolderEntity;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;
use Tchooz\Repositories\Resource\ResourceFolderAccessRepository;
use Tchooz\Repositories\Resource\ResourceFolderRepository;
use Tchooz\Repositories\Resource\ResourceRepository;

/**
 * @covers \Tchooz\Repositories\Resource\ResourceFolderAccessRepository
 */
class ResourceFolderAccessRepositoryTest extends UnitTestCase
{
	private ResourceFolderAccessRepository $repository;

	private ResourceFolderRepository $folderRepository;

	private ResourceRepository $resourceRepository;

	private array $folderFixtures = [];

	private array $resourceFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initDataSet();

		$this->repository         = new ResourceFolderAccessRepository();
		$this->folderRepository   = new ResourceFolderRepository();
		$this->resourceRepository = new ResourceRepository();
	}

	private function makeFolder(string $name): ResourceFolderEntity
	{
		$folder = new ResourceFolderEntity(name: $name, parentId: null, createdBy: $this->dataset['coordinator']);
		$this->folderRepository->flush($folder);
		$this->folderFixtures[] = $folder;

		return $folder;
	}

	private function makeResource(string $name, int $folderId): ResourceEntity
	{
		$resource = new ResourceEntity(
			name: $name,
			format: 'pdf',
			filename: 'images/emundus/resources/' . $name . '.pdf',
			size: 512,
			folderId: $folderId,
			createdBy: $this->dataset['coordinator']
		);
		$this->resourceRepository->flush($resource);
		$this->resourceFixtures[] = $resource;

		return $resource;
	}

	private function accessFor(int $folderId, int $userId, ResourcePermissionEnum $permission): ResourceFolderAccessEntity
	{
		return new ResourceFolderAccessEntity(
			folderId: $folderId,
			type: ResourceAccessTypeEnum::USER,
			targetId: $userId,
			permission: $permission
		);
	}

	private function clearFixtures(): void
	{
		foreach ($this->resourceFixtures as $resource)
		{
			$this->resourceRepository->delete($resource->getId());
		}
		$this->resourceFixtures = [];

		foreach ($this->folderFixtures as $folder)
		{
			// Folder access rows are removed by the folder FK cascade, but clear explicitly too.
			$this->repository->replaceForFolder($folder->getId(), []);
			$this->folderRepository->delete($folder->getId());
		}
		$this->folderFixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderAccessRepository::replaceForFolder
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderAccessRepository::findByFolder
	 */
	public function testReplaceForFolderIsAtomicSet(): void
	{
		$folder = $this->makeFolder('Folder Access');

		$this->repository->replaceForFolder($folder->getId(), [
			$this->accessFor($folder->getId(), $this->dataset['applicant'], ResourcePermissionEnum::VIEW),
			$this->accessFor($folder->getId(), $this->dataset['coordinator'], ResourcePermissionEnum::MANAGE),
		]);
		$this->assertCount(2, $this->repository->findByFolder($folder->getId()), 'Both accesses inserted');

		$this->repository->replaceForFolder($folder->getId(), [
			$this->accessFor($folder->getId(), $this->dataset['applicant'], ResourcePermissionEnum::EDIT),
		]);
		$remaining = $this->repository->findByFolder($folder->getId());
		$this->assertCount(1, $remaining, 'Old accesses cleared, only new one remains');
		$this->assertEquals(ResourcePermissionEnum::EDIT, $remaining[0]->getPermission());

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderAccessRepository::getUserPermissionRankForFile
	 */
	public function testGetUserPermissionRankForFile(): void
	{
		$folder = $this->makeFolder('Rank Folder');
		$file   = $this->makeResource('Folder File', $folder->getId());

		$this->assertEquals(0, $this->repository->getUserPermissionRankForFile($this->dataset['applicant'], $file->getId()), 'No folder share means rank 0');

		$this->repository->replaceForFolder($folder->getId(), [
			$this->accessFor($folder->getId(), $this->dataset['applicant'], ResourcePermissionEnum::MANAGE),
		]);
		$this->assertEquals(3, $this->repository->getUserPermissionRankForFile($this->dataset['applicant'], $file->getId()), 'Folder MANAGE share maps to rank 3 on the file');
		$this->assertEquals(0, $this->repository->getUserPermissionRankForFile(0, $file->getId()), 'Invalid user returns 0');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderAccessRepository::delete
	 */
	public function testDelete(): void
	{
		$folder = $this->makeFolder('Delete Folder Access');
		$this->repository->replaceForFolder($folder->getId(), [
			$this->accessFor($folder->getId(), $this->dataset['applicant'], ResourcePermissionEnum::VIEW),
		]);

		$access = $this->repository->findByFolder($folder->getId())[0];
		$this->assertTrue($this->repository->delete($access->getId()));
		$this->assertEmpty($this->repository->findByFolder($folder->getId()), 'Access removed');

		$this->clearFixtures();
	}
}
