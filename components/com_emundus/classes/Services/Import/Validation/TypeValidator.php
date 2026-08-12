<?php
/**
 * @package     Tchooz\Services\Import\Validation
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Validation;

use Joomla\CMS\Language\Text;
use Tchooz\Enums\Import\FieldTypeEnum;
use Tchooz\Services\DateParser;
use Tchooz\Services\Import\Mapping\FieldDescriptor;

/**
 * Generic per-field validator driven by a FieldDescriptor.
 *
 * Single source of truth: when a field is declared with
 * `type: FieldTypeEnum::EMAIL` the pipeline rejects rows whose email is not
 * RFC-compliant — no need to repeat that rule in each importer's validate().
 *
 * Empty values are *not* the validator's concern: they are handled by the
 * pipeline's required-field check before this validator runs. Likewise, this
 * validator is opt-out per field via $descriptor->validate === false.
 *
 * Unknown format strings on STRING fields pass silently: they remain
 * documentation hints (shown in describe() and in the model's doc sheet)
 * without being enforced.
 */
final class TypeValidator
{
	/** Format hints with a corresponding strict check. */
	private const KNOWN_STRING_FORMATS = [
		'iso-3166-1-alpha-2',
		'iso-3166-1-alpha-3',
		'iso-4217',
		'E.164',
	];

	/**
	 * Truthy/falsy tokens accepted for FieldTypeEnum::BOOLEAN.
	 * Comparison is case-insensitive after trim().
	 */
	private const BOOLEAN_TOKENS = [
		'true', 'false',
		'1', '0',
		'yes', 'no',
		'oui', 'non',
		'y',   'n',
	];

	/**
	 * @return string[] errors (empty array = field is valid)
	 */
	public function validate(mixed $value, FieldDescriptor $descriptor): array
	{
		// Opt-out: the field is type-documented but not enforced.
		if (!$descriptor->validate)
		{
			return [];
		}

		// Empty values fall through: required-field handling is done upstream.
		if ($this->isEmpty($value))
		{
			return [];
		}

		return match ($descriptor->type)
		{
			FieldTypeEnum::INTEGER => $this->validateInteger($value, $descriptor),
			FieldTypeEnum::NUMBER  => $this->validateNumber($value, $descriptor),
			FieldTypeEnum::BOOLEAN => $this->validateBoolean($value, $descriptor),
			FieldTypeEnum::DATE    => $this->validateDate($value, $descriptor),
			FieldTypeEnum::EMAIL   => $this->validateEmail($value, $descriptor),
			FieldTypeEnum::URL     => $this->validateUrl($value, $descriptor),
			FieldTypeEnum::ENUM    => $this->validateEnum($value, $descriptor),
			FieldTypeEnum::STRING  => $this->validateString($value, $descriptor),
		};
	}

	private function validateInteger(mixed $value, FieldDescriptor $descriptor): array
	{
		$str = trim((string) $value);

		// Allow a leading sign, then digits only.
		if (preg_match('/^-?\d+$/', $str) === 1)
		{
			return [];
		}

		return [$this->message('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_INTEGER', $descriptor, $value)];
	}

	private function validateNumber(mixed $value, FieldDescriptor $descriptor): array
	{
		// Tolerate "1,5" as a decimal separator and stripped spaces.
		$str = str_replace([',', ' '], ['.', ''], trim((string) $value));

		if (is_numeric($str))
		{
			return [];
		}

		return [$this->message('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_NUMBER', $descriptor, $value)];
	}

	private function validateBoolean(mixed $value, FieldDescriptor $descriptor): array
	{
		if (is_bool($value))
		{
			return [];
		}

		$token = strtolower(trim((string) $value));
		if (in_array($token, self::BOOLEAN_TOKENS, true))
		{
			return [];
		}

		return [$this->message('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_BOOLEAN', $descriptor, $value)];
	}

	private function validateDate(mixed $value, FieldDescriptor $descriptor): array
	{
		if (DateParser::isValid($value))
		{
			return [];
		}

		return [$this->message('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_DATE', $descriptor, $value)];
	}

	private function validateEmail(mixed $value, FieldDescriptor $descriptor): array
	{
		if (filter_var(trim((string) $value), FILTER_VALIDATE_EMAIL) !== false)
		{
			return [];
		}

		return [$this->message('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_EMAIL', $descriptor, $value)];
	}

	private function validateUrl(mixed $value, FieldDescriptor $descriptor): array
	{
		if (filter_var(trim((string) $value), FILTER_VALIDATE_URL) !== false)
		{
			return [];
		}

		return [$this->message('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_URL', $descriptor, $value)];
	}

	private function validateEnum(mixed $value, FieldDescriptor $descriptor): array
	{
		$allowed = array_map(
			static fn(array $entry) => (string) $entry['value'],
			$descriptor->values ?? []
		);

		if (in_array((string) $value, $allowed, true))
		{
			return [];
		}

		return [$this->message(
			'COM_EMUNDUS_IMPORT_VALIDATION_INVALID_ENUM_VALUE',
			$descriptor,
			$value,
			implode(', ', $allowed)
		)];
	}

	private function validateString(mixed $value, FieldDescriptor $descriptor): array
	{
		// No format declared, or an unknown format hint → silent pass.
		// Known formats below remain enforced.
		if ($descriptor->format === null || !in_array($descriptor->format, self::KNOWN_STRING_FORMATS, true))
		{
			return [];
		}

		$str = trim((string) $value);

		return match ($descriptor->format)
		{
			'iso-3166-1-alpha-2' => $this->validateIsoLetters($str, 2, $descriptor, $value, 'COM_EMUNDUS_IMPORT_VALIDATION_INVALID_ISO2'),
			'iso-3166-1-alpha-3' => $this->validateIsoLetters($str, 3, $descriptor, $value, 'COM_EMUNDUS_IMPORT_VALIDATION_INVALID_ISO3'),
			'iso-4217'           => $this->validateIsoLetters($str, 3, $descriptor, $value, 'COM_EMUNDUS_IMPORT_VALIDATION_INVALID_ISO_4217'),
			'E.164'              => $this->validateE164($str, $descriptor, $value),
		};
	}

	private function validateIsoLetters(string $str, int $length, FieldDescriptor $descriptor, mixed $rawValue, string $key): array
	{
		$pattern = sprintf('/^[A-Za-z]{%d}$/', $length);

		return preg_match($pattern, $str) === 1
			? []
			: [$this->message($key, $descriptor, $rawValue)];
	}

	private function validateE164(string $str, FieldDescriptor $descriptor, mixed $rawValue): array
	{
		// Strip spaces (and similar separators) before checking the canonical
		// E.164 shape: a mandatory + then 1..15 digits, first non-zero.
		$compact = preg_replace('/[\s.\-()]/', '', $str);

		if (preg_match('/^\+[1-9]\d{1,14}$/', (string) $compact) === 1)
		{
			return [];
		}

		return [$this->message('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_PHONE_E164', $descriptor, $rawValue)];
	}

	private function isEmpty(mixed $value): bool
	{
		if ($value === null)
		{
			return true;
		}

		return is_string($value) && trim($value) === '';
	}

	private function message(string $key, FieldDescriptor $descriptor, mixed $value, string ...$extras): string
	{
		$fieldLabel   = $descriptor->aliases[0] ?? $descriptor->canonical;
		$displayValue = mb_strimwidth((string) $value, 0, 80, '…');

		return Text::sprintf($key, $fieldLabel, $displayValue, ...$extras);
	}
}
