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
use Tchooz\Attributes\TableAttribute;

/**
 * @package     Unit\Component\Emundus\Class\Attributes
 *
 * @covers \Tchooz\Attributes\TableAttribute
 */
class TableAttributeTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Attributes\TableAttribute::__construct()
	 */
	public function testInstanciationWithTableOnly(): void
	{
		$attribute = new TableAttribute('jos_users');

		$this->assertSame('jos_users', $attribute->table);
		$this->assertSame('jos_users', $attribute->alias, 'L\'alias doit reprendre le nom de la table par défaut');
		$this->assertSame([], $attribute->columns);
	}

	/**
	 * @covers \Tchooz\Attributes\TableAttribute::__construct()
	 */
	public function testInstanciationWithTableAndAlias(): void
	{
		$attribute = new TableAttribute('jos_users', 'u');

		$this->assertSame('jos_users', $attribute->table);
		$this->assertSame('u', $attribute->alias);
		$this->assertSame([], $attribute->columns);
	}

	/**
	 * @covers \Tchooz\Attributes\TableAttribute::__construct()
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$columns = ['id', 'name', 'email'];
		$attribute = new TableAttribute('jos_users', 'u', $columns);

		$this->assertSame('jos_users', $attribute->table);
		$this->assertSame('u', $attribute->alias);
		$this->assertSame($columns, $attribute->columns);
	}

	/**
	 * @covers \Tchooz\Attributes\TableAttribute::__construct()
	 */
	public function testEmptyAliasDefaultsToTable(): void
	{
		$attribute = new TableAttribute('jos_users', '');

		$this->assertSame('jos_users', $attribute->alias);
	}

	/**
	 * @covers \Tchooz\Attributes\TableAttribute::__construct()
	 */
	public function testIsAttribute(): void
	{
		$reflection = new \ReflectionClass(TableAttribute::class);
		$attributes = $reflection->getAttributes(\Attribute::class);

		$this->assertCount(1, $attributes);
	}
}