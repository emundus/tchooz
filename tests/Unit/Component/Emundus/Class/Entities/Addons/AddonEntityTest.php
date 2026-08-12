<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Addons;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Enums\Addons\AddonEnum;

/**
 * @covers \Tchooz\Entities\Addons\AddonEntity
 */
class AddonEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::__construct
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getNamekey
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getAddon
	 * @covers \Tchooz\Entities\Addons\AddonEntity::isActivated
	 * @covers \Tchooz\Entities\Addons\AddonEntity::isDisplayed
	 * @covers \Tchooz\Entities\Addons\AddonEntity::isSuggested
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getParams
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getDefault
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getActivatedAt
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new AddonEntity('payment');

		$this->assertSame('payment', $entity->getNamekey());
		$this->assertSame(AddonEnum::PAYMENT, $entity->getAddon());
		$this->assertFalse($entity->isActivated());
		$this->assertFalse($entity->isDisplayed());
		$this->assertFalse($entity->isSuggested());
		$this->assertSame([], $entity->getParams());
		$this->assertSame([], $entity->getDefault());
		$this->assertNull($entity->getActivatedAt());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$activatedAt = new \DateTimeImmutable('2025-06-01 10:00:00');

		$entity = new AddonEntity(
			'payment',
			true,
			true,
			true,
			['key' => 'value'],
			['key' => 'default'],
			$activatedAt
		);

		$this->assertSame('payment', $entity->getNamekey());
		$this->assertSame(AddonEnum::PAYMENT, $entity->getAddon());
		$this->assertTrue($entity->isActivated());
		$this->assertTrue($entity->isDisplayed());
		$this->assertTrue($entity->isSuggested());
		$this->assertSame(['key' => 'value'], $entity->getParams());
		$this->assertSame(['key' => 'default'], $entity->getDefault());
		$this->assertSame($activatedAt, $entity->getActivatedAt());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::__construct
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getAddon
	 */
	public function testInstanciationWithUnknownNamekeySetsAddonToNull(): void
	{
		$entity = new AddonEntity('not_a_real_addon');

		$this->assertSame('not_a_real_addon', $entity->getNamekey());
		$this->assertNull($entity->getAddon());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::setNamekey
	 */
	public function testSetNamekey(): void
	{
		$entity = new AddonEntity('payment');
		$entity->setNamekey('sms');

		$this->assertSame('sms', $entity->getNamekey());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::setAddon
	 */
	public function testSetAddonIsFluentAndUpdatesValue(): void
	{
		$entity = new AddonEntity('payment');

		$result = $entity->setAddon(AddonEnum::SMS);

		$this->assertSame($entity, $result);
		$this->assertSame(AddonEnum::SMS, $entity->getAddon());

		$entity->setAddon(null);
		$this->assertNull($entity->getAddon());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::setActivated
	 * @covers \Tchooz\Entities\Addons\AddonEntity::isActivated
	 */
	public function testSetActivatedIsFluent(): void
	{
		$entity = new AddonEntity('payment');

		$result = $entity->setActivated(true);

		$this->assertSame($entity, $result);
		$this->assertTrue($entity->isActivated());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::setDisplayed
	 * @covers \Tchooz\Entities\Addons\AddonEntity::isDisplayed
	 */
	public function testSetDisplayedIsFluent(): void
	{
		$entity = new AddonEntity('payment');

		$result = $entity->setDisplayed(true);

		$this->assertSame($entity, $result);
		$this->assertTrue($entity->isDisplayed());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::setSuggested
	 * @covers \Tchooz\Entities\Addons\AddonEntity::isSuggested
	 */
	public function testSetSuggestedIsFluent(): void
	{
		$entity = new AddonEntity('payment');

		$result = $entity->setSuggested(true);

		$this->assertSame($entity, $result);
		$this->assertTrue($entity->isSuggested());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::setParams
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getParams
	 */
	public function testSetParams(): void
	{
		$entity = new AddonEntity('payment');
		$entity->setParams(['a' => 1, 'b' => ['c' => 2]]);

		$this->assertSame(['a' => 1, 'b' => ['c' => 2]], $entity->getParams());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getParam
	 */
	public function testGetParamWithoutParent(): void
	{
		$entity = new AddonEntity('payment');
		$entity->setParams(['mode' => 'live']);

		$this->assertSame('live', $entity->getParam('mode', null));
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getParam
	 */
	public function testGetParamWithParent(): void
	{
		$entity = new AddonEntity('payment');
		$entity->setParams(['gateway' => ['key' => 'secret']]);

		$this->assertSame('secret', $entity->getParam('key', 'gateway'));
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getParam
	 */
	public function testGetParamReturnsNullWhenMissing(): void
	{
		$entity = new AddonEntity('payment');

		$this->assertNull($entity->getParam('unknown', null));
		$this->assertNull($entity->getParam('unknown', 'missing_parent'));
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::setDefault
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getDefault
	 */
	public function testSetDefaultIsFluent(): void
	{
		$entity = new AddonEntity('payment');

		$result = $entity->setDefault(['x' => 'y']);

		$this->assertSame($entity, $result);
		$this->assertSame(['x' => 'y'], $entity->getDefault());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::setActivatedAt
	 * @covers \Tchooz\Entities\Addons\AddonEntity::getActivatedAt
	 */
	public function testSetActivatedAt(): void
	{
		$entity = new AddonEntity('payment');
		$activatedAt = new \DateTimeImmutable('2025-06-02 12:00:00');

		$entity->setActivatedAt($activatedAt);
		$this->assertSame($activatedAt, $entity->getActivatedAt());

		$entity->setActivatedAt(null);
		$this->assertNull($entity->getActivatedAt());
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::__serialize
	 */
	public function testSerializeWithKnownAddon(): void
	{
		$activatedAt = new \DateTimeImmutable('2025-06-01 10:00:00');

		$entity = new AddonEntity(
			'payment',
			true,
			false,
			true,
			['key' => 'value'],
			['key' => 'default'],
			$activatedAt
		);

		$serialized = $entity->__serialize();

		$this->assertSame('payment', $serialized['namekey']);
		// label / description are delegated to the enum (translated via Text), so compare to the live enum output
		$this->assertSame(AddonEnum::PAYMENT->getLabel(), $serialized['label']);
		$this->assertSame(AddonEnum::PAYMENT->getDescription(), $serialized['description']);
		$this->assertSame(AddonEnum::PAYMENT->getIcon(), $serialized['icon']);
		$this->assertTrue($serialized['activated']);
		$this->assertFalse($serialized['displayed']);
		$this->assertTrue($serialized['suggested']);
		$this->assertSame(['key' => 'value'], $serialized['params']);
		$this->assertSame(['key' => 'default'], $serialized['default']);
		$this->assertSame('2025-06-01 10:00:00', $serialized['activatedAt']);
	}

	/**
	 * @covers \Tchooz\Entities\Addons\AddonEntity::__serialize
	 */
	public function testSerializeWithUnknownAddonNullsTheEnumFields(): void
	{
		$entity = new AddonEntity('not_a_real_addon');

		$serialized = $entity->__serialize();

		$this->assertSame('not_a_real_addon', $serialized['namekey']);
		$this->assertNull($serialized['label']);
		$this->assertNull($serialized['icon']);
		$this->assertNull($serialized['description']);
		$this->assertNull($serialized['activatedAt']);
	}
}
