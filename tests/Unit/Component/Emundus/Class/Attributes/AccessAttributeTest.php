<?php
/**
 * @package     Unit\Component\Emundus\Class\Attributes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Attributes;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;

/**
 * @package     Unit\Component\Emundus\Class\Attributes
 *
 * @covers \Tchooz\Attributes\AccessAttribute
 */
class AccessAttributeTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Attributes\AccessAttribute::__construct()
	 */
	public function testDefaultInstanciation(): void
	{
		$attribute = new AccessAttribute();

		$this->assertNull($attribute->accessLevel);
		$this->assertSame([], $attribute->actions);
	}

	/**
	 * @covers \Tchooz\Attributes\AccessAttribute::__construct()
	 */
	public function testInstanciationWithAccessLevelOnly(): void
	{
		$attribute = new AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR);

		$this->assertSame(AccessLevelEnum::COORDINATOR, $attribute->accessLevel);
		$this->assertSame([], $attribute->actions);
	}

	/**
	 * @covers \Tchooz\Attributes\AccessAttribute::__construct()
	 */
	public function testInstanciationWithActionsOnly(): void
	{
		$actions = [['id' => 'workflow', 'mode' => CrudEnum::READ]];
		$attribute = new AccessAttribute(actions: $actions);

		$this->assertNull($attribute->accessLevel);
		$this->assertSame($actions, $attribute->actions);
	}

	/**
	 * @covers \Tchooz\Attributes\AccessAttribute::__construct()
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$actions = [['id' => 'campaign', 'mode' => CrudEnum::UPDATE]];
		$attribute = new AccessAttribute(
			accessLevel: AccessLevelEnum::PARTNER,
			actions: $actions
		);

		$this->assertSame(AccessLevelEnum::PARTNER, $attribute->accessLevel);
		$this->assertSame($actions, $attribute->actions);
	}

	/**
	 * @covers \Tchooz\Attributes\AccessAttribute::__construct()
	 */
	public function testIsRepeatableAttribute(): void
	{
		$reflection = new \ReflectionClass(AccessAttribute::class);
		$attributes = $reflection->getAttributes(\Attribute::class);

		$this->assertCount(1, $attributes);

		$attr = $attributes[0]->newInstance();
		$this->assertTrue(($attr->flags & \Attribute::IS_REPEATABLE) !== 0);
		$this->assertTrue(($attr->flags & \Attribute::TARGET_METHOD) !== 0);
		$this->assertTrue(($attr->flags & \Attribute::TARGET_CLASS) !== 0);
	}
}