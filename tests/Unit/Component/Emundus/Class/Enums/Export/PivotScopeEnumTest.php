<?php

namespace Unit\Component\Emundus\Class\Enums\Export;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Export\PivotScopeEnum;

/**
 * @package     Unit\Component\Emundus\Class\Enums\Export
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Enums\Export\PivotScopeEnum
 */
class PivotScopeEnumTest extends TestCase
{
	// -------------------------------------------------------------------------
	// Enum cases
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Enums\Export\PivotScopeEnum
	 * @return void
	 */
	public function testEnumExposesThreeCases(): void
	{
		$cases = PivotScopeEnum::cases();

		$this->assertCount(3, $cases, 'PivotScopeEnum doit exposer exactement 3 cases');
	}

	/**
	 * @covers \Tchooz\Enums\Export\PivotScopeEnum
	 * @return void
	 */
	public function testEachCaseHasExpectedValue(): void
	{
		$this->assertSame('group', PivotScopeEnum::GROUP->value, 'La case GROUP doit avoir la valeur "group"');
		$this->assertSame('element', PivotScopeEnum::ELEMENT->value, 'La case ELEMENT doit avoir la valeur "element"');
		$this->assertSame('evaluation', PivotScopeEnum::EVALUATION->value, 'La case EVALUATION doit conserver la valeur "evaluation" (affichée "Formulaire")');
	}

	/**
	 * The old candidate-form pivot scope was removed; only element/group/evaluation remain.
	 *
	 * @covers \Tchooz\Enums\Export\PivotScopeEnum
	 * @return void
	 */
	public function testFormScopeNoLongerExists(): void
	{
		$this->assertNull(PivotScopeEnum::tryFrom('form'), 'Le scope "form" (page de formulaire candidat) doit avoir été retiré');
	}

	/**
	 * Declaration order drives the UI display order: Element > Section > Form.
	 *
	 * @covers \Tchooz\Enums\Export\PivotScopeEnum
	 * @return void
	 */
	public function testCasesAreDeclaredInDisplayOrder(): void
	{
		$values = array_map(fn(PivotScopeEnum $c) => $c->value, PivotScopeEnum::cases());

		$this->assertSame(['element', 'group', 'evaluation'], $values, 'L\'ordre des cases doit être Élément > Section > Formulaire');
	}

	/**
	 * @covers \Tchooz\Enums\Export\PivotScopeEnum
	 * @return void
	 */
	public function testTryFromResolvesKnownValues(): void
	{
		$this->assertSame(PivotScopeEnum::GROUP, PivotScopeEnum::tryFrom('group'), 'tryFrom("group") doit résoudre la case GROUP');
		$this->assertSame(PivotScopeEnum::EVALUATION, PivotScopeEnum::tryFrom('evaluation'), 'tryFrom("evaluation") doit résoudre la case EVALUATION');
	}

	/**
	 * @covers \Tchooz\Enums\Export\PivotScopeEnum
	 * @return void
	 */
	public function testTryFromReturnsNullForUnknownValue(): void
	{
		$this->assertNull(PivotScopeEnum::tryFrom('unknown'), 'tryFrom doit renvoyer null pour une valeur inconnue');
	}

	// -------------------------------------------------------------------------
	// getLabel — translation keys
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Enums\Export\PivotScopeEnum::getLabel
	 * @return void
	 */
	public function testGetLabelReturnsAKeyForEveryCase(): void
	{
		foreach (PivotScopeEnum::cases() as $case)
		{
			$label = $case->getLabel();
			$this->assertIsString($label, 'getLabel doit renvoyer une string pour ' . $case->value);
			$this->assertStringStartsWith('COM_EMUNDUS_EXPORT_PIVOT_SCOPE_', $label, 'getLabel pour ' . $case->value . ' doit renvoyer une clé COM_EMUNDUS_EXPORT_PIVOT_SCOPE_*');
		}
	}

	/**
	 * @covers \Tchooz\Enums\Export\PivotScopeEnum::getLabel
	 * @return void
	 */
	public function testEachCaseMapsToAUniqueLabelKey(): void
	{
		$labels = array_map(fn(PivotScopeEnum $c) => $c->getLabel(), PivotScopeEnum::cases());

		$this->assertSame(count($labels), count(array_unique($labels)), 'Chaque case doit avoir une clé de label distincte');
	}
}
