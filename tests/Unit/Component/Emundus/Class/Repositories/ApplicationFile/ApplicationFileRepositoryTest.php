<?php

namespace Unit\Component\Emundus\Class\Repositories\ApplicationFile;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

class ApplicationFileRepositoryTest extends UnitTestCase
{
	private ApplicationFileRepository $repository;

	public function setUp(): void
	{
		parent::setUp();
		// Setup code before each test
		$this->repository = new ApplicationFileRepository();
	}

	/**
	 * @covers ApplicationFileRepository::getByFnum
	 * @return void
	 */
	public function testGetByFnum(): void
	{
		$applicationFile = $this->repository->getByFnum($this->dataset['fnum']);
		$this->assertNotNull($applicationFile);
		$this->assertEquals($this->dataset['fnum'], $applicationFile->getFnum());
	}

	/**
	 * @covers ApplicationFileRepository::flush
	 * @return void
	 */
	public function testFlush(): void
	{
		$applicationFile = $this->repository->getByFnum($this->dataset['fnum']);
		$applicationFile->setStatus(1);
		$applicationFile->setDateSubmitted(new \Datetime());

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->assertTrue($flushed);

		$updatedApplicationFile = $this->repository->getByFnum($this->dataset['fnum']);
		$this->assertEquals(1, $updatedApplicationFile->getStatus()->getId());
	}
}