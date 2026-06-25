<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Attachments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Attachments;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Attachments\AttachmentType;
use Tchooz\Entities\Attachments\AttachmentTypeProperty;

/**
 * @covers \Tchooz\Entities\Attachments\AttachmentType
 */
class AttachmentTypeTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::__construct
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getId
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getLbl
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getName
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getDescription
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getAllowedTypes
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getNbMax
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getOrdering
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::isPublished
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getCategory
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::isRequired
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::getProperties
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new AttachmentType(1, 'LBL_TEST', 'test_attachment', 'A description', 'pdf,jpg', 5);

		$this->assertSame(1, $entity->getId());
		$this->assertSame('LBL_TEST', $entity->getLbl());
		$this->assertSame('test_attachment', $entity->getName());
		$this->assertSame('A description', $entity->getDescription());
		$this->assertSame('pdf,jpg', $entity->getAllowedTypes());
		$this->assertSame(5, $entity->getNbMax());
		$this->assertSame(0, $entity->getOrdering());
		$this->assertTrue($entity->isPublished());
		$this->assertNull($entity->getCategory());
		$this->assertFalse($entity->isRequired());
		$this->assertNull($entity->getProperties());
	}

	/**
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$properties = new AttachmentTypeProperty();
		$entity = new AttachmentType(
			2, 'LBL', 'name', 'desc', 'pdf', 3,
			10, false, 'cat1', true, $properties
		);

		$this->assertSame(2, $entity->getId());
		$this->assertSame(10, $entity->getOrdering());
		$this->assertFalse($entity->isPublished());
		$this->assertSame('cat1', $entity->getCategory());
		$this->assertTrue($entity->isRequired());
		$this->assertSame($properties, $entity->getProperties());
	}

	/**
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::__construct
	 */
	public function testInstanciationWithNullDescription(): void
	{
		$entity = new AttachmentType(1, 'LBL', 'name', null, 'pdf', 1);

		$this->assertNull($entity->getDescription());
	}

	/**
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setId
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setLbl
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setName
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setDescription
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setAllowedTypes
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setNbMax
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setOrdering
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setPublished
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setCategory
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setIsRequired
	 * @covers \Tchooz\Entities\Attachments\AttachmentType::setProperties
	 */
	public function testSetters(): void
	{
		$entity = new AttachmentType(1, 'LBL', 'name', 'desc', 'pdf', 1);
		$properties = new AttachmentTypeProperty();

		$entity->setId(99);
		$entity->setLbl('NEW_LBL');
		$entity->setName('new_name');
		$entity->setDescription('new desc');
		$entity->setAllowedTypes('png,gif');
		$entity->setNbMax(10);
		$entity->setOrdering(5);
		$entity->setPublished(false);
		$entity->setCategory('new_cat');
		$entity->setIsRequired(true);
		$entity->setProperties($properties);

		$this->assertSame(99, $entity->getId());
		$this->assertSame('NEW_LBL', $entity->getLbl());
		$this->assertSame('new_name', $entity->getName());
		$this->assertSame('new desc', $entity->getDescription());
		$this->assertSame('png,gif', $entity->getAllowedTypes());
		$this->assertSame(10, $entity->getNbMax());
		$this->assertSame(5, $entity->getOrdering());
		$this->assertFalse($entity->isPublished());
		$this->assertSame('new_cat', $entity->getCategory());
		$this->assertTrue($entity->isRequired());
		$this->assertSame($properties, $entity->getProperties());
	}
}

