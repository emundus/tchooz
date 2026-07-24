<?php

namespace Unit\Component\Emundus\Class\Services\Export\OptionsSchema;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Enums\Export\PivotScopeEnum;
use Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema;

/**
 * @package     Unit\Component\Emundus\Class\Services\Export\OptionsSchema
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema
 */
class ExcelOptionsSchemaTest extends TestCase
{
	/**
	 * Cross the abstract's `getCommonFields()` / `getCommonDefaults()` which need a
	 * Joomla app runtime (language pack lookup). We only exercise the format-specific
	 * declarations via reflection to stay pure-unit.
	 */
	private function callProtected(ExcelOptionsSchema $schema, string $method): array
	{
		$ref = new ReflectionMethod(ExcelOptionsSchema::class, $method);
		$ref->setAccessible(true);

		return $ref->invoke($schema);
	}

	// -------------------------------------------------------------------------
	// getFormatFields — pivot_scope, pivot_target, display_evaluator_name
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema::getFormatFields
	 * @return void
	 */
	public function testFormatFieldsExposesPivotAndEvaluatorToggles(): void
	{
		$schema = new ExcelOptionsSchema();
		$fields = $this->callProtected($schema, 'getFormatFields');

		$names = array_map(fn($f) => $f->getName(), $fields);

		$this->assertContains(ExcelOptionsSchema::PIVOT_SCOPE, $names, 'pivot_scope doit être déclaré');
		$this->assertContains(ExcelOptionsSchema::PIVOT_TARGET, $names, 'pivot_target doit être déclaré');
		$this->assertContains(ExcelOptionsSchema::DISPLAY_EVALUATOR_NAME, $names, 'display_evaluator_name doit être déclaré');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema::getFormatFields
	 * @return void
	 */
	public function testPivotScopeIsAChoiceFieldWithFourEnumEntries(): void
	{
		$field = $this->findField(new ExcelOptionsSchema(), ExcelOptionsSchema::PIVOT_SCOPE);

		$this->assertInstanceOf(ChoiceField::class, $field, 'pivot_scope doit être un ChoiceField');
		$this->assertFalse($field->getMultiple(), 'pivot_scope doit être single-select');

		$choiceValues = array_map(fn($c) => $c->getValue(), $field->getChoices());

		foreach (PivotScopeEnum::cases() as $case)
		{
			$this->assertContains($case->value, $choiceValues, 'pivot_scope doit inclure ' . $case->value);
		}
	}

	/**
	 * `addSelectOption: true` on pivot_scope prepends a `null`-valued placeholder
	 * so the user can un-pick the scope (pivot becomes optional).
	 *
	 * @covers \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema::getFormatFields
	 * @return void
	 */
	public function testPivotScopeAllowsUnpickingViaPlaceholderChoice(): void
	{
		$field = $this->findField(new ExcelOptionsSchema(), ExcelOptionsSchema::PIVOT_SCOPE);
		$choiceValues = array_map(fn($c) => $c->getValue(), $field->getChoices());

		$this->assertContains(null, $choiceValues, 'pivot_scope doit inclure un choix null (placeholder "please select")');
	}

	/**
	 * pivot_target choices stay empty server-side — the frontend fills them
	 * dynamically from the user's selected export content.
	 *
	 * @covers \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema::getFormatFields
	 * @return void
	 */
	public function testPivotTargetHasEmptyChoicesAndNoPlaceholder(): void
	{
		$field = $this->findField(new ExcelOptionsSchema(), ExcelOptionsSchema::PIVOT_TARGET);

		$this->assertInstanceOf(ChoiceField::class, $field, 'pivot_target doit être un ChoiceField');
		$this->assertSame([], $field->getChoices(), 'pivot_target ne doit pas déclarer de choices côté backend');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema::getFormatFields
	 * @return void
	 */
	public function testDisplayEvaluatorNameIsABooleanField(): void
	{
		$field = $this->findField(new ExcelOptionsSchema(), ExcelOptionsSchema::DISPLAY_EVALUATOR_NAME);

		$this->assertInstanceOf(BooleanField::class, $field, 'display_evaluator_name doit être un BooleanField');
	}

	// -------------------------------------------------------------------------
	// getFormatDefaults
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema::getFormatDefaults
	 * @return void
	 */
	public function testFormatDefaultsCoverEveryFormatKey(): void
	{
		$defaults = $this->callProtected(new ExcelOptionsSchema(), 'getFormatDefaults');

		$this->assertArrayHasKey(ExcelOptionsSchema::DISPLAY_EVALUATOR_NAME, $defaults, 'defaults doit contenir display_evaluator_name');
		$this->assertArrayHasKey(ExcelOptionsSchema::PIVOT_SCOPE, $defaults, 'defaults doit contenir pivot_scope');
		$this->assertArrayHasKey(ExcelOptionsSchema::PIVOT_TARGET, $defaults, 'defaults doit contenir pivot_target');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema::getFormatDefaults
	 * @return void
	 */
	public function testPivotDefaultsAreNullSoPivotIsOffByDefault(): void
	{
		$defaults = $this->callProtected(new ExcelOptionsSchema(), 'getFormatDefaults');

		$this->assertNull($defaults[ExcelOptionsSchema::PIVOT_SCOPE], 'pivot_scope doit avoir null pour défaut');
		$this->assertNull($defaults[ExcelOptionsSchema::PIVOT_TARGET], 'pivot_target doit avoir null pour défaut');
	}

	/**
	 * @covers \Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema::getFormatDefaults
	 * @return void
	 */
	public function testDisplayEvaluatorNameDefaultsToTrue(): void
	{
		$defaults = $this->callProtected(new ExcelOptionsSchema(), 'getFormatDefaults');

		$this->assertTrue($defaults[ExcelOptionsSchema::DISPLAY_EVALUATOR_NAME], 'display_evaluator_name doit être true par défaut');
	}

	// -------------------------------------------------------------------------
	// Helper
	// -------------------------------------------------------------------------

	private function findField(ExcelOptionsSchema $schema, string $name)
	{
		foreach ($this->callProtected($schema, 'getFormatFields') as $field)
		{
			if ($field->getName() === $name)
			{
				return $field;
			}
		}

		$this->fail('Champ non trouvé dans le schema : ' . $name);
	}
}
