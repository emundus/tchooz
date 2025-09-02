<?php
/**
 * @package     Joomla\Plugin\Task\Inactiveaccounts\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Task\Inactiveaccounts\Helper;

use Joomla\CMS\Factory;

class Date
{
	public static function getModifiedDate($days, $negative = false): string
	{
		// If negative, we need to subtract days
		if ($negative)
		{
			$days = -$days;
		}

		$date = Factory::getDate();
		$date->modify("$days days");

		return $date->toSql();
	}
}