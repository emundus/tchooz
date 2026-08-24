<?php
/**
 * @package     Unit\Component\Emundus\Class\Enums\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Enums\Import;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Import\BooleanValueEnum;

/**
 * @covers \Tchooz\Enums\Import\BooleanValueEnum
 */
class BooleanValueEnumTest extends TestCase
{
	// --------------------------------------------------------------------
	// Closed list offered to the integrator
	// --------------------------------------------------------------------

	public function testEntriesExposeExactlyTwoLocalizedValues(): void
	{
		$entries = BooleanValueEnum::entries();

		$this->assertCount(2, $entries, 'A boolean field offers exactly two values');

		foreach ($entries as $entry)
		{
			$this->assertNotSame('', $entry['value'], 'The offered value is the localized label, never empty');
			$this->assertSame(
				$entry['value'],
				$entry['label'],
				'A boolean value is its own label: what is typed in the cell is what is read'
			);
		}
	}

	// --------------------------------------------------------------------
	// Reading a cell back
	// --------------------------------------------------------------------

	public function testTruthyTokensResolveToTrue(): void
	{
		foreach (['true', 'TRUE', ' 1 ', 'yes', 'Oui', 'y', 1, true] as $value)
		{
			$case = BooleanValueEnum::tryFromValue($value);

			$this->assertNotNull($case, 'Unexpected rejection of: ' . var_export($value, true));
			$this->assertTrue($case->toBool(), 'Failed for: ' . var_export($value, true));
		}
	}

	public function testFalsyTokensResolveToFalse(): void
	{
		foreach (['false', 'False', ' 0 ', 'no', 'NON', 'n', 0, false] as $value)
		{
			$case = BooleanValueEnum::tryFromValue($value);

			$this->assertNotNull($case, 'Unexpected rejection of: ' . var_export($value, true));
			$this->assertFalse($case->toBool(), 'Failed for: ' . var_export($value, true));
		}
	}

	public function testOfferedValuesAreAlwaysReadableBack(): void
	{
		// The guarantee tying both directions together: whatever the current
		// language offers in the model file must import.
		foreach (BooleanValueEnum::entries() as $index => $entry)
		{
			$case = BooleanValueEnum::tryFromValue($entry['value']);

			$this->assertNotNull($case, 'Offered value rejected on read: ' . $entry['value']);
			$this->assertSame(BooleanValueEnum::cases()[$index], $case);
		}
	}

	public function testUnknownAndEmptyValuesAreRejected(): void
	{
		foreach (['maybe', '2', '', '   ', null, [], new \stdClass()] as $value)
		{
			$this->assertNull(
				BooleanValueEnum::tryFromValue($value),
				'Unexpected acceptance of: ' . var_export($value, true)
			);
		}
	}

	public function testFromBoolIsTheInverseOfToBool(): void
	{
		$this->assertTrue(BooleanValueEnum::fromBool(true)->toBool());
		$this->assertFalse(BooleanValueEnum::fromBool(false)->toBool());
	}
}
