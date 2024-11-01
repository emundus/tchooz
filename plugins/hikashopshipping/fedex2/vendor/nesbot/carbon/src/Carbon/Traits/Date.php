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

use BadMethodCallException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Carbon\CarbonTimeZone;
use Carbon\Exceptions\BadComparisonUnitException;
use Carbon\Exceptions\ImmutableException;
use Carbon\Exceptions\InvalidTimeZoneException;
use Carbon\Exceptions\InvalidTypeException;
use Carbon\Exceptions\UnknownGetterException;
use Carbon\Exceptions\UnknownMethodException;
use Carbon\Exceptions\UnknownSetterException;
use Carbon\Exceptions\UnknownUnitException;
use Closure;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use ReflectionException;
use ReturnTypeWillChange;
use Throwable;

trait Date
{
    use Boundaries;
    use Comparison;
    use Converter;
    use Creator;
    use Difference;
    use Macro;
    use MagicParameter;
    use Modifiers;
    use Mutability;
    use ObjectInitialisation;
    use Options;
    use Rounding;
    use Serialization;
    use Test;
    use Timestamp;
    use Units;
    use Week;

    protected static $days = [
        CarbonInterface::SUNDAY => 'Sunday',
        CarbonInterface::MONDAY => 'Monday',
        CarbonInterface::TUESDAY => 'Tuesday',
        CarbonInterface::WEDNESDAY => 'Wednesday',
        CarbonInterface::THURSDAY => 'Thursday',
        CarbonInterface::FRIDAY => 'Friday',
        CarbonInterface::SATURDAY => 'Saturday',
    ];

    protected static $utf8 = false;

    protected static $units = [
        'year',
        'month',
        'day',
        'hour',
        'minute',
        'second',
        'milli',
        'millisecond',
        'micro',
        'microsecond',
    ];

    protected static function safeCreateDateTimeZone($object, $objectDump = null)
    {
        return CarbonTimeZone::instance($object, $objectDump);
    }

    #[ReturnTypeWillChange]
    public function getTimezone()
    {
        return CarbonTimeZone::instance(parent::getTimezone());
    }

    protected static function getRangesByUnit(int $daysInMonth = 31): array
    {
        return [
            'year' => [1, 9999],
            'month' => [1, static::MONTHS_PER_YEAR],
            'day' => [1, $daysInMonth],
            'hour' => [0, static::HOURS_PER_DAY - 1],
            'minute' => [0, static::MINUTES_PER_HOUR - 1],
            'second' => [0, static::SECONDS_PER_MINUTE - 1],
        ];
    }

    public function copy()
    {
        return clone $this;
    }

    public function clone()
    {
        return clone $this;
    }

    public function avoidMutation(): self
    {
        if ($this instanceof DateTimeImmutable) {
            return $this;
        }

        return clone $this;
    }

    public function nowWithSameTz()
    {
        return static::now($this->getTimezone());
    }

    protected static function expectDateTime($date, $other = [])
    {
        $message = 'Expected ';
        foreach ((array) $other as $expect) {
            $message .= "$expect, ";
        }

        if (!$date instanceof DateTime && !$date instanceof DateTimeInterface) {
            throw new InvalidTypeException(
                $message.'DateTime or DateTimeInterface, '.
                (\is_object($date) ? \get_class($date) : \gettype($date)).' given'
            );
        }
    }

    protected function resolveCarbon($date = null)
    {
        if (!$date) {
            return $this->nowWithSameTz();
        }

        if (\is_string($date)) {
            return static::parse($date, $this->getTimezone());
        }

        static::expectDateTime($date, ['null', 'string']);

        return $date instanceof self ? $date : static::instance($date);
    }

    protected function resolveUTC($date = null): self
    {
        if (!$date) {
            return static::now('UTC');
        }

        if (\is_string($date)) {
            return static::parse($date, $this->getTimezone())->utc();
        }

        static::expectDateTime($date, ['null', 'string']);

        return $date instanceof self ? $date : static::instance($date)->utc();
    }

    public function carbonize($date = null)
    {
        if ($date instanceof DateInterval) {
            return $this->avoidMutation()->add($date);
        }

        if ($date instanceof DatePeriod || $date instanceof CarbonPeriod) {
            $date = $date->getStartDate();
        }

        return $this->resolveCarbon($date);
    }


    public function __get($name)
    {
        return $this->get($name);
    }

