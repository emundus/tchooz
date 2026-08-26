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
use Tchooz\Entities\Resource\ResourceSeenEntity;
use Tchooz\Repositories\Resource\ResourceRepository;
use Tchooz\Repositories\Resource\ResourceSeenRepository;

/**
 * @covers \Tchooz\Repositories\Resource\ResourceSeenRepository
 */
class ResourceSeenRepositoryTest extends UnitTestCase
{
	private ResourceSeenRepository $repository;

	private ResourceRepository $resourceRepository;

	private array $resourceFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initDataSet();

		$this->repository         = new ResourceSeenRepository();
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

	private function seenRowId(int $userId, int $resourceId): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('jos_emundus_resource_seen'))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $userId)
			->where($this->db->quoteName('resource_id') . ' = ' . (int) $resourceId);

		return (int) $this->db->setQuery($query)->loadResult();
	}

	private function clearFixtures(): void
	{
		foreach ($this->resourceFixtures as $resource)
		{
			$id = $this->seenRowId($this->dataset['applicant'], $resource->getId());
			if ($id > 0)
			{
				$this->repository->delete($id);
			}
			$this->resourceRepository->delete($resource->getId());
		}
		$this->resourceFixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceSeenRepository::markSeen
	 * @covers \Tchooz\Repositories\Resource\ResourceSeenRepository::getSeenResourceIds
	 */
	public function testMarkSeenIsIdempotent(): void
	{
		$resource = $this->makeResource('Seen Doc');

		$this->assertTrue($this->repository->markSeen($this->dataset['applicant'], $resource->getId()));
		$this->assertTrue($this->repository->markSeen($this->dataset['applicant'], $resource->getId()), 'Second call still succeeds');

		$seenIds     = $this->repository->getSeenResourceIds($this->dataset['applicant']);
		$occurrences = array_filter($seenIds, static fn ($id) => (int) $id === $resource->getId());
		$this->assertCount(1, $occurrences, 'Resource marked seen exactly once (unique key)');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceSeenRepository::markSeen
	 */
	public function testMarkSeenRejectsInvalidIds(): void
	{
		$this->assertFalse($this->repository->markSeen(0, 5), 'Invalid user id');
		$this->assertFalse($this->repository->markSeen(5, 0), 'Invalid resource id');
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceSeenRepository::getSeenResourceIds
	 */
	public function testGetSeenResourceIdsEmptyForInvalidUser(): void
	{
		$this->assertSame([], $this->repository->getSeenResourceIds(0));
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceSeenRepository::getById
	 * @covers \Tchooz\Repositories\Resource\ResourceSeenRepository::delete
	 */
	public function testGetByIdAndDelete(): void
	{
		$resource = $this->makeResource('Seen Delete Doc');
		$this->repository->markSeen($this->dataset['applicant'], $resource->getId());

		$id     = $this->seenRowId($this->dataset['applicant'], $resource->getId());
		$entity = $this->repository->getById($id);
		$this->assertInstanceOf(ResourceSeenEntity::class, $entity);
		$this->assertEquals($resource->getId(), $entity->getResourceId());
		$this->assertEquals($this->dataset['applicant'], $entity->getUserId());

		$this->assertTrue($this->repository->delete($id));
		$this->assertNull($this->repository->getById($id), 'Seen row removed');

		$this->resourceRepository->delete($resource->getId());
		$this->resourceFixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceSeenRepository::getById
	 */
	public function testGetByIdReturnsNullWhenMissing(): void
	{
		$this->assertNull($this->repository->getById(999999999));
	}
}
