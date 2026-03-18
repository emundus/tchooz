<?php

namespace Unit\Component\Emundus\Class\Repositories\Export;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Export\ExportRepository;

class ExportRepositoryTest extends UnitTestCase
{
	private ExportRepository $repository;

	private User $coord;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new ExportRepository();
		$this->coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
	}

	/**
	 * @covers \Tchooz\Repositories\Export\ExportRepository::flush
	 * @return void
	 */
	public function testFlush(): void
	{
		$export = new ExportEntity(0, new \DateTime(), $this->coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$flushed = $this->repository->flush($export);
		$this->assertTrue($flushed, 'The flush method should return true on success');
		$this->assertGreaterThan(0, $export->getId(), 'The export entity should have been saved and assigned an ID');
	}

	/**
	 * @covers \Tchooz\Repositories\Export\ExportRepository::getById()
	 * @return void
	 */
	public function testGetById(): void
	{
		$export = new ExportEntity(0, new \DateTime(), $this->coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$this->repository->flush($export);

		$fetchedExport = $this->repository->getById($export->getId());
		$this->assertNotNull($fetchedExport, 'The getById method should return an export entity');
		$this->assertEquals($export->getId(), $fetchedExport->getId(), 'The fetched export entity should have the same ID as the original');
	}

	/**
	 * @covers \Tchooz\Repositories\Export\ExportRepository::isCancelled()
	 * @return void
	 */
	public function testVerifyIsCancelled(): void
	{
		$export = new ExportEntity(0, new \DateTime(), $this->coord, '', ExportFormatEnum::XLSX, null, null, 0);
		$export->setCancelled(true);
		$this->repository->flush($export);

		$isCancelled = $this->repository->isCancelled($export->getId());
		$this->assertTrue($isCancelled, 'The isCancelled method should return true for a cancelled export');

		$export->setCancelled(false);
		$this->repository->flush($export);

		$isCancelled = $this->repository->isCancelled($export->getId());
		$this->assertFalse($isCancelled, 'The isCancelled method should return false for a non-cancelled export');
	}
}