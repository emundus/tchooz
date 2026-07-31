<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Synchronizer
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Synchronizer;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Synchronizer\SynchronizerContextEnum;

/**
 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity
 */
class SynchronizerEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::__construct
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getId
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getType
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getName
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getDescription
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getParams
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getConfig
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::isPublished
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::isEnabled
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getIcon
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getConsumptions
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::getContext
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new SynchronizerEntity(1, 'api', 'Sync Name', 'A description');

		$this->assertSame(1, $entity->getId());
		$this->assertSame('api', $entity->getType());
		$this->assertSame('Sync Name', $entity->getName());
		$this->assertSame('A description', $entity->getDescription());
		$this->assertSame([], $entity->getParams());
		$this->assertSame([], $entity->getConfig());
		$this->assertFalse($entity->isPublished());
		$this->assertFalse($entity->isEnabled());
		$this->assertNull($entity->getIcon());
		$this->assertNull($entity->getConsumptions());
		$this->assertNull($entity->getContext());
	}

	/**
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$entity = new SynchronizerEntity(
			2, 'webhook', 'Full Sync', 'Full desc',
			['param1' => 'val1'], ['cfg1' => 'val2'],
			true, true, 'icon.svg', '100',
			SynchronizerContextEnum::PAYMENT
		);

		$this->assertSame(2, $entity->getId());
		$this->assertSame('webhook', $entity->getType());
		$this->assertSame('Full Sync', $entity->getName());
		$this->assertSame('Full desc', $entity->getDescription());
		$this->assertSame(['param1' => 'val1'], $entity->getParams());
		$this->assertSame(['cfg1' => 'val2'], $entity->getConfig());
		$this->assertTrue($entity->isPublished());
		$this->assertTrue($entity->isEnabled());
		$this->assertSame('icon.svg', $entity->getIcon());
		$this->assertSame('100', $entity->getConsumptions());
		$this->assertSame(SynchronizerContextEnum::PAYMENT, $entity->getContext());
	}

	/**
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setId
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setType
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setName
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setDescription
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setParams
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setConfig
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setPublished
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setEnabled
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setIcon
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setConsumptions
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::setContext
	 */
	public function testSetters(): void
	{
		$entity = new SynchronizerEntity(1, 'api', 'Name', 'Desc');

		$entity->setId(50);
		$entity->setType('webhook');
		$entity->setName('New Name');
		$entity->setDescription('New Desc');
		$entity->setParams(['key' => 'val']);
		$entity->setConfig(['cfg' => 'val']);
		$entity->setPublished(true);
		$entity->setEnabled(true);
		$entity->setIcon('new_icon.svg');
		$entity->setConsumptions('50');
		$entity->setContext(SynchronizerContextEnum::NUMERIC_SIGN);

		$this->assertSame(50, $entity->getId());
		$this->assertSame('webhook', $entity->getType());
		$this->assertSame('New Name', $entity->getName());
		$this->assertSame('New Desc', $entity->getDescription());
		$this->assertSame(['key' => 'val'], $entity->getParams());
		$this->assertSame(['cfg' => 'val'], $entity->getConfig());
		$this->assertTrue($entity->isPublished());
		$this->assertTrue($entity->isEnabled());
		$this->assertSame('new_icon.svg', $entity->getIcon());
		$this->assertSame('50', $entity->getConsumptions());
		$this->assertSame(SynchronizerContextEnum::NUMERIC_SIGN, $entity->getContext());
	}

	/**
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::serialize
	 */
	public function testSerialize(): void
	{
		$entity = new SynchronizerEntity(
			1, 'api', 'Sync', 'Desc',
			[], [], true, true, 'icon.svg', '100',
			SynchronizerContextEnum::PAYMENT
		);

		$serialized = $entity->serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertSame('api', $serialized['type']);
		$this->assertSame('Sync', $serialized['name']);
		$this->assertSame('Desc', $serialized['description']);
		$this->assertTrue($serialized['published']);
		$this->assertTrue($serialized['enabled']);
		$this->assertSame('icon.svg', $serialized['icon']);
		$this->assertSame('100', $serialized['consumptions']);
		$this->assertSame('payment', $serialized['context']);
	}

	/**
	 * @covers \Tchooz\Entities\Synchronizer\SynchronizerEntity::serialize
	 */
	public function testSerializeWithNullContext(): void
	{
		$entity = new SynchronizerEntity(1, 'api', 'Sync', 'Desc');

		$serialized = $entity->serialize();

		$this->assertNull($serialized['context']);
	}
}

