<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Upload
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Upload;

use DateTimeImmutable;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Enums\Upload\UploadValidationStatusEnum;

/**
 * @covers \Tchooz\Entities\Upload\UploadEntity
 */
class UploadEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Upload\UploadEntity::__construct
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getId
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getUserId
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getFnum
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getAttachmentId
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getFilename
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getDescription
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getLocalFilename
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getCampaignId
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getSize
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getValidationStatus
	 * @covers \Tchooz\Entities\Upload\UploadEntity::isSigned
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getThumbnail
	 * @covers \Tchooz\Entities\Upload\UploadEntity::canBeDeleted
	 * @covers \Tchooz\Entities\Upload\UploadEntity::canBeViewed
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new UploadEntity(
			1, 42, '0001-0001', 10,
			'file.pdf', 'A description', 'local_file.pdf'
		);

		$this->assertSame(1, $entity->getId());
		$this->assertSame(42, $entity->getUserId());
		$this->assertSame('0001-0001', $entity->getFnum());
		$this->assertSame(10, $entity->getAttachmentId());
		$this->assertSame('file.pdf', $entity->getFilename());
		$this->assertSame('A description', $entity->getDescription());
		$this->assertSame('local_file.pdf', $entity->getLocalFilename());
		$this->assertNull($entity->getCampaignId());
		$this->assertNull($entity->getSize());
		$this->assertSame(UploadValidationStatusEnum::TO_BE_VALIDATED, $entity->getValidationStatus());
		$this->assertFalse($entity->isSigned());
		$this->assertNull($entity->getThumbnail());
		$this->assertTrue($entity->canBeDeleted());
		$this->assertTrue($entity->canBeViewed());
	}

	/**
	 * @covers \Tchooz\Entities\Upload\UploadEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$entity = new UploadEntity(
			2, 43, '0002-0002', 11,
			'image.jpg', 'Description', 'local_image.jpg',
			5, 1024, UploadValidationStatusEnum::VALIDATED,
			true, 'thumb.jpg', false, false
		);

		$this->assertSame(2, $entity->getId());
		$this->assertSame(5, $entity->getCampaignId());
		$this->assertSame(1024, $entity->getSize());
		$this->assertSame(UploadValidationStatusEnum::VALIDATED, $entity->getValidationStatus());
		$this->assertTrue($entity->isSigned());
		$this->assertSame('thumb.jpg', $entity->getThumbnail());
		$this->assertFalse($entity->canBeDeleted());
		$this->assertFalse($entity->canBeViewed());
	}

	/**
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setId
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setUserId
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setFnum
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setAttachmentId
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setFilename
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setDescription
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setLocalFilename
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setCampaignId
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setSize
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setValidationStatus
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setIsSigned
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setThumbnail
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setCanBeDeleted
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setCanBeViewed
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setTimedate
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getTimedate
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setModified
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getModified
	 * @covers \Tchooz\Entities\Upload\UploadEntity::setModifiedBy
	 * @covers \Tchooz\Entities\Upload\UploadEntity::getModifiedBy
	 */
	public function testSetters(): void
	{
		$entity = new UploadEntity(
			1, 42, '0001-0001', 10,
			'file.pdf', 'desc', 'local.pdf'
		);

		$timedate = new DateTimeImmutable('2025-01-15 10:00:00');
		$modified = new DateTimeImmutable('2025-02-01 12:00:00');

		$entity->setId(99);
		$entity->setUserId(100);
		$entity->setFnum('9999-9999');
		$entity->setAttachmentId(20);
		$entity->setFilename('new_file.pdf');
		$entity->setDescription('new desc');
		$entity->setLocalFilename('new_local.pdf');
		$entity->setCampaignId(7);
		$entity->setSize(2048);
		$entity->setValidationStatus(UploadValidationStatusEnum::INVALID);
		$entity->setIsSigned(true);
		$entity->setThumbnail('new_thumb.jpg');
		$entity->setCanBeDeleted(false);
		$entity->setCanBeViewed(false);
		$entity->setTimedate($timedate);
		$entity->setModified($modified);
		$entity->setModifiedBy(55);

		$this->assertSame(99, $entity->getId());
		$this->assertSame(100, $entity->getUserId());
		$this->assertSame('9999-9999', $entity->getFnum());
		$this->assertSame(20, $entity->getAttachmentId());
		$this->assertSame('new_file.pdf', $entity->getFilename());
		$this->assertSame('new desc', $entity->getDescription());
		$this->assertSame('new_local.pdf', $entity->getLocalFilename());
		$this->assertSame(7, $entity->getCampaignId());
		$this->assertSame(2048, $entity->getSize());
		$this->assertSame(UploadValidationStatusEnum::INVALID, $entity->getValidationStatus());
		$this->assertTrue($entity->isSigned());
		$this->assertSame('new_thumb.jpg', $entity->getThumbnail());
		$this->assertFalse($entity->canBeDeleted());
		$this->assertFalse($entity->canBeViewed());
		$this->assertSame($timedate, $entity->getTimedate());
		$this->assertSame($modified, $entity->getModified());
		$this->assertSame(55, $entity->getModifiedBy());
	}
}

