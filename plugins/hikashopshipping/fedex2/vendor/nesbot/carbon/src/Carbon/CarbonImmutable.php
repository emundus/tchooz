<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace Carbon;

use Carbon\Traits\Date;
use Carbon\Traits\DeprecatedProperties;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class CarbonImmutable extends DateTimeImmutable implements CarbonInterface
{
    use Date {
        __clone as dateTraitClone;
    }

    public function __clone()
    {
        $this->dateTraitClone();
        $this->endOfTime = false;
        $this->startOfTime = false;
    }

    public static function startOfTime(): self
    {
        $date = static::parse('0001-01-01')->years(self::getStartOfTimeYear());
        $date->startOfTime = true;

        return $date;
    }

    public static function endOfTime(): self
    {
        $date = static::parse('9999-12-31 23:59:59.999999')->years(self::getEndOfTimeYear());
        $date->endOfTime = true;

        return $date;
    }

    private static function getEndOfTimeYear(): int
    {
        if (version_compare(PHP_VERSION, '7.3.0-dev', '<')) {
            return 145261681241552;
        }

        if (version_compare(PHP_VERSION, '8.1.0-dev', '>=')) {
            return 1118290769066902787;
        }

        return PHP_INT_MAX;
    }

    private static function getStartOfTimeYear(): int
    {
        if (version_compare(PHP_VERSION, '7.3.0-dev', '<')) {
            return -135908816449551;
        }

        if (version_compare(PHP_VERSION, '8.1.0-dev', '>=')) {
            return -1118290769066898816;
        }

        return max(PHP_INT_MIN, -9223372036854773760);
    }
}
