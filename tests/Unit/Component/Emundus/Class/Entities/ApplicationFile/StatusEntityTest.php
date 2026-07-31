<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\ApplicationFile;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\StatusEntity;

/**
 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity
 */
class StatusEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::__construct
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::getId
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::getStep
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::getLabel
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::getOrdering
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::getColor
	 */
	public function testInstanciation(): void
	{
		$entity = new StatusEntity(1, 2, 'Submitted', 3, '#00FF00');

		$this->assertSame(1, $entity->getId());
		$this->assertSame(2, $entity->getStep());
		$this->assertSame('Submitted', $entity->getLabel());
		$this->assertSame(3, $entity->getOrdering());
		$this->assertSame('#00FF00', $entity->getColor());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::setId
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::setStep
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::setLabel
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::setOrdering
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::setColor
	 */
	public function testSetters(): void
	{
		$entity = new StatusEntity(1, 1, 'Draft', 1, '#000000');

		$entity->setId(99);
		$entity->setStep(5);
		$entity->setLabel('Accepted');
		$entity->setOrdering(10);
		$entity->setColor('#FF0000');

		$this->assertSame(99, $entity->getId());
		$this->assertSame(5, $entity->getStep());
		$this->assertSame('Accepted', $entity->getLabel());
		$this->assertSame(10, $entity->getOrdering());
		$this->assertSame('#FF0000', $entity->getColor());
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::__serialize
	 */
	public function testSerialize(): void
	{
		$entity = new StatusEntity(1, 2, 'Submitted', 3, '#00FF00');

		$serialized = $entity->__serialize();

		$this->assertSame([
			'id'       => 2,
			'label'    => 'Submitted',
			'ordering' => 3,
			'color'    => '#00FF00',
		], $serialized);
	}

	/**
	 * @covers \Tchooz\Entities\ApplicationFile\StatusEntity::__serialize
	 */
	public function testSerializeUsesStepAsId(): void
	{
		$entity = new StatusEntity(10, 42, 'Label', 1, '#FFF');

		$serialized = $entity->__serialize();

		$this->assertSame(42, $serialized['id']);
	}
}