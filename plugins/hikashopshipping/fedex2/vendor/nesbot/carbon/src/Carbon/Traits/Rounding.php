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
use Carbon\Exceptions\UnknownUnitException;

trait Rounding
{
    use IntervalRounding;

    public function roundUnit($unit, $precision = 1, $function = 'round')
    {
        $metaUnits = [
            'millennium' => [static::YEARS_PER_MILLENNIUM, 'year'],
            'century' => [static::YEARS_PER_CENTURY, 'year'],
            'decade' => [static::YEARS_PER_DECADE, 'year'],
            'quarter' => [static::MONTHS_PER_QUARTER, 'month'],
            'millisecond' => [1000, 'microsecond'],
        ];
        $normalizedUnit = static::singularUnit($unit);
        $ranges = array_merge(static::getRangesByUnit($this->daysInMonth), [
            'microsecond' => [0, 999999],
        ]);
        $factor = 1;

        if ($normalizedUnit === 'week') {
            $normalizedUnit = 'day';
            $precision *= static::DAYS_PER_WEEK;
        }

        if (isset($metaUnits[$normalizedUnit])) {
            [$factor, $normalizedUnit] = $metaUnits[$normalizedUnit];
        }

        $precision *= $factor;

        if (!isset($ranges[$normalizedUnit])) {
            throw new UnknownUnitException($unit);
        }

        $found = false;
        $fraction = 0;
        $arguments = null;
        $initialValue = null;
        $factor = $this->year < 0 ? -1 : 1;
        $changes = [];
        $minimumInc = null;

        foreach ($ranges as $unit => [$minimum, $maximum]) {
            if ($normalizedUnit === $unit) {
                $arguments = [$this->$unit, $minimum];
                $initialValue = $this->$unit;
                $fraction = $precision - floor($precision);
                $found = true;

                continue;
            }

            if ($found) {
                $delta = $maximum + 1 - $minimum;
                $factor /= $delta;
                $fraction *= $delta;
                $inc = ($this->$unit - $minimum) * $factor;

                if ($inc !== 0.0) {
                    $minimumInc = $minimumInc ?? ($arguments[0] / pow(2, 52));

                    if (abs($inc) < $minimumInc) {
                        $inc = $minimumInc * ($inc < 0 ? -1 : 1);
                    }

                    if ($function !== 'floor' || abs($arguments[0] + $inc - $initialValue) >= $precision) {
                        $arguments[0] += $inc;
                    }
                }

                $changes[$unit] = round(
                    $minimum + ($fraction ? $fraction * $function(($this->$unit - $minimum) / $fraction) : 0)
                );

                while ($changes[$unit] >= $delta) {
                    $changes[$unit] -= $delta;
                }

                $fraction -= floor($fraction);
            }
        }

        [$value, $minimum] = $arguments;
        $normalizedValue = floor($function(($value - $minimum) / $precision) * $precision + $minimum);


        $result = $this;

        foreach ($changes as $unit => $value) {
            $result = $result->$unit($value);
        }

        return $result->$normalizedUnit($normalizedValue);
    }

    public function floorUnit($unit, $precision = 1)
    {
        return $this->roundUnit($unit, $precision, 'floor');
    }

    public function ceilUnit($unit, $precision = 1)
    {
        return $this->roundUnit($unit, $precision, 'ceil');
    }

    public function round($precision = 1, $function = 'round')
    {
        return $this->roundWith($precision, $function);
    }

    public function floor($precision = 1)
    {
        return $this->round($precision, 'floor');
    }

    public function ceil($precision = 1)
    {
        return $this->round($precision, 'ceil');
    }

    public function roundWeek($weekStartsAt = null)
    {
        return $this->closest(
            $this->avoidMutation()->floorWeek($weekStartsAt),
            $this->avoidMutation()->ceilWeek($weekStartsAt)
        );
    }

    public function floorWeek($weekStartsAt = null)
    {
        return $this->startOfWeek($weekStartsAt);
    }

    public function ceilWeek($weekStartsAt = null)
    {
        if ($this->isMutable()) {
            $startOfWeek = $this->avoidMutation()->startOfWeek($weekStartsAt);

            return $startOfWeek != $this ?
                $this->startOfWeek($weekStartsAt)->addWeek() :
                $this;
        }

        $startOfWeek = $this->startOfWeek($weekStartsAt);

        return $startOfWeek != $this ?
            $startOfWeek->addWeek() :
            $this->avoidMutation();
    }
}