    public function get($name)
    {
        static $formats = [
            'year' => 'Y',
            'yearIso' => 'o',
            'month' => 'n',
            'day' => 'j',
            'hour' => 'G',
            'minute' => 'i',
            'second' => 's',
            'micro' => 'u',
            'microsecond' => 'u',
            'dayOfWeek' => 'w',
            'dayOfWeekIso' => 'N',
            'weekOfYear' => 'W',
            'daysInMonth' => 't',
            'timestamp' => 'U',
            'latinMeridiem' => 'a',
            'latinUpperMeridiem' => 'A',
            'englishDayOfWeek' => 'l',
            'shortEnglishDayOfWeek' => 'D',
            'englishMonth' => 'F',
            'shortEnglishMonth' => 'M',
            'localeDayOfWeek' => '%A',
            'shortLocaleDayOfWeek' => '%a',
            'localeMonth' => '%B',
            'shortLocaleMonth' => '%b',
            'timezoneAbbreviatedName' => 'T',
            'tzAbbrName' => 'T',
        ];

        switch (true) {
            case isset($formats[$name]):
                $format = $formats[$name];
                $method = str_starts_with($format, '%') ? 'formatLocalized' : 'rawFormat';
                $value = $this->$method($format);

                return is_numeric($value) ? (int) $value : $value;

            case $name === 'dayName':
                return $this->getTranslatedDayName();
            case $name === 'shortDayName':
                return $this->getTranslatedShortDayName();
            case $name === 'minDayName':
                return $this->getTranslatedMinDayName();
            case $name === 'monthName':
                return $this->getTranslatedMonthName();
            case $name === 'shortMonthName':
                return $this->getTranslatedShortMonthName();
            case $name === 'meridiem':
                return $this->meridiem(true);
            case $name === 'upperMeridiem':
                return $this->meridiem();
            case $name === 'noZeroHour':
                return $this->hour ?: 24;
            case $name === 'milliseconds':
            case $name === 'millisecond':
            case $name === 'milli':
                return (int) floor(((int) $this->rawFormat('u')) / 1000);

            case $name === 'week':
                return (int) $this->week();

            case $name === 'isoWeek':
                return (int) $this->isoWeek();

            case $name === 'weekYear':
                return (int) $this->weekYear();

            case $name === 'isoWeekYear':
                return (int) $this->isoWeekYear();

            case $name === 'weeksInYear':
                return $this->weeksInYear();

            case $name === 'isoWeeksInYear':
                return $this->isoWeeksInYear();

            case $name === 'weekOfMonth':
                return (int) ceil($this->day / static::DAYS_PER_WEEK);

            case $name === 'weekNumberInMonth':
                return (int) ceil(($this->day + $this->avoidMutation()->startOfMonth()->dayOfWeekIso - 1) / static::DAYS_PER_WEEK);

            case $name === 'firstWeekDay':
                return $this->localTranslator ? ($this->getTranslationMessage('first_day_of_week') ?? 0) : static::getWeekStartsAt();

            case $name === 'lastWeekDay':
                return $this->localTranslator ? (($this->getTranslationMessage('first_day_of_week') ?? 0) + static::DAYS_PER_WEEK - 1) % static::DAYS_PER_WEEK : static::getWeekEndsAt();

            case $name === 'dayOfYear':
                return 1 + (int) ($this->rawFormat('z'));

            case $name === 'daysInYear':
                return $this->isLeapYear() ? 366 : 365;

            case $name === 'age':
                return $this->diffInYears();

            case $name === 'quarter':
                return (int) ceil($this->month / static::MONTHS_PER_QUARTER);

            case $name === 'decade':
                return (int) ceil($this->year / static::YEARS_PER_DECADE);

            case $name === 'century':
                $factor = 1;
                $year = $this->year;
                if ($year < 0) {
                    $year = -$year;
                    $factor = -1;
                }

                return (int) ($factor * ceil($year / static::YEARS_PER_CENTURY));

            case $name === 'millennium':
                $factor = 1;
                $year = $this->year;
                if ($year < 0) {
                    $year = -$year;
                    $factor = -1;
                }

                return (int) ($factor * ceil($year / static::YEARS_PER_MILLENNIUM));

            case $name === 'offset':
                return $this->getOffset();

            case $name === 'offsetMinutes':
                return $this->getOffset() / static::SECONDS_PER_MINUTE;

            case $name === 'offsetHours':
                return $this->getOffset() / static::SECONDS_PER_MINUTE / static::MINUTES_PER_HOUR;

            case $name === 'dst':
                return $this->rawFormat('I') === '1';

            case $name === 'local':
                return $this->getOffset() === $this->avoidMutation()->setTimezone(date_default_timezone_get())->getOffset();

            case $name === 'utc':
                return $this->getOffset() === 0;

            case $name === 'timezone' || $name === 'tz':
                return CarbonTimeZone::instance($this->getTimezone());

            case $name === 'timezoneName' || $name === 'tzName':
                return $this->getTimezone()->getName();

            case $name === 'locale':
                return $this->getTranslatorLocale();

            default:
                $macro = $this->getLocalMacro('get'.ucfirst($name));

                if ($macro) {
                    return $this->executeCallableWithContext($macro);
                }

                throw new UnknownGetterException($name);
        }
    }

    public function __isset($name)
    {
        try {
            $this->__get($name);
        } catch (UnknownGetterException | ReflectionException $e) {
            return false;
        }

        return true;
    }

    public function __set($name, $value)
    {
        if ($this->constructedObjectId === spl_object_hash($this)) {
            $this->set($name, $value);

            return;
        }

        $this->$name = $value;
    }

