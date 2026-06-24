<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Validation
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Validation;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Import\FieldTypeEnum;
use Tchooz\Services\Import\Mapping\FieldDescriptor;
use Tchooz\Services\Import\Validation\TypeValidator;

/**
 * @covers \Tchooz\Services\Import\Validation\TypeValidator
 */
class TypeValidatorTest extends TestCase
{
	private TypeValidator $validator;

	protected function setUp(): void
	{
		$this->validator = new TypeValidator();
	}

	// --------------------------------------------------------------------
	// Skip cases: empty values and opt-out flag
	// --------------------------------------------------------------------

	public function testEmptyValuesAreSilentlyAcceptedRegardlessOfType(): void
	{
		// Empties are the required-field check's job, not the validator's.
		foreach ([null, '', '   ', "\t"] as $emptyValue)
		{
			foreach (FieldTypeEnum::cases() as $type)
			{
				$values = $type === FieldTypeEnum::ENUM ? [['value' => 'x', 'label' => 'x']] : null;
				$descriptor = $this->descriptor(type: $type, values: $values);

				$this->assertSame(
					[],
					$this->validator->validate($emptyValue, $descriptor),
					sprintf('Type %s should accept empty value', $type->value)
				);
			}
		}
	}

	public function testValidateFalseFlagDisablesAllChecks(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::EMAIL, validate: false);

