<?php
/**
 * @package     Tchooz\Transformers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Transformers;

use Tchooz\Interfaces\FabrikTransformerInterface;

class CurrencyTransformer implements FabrikTransformerInterface
{
	public function transform(mixed $value, array $options = []): string
	{
		// Manage raw values when save
		$rawValue = explode(',', $value);
		if (count($rawValue) === 3)
		{
			$value = $rawValue[1];
		}

		// Step 1: Extract the first number-like segment (with digits and optional commas/dots)
		if (!preg_match('/-?\d(?:[\s00A0]?\d|[.,])*/', $value, $matches))
		{
			return 0.0; // No valid number found
		}

		$number = $matches[0];

		// Step 2: Normalize separators (dots and commas)
		$commaPos = strrpos($number, ',');
		$dotPos   = strrpos($number, '.');

		if ($commaPos !== false && $dotPos !== false)
		{
			if ($commaPos > $dotPos)
			{
				// European: "1.234,56"
				$number = str_replace(['.', ','], ['', '.'], $number); // remove thousand dots and convert decimal comma
			}
			else
			{
				// US: "1,234.56"
				$number = str_replace(',', '', $number);     // remove thousand commas
			}
		}
		elseif ($commaPos !== false)
		{
			// Assume comma is decimal separator
			$number = str_replace(',', '.', $number);
		}
		else
		{
			// Only dot or plain digits
			if (substr_count($number, '.') > 1)
			{
				// Too many dots? Likely thousand separators → remove all
				$number = str_replace('.', '', $number);
			}
		}

		// Finally, remove spaces used as thousand separators
		$number = str_replace(' ', '', $number);

		return (float) $number;
	}
}