<?php
/**
 * @package     Tchooz\Services\Import\Mapping
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Mapping;

/**
 * Normalizes raw header strings to a canonical comparable form.
 *
 * Used both when building the alias index (AliasColumnMap) and when
 * resolving an incoming header. As long as both sides go through the
 * same normalization, "Adresse e-mail", " adresse e-mail " and
 * "ADRESSE É-MAIL" all collide on the same key.
 */
final class HeaderNormalizer
{
	public static function normalize(string $value): string
	{
		$value = trim($value);

		if ($value === '')
		{
			return '';
		}

		// Normalize whitespace characters (tabs, newlines, non-breaking spaces, narrow no-break spaces)
		// to regular spaces so they become underscores later, then strip truly invisible characters.
		$value = preg_replace('/[\t\n\r\xA0\x{202F}]/u', ' ', $value);
		$value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{200B}]/u', '', $value);
		$value = preg_replace('/ {2,}/', ' ', $value);

		$value = self::stripAccents($value);
		$value = mb_strtolower($value, 'UTF-8');

		// Collapse any non-alphanumeric run to a single underscore so
		// "E-mail", "e mail" and "e_mail" all collapse to "e_mail".
		$value = preg_replace('/[^a-z0-9]+/u', '_', $value);

		return trim($value, '_');
	}

	private static function stripAccents(string $value): string
	{
		if (function_exists('iconv'))
		{
			$converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
			if ($converted !== false)
			{
				return $converted;
			}
		}

		// Fallback for environments where iconv is unreliable.
		$map = [
			'à' => 'a','á' => 'a','â' => 'a','ã' => 'a','ä' => 'a','å' => 'a',
			'ç' => 'c',
			'è' => 'e','é' => 'e','ê' => 'e','ë' => 'e',
			'ì' => 'i','í' => 'i','î' => 'i','ï' => 'i',
			'ñ' => 'n',
			'ò' => 'o','ó' => 'o','ô' => 'o','õ' => 'o','ö' => 'o',
			'ù' => 'u','ú' => 'u','û' => 'u','ü' => 'u',
			'ý' => 'y','ÿ' => 'y',
			'À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A',
			'Ç' => 'C',
			'È' => 'E','É' => 'E','Ê' => 'E','Ë' => 'E',
			'Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I',
			'Ñ' => 'N',
			'Ò' => 'O','Ó' => 'O','Ô' => 'O','Õ' => 'O','Ö' => 'O',
			'Ù' => 'U','Ú' => 'U','Û' => 'U','Ü' => 'U',
			'Ý' => 'Y',
		];

		return strtr($value, $map);
	}
}
