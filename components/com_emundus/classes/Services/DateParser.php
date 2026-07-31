<?php

namespace Tchooz\Services;

use DateTimeImmutable;

final class DateParser
{
	public const DEFAULT_FORMATS = [
		'Y-m-d',
		'Y-m-d H:i:s',
		'Y/m/d',
		'd/m/Y',
		'd-m-Y',
		'd.m.Y',
		'd/m/Y H:i',
		'd/m/Y H:i:s',
	];

	public static function parse(mixed $value, array $formats = self::DEFAULT_FORMATS): ?DateTimeImmutable
	{
		if ($value === null)
		{
			return null;
		}

		$str = trim((string) $value);
		if ($str === '' || $str === '0000-00-00' || $str === '0000-00-00 00:00:00')
		{
			return null;
		}

		foreach ($formats as $format)
		{
			$date = DateTimeImmutable::createFromFormat($format, $str);
			if ($date !== false && $date->format($format) === $str)
			{
				return $date;
			}
		}

		return null;
	}

	public static function normalize(mixed $value, string $outputFormat = 'Y-m-d', array $formats = self::DEFAULT_FORMATS): ?string
	{
		return self::parse($value, $formats)?->format($outputFormat);
	}

	public static function isValid(mixed $value, array $formats = self::DEFAULT_FORMATS): bool
	{
		return self::parse($value, $formats) !== null;
	}
}
