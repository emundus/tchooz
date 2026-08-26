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
use Tchooz\Entities\Resource\ResourceDisplaySpaceEntity;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Enums\Resource\DisplaySpaceTypeEnum;
use Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository;
use Tchooz\Repositories\Resource\ResourceRepository;

/**
 * @covers \Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository
 */
class ResourceDisplaySpaceRepositoryTest extends UnitTestCase
{
	private ResourceDisplaySpaceRepository $repository;

	private ResourceRepository $resourceRepository;

	private array $resourceFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initDataSet();

		$this->repository         = new ResourceDisplaySpaceRepository();
		$this->resourceRepository = new ResourceRepository();
	}

	private function makeResource(string $name): ResourceEntity
	{
		$resource = new ResourceEntity(
			name: $name,
			format: 'pdf',
			filename: 'images/emundus/resources/' . $name . '.pdf',
			createdBy: $this->dataset['coordinator']
		);
		$this->resourceRepository->flush($resource);
		$this->resourceFixtures[] = $resource;

		return $resource;
	}

	private function space(int $resourceId, DisplaySpaceTypeEnum $type, ?int $targetId): ResourceDisplaySpaceEntity
	{
		return new ResourceDisplaySpaceEntity(resourceId: $resourceId, type: $type, targetId: $targetId);
	}

	private function clearFixtures(): void
	{
		foreach ($this->resourceFixtures as $resource)
		{
			foreach ($this->repository->findByResource($resource->getId()) as $space)
			{
				$this->repository->delete($space->getId());
			}
			$this->resourceRepository->delete($resource->getId());
		}
		$this->resourceFixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository::replaceForResource
	 * @covers \Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository::findByResource
	 */
	public function testReplaceForResourceIsAtomicSet(): void
	{
		$resource = $this->makeResource('Display Doc');

		$this->repository->replaceForResource($resource->getId(), [
			$this->space($resource->getId(), DisplaySpaceTypeEnum::FORM, null),
			$this->space($resource->getId(), DisplaySpaceTypeEnum::PROGRAM, 12),
		]);
		$this->assertCount(2, $this->repository->findByResource($resource->getId()));

		$this->repository->replaceForResource($resource->getId(), [
			$this->space($resource->getId(), DisplaySpaceTypeEnum::PUBLIC_PAGE, null),
		]);
		$remaining = $this->repository->findByResource($resource->getId());
		$this->assertCount(1, $remaining, 'Previous spaces cleared');
		$this->assertEquals(DisplaySpaceTypeEnum::PUBLIC_PAGE, $remaining[0]->getType());

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository::findByResource
	 */
	public function testFindByResourceEmptyWhenNone(): void
	{
		$resource = $this->makeResource('No Space Doc');
		$this->assertSame([], $this->repository->findByResource($resource->getId()));
		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository::delete
	 */
	public function testDelete(): void
	{
		$resource = $this->makeResource('Delete Space Doc');
		$this->repository->replaceForResource($resource->getId(), [
			$this->space($resource->getId(), DisplaySpaceTypeEnum::FORM, 3),
		]);

		$space = $this->repository->findByResource($resource->getId())[0];
		$this->assertTrue($this->repository->delete($space->getId()));
		$this->assertEmpty($this->repository->findByResource($resource->getId()));

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceDisplaySpaceRepository::getById
	 */
	public function testGetByIdReturnsNullWhenMissing(): void
	{
		$this->assertNull($this->repository->getById(999999999));
	}
}
