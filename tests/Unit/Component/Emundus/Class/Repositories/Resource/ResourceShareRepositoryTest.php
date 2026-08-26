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
use Tchooz\Entities\Resource\ResourceShareEntity;
use Tchooz\Repositories\Resource\ResourceRepository;
use Tchooz\Repositories\Resource\ResourceShareRepository;

/**
 * @covers \Tchooz\Repositories\Resource\ResourceShareRepository
 */
class ResourceShareRepositoryTest extends UnitTestCase
{
	private ResourceShareRepository $repository;

	private ResourceRepository $resourceRepository;

	private array $resourceFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initDataSet();

		$this->repository         = new ResourceShareRepository();
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

	private function clearFixtures(): void
	{
		foreach ($this->resourceFixtures as $resource)
		{
			$this->repository->deleteByResource($resource->getId());
			$this->resourceRepository->delete($resource->getId());
		}
		$this->resourceFixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceShareRepository::flush
	 * @covers \Tchooz\Repositories\Resource\ResourceShareRepository::findByResource
	 * @covers \Tchooz\Repositories\Resource\ResourceShareRepository::findByCode
	 */
	public function testFlushThenFindByResourceAndCode(): void
	{
		$resource = $this->makeResource('Share Doc');
		$code     = 'unit-share-' . $resource->getId();

		$share = new ResourceShareEntity(
			resourceId: $resource->getId(),
			code: $code,
			passwordHash: password_hash('secret', PASSWORD_DEFAULT)
		);
		$this->assertTrue($this->repository->flush($share));
		$this->assertGreaterThan(0, $share->getId());

		$byResource = $this->repository->findByResource($resource->getId());
		$this->assertInstanceOf(ResourceShareEntity::class, $byResource);
		$this->assertEquals($code, $byResource->getCode());
		$this->assertTrue(password_verify('secret', $byResource->getPasswordHash()), 'Password hash round-trips');

		$byCode = $this->repository->findByCode($code);
		$this->assertInstanceOf(ResourceShareEntity::class, $byCode);
		$this->assertEquals($resource->getId(), $byCode->getResourceId());

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceShareRepository::flush
	 */
	public function testFlushUpdatesExistingShare(): void
	{
		$resource = $this->makeResource('Share Update Doc');
		$share    = new ResourceShareEntity(resourceId: $resource->getId(), code: 'code-' . $resource->getId());
		$this->repository->flush($share);

		$share->setExpirationDate(new \DateTimeImmutable('-1 day'));
		$this->assertTrue($this->repository->flush($share));

		$reloaded = $this->repository->getById($share->getId());
		$this->assertInstanceOf(ResourceShareEntity::class, $reloaded);
		$this->assertTrue($reloaded->isExpired(), 'A past expiration date marks the share expired');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceShareRepository::findByResource
	 * @covers \Tchooz\Repositories\Resource\ResourceShareRepository::findByCode
	 */
	public function testFindersReturnNullWhenMissing(): void
	{
		$resource = $this->makeResource('No Share Doc');
		$this->assertNull($this->repository->findByResource($resource->getId()));
		$this->assertNull($this->repository->findByCode('does-not-exist-code'));
		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceShareRepository::deleteByResource
	 */
	public function testDeleteByResource(): void
	{
		$resource = $this->makeResource('Share Delete Doc');
		$this->repository->flush(new ResourceShareEntity(resourceId: $resource->getId(), code: 'del-' . $resource->getId()));

		$this->assertTrue($this->repository->deleteByResource($resource->getId()));
		$this->assertNull($this->repository->findByResource($resource->getId()), 'Share removed for the resource');

		$this->clearFixtures();
	}
}
