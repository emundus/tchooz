<?php

namespace Unit\Component\Emundus\Class\Repositories\Synchronizer;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class SynchronizerRepositoryTest extends UnitTestCase
{
	private SynchronizerRepository $repository;

	public function setUp(): void
	{
		parent::setUp();
		$this->repository = new SynchronizerRepository();
	}

	/**
	 * @covers SynchronizerRepository::getByType
	 */
	public function testGetByType(): void
	{
		$synchronizer = $this->repository->getByType('teams');
		$this->assertNotNull($synchronizer);
		$this->assertEquals('teams', $synchronizer->getType());
	}

	/**
	 * @covers SynchronizerRepository::getById
	 */
	public function testGetById(): void
	{
		$synchronizerByType = $this->repository->getByType('teams');
		$this->assertNotEmpty($synchronizerByType->getId());
		$synchronizerById = $this->repository->getById($synchronizerByType->getId());
		$this->assertNotNull($synchronizerById);
		$this->assertEquals($synchronizerByType->getId(), $synchronizerById->getId());
	}

	/**
	 * @covers SynchronizerRepository::flush
	 */
	public function testFlush(): void
	{
		$synchronizer = $this->repository->getByType('teams');
		$synchronizer->setPublished(true);
		$result = $this->repository->flush($synchronizer);
		$this->assertTrue($result);

		$updatedSynchronizer = $this->repository->getByType('teams');
		$this->assertTrue($updatedSynchronizer->isPublished());
	}
}