    public function set($name, $value = null)
    {
        if ($this->isImmutable()) {
            throw new ImmutableException(sprintf('%s class', static::class));
        }

        if (\is_array($name)) {
            foreach ($name as $key => $value) {
                $this->set($key, $value);
            }

            return $this;
        }

        switch ($name) {
            case 'milliseconds':
            case 'millisecond':
            case 'milli':
            case 'microseconds':
            case 'microsecond':
            case 'micro':
                if (str_starts_with($name, 'milli')) {
                    $value *= 1000;
                }

                while ($value < 0) {
                    $this->subSecond();
                    $value += static::MICROSECONDS_PER_SECOND;
                }

                while ($value >= static::MICROSECONDS_PER_SECOND) {
                    $this->addSecond();
                    $value -= static::MICROSECONDS_PER_SECOND;
                }

                $this->modify($this->rawFormat('H:i:s.').str_pad((string) round($value), 6, '0', STR_PAD_LEFT));

                break;

            case 'year':
            case 'month':
            case 'day':
            case 'hour':
            case 'minute':
            case 'second':
                [$year, $month, $day, $hour, $minute, $second] = array_map('intval', explode('-', $this->rawFormat('Y-n-j-G-i-s')));
                $$name = $value;
                $this->setDateTime($year, $month, $day, $hour, $minute, $second);

                break;

            case 'week':
                $this->week($value);

                break;

            case 'isoWeek':
                $this->isoWeek($value);

                break;

            case 'weekYear':
                $this->weekYear($value);

                break;

            case 'isoWeekYear':
                $this->isoWeekYear($value);

                break;

            case 'dayOfYear':
                $this->addDays($value - $this->dayOfYear);

                break;

            case 'timestamp':
                $this->setTimestamp($value);

                break;

            case 'offset':
                $this->setTimezone(static::safeCreateDateTimeZone($value / static::SECONDS_PER_MINUTE / static::MINUTES_PER_HOUR));

                break;

            case 'offsetMinutes':
                $this->setTimezone(static::safeCreateDateTimeZone($value / static::MINUTES_PER_HOUR));

                break;

            case 'offsetHours':
                $this->setTimezone(static::safeCreateDateTimeZone($value));

                break;

            case 'timezone':
            case 'tz':
                $this->setTimezone($value);

                break;

            default:
                $macro = $this->getLocalMacro('set'.ucfirst($name));

                if ($macro) {
                    $this->executeCallableWithContext($macro, $value);

                    break;
                }

                if ($this->localStrictModeEnabled ?? static::isStrictModeEnabled()) {
                    throw new UnknownSetterException($name);
                }

                $this->$name = $value;
        }

        return $this;
    }

    protected function getTranslatedFormByRegExp($baseKey, $keySuffix, $context, $subKey, $defaultValue)
    {
        $key = $baseKey.$keySuffix;
        $standaloneKey = "{$key}_standalone";
        $baseTranslation = $this->getTranslationMessage($key);

        if ($baseTranslation instanceof Closure) {
            return $baseTranslation($this, $context, $subKey) ?: $defaultValue;
        }

        if (
            $this->getTranslationMessage("$standaloneKey.$subKey") &&
            (!$context || (($regExp = $this->getTranslationMessage("{$baseKey}_regexp")) && !preg_match($regExp, $context)))
        ) {
            $key = $standaloneKey;
        }

        return $this->getTranslationMessage("$key.$subKey", null, $defaultValue);
    }

    public function getTranslatedDayName($context = null, $keySuffix = '', $defaultValue = null)
    {
        return $this->getTranslatedFormByRegExp('weekdays', $keySuffix, $context, $this->dayOfWeek, $defaultValue ?: $this->englishDayOfWeek);
    }

    public function getTranslatedShortDayName($context = null)
    {
        return $this->getTranslatedDayName($context, '_short', $this->shortEnglishDayOfWeek);
    }

    public function getTranslatedMinDayName($context = null)
    {
        return $this->getTranslatedDayName($context, '_min', $this->shortEnglishDayOfWeek);
    }

    public function getTranslatedMonthName($context = null, $keySuffix = '', $defaultValue = null)
    {
        return $this->getTranslatedFormByRegExp('months', $keySuffix, $context, $this->month - 1, $defaultValue ?: $this->englishMonth);
    }

    public function getTranslatedShortMonthName($context = null)
    {
        return $this->getTranslatedMonthName($context, '_short', $this->shortEnglishMonth);
    }

    public function dayOfYear($value = null)
    {
        $dayOfYear = $this->dayOfYear;

        return $value === null ? $dayOfYear : $this->addDays($value - $dayOfYear);
    }

    public function weekday($value = null)
    {
        if ($value === null) {
            return $this->dayOfWeek;
        }

        $firstDay = (int) ($this->getTranslationMessage('first_day_of_week') ?? 0);
        $dayOfWeek = ($this->dayOfWeek + 7 - $firstDay) % 7;

        return $this->addDays((($value + 7 - $firstDay) % 7) - $dayOfWeek);
    }

    public function isoWeekday($value = null)
    {
        $dayOfWeekIso = $this->dayOfWeekIso;

        return $value === null ? $dayOfWeekIso : $this->addDays($value - $dayOfWeekIso);
    }

    public function getDaysFromStartOfWeek(int $weekStartsAt = null): int
    {
        $firstDay = (int) ($weekStartsAt ?? $this->getTranslationMessage('first_day_of_week') ?? 0);

        return ($this->dayOfWeek + 7 - $firstDay) % 7;
    }

    public function setDaysFromStartOfWeek(int $numberOfDays, int $weekStartsAt = null)
    {
        return $this->addDays($numberOfDays - $this->getDaysFromStartOfWeek($weekStartsAt));
    }

    public function setUnitNoOverflow($valueUnit, $value, $overflowUnit)
    {
        try {
            $original = $this->avoidMutation();

            $date = $this->$valueUnit($value);
            $end = $original->avoidMutation()->endOf($overflowUnit);
            $start = $original->avoidMutation()->startOf($overflowUnit);
            if ($date < $start) {
                $date = $date->setDateTimeFrom($start);
            } elseif ($date > $end) {
                $date = $date->setDateTimeFrom($end);
            }

            return $date;
        } catch (BadMethodCallException | ReflectionException $exception) {
            throw new UnknownUnitException($valueUnit, 0, $exception);
        }
    }

    public function addUnitNoOverflow($valueUnit, $value, $overflowUnit)
    {
        return $this->setUnitNoOverflow($valueUnit, $this->$valueUnit + $value, $overflowUnit);
    }

