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


namespace Carbon\Traits;

use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Throwable;

trait Test
{

    protected static $testNow;

    protected static $testDefaultTimezone;

    public static function setTestNow($testNow = null)
    {
        static::$testNow = $testNow instanceof self || $testNow instanceof Closure
            ? $testNow
            : static::make($testNow);
    }

    public static function setTestNowAndTimezone($testNow = null, $tz = null)
    {
        if ($testNow) {
            self::$testDefaultTimezone = self::$testDefaultTimezone ?? date_default_timezone_get();
        }

        $useDateInstanceTimezone = $testNow instanceof DateTimeInterface;

        if ($useDateInstanceTimezone) {
            self::setDefaultTimezone($testNow->getTimezone()->getName(), $testNow);
        }

        static::setTestNow($testNow);

        if (!$useDateInstanceTimezone) {
            $now = static::getMockedTestNow(\func_num_args() === 1 ? null : $tz);
            $tzName = $now ? $now->tzName : null;
            self::setDefaultTimezone($tzName ?? self::$testDefaultTimezone ?? 'UTC', $now);
        }

        if (!$testNow) {
            self::$testDefaultTimezone = null;
        }
    }

    public static function withTestNow($testNow, $callback)
    {
        static::setTestNow($testNow);

        try {
            $result = $callback();
        } finally {
            static::setTestNow();
        }

        return $result;
    }

    public static function getTestNow()
    {
        return static::$testNow;
    }

    public static function hasTestNow()
    {
        return static::getTestNow() !== null;
    }

    protected static function getMockedTestNow($tz)
    {
        $testNow = static::getTestNow();

        if ($testNow instanceof Closure) {
            $realNow = new DateTimeImmutable('now');
            $testNow = $testNow(static::parse(
                $realNow->format('Y-m-d H:i:s.u'),
                $tz ?: $realNow->getTimezone()
            ));
        }


        return $testNow instanceof CarbonInterface
            ? $testNow->avoidMutation()->tz($tz)
            : $testNow;
    }

    protected static function mockConstructorParameters(&$time, $tz)
    {

        $testInstance = clone static::getMockedTestNow($tz);

        if (static::hasRelativeKeywords($time)) {
            $testInstance = $testInstance->modify($time);
        }

        $time = $testInstance instanceof self
            ? $testInstance->rawFormat(static::MOCK_DATETIME_FORMAT)
            : $testInstance->format(static::MOCK_DATETIME_FORMAT);
    }

    private static function setDefaultTimezone($timezone, DateTimeInterface $date = null)
    {
        $previous = null;
        $success = false;

        try {
            $success = date_default_timezone_set($timezone);
        } catch (Throwable $exception) {
            $previous = $exception;
        }

        if (!$success) {
            $suggestion = @CarbonTimeZone::create($timezone)->toRegionName($date);

            throw new InvalidArgumentException(
                "Timezone ID '$timezone' is invalid".
                ($suggestion && $suggestion !== $timezone ? ", did you mean '$suggestion'?" : '.')."\n".
                "It must be one of the IDs from DateTimeZone::listIdentifiers(),\n".
                'For the record, hours/minutes offset are relevant only for a particular moment, '.
                'but not as a default timezone.',
                0,
                $previous
            );
        }
    }
}
