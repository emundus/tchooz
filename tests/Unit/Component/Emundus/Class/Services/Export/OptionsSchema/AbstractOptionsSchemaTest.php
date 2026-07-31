<?php

namespace Unit\Component\Emundus\Class\Services\Export\OptionsSchema;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema;

/**
 * Concrete stub used to poke `AbstractOptionsSchema` without touching the Joomla-
 * dependent common fields — see `AbstractOptionsSchemaTest::stubSchema` for details.
 */
class StubOptionsSchema extends AbstractOptionsSchema
{
	public array $formatFields = [];

	public array $formatDefaults = [];

	protected function getFormatFields(): array
	{
		return $this->formatFields;
	}

	protected function getFormatDefaults(): array
	{
		return $this->formatDefaults;
	}

	// Skip getCommonFields()/getCommonDefaults() — they call Joomla LanguageHelper.
	public function getFields(): array
	{
		return $this->getFormatFields();
	}

	public function getDefaults(): array
	{
		return $this->getFormatDefaults();
	}
}

/**
 * @package     Unit\Component\Emundus\Class\Services\Export\OptionsSchema
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema
 */
class AbstractOptionsSchemaTest extends TestCase
{
	// -------------------------------------------------------------------------
	// castValue — dispatch per Field::getType
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::castValue
	 * @return void
	 */
	public function testCastValueOnBooleanNormalisesToBool(): void
	{
		$schema = new StubOptionsSchema();

		$this->assertTrue($this->invokeCast($schema, new BooleanField('flag', 'label'), '1'), 'La chaîne "1" doit être castée en true');
		$this->assertTrue($this->invokeCast($schema, new BooleanField('flag', 'label'), 'true'), '"true" doit être casté en true');
		$this->assertFalse($this->invokeCast($schema, new BooleanField('flag', 'label'), '0'), '"0" doit être casté en false');
		$this->assertFalse($this->invokeCast($schema, new BooleanField('flag', 'label'), ''), 'La chaîne vide doit être castée en false');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::castValue
	 * @return void
	 */
	public function testCastValueOnNumericReturnsIntOrNull(): void
	{
		$schema = new StubOptionsSchema();
		$field  = new NumericField('n', 'label');

		$this->assertSame(42, $this->invokeCast($schema, $field, '42'), 'La chaîne numérique "42" doit être castée en int 42');
		$this->assertNull($this->invokeCast($schema, $field, 'abc'), 'Une valeur non numérique doit renvoyer null');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::castValue
	 * @return void
	 */
	public function testCastValueOnStringCoercesScalars(): void
	{
		$schema = new StubOptionsSchema();
		$field  = new StringField('s', 'label');

		$this->assertSame('42', $this->invokeCast($schema, $field, 42), 'Un int doit être casté en string');
		$this->assertSame('', $this->invokeCast($schema, $field, []), 'Un non-scalaire doit renvoyer une chaîne vide');
	}

	// -------------------------------------------------------------------------
	// castValue — new 'choice' branch
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::castValue
	 * @return void
	 */
	public function testCastChoiceReturnsIntForNumericValue(): void
	{
		$schema = new StubOptionsSchema();
		$field  = new ChoiceField('c', 'label', [], false, false, null, false, false);

		$this->assertSame(811, $this->invokeCast($schema, $field, '811'), 'Une valeur numérique doit être castée en int');
		$this->assertSame(811, $this->invokeCast($schema, $field, 811), 'Un int doit rester un int');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::castValue
	 * @return void
	 */
	public function testCastChoiceReturnsStringForNonNumericScalar(): void
	{
		$schema = new StubOptionsSchema();
		$field  = new ChoiceField('c', 'label', [], false, false, null, false, false);

		$this->assertSame('form', $this->invokeCast($schema, $field, 'form'), 'Une valeur textuelle doit être castée en string');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::castValue
	 * @return void
	 */
	public function testCastChoiceReturnsNullForEmptyOrNonScalar(): void
	{
		$schema = new StubOptionsSchema();
		$field  = new ChoiceField('c', 'label', [], false, false, null, false, false);

		$this->assertNull($this->invokeCast($schema, $field, ''), 'La chaîne vide doit renvoyer null');
		$this->assertNull($this->invokeCast($schema, $field, null), 'null doit renvoyer null');
		$this->assertNull($this->invokeCast($schema, $field, ['not', 'scalar']), 'Un tableau doit renvoyer null');
	}

	// -------------------------------------------------------------------------
	// cast — filters + defaults
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::cast
	 * @return void
	 */
	public function testCastKeepsKnownKeysAndDropsOthers(): void
	{
		$schema = new StubOptionsSchema();
		$schema->formatFields = [
			new BooleanField('flag', 'label'),
			new ChoiceField('choice', 'label', [new ChoiceFieldValue('a', 'A')], false, false, null, false, false),
		];
		$schema->formatDefaults = [
			'flag' => false,
			'choice' => null,
		];

		$out = $schema->cast([
			'flag' => '1',
			'choice' => 'a',
			'unknown' => 'ignored',
		]);

		$this->assertTrue($out['flag'], 'flag doit être casté en bool true');
		$this->assertSame('a', $out['choice'], 'choice doit être casté en string');
		$this->assertArrayNotHasKey('unknown', $out, 'Les clés inconnues doivent être ignorées');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\AbstractOptionsSchema::cast
	 * @return void
	 */
	public function testCastAppliesDefaultsForMissingKeys(): void
	{
		$schema = new StubOptionsSchema();
		$schema->formatFields = [
			new BooleanField('flag', 'label'),
			new ChoiceField('choice', 'label', [], false, false, null, false, false),
		];
		$schema->formatDefaults = [
			'flag' => true,
			'choice' => null,
		];

		$out = $schema->cast([]);

		$this->assertTrue($out['flag'], 'flag doit tomber sur son défaut');
		$this->assertNull($out['choice'], 'choice doit tomber sur son défaut null');
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function invokeCast(AbstractOptionsSchema $schema, $field, $value)
	{
		$method = new ReflectionMethod(AbstractOptionsSchema::class, 'castValue');
		$method->setAccessible(true);

		return $method->invoke($schema, $field, $value);
	}
}
