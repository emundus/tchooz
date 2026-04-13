<?php
/**
 * @package     Tchooz\Providers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Providers;

class DateProvider
{
	public function getCurrentDate(): string
	{
		return date('Y-m-d H:i:s');
	}

	public function getCurrentYear(): int
	{
		return (int) date('Y');
	}

	public static function isNullableDate(string|null $date): bool
	{
		if (empty($date) || $date === '0000-00-00 00:00:00')
		{
			return true;
		}

		return false;
	}
}