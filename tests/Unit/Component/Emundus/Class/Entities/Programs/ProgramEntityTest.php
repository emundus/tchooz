<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Programs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Programs;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Programs\ProgramEntity;

/**
 * @covers \Tchooz\Entities\Programs\ProgramEntity
 */
class ProgramEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::__construct
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getId
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getCode
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getLabel
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::isPublished
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getNotes
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getProgrammes
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getSynthesis
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::isApplyOnline
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getOrdering
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getLogo
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::getColor
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new ProgramEntity('PRG001', 'Programme Test');

		$this->assertSame(0, $entity->getId());
		$this->assertSame('PRG001', $entity->getCode());
		$this->assertSame('Programme Test', $entity->getLabel());
		$this->assertTrue($entity->isPublished());
		$this->assertSame('', $entity->getNotes());
		$this->assertSame('', $entity->getProgrammes());
		$this->assertSame('', $entity->getSynthesis());
		$this->assertFalse($entity->isApplyOnline());
		$this->assertSame(0, $entity->getOrdering());
		$this->assertEmpty($entity->getLogo());
		$this->assertSame('', $entity->getColor());
	}

	/**
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$entity = new ProgramEntity(
			'PRG002', 'Full Programme', 5, false,
			'Some notes', 'cat1', 'synthesis text',
			true, 10, 'logo.png', '#FF0000'
		);

		$this->assertSame(5, $entity->getId());
		$this->assertSame('PRG002', $entity->getCode());
		$this->assertSame('Full Programme', $entity->getLabel());
		$this->assertFalse($entity->isPublished());
		$this->assertSame('Some notes', $entity->getNotes());
		$this->assertSame('cat1', $entity->getProgrammes());
		$this->assertSame('synthesis text', $entity->getSynthesis());
		$this->assertTrue($entity->isApplyOnline());
		$this->assertSame(10, $entity->getOrdering());
		$this->assertSame('logo.png', $entity->getLogo());
		$this->assertSame('#FF0000', $entity->getColor());
	}

	/**
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setId
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setCode
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setLabel
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setPublished
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setNotes
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setProgrammes
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setSynthesis
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setApplyOnline
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setOrdering
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setLogo
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::setColor
	 */
	public function testSetters(): void
	{
		$entity = new ProgramEntity('PRG001', 'Programme');

		$entity->setId(42);
		$entity->setCode('NEW_CODE');
		$entity->setLabel('New Label');
		$entity->setPublished(false);
		$entity->setNotes('notes');
		$entity->setProgrammes('programmes');
		$entity->setSynthesis('synthesis');
		$entity->setApplyOnline(true);
		$entity->setOrdering(3);
		$entity->setLogo('new_logo.png');
		$entity->setColor('#00FF00');

		$this->assertSame(42, $entity->getId());
		$this->assertSame('NEW_CODE', $entity->getCode());
		$this->assertSame('New Label', $entity->getLabel());
		$this->assertFalse($entity->isPublished());
		$this->assertSame('notes', $entity->getNotes());
		$this->assertSame('programmes', $entity->getProgrammes());
		$this->assertSame('synthesis', $entity->getSynthesis());
		$this->assertTrue($entity->isApplyOnline());
		$this->assertSame(3, $entity->getOrdering());
		$this->assertSame('new_logo.png', $entity->getLogo());
		$this->assertSame('#00FF00', $entity->getColor());
	}

	/**
	 * @covers \Tchooz\Entities\Programs\ProgramEntity::__serialize
	 */
	public function testSerialize(): void
	{
		$entity = new ProgramEntity('PRG001', 'Programme Test', 1);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertSame('PRG001', $serialized['code']);
		$this->assertSame('Programme Test', $serialized['label']);
		$this->assertTrue($serialized['published']);
	}
}

