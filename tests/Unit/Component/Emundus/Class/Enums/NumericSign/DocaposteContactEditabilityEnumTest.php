<?php
/**
 * @package     Unit\Component\Emundus\Class\Enums\NumericSign
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Enums\NumericSign;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\NumericSign\DocaposteContactEditabilityEnum;
use Tchooz\Enums\NumericSign\DocaposteContactReadOnlyParameterEnum;

/**
 * @covers \Tchooz\Enums\NumericSign\DocaposteContactEditabilityEnum
 */
class DocaposteContactEditabilityEnumTest extends TestCase
{
	// --------------------------------------------------------------------
	// Payload shape
	// --------------------------------------------------------------------

	public function testEveryCaseDecidesAllThreeReadOnlyParameters(): void
	{
		foreach (DocaposteContactEditabilityEnum::cases() as $editability)
		{
			$parameters = $editability->getReadOnlyParameters(false, false);

			$this->assertCount(
				count(DocaposteContactReadOnlyParameterEnum::cases()),
				$parameters,
				'Docaposte falls back to its own defaults for an omitted parameter, so every case decides all of them: ' . $editability->value
			);

			foreach ($parameters as $readOnly)
			{
				$this->assertIsBool($readOnly, 'A read only parameter is a boolean decision: ' . $editability->value);
			}
		}
	}

	public function testParameterNamesMatchTheDocaposteContract(): void
	{
		$parameters = DocaposteContactEditabilityEnum::ALL->getReadOnlyParameters(false, false);

		$this->assertSame(
			['phoneAndEmailReadOnly', 'emailReadOnly', 'phoneReadOnly'],
			array_keys($parameters),
			'The keys are sent as is to the Docaposte sign URL endpoint'
		);
	}

	// --------------------------------------------------------------------
	// ALL and NONE ignore the per detail toggles
	// --------------------------------------------------------------------

	public function testAllLocksNothingWhateverTheToggles(): void
	{
		foreach ($this->togglePairs() as $toggles)
		{
			$parameters = DocaposteContactEditabilityEnum::ALL->getReadOnlyParameters($toggles[0], $toggles[1]);

			$this->assertSame(
				[false, false, false],
				array_values($parameters),
				'Contact details stay editable, the per detail toggles only apply to CUSTOM'
			);
		}
	}

	public function testNoneLocksEverythingWhateverTheToggles(): void
	{
		foreach ($this->togglePairs() as $toggles)
		{
			$parameters = DocaposteContactEditabilityEnum::NONE->getReadOnlyParameters($toggles[0], $toggles[1]);

			$this->assertSame(
				[true, true, true],
				array_values($parameters),
				'No contact detail can be edited, the per detail toggles only apply to CUSTOM'
			);
		}
	}

	// --------------------------------------------------------------------
	// CUSTOM reflects each toggle
	// --------------------------------------------------------------------

	public function testCustomLocksOnlyTheToggledDetails(): void
	{
		foreach ($this->togglePairs() as $toggles)
		{
			[$emailReadOnly, $phoneReadOnly] = $toggles;

			$parameters = DocaposteContactEditabilityEnum::CUSTOM->getReadOnlyParameters($emailReadOnly, $phoneReadOnly);

			$this->assertSame(
				$emailReadOnly,
				$parameters[DocaposteContactReadOnlyParameterEnum::EMAIL->value],
				'The email toggle drives its own parameter'
			);
			$this->assertSame(
				$phoneReadOnly,
				$parameters[DocaposteContactReadOnlyParameterEnum::PHONE->value],
				'The phone toggle drives its own parameter'
			);
		}
	}

	public function testCustomNeverSetsTheGlobalLock(): void
	{
		foreach ($this->togglePairs() as $toggles)
		{
			$parameters = DocaposteContactEditabilityEnum::CUSTOM->getReadOnlyParameters($toggles[0], $toggles[1]);

			$this->assertFalse(
				$parameters[DocaposteContactReadOnlyParameterEnum::BOTH->value],
				'The global lock takes precedence over the per detail parameters, it would void the detailed setting'
			);
		}
	}

	/**
	 * @return array<array{0: bool, 1: bool}>
	 */
	private function togglePairs(): array
	{
		return [[false, false], [true, false], [false, true], [true, true]];
	}
}
