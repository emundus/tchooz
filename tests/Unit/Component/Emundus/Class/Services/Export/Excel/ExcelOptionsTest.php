<?php

namespace Unit\Component\Emundus\Class\Services\Export\Excel;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Export\PivotScopeEnum;
use Tchooz\Services\Export\Excel\ExcelOptions;
use Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema;

/**
 * @package     Unit\Component\Emundus\Class\Services\Export\Excel
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Export\Excel\ExcelOptions
 */
class ExcelOptionsTest extends TestCase
{
	private ExcelOptions $options;

	protected function setUp(): void
	{
		parent::setUp();
		$this->options = new ExcelOptions();
	}

	// -------------------------------------------------------------------------
	// getPivotScope
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotScope
	 * @return void
	 */
	public function testGetPivotScopeReturnsNullWhenSettingIsMissing(): void
	{
		$this->assertNull($this->options->getPivotScope(), 'getPivotScope doit renvoyer null si le setting n\'est pas défini');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotScope
	 * @return void
	 */
	public function testGetPivotScopeReturnsNullWhenSettingIsEmptyString(): void
	{
		$this->options->setSetting(ExcelOptionsSchema::PIVOT_SCOPE, '');

		$this->assertNull($this->options->getPivotScope(), 'La chaîne vide doit être traitée comme "pas de scope"');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotScope
	 * @return void
	 */
	public function testGetPivotScopeResolvesEnumFromKnownString(): void
	{
		$this->options->setSetting(ExcelOptionsSchema::PIVOT_SCOPE, 'group');

		$this->assertSame(PivotScopeEnum::GROUP, $this->options->getPivotScope(), '"group" doit résoudre la case GROUP');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotScope
	 * @return void
	 */
	public function testGetPivotScopeReturnsNullForUnknownString(): void
	{
		$this->options->setSetting(ExcelOptionsSchema::PIVOT_SCOPE, 'nonsense');

		$this->assertNull($this->options->getPivotScope(), 'Une valeur inconnue doit renvoyer null (pas de scope appliqué)');
	}

	// -------------------------------------------------------------------------
	// getPivotTargetId
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotTargetId
	 * @return void
	 */
	public function testGetPivotTargetIdReturnsNullWhenSettingIsMissing(): void
	{
		$this->assertNull($this->options->getPivotTargetId(), 'getPivotTargetId doit renvoyer null si le setting n\'est pas défini');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotTargetId
	 * @return void
	 */
	public function testGetPivotTargetIdReturnsIntFromString(): void
	{
		$this->options->setSetting(ExcelOptionsSchema::PIVOT_TARGET, '811');

		$this->assertSame(811, $this->options->getPivotTargetId(), 'Une valeur numérique en string doit être castée en int');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotTargetId
	 * @return void
	 */
	public function testGetPivotTargetIdReturnsIntFromInt(): void
	{
		$this->options->setSetting(ExcelOptionsSchema::PIVOT_TARGET, 42);

		$this->assertSame(42, $this->options->getPivotTargetId(), 'Un int doit être renvoyé tel quel');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotTargetId
	 * @return void
	 */
	public function testGetPivotTargetIdReturnsNullForZero(): void
	{
		$this->options->setSetting(ExcelOptionsSchema::PIVOT_TARGET, 0);

		$this->assertNull($this->options->getPivotTargetId(), 'Un id 0 doit être traité comme "pas de cible"');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions::getPivotTargetId
	 * @return void
	 */
	public function testGetPivotTargetIdReturnsNullForNonNumeric(): void
	{
		$this->options->setSetting(ExcelOptionsSchema::PIVOT_TARGET, 'abc');

		$this->assertNull($this->options->getPivotTargetId(), 'Une valeur non numérique doit renvoyer null');
	}

	// -------------------------------------------------------------------------
	// Synthesis defaults still available (backward-compat sanity check)
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions
	 * @return void
	 */
	public function testConstructorWithoutSynthesisYieldsEmptyArray(): void
	{
		$this->assertSame([], $this->options->getSynthesis(), 'Synthesis doit être un tableau vide par défaut');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelOptions
	 * @return void
	 */
	public function testConstructorPassesSynthesisThrough(): void
	{
		$options = new ExcelOptions(['fnum', 'status']);

		$this->assertSame(['fnum', 'status'], $options->getSynthesis(), 'Synthesis doit refléter les IDs passés au constructeur');
	}
}
