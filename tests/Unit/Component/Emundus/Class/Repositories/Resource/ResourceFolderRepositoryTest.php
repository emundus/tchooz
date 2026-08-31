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
use Tchooz\Entities\Resource\ResourceFolderEntity;
use Tchooz\Repositories\Resource\ResourceFolderRepository;

/**
 * @covers \Tchooz\Repositories\Resource\ResourceFolderRepository
 */
class ResourceFolderRepositoryTest extends UnitTestCase
{
	private ResourceFolderRepository $repository;

	private array $folderFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initDataSet();

		$this->repository = new ResourceFolderRepository();
	}

	private function makeFolder(string $name, ?int $parentId = null): ResourceFolderEntity
	{
		$folder = new ResourceFolderEntity(
			name: $name,
			parentId: $parentId,
			createdBy: $this->dataset['coordinator']
		);
		$this->repository->flush($folder);
		$this->folderFixtures[] = $folder;

		return $folder;
	}

	private function clearFixtures(): void
	{
		foreach (array_reverse($this->folderFixtures) as $folder)
		{
			$this->repository->delete($folder->getId());
		}
		$this->folderFixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderRepository::flush
	 */
	public function testFlushInsertsThenUpdates(): void
	{
		$folder = $this->makeFolder('Unit Folder');
		$this->assertGreaterThan(0, $folder->getId(), 'Folder is inserted with a positive id');

		$folder->setName('Unit Folder Renamed');
		$this->assertTrue($this->repository->flush($folder), 'Update returns true');

		$reloaded = $this->repository->getById($folder->getId());
		$this->assertInstanceOf(ResourceFolderEntity::class, $reloaded);
		$this->assertEquals('Unit Folder Renamed', $reloaded->getName(), 'Name was updated');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderRepository::flush
	 */
	public function testFlushRejectsEmptyName(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->flush(new ResourceFolderEntity(name: '', createdBy: $this->dataset['coordinator']));
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderRepository::getById
	 */
	public function testGetByIdReturnsNullWhenMissing(): void
	{
		$this->assertNull($this->repository->getById(999999999), 'Missing folder returns null');
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderRepository::getAll
	 */
	public function testGetAllFiltersByParent(): void
	{
		$parent = $this->makeFolder('Parent Folder');
		$child  = $this->makeFolder('Child Folder', $parent->getId());

		$roots = $this->repository->getAll(null);
		$this->assertContains($parent->getId(), array_map(static fn ($f) => $f->getId(), $roots), 'Root listing contains the root folder');

		$children = $this->repository->getAll($parent->getId());
		$childIds = array_map(static fn ($f) => $f->getId(), $children);
		$this->assertContains($child->getId(), $childIds, 'Child listing contains the child folder');
		$this->assertNotContains($parent->getId(), $childIds, 'Child listing excludes the parent');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderRepository::getAllRows
	 */
	public function testGetAllRowsReturnsRawShape(): void
	{
		$folder = $this->makeFolder('Raw Rows Folder');

		$rows  = $this->repository->getAllRows();
		$this->assertIsArray($rows);

		$match = null;
		foreach ($rows as $row)
		{
			if ((int) $row->id === $folder->getId())
			{
				$match = $row;
				break;
			}
		}

		$this->assertNotNull($match, 'getAllRows contains the created folder');
		$this->assertObjectHasProperty('parent_id', $match);
		$this->assertObjectHasProperty('name', $match);
		$this->assertObjectHasProperty('created_at', $match);

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceFolderRepository::delete
	 */
	public function testDeleteRemovesFolder(): void
	{
		$folder = new ResourceFolderEntity(name: 'To Delete', createdBy: $this->dataset['coordinator']);
		$this->repository->flush($folder);

		$this->assertTrue($this->repository->delete($folder->getId()), 'Delete returns true');
		$this->assertNull($this->repository->getById($folder->getId()), 'Folder is gone after delete');
	}
}