		// Garbage email passes silently because validate=false.
		$this->assertSame([], $this->validator->validate('definitely-not-an-email', $descriptor));
	}

	// --------------------------------------------------------------------
	// INTEGER
	// --------------------------------------------------------------------

	public function testIntegerAcceptsSignedDigits(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::INTEGER);

		foreach (['0', '1', '42', '-1', '-1000'] as $value)
		{
			$this->assertSame([], $this->validator->validate($value, $descriptor), "Failed for $value");
		}
	}

	public function testIntegerRejectsDecimalsAndNonNumeric(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::INTEGER);

		foreach (['1.5', '1,5', 'abc', '1a', '+1'] as $value)
		{
			$this->assertNotEmpty($this->validator->validate($value, $descriptor), "Should reject $value");
		}
	}

	// --------------------------------------------------------------------
	// NUMBER
	// --------------------------------------------------------------------

	public function testNumberAcceptsIntegersAndDecimalsInBothNotations(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::NUMBER);

		foreach (['0', '1', '1.5', '1,5', '-3.14', '1 000'] as $value)
		{
			$this->assertSame([], $this->validator->validate($value, $descriptor), "Failed for $value");
		}
	}

	public function testNumberRejectsNonNumeric(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::NUMBER);

		$this->assertNotEmpty($this->validator->validate('abc', $descriptor));
		$this->assertNotEmpty($this->validator->validate('1abc', $descriptor));
	}

	// --------------------------------------------------------------------
	// BOOLEAN
	// --------------------------------------------------------------------

	public function testBooleanAcceptsCommonTruthyAndFalsyTokens(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::BOOLEAN);

		foreach (['true', 'TRUE', 'False', '1', '0', 'yes', 'no', 'oui', 'non', 'y', 'n', true, false] as $value)
		{
			$this->assertSame(
				[],
				$this->validator->validate($value, $descriptor),
				'Failed for: ' . var_export($value, true)
			);
		}
	}

	public function testBooleanRejectsUnknownTokens(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::BOOLEAN);

		$this->assertNotEmpty($this->validator->validate('maybe', $descriptor));
		$this->assertNotEmpty($this->validator->validate('2', $descriptor));
	}

	// --------------------------------------------------------------------
	// DATE — delegated to DateParser, exercised lightly
	// --------------------------------------------------------------------

	public function testDateAcceptsCommonFormats(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::DATE);

		foreach (['2020-01-15', '15/01/2020'] as $value)
		{
			$this->assertSame([], $this->validator->validate($value, $descriptor), "Failed for $value");
		}
	}

	public function testDateRejectsGarbage(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::DATE);

		$this->assertNotEmpty($this->validator->validate('not a date', $descriptor));
	}

	// --------------------------------------------------------------------
	// EMAIL / URL — driven by filter_var
	// --------------------------------------------------------------------

	public function testEmailAcceptsRfcCompliantAddresses(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::EMAIL);

		$this->assertSame([], $this->validator->validate('john.doe@example.com', $descriptor));
		$this->assertSame([], $this->validator->validate('  contact@example.com  ', $descriptor));
	}

	public function testEmailRejectsInvalidAddresses(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::EMAIL);

		$this->assertNotEmpty($this->validator->validate('not-an-email', $descriptor));
		$this->assertNotEmpty($this->validator->validate('a@', $descriptor));
	}

	public function testUrlAcceptsAbsoluteUrls(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::URL);

		$this->assertSame([], $this->validator->validate('https://example.com', $descriptor));
		$this->assertSame([], $this->validator->validate('http://sub.example.com/path?q=1', $descriptor));
	}

	public function testUrlRejectsNonUrls(): void
	{
		$descriptor = $this->descriptor(type: FieldTypeEnum::URL);

		$this->assertNotEmpty($this->validator->validate('not a url', $descriptor));
		$this->assertNotEmpty($this->validator->validate('example.com', $descriptor));
	}

	// --------------------------------------------------------------------
	// ENUM
	// --------------------------------------------------------------------

	public function testEnumAcceptsDeclaredValuesOnly(): void
	{
		$descriptor = $this->descriptor(
			type: FieldTypeEnum::ENUM,
			values: [
				['value' => 'man',   'label' => 'Homme'],
				['value' => 'woman', 'label' => 'Femme'],
				['value' => 'other', 'label' => 'Autre'],
			]
		);

		$this->assertSame([], $this->validator->validate('man', $descriptor));
		$this->assertSame([], $this->validator->validate('woman', $descriptor));

		$this->assertNotEmpty($this->validator->validate('Homme', $descriptor));
		$this->assertNotEmpty($this->validator->validate('unknown', $descriptor));
	}

	// --------------------------------------------------------------------
	// STRING + format hints
	// --------------------------------------------------------------------

	public function testIso31661Alpha2AcceptsTwoLetterCodes(): void
	{
		$descriptor = $this->descriptor(format: 'iso-3166-1-alpha-2');

		foreach (['FR', 'fr', 'GB', 'us'] as $value)
		{
			$this->assertSame([], $this->validator->validate($value, $descriptor), "Failed for $value");
		}
	}

	public function testIso31661Alpha2RejectsWrongLengthOrDigits(): void
	{
		$descriptor = $this->descriptor(format: 'iso-3166-1-alpha-2');

		$this->assertNotEmpty($this->validator->validate('FRA', $descriptor));
		$this->assertNotEmpty($this->validator->validate('F', $descriptor));
		$this->assertNotEmpty($this->validator->validate('F1', $descriptor));
	}

	public function testIso31661Alpha3AcceptsThreeLetters(): void
	{
		$descriptor = $this->descriptor(format: 'iso-3166-1-alpha-3');

		$this->assertSame([], $this->validator->validate('FRA', $descriptor));
		$this->assertSame([], $this->validator->validate('gbr', $descriptor));
		$this->assertNotEmpty($this->validator->validate('FR', $descriptor));
	}

	public function testIso4217AcceptsThreeLetters(): void
	{
		$descriptor = $this->descriptor(format: 'iso-4217');

		$this->assertSame([], $this->validator->validate('EUR', $descriptor));
		$this->assertSame([], $this->validator->validate('usd', $descriptor));
		$this->assertNotEmpty($this->validator->validate('Bitcoin', $descriptor));
	}

	public function testE164AcceptsInternationalNumbersAndStripsSpaces(): void
	{
		$descriptor = $this->descriptor(format: 'E.164');

		foreach (['+33612345678', '+33 6 12 34 56 78', '+44-7911-123456', '+33.6.12.34.56.78'] as $value)
		{
			$this->assertSame([], $this->validator->validate($value, $descriptor), "Failed for $value");
		}
	}

	public function testE164RejectsLeadingZeroAndNonNumeric(): void
	{
		$descriptor = $this->descriptor(format: 'E.164');

		// First non-+ digit must be 1..9 (no leading zero).
		$this->assertNotEmpty($this->validator->validate('+0612345678', $descriptor));
		$this->assertNotEmpty($this->validator->validate('abc', $descriptor));
	}

	public function testUnknownStringFormatPassesSilently(): void
	{
		// Documentation-only formats must not block any input.
		$descriptor = $this->descriptor(format: 'something-experimental');

		$this->assertSame([], $this->validator->validate('whatever', $descriptor));
		$this->assertSame([], $this->validator->validate('"#?!.', $descriptor));
	}

	public function testStringWithoutFormatPassesSilently(): void
	{
		$descriptor = $this->descriptor();

		$this->assertSame([], $this->validator->validate('anything', $descriptor));
	}

	// --------------------------------------------------------------------
	// Helpers
	// --------------------------------------------------------------------

	/**
	 * @param  array<int, array{value: string, label: string}>|null  $values
	 */
	private function descriptor(
		FieldTypeEnum $type    = FieldTypeEnum::STRING,
		?array        $values  = null,
		?string       $format  = null,
		bool          $validate = true
	): FieldDescriptor
	{
		return new FieldDescriptor(
			canonical: 'test_field',
			aliases:   ['Test field'],
			required:  false,
			type:      $type,
			values:    $values,
			format:    $format,
			examples:  null,
			validate:  $validate
		);
	}
}
