<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Export;

use Joomla\CMS\User\User;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Enums\Export\ExportFormatEnum;

/**
 * @covers \Tchooz\Entities\Export\ExportEntity
 */
class ExportEntityTest extends UnitTestCase
{
	private \DateTime $createdAt;
	private User $user;

	protected function setUp(): void
	{
		parent::setUp();
		$this->createdAt = new \DateTime('2025-01-15 10:00:00');
		$this->user = $this->createMock(User::class);
	}

	/**
	 * @covers \Tchooz\Entities\Export\ExportEntity::__construct
	 * @covers \Tchooz\Entities\Export\ExportEntity::getId
	 * @covers \Tchooz\Entities\Export\ExportEntity::getCreatedAt
	 * @covers \Tchooz\Entities\Export\ExportEntity::getCreatedBy
	 * @covers \Tchooz\Entities\Export\ExportEntity::getFilename
	 * @covers \Tchooz\Entities\Export\ExportEntity::getFormat
	 * @covers \Tchooz\Entities\Export\ExportEntity::getExpiredAt
	 * @covers \Tchooz\Entities\Export\ExportEntity::getTask
	 * @covers \Tchooz\Entities\Export\ExportEntity::getHits
	 * @covers \Tchooz\Entities\Export\ExportEntity::getProgress
	 * @covers \Tchooz\Entities\Export\ExportEntity::isCancelled
	 * @covers \Tchooz\Entities\Export\ExportEntity::isFailed
	 * @covers \Tchooz\Entities\Export\ExportEntity::getResult
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new ExportEntity(
			1, $this->createdAt, $this->user, 'export.xlsx',
			ExportFormatEnum::XLSX, null, null, 0
		);

		$this->assertSame(1, $entity->getId());
		$this->assertSame($this->createdAt, $entity->getCreatedAt());
		$this->assertSame($this->user, $entity->getCreatedBy());
		$this->assertSame('export.xlsx', $entity->getFilename());
		$this->assertSame(ExportFormatEnum::XLSX, $entity->getFormat());
		$this->assertNull($entity->getExpiredAt());
		$this->assertNull($entity->getTask());
		$this->assertSame(0, $entity->getHits());
		$this->assertSame(0.0, $entity->getProgress());
		$this->assertFalse($entity->isCancelled());
		$this->assertFalse($entity->isFailed());
		$this->assertSame([], $entity->getResult());
	}

	/**
	 * @covers \Tchooz\Entities\Export\ExportEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$expiredAt = new \DateTime('2025-02-15 10:00:00');
		$entity = new ExportEntity(
			2, $this->createdAt, $this->user, 'export.pdf',
			ExportFormatEnum::PDF, $expiredAt, null, 5,
			75.5, true, true
		);

		$this->assertSame(2, $entity->getId());
		$this->assertSame(ExportFormatEnum::PDF, $entity->getFormat());
		$this->assertSame($expiredAt, $entity->getExpiredAt());
		$this->assertSame(5, $entity->getHits());
		$this->assertSame(75.5, $entity->getProgress());
		$this->assertTrue($entity->isCancelled());
		$this->assertTrue($entity->isFailed());
	}

	/**
	 * @covers \Tchooz\Entities\Export\ExportEntity::setId
	 * @covers \Tchooz\Entities\Export\ExportEntity::setCreatedAt
	 * @covers \Tchooz\Entities\Export\ExportEntity::setCreatedBy
	 * @covers \Tchooz\Entities\Export\ExportEntity::setFilename
	 * @covers \Tchooz\Entities\Export\ExportEntity::setFormat
	 * @covers \Tchooz\Entities\Export\ExportEntity::setExpiredAt
	 * @covers \Tchooz\Entities\Export\ExportEntity::setTask
	 * @covers \Tchooz\Entities\Export\ExportEntity::setHits
	 * @covers \Tchooz\Entities\Export\ExportEntity::setProgress
	 * @covers \Tchooz\Entities\Export\ExportEntity::setCancelled
	 * @covers \Tchooz\Entities\Export\ExportEntity::setFailed
	 * @covers \Tchooz\Entities\Export\ExportEntity::setResult
	 */
	public function testSetters(): void
	{
		$entity = new ExportEntity(
			1, $this->createdAt, $this->user, 'export.xlsx',
			ExportFormatEnum::XLSX, null, null, 0
		);

		$newDate = new \DateTime('2025-06-01');
		$newUser = $this->createMock(User::class);
		$expiredAt = new \DateTime('2025-12-31');

		$entity->setId(99);
		$entity->setCreatedAt($newDate);
		$entity->setCreatedBy($newUser);
		$entity->setFilename('new_export.pdf');
		$entity->setFormat(ExportFormatEnum::PDF);
		$entity->setExpiredAt($expiredAt);
		$entity->setTask(null);
		$entity->setHits(10);
		$entity->setProgress(50.0);
		$entity->setCancelled(true);
		$entity->setFailed(true);
		$entity->setResult(['file' => 'path/to/file']);

		$this->assertSame(99, $entity->getId());
		$this->assertSame($newDate, $entity->getCreatedAt());
		$this->assertSame($newUser, $entity->getCreatedBy());
		$this->assertSame('new_export.pdf', $entity->getFilename());
		$this->assertSame(ExportFormatEnum::PDF, $entity->getFormat());
		$this->assertSame($expiredAt, $entity->getExpiredAt());
		$this->assertNull($entity->getTask());
		$this->assertSame(10, $entity->getHits());
		$this->assertSame(50.0, $entity->getProgress());
		$this->assertTrue($entity->isCancelled());
		$this->assertTrue($entity->isFailed());
		$this->assertSame(['file' => 'path/to/file'], $entity->getResult());
	}

	/**
	 * @covers \Tchooz\Entities\Export\ExportEntity::__serialize
	 */
	public function testSerialize(): void
	{
		$entity = new ExportEntity(
			1, $this->createdAt, $this->user, 'export.xlsx',
			ExportFormatEnum::XLSX, null, null, 3, 25.0, false, false
		);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertSame($this->createdAt, $serialized['createdAt']);
		$this->assertSame($this->user, $serialized['createdBy']);
		$this->assertSame('export.xlsx', $serialized['filename']);
		$this->assertNull($serialized['expiredAt']);
		$this->assertNull($serialized['task']);
		$this->assertSame(3, $serialized['hits']);
		$this->assertSame(25.0, $serialized['progress']);
		$this->assertSame(ExportFormatEnum::XLSX, $serialized['format']);
		$this->assertFalse($serialized['cancelled']);
		$this->assertFalse($serialized['failed']);
	}
}