    public function subUnitNoOverflow($valueUnit, $value, $overflowUnit)
    {
        return $this->setUnitNoOverflow($valueUnit, $this->$valueUnit - $value, $overflowUnit);
    }

    public function utcOffset(int $minuteOffset = null)
    {
        if (\func_num_args() < 1) {
            return $this->offsetMinutes;
        }

        return $this->setTimezone(CarbonTimeZone::createFromMinuteOffset($minuteOffset));
    }

    #[ReturnTypeWillChange]
    public function setDate($year, $month, $day)
    {
        return parent::setDate((int) $year, (int) $month, (int) $day);
    }

    #[ReturnTypeWillChange]
    public function setISODate($year, $week, $day = 1)
    {
        return parent::setISODate((int) $year, (int) $week, (int) $day);
    }

    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0, $microseconds = 0)
    {
        return $this->setDate($year, $month, $day)->setTime((int) $hour, (int) $minute, (int) $second, (int) $microseconds);
    }

    #[ReturnTypeWillChange]
    public function setTime($hour, $minute, $second = 0, $microseconds = 0)
    {
        return parent::setTime((int) $hour, (int) $minute, (int) $second, (int) $microseconds);
    }

    #[ReturnTypeWillChange]
    public function setTimestamp($unixTimestamp)
    {
        [$timestamp, $microseconds] = self::getIntegerAndDecimalParts($unixTimestamp);

        return parent::setTimestamp((int) $timestamp)->setMicroseconds((int) $microseconds);
    }

    public function setTimeFromTimeString($time)
    {
        if (!str_contains($time, ':')) {
            $time .= ':0';
        }

        return $this->modify($time);
    }

    public function timezone($value)
    {
        return $this->setTimezone($value);
    }

    public function tz($value = null)
    {
        if (\func_num_args() < 1) {
            return $this->tzName;
        }

        return $this->setTimezone($value);
    }

    #[ReturnTypeWillChange]
    public function setTimezone($value)
    {
        $tz = static::safeCreateDateTimeZone($value);

        if ($tz === false && !self::isStrictModeEnabled()) {
            $tz = new CarbonTimeZone();
        }

        return parent::setTimezone($tz);
    }

    public function shiftTimezone($value)
    {
        $dateTimeString = $this->format('Y-m-d H:i:s.u');

        return $this
            ->setTimezone($value)
            ->modify($dateTimeString);
    }

    public function utc()
    {
        return $this->setTimezone('UTC');
    }

    public function setDateFrom($date = null)
    {
        $date = $this->resolveCarbon($date);

        return $this->setDate($date->year, $date->month, $date->day);
    }

    public function setTimeFrom($date = null)
    {
        $date = $this->resolveCarbon($date);

        return $this->setTime($date->hour, $date->minute, $date->second, $date->microsecond);
    }

    public function setDateTimeFrom($date = null)
    {
        $date = $this->resolveCarbon($date);

        return $this->modify($date->rawFormat('Y-m-d H:i:s.u'));
    }

    public static function getDays()
    {
        return static::$days;
    }


    private static function getFirstDayOfWeek(): int
    {
        return (int) static::getTranslationMessageWith(
            static::getTranslator(),
            'first_day_of_week'
        );
    }

    public static function getWeekStartsAt()
    {
        if (static::$weekStartsAt === static::WEEK_DAY_AUTO) {
            return self::getFirstDayOfWeek();
        }

        return static::$weekStartsAt;
    }

    public static function setWeekStartsAt($day)
    {
        static::$weekStartsAt = $day === static::WEEK_DAY_AUTO ? $day : max(0, (7 + $day) % 7);
    }

    public static function getWeekEndsAt()
    {
        if (static::$weekStartsAt === static::WEEK_DAY_AUTO) {
            return (int) (static::DAYS_PER_WEEK - 1 + self::getFirstDayOfWeek()) % static::DAYS_PER_WEEK;
        }

        return static::$weekEndsAt;
    }

    public static function setWeekEndsAt($day)
    {
        static::$weekEndsAt = $day === static::WEEK_DAY_AUTO ? $day : max(0, (7 + $day) % 7);
    }

    public static function getWeekendDays()
    {
        return static::$weekendDays;
    }

    public static function setWeekendDays($days)
    {
        static::$weekendDays = $days;
    }

    public static function hasRelativeKeywords($time)
    {
        if (!$time || strtotime($time) === false) {
            return false;
        }

        $date1 = new DateTime('2000-01-01T00:00:00Z');
        $date1->modify($time);
        $date2 = new DateTime('2001-12-25T00:00:00Z');
        $date2->modify($time);

        return $date1 != $date2;
    }


    public static function setUtf8($utf8)
    {
        static::$utf8 = $utf8;
    }

    public function formatLocalized($format)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format); // @codeCoverageIgnore
        }

        $time = strtotime($this->toDateTimeString());
        $formatted = ($this->localStrictModeEnabled ?? static::isStrictModeEnabled())
            ? strftime($format, $time)
            : @strftime($format, $time);

        return static::$utf8
            ? (
                \function_exists('mb_convert_encoding')
                ? mb_convert_encoding($formatted, 'UTF-8', mb_list_encodings())
                : utf8_encode($formatted)
            )
            : $formatted;
    }

    public function getIsoFormats($locale = null)
    {
        return [
            'LT' => $this->getTranslationMessage('formats.LT', $locale, 'h:mm A'),
            'LTS' => $this->getTranslationMessage('formats.LTS', $locale, 'h:mm:ss A'),
            'L' => $this->getTranslationMessage('formats.L', $locale, 'MM/DD/YYYY'),
            'LL' => $this->getTranslationMessage('formats.LL', $locale, 'MMMM D, YYYY'),
            'LLL' => $this->getTranslationMessage('formats.LLL', $locale, 'MMMM D, YYYY h:mm A'),
            'LLLL' => $this->getTranslationMessage('formats.LLLL', $locale, 'dddd, MMMM D, YYYY h:mm A'),
            'l' => $this->getTranslationMessage('formats.l', $locale),
            'll' => $this->getTranslationMessage('formats.ll', $locale),
            'lll' => $this->getTranslationMessage('formats.lll', $locale),
            'llll' => $this->getTranslationMessage('formats.llll', $locale),
        ];
    }

    public function getCalendarFormats($locale = null)
    {
        return [
            'sameDay' => $this->getTranslationMessage('calendar.sameDay', $locale, '[Today at] LT'),
            'nextDay' => $this->getTranslationMessage('calendar.nextDay', $locale, '[Tomorrow at] LT'),
            'nextWeek' => $this->getTranslationMessage('calendar.nextWeek', $locale, 'dddd [at] LT'),
            'lastDay' => $this->getTranslationMessage('calendar.lastDay', $locale, '[Yesterday at] LT'),
            'lastWeek' => $this->getTranslationMessage('calendar.lastWeek', $locale, '[Last] dddd [at] LT'),
            'sameElse' => $this->getTranslationMessage('calendar.sameElse', $locale, 'L'),
        ];
    }

    public static function getIsoUnits()
    {
        static $units = null;

        if ($units === null) {
            $units = [
                'OD' => ['getAltNumber', ['day']],
                'OM' => ['getAltNumber', ['month']],
                'OY' => ['getAltNumber', ['year']],
                'OH' => ['getAltNumber', ['hour']],
                'Oh' => ['getAltNumber', ['h']],
                'Om' => ['getAltNumber', ['minute']],
                'Os' => ['getAltNumber', ['second']],
                'D' => 'day',
                'DD' => ['rawFormat', ['d']],
                'Do' => ['ordinal', ['day', 'D']],
                'd' => 'dayOfWeek',
                'dd' => function (CarbonInterface $date, $originalFormat = null) {
                    return $date->getTranslatedMinDayName($originalFormat);
                },
                'ddd' => function (CarbonInterface $date, $originalFormat = null) {
                    return $date->getTranslatedShortDayName($originalFormat);
                },
                'dddd' => function (CarbonInterface $date, $originalFormat = null) {
                    return $date->getTranslatedDayName($originalFormat);
                },
                'DDD' => 'dayOfYear',
                'DDDD' => ['getPaddedUnit', ['dayOfYear', 3]],
                'DDDo' => ['ordinal', ['dayOfYear', 'DDD']],
                'e' => ['weekday', []],
                'E' => 'dayOfWeekIso',
                'H' => ['rawFormat', ['G']],
                'HH' => ['rawFormat', ['H']],
                'h' => ['rawFormat', ['g']],
                'hh' => ['rawFormat', ['h']],
                'k' => 'noZeroHour',
                'kk' => ['getPaddedUnit', ['noZeroHour']],
                'hmm' => ['rawFormat', ['gi']],
                'hmmss' => ['rawFormat', ['gis']],
                'Hmm' => ['rawFormat', ['Gi']],
                'Hmmss' => ['rawFormat', ['Gis']],
                'm' => 'minute',
                'mm' => ['rawFormat', ['i']],
                'a' => 'meridiem',
                'A' => 'upperMeridiem',
                's' => 'second',
                'ss' => ['getPaddedUnit', ['second']],
                'S' => function (CarbonInterface $date) {
                    return (string) floor($date->micro / 100000);
                },
                'SS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro / 10000), 2, '0', STR_PAD_LEFT);
                },
                'SSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro / 1000), 3, '0', STR_PAD_LEFT);
                },
                'SSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro / 100), 4, '0', STR_PAD_LEFT);
                },
                'SSSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro / 10), 5, '0', STR_PAD_LEFT);
                },
                'SSSSSS' => ['getPaddedUnit', ['micro', 6]],
                'SSSSSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro * 10), 7, '0', STR_PAD_LEFT);
                },
                'SSSSSSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro * 100), 8, '0', STR_PAD_LEFT);
                },
                'SSSSSSSSS' => function (CarbonInterface $date) {
                    return str_pad((string) floor($date->micro * 1000), 9, '0', STR_PAD_LEFT);
                },
                'M' => 'month',
                'MM' => ['rawFormat', ['m']],
                'MMM' => function (CarbonInterface $date, $originalFormat = null) {
                    $month = $date->getTranslatedShortMonthName($originalFormat);
                    $suffix = $date->getTranslationMessage('mmm_suffix');
                    if ($suffix && $month !== $date->monthName) {
                        $month .= $suffix;
                    }

                    return $month;
                },
                'MMMM' => function (CarbonInterface $date, $originalFormat = null) {
                    return $date->getTranslatedMonthName($originalFormat);
                },
                'Mo' => ['ordinal', ['month', 'M']],
                'Q' => 'quarter',
                'Qo' => ['ordinal', ['quarter', 'M']],
                'G' => 'isoWeekYear',
                'GG' => ['getPaddedUnit', ['isoWeekYear']],
                'GGG' => ['getPaddedUnit', ['isoWeekYear', 3]],
                'GGGG' => ['getPaddedUnit', ['isoWeekYear', 4]],
                'GGGGG' => ['getPaddedUnit', ['isoWeekYear', 5]],
                'g' => 'weekYear',
                'gg' => ['getPaddedUnit', ['weekYear']],
                'ggg' => ['getPaddedUnit', ['weekYear', 3]],
                'gggg' => ['getPaddedUnit', ['weekYear', 4]],
                'ggggg' => ['getPaddedUnit', ['weekYear', 5]],
                'W' => 'isoWeek',
                'WW' => ['getPaddedUnit', ['isoWeek']],
                'Wo' => ['ordinal', ['isoWeek', 'W']],
                'w' => 'week',
                'ww' => ['getPaddedUnit', ['week']],
                'wo' => ['ordinal', ['week', 'w']],
                'x' => ['valueOf', []],
                'X' => 'timestamp',
                'Y' => 'year',
                'YY' => ['rawFormat', ['y']],
                'YYYY' => ['getPaddedUnit', ['year', 4]],
                'YYYYY' => ['getPaddedUnit', ['year', 5]],
                'YYYYYY' => function (CarbonInterface $date) {
                    return ($date->year < 0 ? '' : '+').$date->getPaddedUnit('year', 6);
                },
                'z' => ['rawFormat', ['T']],
                'zz' => 'tzName',
                'Z' => ['getOffsetString', []],
                'ZZ' => ['getOffsetString', ['']],
            ];
        }

        return $units;
    }

    public function getPaddedUnit($unit, $length = 2, $padString = '0', $padType = STR_PAD_LEFT)
    {
        return ($this->$unit < 0 ? '-' : '').str_pad((string) abs($this->$unit), $length, $padString, $padType);
    }

    public function ordinal(string $key, ?string $period = null): string
    {
        $number = $this->$key;
        $result = $this->translate('ordinal', [
            ':number' => $number,
            ':period' => (string) $period,
        ]);

        return (string) ($result === 'ordinal' ? $number : $result);
    }

    public function meridiem(bool $isLower = false): string
    {
        $hour = $this->hour;
        $index = $hour < 12 ? 0 : 1;

        if ($isLower) {
            $key = 'meridiem.'.($index + 2);
            $result = $this->translate($key);

            if ($result !== $key) {
                return $result;
            }
        }

        $key = "meridiem.$index";
        $result = $this->translate($key);
        if ($result === $key) {
            $result = $this->translate('meridiem', [
                ':hour' => $this->hour,
                ':minute' => $this->minute,
                ':isLower' => $isLower,
            ]);

            if ($result === 'meridiem') {
                return $isLower ? $this->latinMeridiem : $this->latinUpperMeridiem;
            }
        } elseif ($isLower) {
            $result = mb_strtolower($result);
        }

        return $result;
    }

    public function getAltNumber(string $key): string
    {
        return $this->translateNumber(\strlen($key) > 1 ? $this->$key : $this->rawFormat('h'));
    }

    public function isoFormat(string $format, ?string $originalFormat = null): string
    {
        $result = '';
        $length = mb_strlen($format);
        $originalFormat = $originalFormat ?: $format;
        $inEscaped = false;
        $formats = null;
        $units = null;

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($format, $i, 1);

            if ($char === '\\') {
                $result .= mb_substr($format, ++$i, 1);

                continue;
            }

            if ($char === '[' && !$inEscaped) {
                $inEscaped = true;

                continue;
            }

            if ($char === ']' && $inEscaped) {
                $inEscaped = false;

                continue;
            }

            if ($inEscaped) {
                $result .= $char;

                continue;
            }

            $input = mb_substr($format, $i);

            if (preg_match('/^(LTS|LT|l{1,4}|L{1,4})/', $input, $match)) {
                if ($formats === null) {
                    $formats = $this->getIsoFormats();
                }

                $code = $match[0];
                $sequence = $formats[$code] ?? preg_replace_callback(
                    '/MMMM|MM|DD|dddd/',
                    function ($code) {
                        return mb_substr($code[0], 1);
                    },
                    $formats[strtoupper($code)] ?? ''
                );
                $rest = mb_substr($format, $i + mb_strlen($code));
                $format = mb_substr($format, 0, $i).$sequence.$rest;
                $length = mb_strlen($format);
                $input = $sequence.$rest;
            }

            if (preg_match('/^'.CarbonInterface::ISO_FORMAT_REGEXP.'/', $input, $match)) {
                $code = $match[0];

                if ($units === null) {
                    $units = static::getIsoUnits();
                }

                $sequence = $units[$code] ?? '';

                if ($sequence instanceof Closure) {
                    $sequence = $sequence($this, $originalFormat);
                } elseif (\is_array($sequence)) {
                    try {
                        $sequence = $this->{$sequence[0]}(...$sequence[1]);
                    } catch (ReflectionException | InvalidArgumentException | BadMethodCallException $e) {
                        $sequence = '';
                    }
                } elseif (\is_string($sequence)) {
                    $sequence = $this->$sequence ?? $code;
                }

                $format = mb_substr($format, 0, $i).$sequence.mb_substr($format, $i + mb_strlen($code));
                $i += mb_strlen((string) $sequence) - 1;
                $length = mb_strlen($format);
                $char = $sequence;
            }

            $result .= $char;
        }

        return $result;
    }

    public static function getFormatsToIsoReplacements()
    {
        static $replacements = null;

        if ($replacements === null) {
            $replacements = [
                'd' => true,
                'D' => 'ddd',
                'j' => true,
                'l' => 'dddd',
                'N' => true,
                'S' => function ($date) {
                    $day = $date->rawFormat('j');

                    return str_replace((string) $day, '', $date->isoFormat('Do'));
                },
                'w' => true,
                'z' => true,
                'W' => true,
                'F' => 'MMMM',
                'm' => true,
                'M' => 'MMM',
                'n' => true,
                't' => true,
                'L' => true,
                'o' => true,
                'Y' => true,
                'y' => true,
                'a' => 'a',
                'A' => 'A',
                'B' => true,
                'g' => true,
                'G' => true,
                'h' => true,
                'H' => true,
                'i' => true,
                's' => true,
                'u' => true,
                'v' => true,
                'E' => true,
                'I' => true,
                'O' => true,
                'P' => true,
                'Z' => true,
                'c' => true,
                'r' => true,
                'U' => true,
                'T' => true,
            ];
        }

        return $replacements;
    }

    public function translatedFormat(string $format): string
    {
        $replacements = static::getFormatsToIsoReplacements();
        $context = '';
        $isoFormat = '';
        $length = mb_strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($format, $i, 1);

            if ($char === '\\') {
                $replacement = mb_substr($format, $i, 2);
                $isoFormat .= $replacement;
                $i++;

                continue;
            }

            if (!isset($replacements[$char])) {
                $replacement = preg_match('/^[A-Za-z]$/', $char) ? "\\$char" : $char;
                $isoFormat .= $replacement;
                $context .= $replacement;

                continue;
            }

            $replacement = $replacements[$char];

            if ($replacement === true) {
                static $contextReplacements = null;

                if ($contextReplacements === null) {
                    $contextReplacements = [
                        'm' => 'MM',
                        'd' => 'DD',
                        't' => 'D',
                        'j' => 'D',
                        'N' => 'e',
                        'w' => 'e',
                        'n' => 'M',
                        'o' => 'YYYY',
                        'Y' => 'YYYY',
                        'y' => 'YY',
                        'g' => 'h',
                        'G' => 'H',
                        'h' => 'hh',
                        'H' => 'HH',
                        'i' => 'mm',
                        's' => 'ss',
                    ];
                }

                $isoFormat .= '['.$this->rawFormat($char).']';
                $context .= $contextReplacements[$char] ?? ' ';

                continue;
            }

            if ($replacement instanceof Closure) {
                $replacement = '['.$replacement($this).']';
                $isoFormat .= $replacement;
                $context .= $replacement;

                continue;
            }

            $isoFormat .= $replacement;
            $context .= $replacement;
        }

        return $this->isoFormat($isoFormat, $context);
    }

    public function getOffsetString($separator = ':')
    {
        $second = $this->getOffset();
        $symbol = $second < 0 ? '-' : '+';
        $minute = abs($second) / static::SECONDS_PER_MINUTE;
        $hour = str_pad((string) floor($minute / static::MINUTES_PER_HOUR), 2, '0', STR_PAD_LEFT);
        $minute = str_pad((string) (((int) $minute) % static::MINUTES_PER_HOUR), 2, '0', STR_PAD_LEFT);

        return "$symbol$hour$separator$minute";
    }

    protected static function executeStaticCallable($macro, ...$parameters)
    {
        return static::bindMacroContext(null, function () use (&$macro, &$parameters) {
            if ($macro instanceof Closure) {
                $boundMacro = @Closure::bind($macro, null, static::class);

                return ($boundMacro ?: $macro)(...$parameters);
            }

            return $macro(...$parameters);
        });
    }

    public static function __callStatic($method, $parameters)
    {
        if (!static::hasMacro($method)) {
            foreach (static::getGenericMacros() as $callback) {
                try {
                    return static::executeStaticCallable($callback, $method, ...$parameters);
                } catch (BadMethodCallException $exception) {
                    continue;
                }
            }
            if (static::isStrictModeEnabled()) {
                throw new UnknownMethodException(sprintf('%s::%s', static::class, $method));
            }

            return null;
        }

        return static::executeStaticCallable(static::$globalMacros[$method], ...$parameters);
    }

    public function setUnit($unit, $value = null)
    {
        $unit = static::singularUnit($unit);
        $dateUnits = ['year', 'month', 'day'];
        if (\in_array($unit, $dateUnits)) {
            return $this->setDate(...array_map(function ($name) use ($unit, $value) {
                return (int) ($name === $unit ? $value : $this->$name);
            }, $dateUnits));
        }

        $units = ['hour', 'minute', 'second', 'micro'];
        if ($unit === 'millisecond' || $unit === 'milli') {
            $value *= 1000;
            $unit = 'micro';
        } elseif ($unit === 'microsecond') {
            $unit = 'micro';
        }

        return $this->setTime(...array_map(function ($name) use ($unit, $value) {
            return (int) ($name === $unit ? $value : $this->$name);
        }, $units));
    }

    public static function singularUnit(string $unit): string
    {
        $unit = rtrim(mb_strtolower($unit), 's');

        if ($unit === 'centurie') {
            return 'century';
        }

        if ($unit === 'millennia') {
            return 'millennium';
        }

        return $unit;
    }

    public static function pluralUnit(string $unit): string
    {
        $unit = rtrim(strtolower($unit), 's');

        if ($unit === 'century') {
            return 'centuries';
        }

        if ($unit === 'millennium' || $unit === 'millennia') {
            return 'millennia';
        }

        return "{$unit}s";
    }

    protected function executeCallable($macro, ...$parameters)
    {
        if ($macro instanceof Closure) {
            $boundMacro = @$macro->bindTo($this, static::class) ?: @$macro->bindTo(null, static::class);

            return ($boundMacro ?: $macro)(...$parameters);
        }

        return $macro(...$parameters);
    }

    protected function executeCallableWithContext($macro, ...$parameters)
    {
        return static::bindMacroContext($this, function () use (&$macro, &$parameters) {
            return $this->executeCallable($macro, ...$parameters);
        });
    }

    protected static function getGenericMacros()
    {
        foreach (static::$globalGenericMacros as $list) {
            foreach ($list as $macro) {
                yield $macro;
            }
        }
    }

    public function __call($method, $parameters)
    {
        $diffSizes = [
            'short' => true,
            'long' => false,
        ];
        $diffSyntaxModes = [
            'Absolute' => CarbonInterface::DIFF_ABSOLUTE,
            'Relative' => CarbonInterface::DIFF_RELATIVE_AUTO,
            'RelativeToNow' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
            'RelativeToOther' => CarbonInterface::DIFF_RELATIVE_TO_OTHER,
        ];
        $sizePattern = implode('|', array_keys($diffSizes));
        $syntaxPattern = implode('|', array_keys($diffSyntaxModes));

        if (preg_match("/^(?<size>$sizePattern)(?<syntax>$syntaxPattern)DiffForHumans$/", $method, $match)) {
            $dates = array_filter($parameters, function ($parameter) {
                return $parameter instanceof DateTimeInterface;
            });
            $other = null;

            if (\count($dates)) {
                $key = key($dates);
                $other = current($dates);
                array_splice($parameters, $key, 1);
            }

            return $this->diffForHumans($other, $diffSyntaxModes[$match['syntax']], $diffSizes[$match['size']], ...$parameters);
        }

        $roundedValue = $this->callRoundMethod($method, $parameters);

        if ($roundedValue !== null) {
            return $roundedValue;
        }

        $unit = rtrim($method, 's');

        if (str_starts_with($unit, 'is')) {
            $word = substr($unit, 2);

            if (\in_array($word, static::$days, true)) {
                return $this->isDayOfWeek($word);
            }

            switch ($word) {
                case 'Utc':
                case 'UTC':
                    return $this->utc;
                case 'Local':
                    return $this->local;
                case 'Valid':
                    return $this->year !== 0;
                case 'DST':
                    return $this->dst;
            }
        }

        $action = substr($unit, 0, 3);
        $overflow = null;

        if ($action === 'set') {
            $unit = strtolower(substr($unit, 3));
        }

        if (\in_array($unit, static::$units, true)) {
            return $this->setUnit($unit, ...$parameters);
        }

        if ($action === 'add' || $action === 'sub') {
            $unit = substr($unit, 3);

            if (str_starts_with($unit, 'Real')) {
                $unit = static::singularUnit(substr($unit, 4));

                return $this->{"{$action}RealUnit"}($unit, ...$parameters);
            }

            if (preg_match('/^(Month|Quarter|Year|Decade|Century|Centurie|Millennium|Millennia)s?(No|With|Without|WithNo)Overflow$/', $unit, $match)) {
                $unit = $match[1];
                $overflow = $match[2] === 'With';
            }

            $unit = static::singularUnit($unit);
        }

        if (static::isModifiableUnit($unit)) {
            return $this->{"{$action}Unit"}($unit, $this->getMagicParameter($parameters, 0, 'value', 1), $overflow);
        }

        $sixFirstLetters = substr($unit, 0, 6);
        $factor = -1;

        if ($sixFirstLetters === 'isLast') {
            $sixFirstLetters = 'isNext';
            $factor = 1;
        }

        if ($sixFirstLetters === 'isNext') {
            $lowerUnit = strtolower(substr($unit, 6));

            if (static::isModifiableUnit($lowerUnit)) {
                return $this->copy()->addUnit($lowerUnit, $factor, false)->isSameUnit($lowerUnit, ...$parameters);
            }
        }

        if ($sixFirstLetters === 'isSame') {
            try {
                return $this->isSameUnit(strtolower(substr($unit, 6)), ...$parameters);
            } catch (BadComparisonUnitException $exception) {
            }
        }

        if (str_starts_with($unit, 'isCurrent')) {
            try {
                return $this->isCurrentUnit(strtolower(substr($unit, 9)));
            } catch (BadComparisonUnitException | BadMethodCallException $exception) {
            }
        }

        if (str_ends_with($method, 'Until')) {
            try {
                $unit = static::singularUnit(substr($method, 0, -5));

                return $this->range(
                    $this->getMagicParameter($parameters, 0, 'endDate', $this),
                    $this->getMagicParameter($parameters, 1, 'factor', 1),
                    $unit
                );
            } catch (InvalidArgumentException $exception) {
            }
        }

        return static::bindMacroContext($this, function () use (&$method, &$parameters) {
            $macro = $this->getLocalMacro($method);

            if (!$macro) {
                foreach ([$this->localGenericMacros ?: [], static::getGenericMacros()] as $list) {
                    foreach ($list as $callback) {
                        try {
                            return $this->executeCallable($callback, $method, ...$parameters);
                        } catch (BadMethodCallException $exception) {
                            continue;
                        }
                    }
                }

                if ($this->localStrictModeEnabled ?? static::isStrictModeEnabled()) {
                    throw new UnknownMethodException($method);
                }

                return null;
            }

            return $this->executeCallable($macro, ...$parameters);
        });
    }
}
