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

use Carbon\CarbonConverterInterface;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Carbon\Exceptions\UnitException;
use Closure;
use DateInterval;
use DateMalformedStringException;
use ReturnTypeWillChange;

trait Units
{
    public function addRealUnit($unit, $value = 1)
    {
        switch ($unit) {
            case 'micro':

            case 'microsecond':

                $diff = $this->microsecond + $value;
                $time = $this->getTimestamp();
                $seconds = (int) floor($diff / static::MICROSECONDS_PER_SECOND);
                $time += $seconds;
                $diff -= $seconds * static::MICROSECONDS_PER_SECOND;
                $microtime = str_pad((string) $diff, 6, '0', STR_PAD_LEFT);
                $tz = $this->tz;

                return $this->tz('UTC')->modify("@$time.$microtime")->tz($tz);

            case 'milli':
            case 'millisecond':
                return $this->addRealUnit('microsecond', $value * static::MICROSECONDS_PER_MILLISECOND);

            case 'second':
                break;

            case 'minute':
                $value *= static::SECONDS_PER_MINUTE;

                break;

            case 'hour':
                $value *= static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            case 'day':
                $value *= static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            case 'week':
                $value *= static::DAYS_PER_WEEK * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            case 'month':
                $value *= 30 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            case 'quarter':
                $value *= static::MONTHS_PER_QUARTER * 30 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            case 'year':
                $value *= 365 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            case 'decade':
                $value *= static::YEARS_PER_DECADE * 365 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            case 'century':
                $value *= static::YEARS_PER_CENTURY * 365 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            case 'millennium':
                $value *= static::YEARS_PER_MILLENNIUM * 365 * static::HOURS_PER_DAY * static::MINUTES_PER_HOUR * static::SECONDS_PER_MINUTE;

                break;

            default:
                if ($this->localStrictModeEnabled ?? static::isStrictModeEnabled()) {
                    throw new UnitException("Invalid unit for real timestamp add/sub: '$unit'");
                }

                return $this;
        }


        return $this->setTimestamp((int) ($this->getTimestamp() + $value));
    }

    public function subRealUnit($unit, $value = 1)
    {
        return $this->addRealUnit($unit, -$value);
    }

    public static function isModifiableUnit($unit)
    {
        static $modifiableUnits = [
            'millennium',
            'century',
            'decade',
            'quarter',
            'week',
            'weekday',
        ];

        return \in_array($unit, $modifiableUnits, true) || \in_array($unit, static::$units, true);
    }

    public function rawAdd(DateInterval $interval)
    {
        return parent::add($interval);
    }

    #[ReturnTypeWillChange]
    public function add($unit, $value = 1, $overflow = null)
    {
        if (\is_string($unit) && \func_num_args() === 1) {
            $unit = CarbonInterval::make($unit, [], true);
        }

        if ($unit instanceof CarbonConverterInterface) {
            return $this->resolveCarbon($unit->convertDate($this, false));
        }

        if ($unit instanceof Closure) {
            return $this->resolveCarbon($unit($this, false));
        }

        if ($unit instanceof DateInterval) {
            return parent::add($unit);
        }

        if (is_numeric($unit)) {
            [$value, $unit] = [$unit, $value];
        }

        return $this->addUnit($unit, $value, $overflow);
    }

    public function addUnit($unit, $value = 1, $overflow = null)
    {
        $originalArgs = \func_get_args();

        $date = $this;

        if (!is_numeric($value) || !(float) $value) {
            return $date->isMutable() ? $date : $date->avoidMutation();
        }

        $unit = self::singularUnit($unit);
        $metaUnits = [
            'millennium' => [static::YEARS_PER_MILLENNIUM, 'year'],
            'century' => [static::YEARS_PER_CENTURY, 'year'],
            'decade' => [static::YEARS_PER_DECADE, 'year'],
            'quarter' => [static::MONTHS_PER_QUARTER, 'month'],
        ];

        if (isset($metaUnits[$unit])) {
            [$factor, $unit] = $metaUnits[$unit];
            $value *= $factor;
        }

        if ($unit === 'weekday') {
            $weekendDays = static::getWeekendDays();

            if ($weekendDays !== [static::SATURDAY, static::SUNDAY]) {
                $absoluteValue = abs($value);
                $sign = $value / max(1, $absoluteValue);
                $weekDaysCount = 7 - min(6, \count(array_unique($weekendDays)));
                $weeks = floor($absoluteValue / $weekDaysCount);

                for ($diff = $absoluteValue % $weekDaysCount; $diff; $diff--) {

                    $date = $date->addDays($sign);

                    while (\in_array($date->dayOfWeek, $weekendDays, true)) {
                        $date = $date->addDays($sign);
                    }
                }

                $value = $weeks * $sign;
                $unit = 'week';
            }

            $timeString = $date->toTimeString();
        } elseif ($canOverflow = (\in_array($unit, [
                'month',
                'year',
            ]) && ($overflow === false || (
                $overflow === null &&
                ($ucUnit = ucfirst($unit).'s') &&
                !($this->{'local'.$ucUnit.'Overflow'} ?? static::{'shouldOverflow'.$ucUnit}())
            )))) {
            $day = $date->day;
        }

        $value = (int) $value;

        if ($unit === 'milli' || $unit === 'millisecond') {
            $unit = 'microsecond';
            $value *= static::MICROSECONDS_PER_MILLISECOND;
        }

        if ($unit === 'micro' || $unit === 'microsecond') {
            $microseconds = $this->micro + $value;
            $second = (int) floor($microseconds / static::MICROSECONDS_PER_SECOND);
            $microseconds %= static::MICROSECONDS_PER_SECOND;
            if ($microseconds < 0) {
                $microseconds += static::MICROSECONDS_PER_SECOND;
            }
            $date = $date->microseconds($microseconds);
            $unit = 'second';
            $value = $second;
        }

        try {
            $date = $date->modify("$value $unit");

            if (isset($timeString)) {
                $date = $date->setTimeFromTimeString($timeString);
            } elseif (isset($canOverflow, $day) && $canOverflow && $day !== $date->day) {
                $date = $date->modify('last day of previous month');
            }
        } catch (DateMalformedStringException $ignoredException) { // @codeCoverageIgnore
            $date = null; // @codeCoverageIgnore
        }

        if (!$date) {
            throw new UnitException('Unable to add unit '.var_export($originalArgs, true));
        }

        return $date;
    }

    public function subUnit($unit, $value = 1, $overflow = null)
    {
        return $this->addUnit($unit, -$value, $overflow);
    }

    public function rawSub(DateInterval $interval)
    {
        return parent::sub($interval);
    }

    #[ReturnTypeWillChange]
    public function sub($unit, $value = 1, $overflow = null)
    {
        if (\is_string($unit) && \func_num_args() === 1) {
            $unit = CarbonInterval::make($unit, [], true);
        }

        if ($unit instanceof CarbonConverterInterface) {
            return $this->resolveCarbon($unit->convertDate($this, true));
        }

        if ($unit instanceof Closure) {
            return $this->resolveCarbon($unit($this, true));
        }

        if ($unit instanceof DateInterval) {
            return parent::sub($unit);
        }

        if (is_numeric($unit)) {
            [$value, $unit] = [$unit, $value];
        }

        return $this->addUnit($unit, -(float) $value, $overflow);
    }

    public function subtract($unit, $value = 1, $overflow = null)
    {
        if (\is_string($unit) && \func_num_args() === 1) {
            $unit = CarbonInterval::make($unit, [], true);
        }

        return $this->sub($unit, $value, $overflow);
    }
}
