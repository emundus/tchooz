<?php
/**
 * @package     Tchooz\Enums\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Enums\Import;

use Joomla\CMS\Language\Text;

/**
 * The two values a FieldTypeEnum::BOOLEAN import field can hold.
 *
 * Single source of truth for both directions of the exchange:
 *   - what is offered to the integrator — the localized label ("Oui" / "Non",
 *     "Yes" / "No") is the value itself, because that is literally what a cell
 *     of the model file contains;
 *   - what is accepted when the cell is read back, deliberately wider than what
 *     is offered (true/false, 1/0, yes/no, oui/non, y/n plus the current
 *     language's labels) so a file written elsewhere still imports.
 */
enum BooleanValueEnum: string
{
	case TRUE  = 'true';
	case FALSE = 'false';

	/** Language-independent tokens accepted for each case, lowercase. */
	private const TOKENS = [
		'true'  => ['true', '1', 'yes', 'oui', 'y'],
		'false' => ['false', '0', 'no', 'non', 'n'],
	];

	public function getLabel(): string
	{
		return match ($this)
		{
			self::TRUE  => Text::_('COM_EMUNDUS_IMPORT_BOOLEAN_TRUE'),
			self::FALSE => Text::_('COM_EMUNDUS_IMPORT_BOOLEAN_FALSE'),
		};
	}

	public function toBool(): bool
	{
		return $this === self::TRUE;
	}

	public static function fromBool(bool $value): self
	{
		return $value ? self::TRUE : self::FALSE;
	}

	/**
	 * Closed list of the values a BOOLEAN field accepts, in the same
	 * {value, label} shape as an ENUM's declared list.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function entries(): array
	{
		return array_map(
			static fn(self $case) => ['value' => $case->getLabel(), 'label' => $case->getLabel()],
			self::cases()
		);
	}

	/**
	 * Reads a cell back to its case. Returns null when the value matches no
	 * accepted token, which is what makes it a validation failure upstream.
	 */
	public static function tryFromValue(mixed $value): ?self
	{
		if (is_bool($value))
		{
			return self::fromBool($value);
		}

		if (!is_scalar($value))
		{
			return null;
		}

		$token = mb_strtolower(trim((string) $value));

		if ($token === '')
		{
			return null;
		}

		foreach (self::cases() as $case)
		{
			if (in_array($token, $case->acceptedTokens(), true))
			{
				return $case;
			}
		}

		return null;
	}

	/**
	 * @return string[] lowercase tokens accepted for this case, the current
	 *                  language's label included.
	 */
	private function acceptedTokens(): array
	{
		$tokens   = self::TOKENS[$this->value];
		$tokens[] = mb_strtolower($this->getLabel());

		return array_values(array_unique($tokens));
	}
}
