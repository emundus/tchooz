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

use Carbon\Exceptions\BadFluentConstructorException;
use Carbon\Exceptions\BadFluentSetterException;
use Carbon\Exceptions\InvalidCastException;
use Carbon\Exceptions\InvalidIntervalException;
use Carbon\Exceptions\OutOfRangeException;
use Carbon\Exceptions\ParseErrorException;
use Carbon\Exceptions\UnitNotConfiguredException;
use Carbon\Exceptions\UnknownGetterException;
use Carbon\Exceptions\UnknownSetterException;
use Carbon\Exceptions\UnknownUnitException;
use Carbon\Traits\IntervalRounding;
use Carbon\Traits\IntervalStep;
use Carbon\Traits\MagicParameter;
use Carbon\Traits\Mixin;
use Carbon\Traits\Options;
use Carbon\Traits\ToStringFormat;
use Closure;
use DateInterval;
use DateMalformedIntervalStringException;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use ReflectionException;
use ReturnTypeWillChange;
use RuntimeException;
use Throwable;

class CarbonInterval extends DateInterval implements CarbonConverterInterface
{
    use IntervalRounding;
    use IntervalStep;
    use MagicParameter;
    use Mixin {
        Mixin::mixin as baseMixin;
    }
    use Options;
    use ToStringFormat;

    public const PERIOD_PREFIX = 'P';
    public const PERIOD_YEARS = 'Y';
    public const PERIOD_MONTHS = 'M';
    public const PERIOD_DAYS = 'D';
    public const PERIOD_TIME_PREFIX = 'T';
    public const PERIOD_HOURS = 'H';
    public const PERIOD_MINUTES = 'M';
    public const PERIOD_SECONDS = 'S';

    protected static $translator;

    protected static $cascadeFactors;

    protected static $formats = [
        'y' => 'y',
        'Y' => 'y',
        'o' => 'y',
        'm' => 'm',
        'n' => 'm',
        'W' => 'weeks',
        'd' => 'd',
        'j' => 'd',
        'z' => 'd',
        'h' => 'h',
        'g' => 'h',
        'H' => 'h',
        'G' => 'h',
        'i' => 'i',
        's' => 's',
        'u' => 'micro',
        'v' => 'milli',
    ];

    private static $flipCascadeFactors;

    private static $floatSettersEnabled = false;

    protected static $macros = [];

    protected $tzName;

    public function setTimezone($tzName)
    {
        $this->tzName = $tzName;

        return $this;
    }

    public function shiftTimezone($tzName)
    {
        $this->tzName = $tzName;

        return $this;
    }

    public static function getCascadeFactors()
    {
        return static::$cascadeFactors ?: static::getDefaultCascadeFactors();
    }

    protected static function getDefaultCascadeFactors(): array
    {
        return [
            'milliseconds' => [Carbon::MICROSECONDS_PER_MILLISECOND, 'microseconds'],
            'seconds' => [Carbon::MILLISECONDS_PER_SECOND, 'milliseconds'],
            'minutes' => [Carbon::SECONDS_PER_MINUTE, 'seconds'],
            'hours' => [Carbon::MINUTES_PER_HOUR, 'minutes'],
            'dayz' => [Carbon::HOURS_PER_DAY, 'hours'],
            'weeks' => [Carbon::DAYS_PER_WEEK, 'dayz'],
            'months' => [Carbon::WEEKS_PER_MONTH, 'weeks'],
            'years' => [Carbon::MONTHS_PER_YEAR, 'months'],
        ];
    }

    private static function standardizeUnit($unit)
    {
        $unit = rtrim($unit, 'sz').'s';

        return $unit === 'days' ? 'dayz' : $unit;
    }

    private static function getFlipCascadeFactors()
    {
        if (!self::$flipCascadeFactors) {
            self::$flipCascadeFactors = [];

            foreach (static::getCascadeFactors() as $to => [$factor, $from]) {
                self::$flipCascadeFactors[self::standardizeUnit($from)] = [self::standardizeUnit($to), $factor];
            }
        }

        return self::$flipCascadeFactors;
    }

    public static function setCascadeFactors(array $cascadeFactors)
    {
        self::$flipCascadeFactors = null;
        static::$cascadeFactors = $cascadeFactors;
    }

    public static function enableFloatSetters(bool $floatSettersEnabled = true): void
    {
        self::$floatSettersEnabled = $floatSettersEnabled;
    }


    public function __construct($years = 1, $months = null, $weeks = null, $days = null, $hours = null, $minutes = null, $seconds = null, $microseconds = null)
    {
        if ($years instanceof Closure) {
            $this->step = $years;
            $years = null;
        }

        if ($years instanceof DateInterval) {
            parent::__construct(static::getDateIntervalSpec($years));
            $this->f = $years->f;
            self::copyNegativeUnits($years, $this);

            return;
        }

        $spec = $years;
        $isStringSpec = (\is_string($spec) && !preg_match('/^[\d.]/', $spec));

        if (!$isStringSpec || (float) $years) {
            $spec = static::PERIOD_PREFIX;

            $spec .= $years > 0 ? $years.static::PERIOD_YEARS : '';
            $spec .= $months > 0 ? $months.static::PERIOD_MONTHS : '';

            $specDays = 0;
            $specDays += $weeks > 0 ? $weeks * static::getDaysPerWeek() : 0;
            $specDays += $days > 0 ? $days : 0;

            $spec .= $specDays > 0 ? $specDays.static::PERIOD_DAYS : '';

            if ($hours > 0 || $minutes > 0 || $seconds > 0) {
                $spec .= static::PERIOD_TIME_PREFIX;
                $spec .= $hours > 0 ? $hours.static::PERIOD_HOURS : '';
                $spec .= $minutes > 0 ? $minutes.static::PERIOD_MINUTES : '';
                $spec .= $seconds > 0 ? $seconds.static::PERIOD_SECONDS : '';
            }

            if ($spec === static::PERIOD_PREFIX) {
                $spec .= '0'.static::PERIOD_YEARS;
            }
        }

        try {
            parent::__construct($spec);
        } catch (Throwable $exception) {
            try {
                parent::__construct('PT0S');

                if ($isStringSpec) {
                    if (!preg_match('/^P
                        (?:(?<year>[+-]?\d*(?:\.\d+)?)Y)?
                        (?:(?<month>[+-]?\d*(?:\.\d+)?)M)?
                        (?:(?<week>[+-]?\d*(?:\.\d+)?)W)?
                        (?:(?<day>[+-]?\d*(?:\.\d+)?)D)?
                        (?:T
                            (?:(?<hour>[+-]?\d*(?:\.\d+)?)H)?
                            (?:(?<minute>[+-]?\d*(?:\.\d+)?)M)?
                            (?:(?<second>[+-]?\d*(?:\.\d+)?)S)?
                        )?
                    $/x', $spec, $match)) {
                        throw new InvalidArgumentException("Invalid duration: $spec");
                    }

                    $years = (float) ($match['year'] ?? 0);
                    $this->assertSafeForInteger('year', $years);
                    $months = (float) ($match['month'] ?? 0);
                    $this->assertSafeForInteger('month', $months);
                    $weeks = (float) ($match['week'] ?? 0);
                    $this->assertSafeForInteger('week', $weeks);
                    $days = (float) ($match['day'] ?? 0);
                    $this->assertSafeForInteger('day', $days);
                    $hours = (float) ($match['hour'] ?? 0);
                    $this->assertSafeForInteger('hour', $hours);
                    $minutes = (float) ($match['minute'] ?? 0);
                    $this->assertSafeForInteger('minute', $minutes);
                    $seconds = (float) ($match['second'] ?? 0);
                    $this->assertSafeForInteger('second', $seconds);
                }

                $totalDays = (($weeks * static::getDaysPerWeek()) + $days);
                $this->assertSafeForInteger('days total (including weeks)', $totalDays);

                $this->y = (int) $years;
                $this->m = (int) $months;
                $this->d = (int) $totalDays;
                $this->h = (int) $hours;
                $this->i = (int) $minutes;
                $this->s = (int) $seconds;

                if (
                    ((float) $this->y) !== $years ||
                    ((float) $this->m) !== $months ||
                    ((float) $this->d) !== $totalDays ||
                    ((float) $this->h) !== $hours ||
                    ((float) $this->i) !== $minutes ||
                    ((float) $this->s) !== $seconds
                ) {
                    $this->add(static::fromString(
                        ($years - $this->y).' years '.
                        ($months - $this->m).' months '.
                        ($totalDays - $this->d).' days '.
                        ($hours - $this->h).' hours '.
                        ($minutes - $this->i).' minutes '.
                        ($seconds - $this->s).' seconds '
                    ));
                }
            } catch (Throwable $secondException) {
                throw $secondException instanceof OutOfRangeException ? $secondException : $exception;
            }
        }

        if ($microseconds !== null) {
            $this->f = $microseconds / Carbon::MICROSECONDS_PER_SECOND;
        }
    }

    public static function getFactor($source, $target)
    {
        $source = self::standardizeUnit($source);
        $target = self::standardizeUnit($target);
        $factors = self::getFlipCascadeFactors();

        if (isset($factors[$source])) {
            [$to, $factor] = $factors[$source];

            if ($to === $target) {
                return $factor;
            }

            return $factor * static::getFactor($to, $target);
        }

        return null;
    }

    public static function getFactorWithDefault($source, $target)
    {
        $factor = self::getFactor($source, $target);

        if ($factor) {
            return $factor;
        }

        static $defaults = [
            'month' => ['year' => Carbon::MONTHS_PER_YEAR],
            'week' => ['month' => Carbon::WEEKS_PER_MONTH],
            'day' => ['week' => Carbon::DAYS_PER_WEEK],
            'hour' => ['day' => Carbon::HOURS_PER_DAY],
            'minute' => ['hour' => Carbon::MINUTES_PER_HOUR],
            'second' => ['minute' => Carbon::SECONDS_PER_MINUTE],
            'millisecond' => ['second' => Carbon::MILLISECONDS_PER_SECOND],
            'microsecond' => ['millisecond' => Carbon::MICROSECONDS_PER_MILLISECOND],
        ];

        return $defaults[$source][$target] ?? null;
    }

    public static function getDaysPerWeek()
    {
        return static::getFactor('dayz', 'weeks') ?: Carbon::DAYS_PER_WEEK;
    }

    public static function getHoursPerDay()
    {
        return static::getFactor('hours', 'dayz') ?: Carbon::HOURS_PER_DAY;
    }

    public static function getMinutesPerHour()
    {
        return static::getFactor('minutes', 'hours') ?: Carbon::MINUTES_PER_HOUR;
    }

    public static function getSecondsPerMinute()
    {
        return static::getFactor('seconds', 'minutes') ?: Carbon::SECONDS_PER_MINUTE;
    }

    public static function getMillisecondsPerSecond()
    {
        return static::getFactor('milliseconds', 'seconds') ?: Carbon::MILLISECONDS_PER_SECOND;
    }

    public static function getMicrosecondsPerMillisecond()
    {
        return static::getFactor('microseconds', 'milliseconds') ?: Carbon::MICROSECONDS_PER_MILLISECOND;
    }

    public static function create($years = 1, $months = null, $weeks = null, $days = null, $hours = null, $minutes = null, $seconds = null, $microseconds = null)
    {
        return new static($years, $months, $weeks, $days, $hours, $minutes, $seconds, $microseconds);
    }

    public static function createFromFormat(string $format, ?string $interval)
    {
        $instance = new static(0);
        $length = mb_strlen($format);

        if (preg_match('/s([,.])([uv])$/', $format, $match)) {
            $interval = explode($match[1], $interval);
            $index = \count($interval) - 1;
            $interval[$index] = str_pad($interval[$index], $match[2] === 'v' ? 3 : 6, '0');
            $interval = implode($match[1], $interval);
        }

        $interval = $interval ?? '';

        for ($index = 0; $index < $length; $index++) {
            $expected = mb_substr($format, $index, 1);
            $nextCharacter = mb_substr($interval, 0, 1);
            $unit = static::$formats[$expected] ?? null;

            if ($unit) {
                if (!preg_match('/^-?\d+/', $interval, $match)) {
                    throw new ParseErrorException('number', $nextCharacter);
                }

                $interval = mb_substr($interval, mb_strlen($match[0]));
                $instance->$unit += (int) ($match[0]);

                continue;
            }

            if ($nextCharacter !== $expected) {
                throw new ParseErrorException(
                    "'$expected'",
                    $nextCharacter,
                    'Allowed substitutes for interval formats are '.implode(', ', array_keys(static::$formats))."\n".
                    'See https://php.net/manual/en/function.date.php for their meaning'
                );
            }

            $interval = mb_substr($interval, 1);
        }

        if ($interval !== '') {
            throw new ParseErrorException(
                'end of string',
                $interval
            );
        }

        return $instance;
    }

    public function copy()
    {
        $date = new static(0);
        $date->copyProperties($this);
        $date->step = $this->step;

        return $date;
    }

    public function clone()
    {
        return $this->copy();
    }

    public static function __callStatic($method, $parameters)
    {
        try {
            $interval = new static(0);
            $localStrictModeEnabled = $interval->localStrictModeEnabled;
            $interval->localStrictModeEnabled = true;

            $result = static::hasMacro($method)
                ? static::bindMacroContext(null, function () use (&$method, &$parameters, &$interval) {
                    return $interval->callMacro($method, $parameters);
                })
                : $interval->$method(...$parameters);

            $interval->localStrictModeEnabled = $localStrictModeEnabled;

            return $result;
        } catch (BadFluentSetterException $exception) {
            if (Carbon::isStrictModeEnabled()) {
                throw new BadFluentConstructorException($method, 0, $exception);
            }

            return null;
        }
    }

    #[ReturnTypeWillChange]
    public static function __set_state($dump)
    {


        $dateInterval = parent::__set_state($dump);

        return static::instance($dateInterval);
    }

    protected static function this()
    {
        return end(static::$macroContextStack) ?: new static(0);
    }

    public static function fromString($intervalDefinition)
    {
        if (empty($intervalDefinition)) {
            return new static(0);
        }

        $years = 0;
        $months = 0;
        $weeks = 0;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        $milliseconds = 0;
        $microseconds = 0;

        $pattern = '/(\d+(?:\.\d+)?)\h*([^\d\h]*)/i';
        preg_match_all($pattern, $intervalDefinition, $parts, PREG_SET_ORDER);

        while ([$part, $value, $unit] = array_shift($parts)) {
            $intValue = (int) $value;
            $fraction = (float) $value - $intValue;

            switch (round($fraction, 6)) {
                case 1:
                    $fraction = 0;
                    $intValue++;

                    break;
                case 0:
                    $fraction = 0;

                    break;
            }

            switch ($unit === 'µs' ? 'µs' : strtolower($unit)) {
                case 'millennia':
                case 'millennium':
                    $years += $intValue * CarbonInterface::YEARS_PER_MILLENNIUM;

                    break;

                case 'century':
                case 'centuries':
                    $years += $intValue * CarbonInterface::YEARS_PER_CENTURY;

                    break;

                case 'decade':
                case 'decades':
                    $years += $intValue * CarbonInterface::YEARS_PER_DECADE;

                    break;

                case 'year':
                case 'years':
                case 'y':
                case 'yr':
                case 'yrs':
                    $years += $intValue;

                    break;

                case 'quarter':
                case 'quarters':
                    $months += $intValue * CarbonInterface::MONTHS_PER_QUARTER;

                    break;

                case 'month':
                case 'months':
                case 'mo':
                case 'mos':
                    $months += $intValue;

                    break;

                case 'week':
                case 'weeks':
                case 'w':
                    $weeks += $intValue;

                    if ($fraction) {
                        $parts[] = [null, $fraction * static::getDaysPerWeek(), 'd'];
                    }

                    break;

                case 'day':
                case 'days':
                case 'd':
                    $days += $intValue;

                    if ($fraction) {
                        $parts[] = [null, $fraction * static::getHoursPerDay(), 'h'];
                    }

                    break;

                case 'hour':
                case 'hours':
                case 'h':
                    $hours += $intValue;

                    if ($fraction) {
                        $parts[] = [null, $fraction * static::getMinutesPerHour(), 'm'];
                    }

                    break;

                case 'minute':
                case 'minutes':
                case 'm':
                    $minutes += $intValue;

                    if ($fraction) {
                        $parts[] = [null, $fraction * static::getSecondsPerMinute(), 's'];
                    }

                    break;

                case 'second':
                case 'seconds':
                case 's':
                    $seconds += $intValue;

                    if ($fraction) {
                        $parts[] = [null, $fraction * static::getMillisecondsPerSecond(), 'ms'];
                    }

                    break;

                case 'millisecond':
                case 'milliseconds':
                case 'milli':
                case 'ms':
                    $milliseconds += $intValue;

                    if ($fraction) {
                        $microseconds += round($fraction * static::getMicrosecondsPerMillisecond());
                    }

                    break;

                case 'microsecond':
                case 'microseconds':
                case 'micro':
                case 'µs':
                    $microseconds += $intValue;

                    break;

                default:
                    throw new InvalidIntervalException(
                        sprintf('Invalid part %s in definition %s', $part, $intervalDefinition)
                    );
            }
        }

        return new static($years, $months, $weeks, $days, $hours, $minutes, $seconds, $milliseconds * Carbon::MICROSECONDS_PER_MILLISECOND + $microseconds);
    }

    public static function parseFromLocale($interval, $locale = null)
    {
        return static::fromString(Carbon::translateTimeString($interval, $locale ?: static::getLocale(), 'en'));
    }

    private static function castIntervalToClass(DateInterval $interval, string $className, array $skip = [])
    {
        $mainClass = DateInterval::class;

        if (!is_a($className, $mainClass, true)) {
            throw new InvalidCastException("$className is not a sub-class of $mainClass.");
        }

        $microseconds = $interval->f;
        $instance = new $className(static::getDateIntervalSpec($interval, false, $skip));

        if ($microseconds) {
            $instance->f = $microseconds;
        }

        if ($interval instanceof self && is_a($className, self::class, true)) {
            self::copyStep($interval, $instance);
        }

        self::copyNegativeUnits($interval, $instance);

        return $instance;
    }

    private static function copyNegativeUnits(DateInterval $from, DateInterval $to): void
    {
        $to->invert = $from->invert;

        foreach (['y', 'm', 'd', 'h', 'i', 's'] as $unit) {
            if ($from->$unit < 0) {
                $to->$unit *= -1;
            }
        }
    }

    private static function copyStep(self $from, self $to): void
    {
        $to->setStep($from->getStep());
    }

    public function cast(string $className)
    {
        return self::castIntervalToClass($this, $className);
    }

    public static function instance(DateInterval $interval, array $skip = [], bool $skipCopy = false)
    {
        if ($skipCopy && $interval instanceof static) {
            return $interval;
        }

        return self::castIntervalToClass($interval, static::class, $skip);
    }

    public static function make($interval, $unit = null, bool $skipCopy = false)
    {
        if ($unit) {
            $interval = "$interval ".Carbon::pluralUnit($unit);
        }

        if ($interval instanceof DateInterval) {
            return static::instance($interval, [], $skipCopy);
        }

        if ($interval instanceof Closure) {
            return new static($interval);
        }

        if (!\is_string($interval)) {
            return null;
        }

        return static::makeFromString($interval);
    }

    protected static function makeFromString(string $interval)
    {
        $interval = preg_replace('/\s+/', ' ', trim($interval));

        if (preg_match('/^P[T\d]/', $interval)) {
            return new static($interval);
        }

        if (preg_match('/^(?:\h*\d+(?:\.\d+)?\h*[a-z]+)+$/i', $interval)) {
            return static::fromString($interval);
        }

        try {

            $interval = static::createFromDateString($interval);
        } catch (DateMalformedIntervalStringException $e) {
            return null;
        }

        return !$interval || $interval->isEmpty() ? null : $interval;
    }

    protected function resolveInterval($interval)
    {
        if (!($interval instanceof self)) {
            return self::make($interval);
        }

        return $interval;
    }

    #[ReturnTypeWillChange]
    public static function createFromDateString($time)
    {
        $interval = @parent::createFromDateString(strtr($time, [
            ',' => ' ',
            ' and ' => ' ',
        ]));

        if ($interval instanceof DateInterval) {
            $interval = static::instance($interval);
        }

        return $interval;
    }


    public function get($name)
    {
        if (str_starts_with($name, 'total')) {
            return $this->total(substr($name, 5));
        }

        switch ($name) {
            case 'years':
                return $this->y;

            case 'months':
                return $this->m;

            case 'dayz':
                return $this->d;

            case 'hours':
                return $this->h;

            case 'minutes':
                return $this->i;

            case 'seconds':
                return $this->s;

            case 'milli':
            case 'milliseconds':
                return (int) (round($this->f * Carbon::MICROSECONDS_PER_SECOND) / Carbon::MICROSECONDS_PER_MILLISECOND);

            case 'micro':
            case 'microseconds':
                return (int) round($this->f * Carbon::MICROSECONDS_PER_SECOND);

            case 'microExcludeMilli':
                return (int) round($this->f * Carbon::MICROSECONDS_PER_SECOND) % Carbon::MICROSECONDS_PER_MILLISECOND;

            case 'weeks':
                return (int) ($this->d / (int) static::getDaysPerWeek());

            case 'daysExcludeWeeks':
            case 'dayzExcludeWeeks':
                return $this->d % (int) static::getDaysPerWeek();

            case 'locale':
                return $this->getTranslatorLocale();

            default:
                throw new UnknownGetterException($name);
        }
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function set($name, $value = null)
    {
        $properties = \is_array($name) ? $name : [$name => $value];

        foreach ($properties as $key => $value) {
            switch (Carbon::singularUnit(rtrim($key, 'z'))) {
                case 'year':
                    $this->checkIntegerValue($key, $value);
                    $this->y = $value;
                    $this->handleDecimalPart('year', $value, $this->y);

                    break;

                case 'month':
                    $this->checkIntegerValue($key, $value);
                    $this->m = $value;
                    $this->handleDecimalPart('month', $value, $this->m);

                    break;

                case 'week':
                    $this->checkIntegerValue($key, $value);
                    $days = $value * (int) static::getDaysPerWeek();
                    $this->assertSafeForInteger('days total (including weeks)', $days);
                    $this->d = $days;
                    $this->handleDecimalPart('day', $days, $this->d);

                    break;

                case 'day':
                    $this->checkIntegerValue($key, $value);
                    $this->d = $value;
                    $this->handleDecimalPart('day', $value, $this->d);

                    break;

                case 'daysexcludeweek':
                case 'dayzexcludeweek':
                    $this->checkIntegerValue($key, $value);
                    $days = $this->weeks * (int) static::getDaysPerWeek() + $value;
                    $this->assertSafeForInteger('days total (including weeks)', $days);
                    $this->d = $days;
                    $this->handleDecimalPart('day', $days, $this->d);

                    break;

                case 'hour':
                    $this->checkIntegerValue($key, $value);
                    $this->h = $value;
                    $this->handleDecimalPart('hour', $value, $this->h);

                    break;

                case 'minute':
                    $this->checkIntegerValue($key, $value);
                    $this->i = $value;
                    $this->handleDecimalPart('minute', $value, $this->i);

                    break;

                case 'second':
                    $this->checkIntegerValue($key, $value);
                    $this->s = $value;
                    $this->handleDecimalPart('second', $value, $this->s);

                    break;

                case 'milli':
                case 'millisecond':
                    $this->microseconds = $value * Carbon::MICROSECONDS_PER_MILLISECOND + $this->microseconds % Carbon::MICROSECONDS_PER_MILLISECOND;

                    break;

                case 'micro':
                case 'microsecond':
                    $this->f = $value / Carbon::MICROSECONDS_PER_SECOND;

                    break;

                default:
                    if ($this->localStrictModeEnabled ?? Carbon::isStrictModeEnabled()) {
                        throw new UnknownSetterException($key);
                    }

                    $this->$key = $value;
            }
        }

        return $this;
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function weeksAndDays($weeks, $days)
    {
        $this->dayz = ($weeks * static::getDaysPerWeek()) + $days;

        return $this;
    }

    public function isEmpty()
    {
        return $this->years === 0 &&
            $this->months === 0 &&
            $this->dayz === 0 &&
            !$this->days &&
            $this->hours === 0 &&
            $this->minutes === 0 &&
            $this->seconds === 0 &&
            $this->microseconds === 0;
    }

    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    public static function mixin($mixin)
    {
        static::baseMixin($mixin);
    }

    public static function hasMacro($name)
    {
        return isset(static::$macros[$name]);
    }

    protected function callMacro($name, $parameters)
    {
        $macro = static::$macros[$name];

        if ($macro instanceof Closure) {
            $boundMacro = @$macro->bindTo($this, static::class) ?: @$macro->bindTo(null, static::class);

            return ($boundMacro ?: $macro)(...$parameters);
        }

        return $macro(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return static::bindMacroContext($this, function () use (&$method, &$parameters) {
                return $this->callMacro($method, $parameters);
            });
        }

        $roundedValue = $this->callRoundMethod($method, $parameters);

        if ($roundedValue !== null) {
            return $roundedValue;
        }

        if (preg_match('/^(?<method>add|sub)(?<unit>[A-Z].*)$/', $method, $match)) {
            $value = $this->getMagicParameter($parameters, 0, Carbon::pluralUnit($match['unit']), 0);

            return $this->{$match['method']}($value, $match['unit']);
        }

        $value = $this->getMagicParameter($parameters, 0, Carbon::pluralUnit($method), 1);

        try {
            $this->set($method, $value);
        } catch (UnknownSetterException $exception) {
            if ($this->localStrictModeEnabled ?? Carbon::isStrictModeEnabled()) {
                throw new BadFluentSetterException($method, 0, $exception);
            }
        }

        return $this;
    }

    protected function getForHumansInitialVariables($syntax, $short)
    {
        if (\is_array($syntax)) {
            return $syntax;
        }

        if (\is_int($short)) {
            return [
                'parts' => $short,
                'short' => false,
            ];
        }

        if (\is_bool($syntax)) {
            return [
                'short' => $syntax,
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            ];
        }

        return [];
    }

    protected function getForHumansParameters($syntax = null, $short = false, $parts = -1, $options = null)
    {
        $optionalSpace = ' ';
        $default = $this->getTranslationMessage('list.0') ?? $this->getTranslationMessage('list') ?? ' ';
        $join = $default === '' ? '' : ' ';
        $altNumbers = false;
        $aUnit = false;
        $minimumUnit = 's';
        $skip = [];
        extract($this->getForHumansInitialVariables($syntax, $short));
        $skip = array_map('strtolower', array_filter((array) $skip, static function ($value) {
            return \is_string($value) && $value !== '';
        }));

        if ($syntax === null) {
            $syntax = CarbonInterface::DIFF_ABSOLUTE;
        }

        if ($parts === -1) {
            $parts = INF;
        }

        if ($options === null) {
            $options = static::getHumanDiffOptions();
        }

        if ($join === false) {
            $join = ' ';
        } elseif ($join === true) {
            $join = [
                $default,
                $this->getTranslationMessage('list.1') ?? $default,
            ];
        }

        if ($altNumbers && $altNumbers !== true) {
            $language = new Language($this->locale);
            $altNumbers = \in_array($language->getCode(), (array) $altNumbers, true);
        }

        if (\is_array($join)) {
            [$default, $last] = $join;

            if ($default !== ' ') {
                $optionalSpace = '';
            }

            $join = function ($list) use ($default, $last) {
                if (\count($list) < 2) {
                    return implode('', $list);
                }

                $end = array_pop($list);

                return implode($default, $list).$last.$end;
            };
        }

        if (\is_string($join)) {
            if ($join !== ' ') {
                $optionalSpace = '';
            }

            $glue = $join;
            $join = function ($list) use ($glue) {
                return implode($glue, $list);
            };
        }

        $interpolations = [
            ':optional-space' => $optionalSpace,
        ];

        return [$syntax, $short, $parts, $options, $join, $aUnit, $altNumbers, $interpolations, $minimumUnit, $skip];
    }

    protected static function getRoundingMethodFromOptions(int $options): ?string
    {
        if ($options & CarbonInterface::ROUND) {
            return 'round';
        }

        if ($options & CarbonInterface::CEIL) {
            return 'ceil';
        }

        if ($options & CarbonInterface::FLOOR) {
            return 'floor';
        }

        return null;
    }

    public function toArray()
    {
        return [
            'years' => $this->years,
            'months' => $this->months,
            'weeks' => $this->weeks,
            'days' => $this->daysExcludeWeeks,
            'hours' => $this->hours,
            'minutes' => $this->minutes,
            'seconds' => $this->seconds,
            'microseconds' => $this->microseconds,
        ];
    }

    public function getNonZeroValues()
    {
        return array_filter($this->toArray(), 'intval');
    }

    public function getValuesSequence()
    {
        $nonZeroValues = $this->getNonZeroValues();

        if ($nonZeroValues === []) {
            return [];
        }

        $keys = array_keys($nonZeroValues);
        $firstKey = $keys[0];
        $lastKey = $keys[\count($keys) - 1];
        $values = [];
        $record = false;

        foreach ($this->toArray() as $unit => $count) {
            if ($unit === $firstKey) {
                $record = true;
            }

            if ($record) {
                $values[$unit] = $count;
            }

            if ($unit === $lastKey) {
                $record = false;
            }
        }

        return $values;
    }

    public function forHumans($syntax = null, $short = false, $parts = -1, $options = null)
    {
        [$syntax, $short, $parts, $options, $join, $aUnit, $altNumbers, $interpolations, $minimumUnit, $skip] = $this
            ->getForHumansParameters($syntax, $short, $parts, $options);

        $interval = [];

        $syntax = (int) ($syntax ?? CarbonInterface::DIFF_ABSOLUTE);
        $absolute = $syntax === CarbonInterface::DIFF_ABSOLUTE;
        $relativeToNow = $syntax === CarbonInterface::DIFF_RELATIVE_TO_NOW;
        $count = 1;
        $unit = $short ? 's' : 'second';
        $isFuture = $this->invert === 1;
        $transId = $relativeToNow ? ($isFuture ? 'from_now' : 'ago') : ($isFuture ? 'after' : 'before');
        $declensionMode = null;


        $translator = $this->getLocalTranslator();

        $handleDeclensions = function ($unit, $count, $index = 0, $parts = 1) use ($interpolations, $transId, $translator, $altNumbers, $absolute, &$declensionMode) {
            if (!$absolute) {
                $declensionMode = $declensionMode ?? $this->translate($transId.'_mode');

                if ($this->needsDeclension($declensionMode, $index, $parts)) {
                    $key = $unit.'_'.$transId;
                    $result = $this->translate($key, $interpolations, $count, $translator, $altNumbers);

                    if ($result !== $key) {
                        return $result;
                    }
                }
            }

            $result = $this->translate($unit, $interpolations, $count, $translator, $altNumbers);

            if ($result !== $unit) {
                return $result;
            }

            return null;
        };

        $intervalValues = $this;
        $method = static::getRoundingMethodFromOptions($options);

        if ($method) {
            $previousCount = INF;

            while (
                \count($intervalValues->getNonZeroValues()) > $parts &&
                ($count = \count($keys = array_keys($intervalValues->getValuesSequence()))) > 1
            ) {
                $index = min($count, $previousCount - 1) - 2;

                if ($index < 0) {
                    break;
                }

                $intervalValues = $this->copy()->roundUnit(
                    $keys[$index],
                    1,
                    $method
                );
                $previousCount = $count;
            }
        }

        $diffIntervalArray = [
            ['value' => $intervalValues->years,             'unit' => 'year',        'unitShort' => 'y'],
            ['value' => $intervalValues->months,            'unit' => 'month',       'unitShort' => 'm'],
            ['value' => $intervalValues->weeks,             'unit' => 'week',        'unitShort' => 'w'],
            ['value' => $intervalValues->daysExcludeWeeks,  'unit' => 'day',         'unitShort' => 'd'],
            ['value' => $intervalValues->hours,             'unit' => 'hour',        'unitShort' => 'h'],
            ['value' => $intervalValues->minutes,           'unit' => 'minute',      'unitShort' => 'min'],
            ['value' => $intervalValues->seconds,           'unit' => 'second',      'unitShort' => 's'],
            ['value' => $intervalValues->milliseconds,      'unit' => 'millisecond', 'unitShort' => 'ms'],
            ['value' => $intervalValues->microExcludeMilli, 'unit' => 'microsecond', 'unitShort' => 'µs'],
        ];

        if (!empty($skip)) {
            foreach ($diffIntervalArray as $index => &$unitData) {
                $nextIndex = $index + 1;

                if ($unitData['value'] &&
                    isset($diffIntervalArray[$nextIndex]) &&
                    \count(array_intersect([$unitData['unit'], $unitData['unit'].'s', $unitData['unitShort']], $skip))
                ) {
                    $diffIntervalArray[$nextIndex]['value'] += $unitData['value'] *
                        self::getFactorWithDefault($diffIntervalArray[$nextIndex]['unit'], $unitData['unit']);
                    $unitData['value'] = 0;
                }
            }
        }

        $transChoice = function ($short, $unitData, $index, $parts) use ($absolute, $handleDeclensions, $translator, $aUnit, $altNumbers, $interpolations) {
            $count = $unitData['value'];

            if ($short) {
                $result = $handleDeclensions($unitData['unitShort'], $count, $index, $parts);

                if ($result !== null) {
                    return $result;
                }
            } elseif ($aUnit) {
                $result = $handleDeclensions('a_'.$unitData['unit'], $count, $index, $parts);

                if ($result !== null) {
                    return $result;
                }
            }

            if (!$absolute) {
                return $handleDeclensions($unitData['unit'], $count, $index, $parts);
            }

            return $this->translate($unitData['unit'], $interpolations, $count, $translator, $altNumbers);
        };

        $fallbackUnit = ['second', 's'];

        foreach ($diffIntervalArray as $diffIntervalData) {
            if ($diffIntervalData['value'] > 0) {
                $unit = $short ? $diffIntervalData['unitShort'] : $diffIntervalData['unit'];
                $count = $diffIntervalData['value'];
                $interval[] = [$short, $diffIntervalData];
            } elseif ($options & CarbonInterface::SEQUENTIAL_PARTS_ONLY && \count($interval) > 0) {
                break;
            }

            if (\count($interval) >= $parts) {
                break;
            }

            if (\in_array($minimumUnit, [$diffIntervalData['unit'], $diffIntervalData['unitShort']], true)) {
                $fallbackUnit = [$diffIntervalData['unit'], $diffIntervalData['unitShort']];

                break;
            }
        }

        $actualParts = \count($interval);

        foreach ($interval as $index => &$item) {
            $item = $transChoice($item[0], $item[1], $index, $actualParts);
        }

        if (\count($interval) === 0) {
            if ($relativeToNow && $options & CarbonInterface::JUST_NOW) {
                $key = 'diff_now';
                $translation = $this->translate($key, $interpolations, null, $translator);

                if ($translation !== $key) {
                    return $translation;
                }
            }

            $count = $options & CarbonInterface::NO_ZERO_DIFF ? 1 : 0;
            $unit = $fallbackUnit[$short ? 1 : 0];
            $interval[] = $this->translate($unit, $interpolations, $count, $translator, $altNumbers);
        }

        $time = $join($interval);

        unset($diffIntervalArray, $interval);

        if ($absolute) {
            return $time;
        }

        $isFuture = $this->invert === 1;

        $transId = $relativeToNow ? ($isFuture ? 'from_now' : 'ago') : ($isFuture ? 'after' : 'before');

        if ($parts === 1) {
            if ($relativeToNow && $unit === 'day') {
                if ($count === 1 && $options & CarbonInterface::ONE_DAY_WORDS) {
                    $key = $isFuture ? 'diff_tomorrow' : 'diff_yesterday';
                    $translation = $this->translate($key, $interpolations, null, $translator);

                    if ($translation !== $key) {
                        return $translation;
                    }
                }

                if ($count === 2 && $options & CarbonInterface::TWO_DAY_WORDS) {
                    $key = $isFuture ? 'diff_after_tomorrow' : 'diff_before_yesterday';
                    $translation = $this->translate($key, $interpolations, null, $translator);

                    if ($translation !== $key) {
                        return $translation;
                    }
                }
            }

            $aTime = $aUnit ? $handleDeclensions('a_'.$unit, $count) : null;

            $time = $aTime ?: $handleDeclensions($unit, $count) ?: $time;
        }

        $time = [':time' => $time];

        return $this->translate($transId, array_merge($time, $interpolations, $time), null, $translator);
    }

    public function __toString()
    {
        $format = $this->localToStringFormat ?? static::$toStringFormat;

        if (!$format) {
            return $this->forHumans();
        }

        if ($format instanceof Closure) {
            return $format($this);
        }

        return $this->format($format);
    }

    public function toDateInterval()
    {
        return self::castIntervalToClass($this, DateInterval::class);
    }

    public function toPeriod(...$params)
    {
        if ($this->tzName) {
            $tz = \is_string($this->tzName) ? new DateTimeZone($this->tzName) : $this->tzName;

            if ($tz instanceof DateTimeZone) {
                array_unshift($params, $tz);
            }
        }

        return CarbonPeriod::create($this, ...$params);
    }

    public function invert($inverted = null)
    {
        $this->invert = (\func_num_args() === 0 ? !$this->invert : $inverted) ? 1 : 0;

        return $this;
    }

    protected function solveNegativeInterval()
    {
        if (!$this->isEmpty() && $this->years <= 0 && $this->months <= 0 && $this->dayz <= 0 && $this->hours <= 0 && $this->minutes <= 0 && $this->seconds <= 0 && $this->microseconds <= 0) {
            $this->years *= -1;
            $this->months *= -1;
            $this->dayz *= -1;
            $this->hours *= -1;
            $this->minutes *= -1;
            $this->seconds *= -1;
            $this->microseconds *= -1;
            $this->invert();
        }

        return $this;
    }

    public function add($unit, $value = 1)
    {
        if (is_numeric($unit)) {
            [$value, $unit] = [$unit, $value];
        }

        if (\is_string($unit) && !preg_match('/^\s*\d/', $unit)) {
            $unit = "$value $unit";
            $value = 1;
        }

        $interval = static::make($unit);

        if (!$interval) {
            throw new InvalidIntervalException('This type of data cannot be added/subtracted.');
        }

        if ($value !== 1) {
            $interval->times($value);
        }

        $sign = ($this->invert === 1) !== ($interval->invert === 1) ? -1 : 1;
        $this->years += $interval->y * $sign;
        $this->months += $interval->m * $sign;
        $this->dayz += ($interval->days === false ? $interval->d : $interval->days) * $sign;
        $this->hours += $interval->h * $sign;
        $this->minutes += $interval->i * $sign;
        $this->seconds += $interval->s * $sign;
        $this->microseconds += $interval->microseconds * $sign;

        $this->solveNegativeInterval();

        return $this;
    }

    public function sub($unit, $value = 1)
    {
        if (is_numeric($unit)) {
            [$value, $unit] = [$unit, $value];
        }

        return $this->add($unit, -(float) $value);
    }

    public function subtract($unit, $value = 1)
    {
        return $this->sub($unit, $value);
    }

    public function plus(
        $years = 0,
        $months = 0,
        $weeks = 0,
        $days = 0,
        $hours = 0,
        $minutes = 0,
        $seconds = 0,
        $microseconds = 0
    ): self {
        return $this->add("
            $years years $months months $weeks weeks $days days
            $hours hours $minutes minutes $seconds seconds $microseconds microseconds
        ");
    }

    public function minus(
        $years = 0,
        $months = 0,
        $weeks = 0,
        $days = 0,
        $hours = 0,
        $minutes = 0,
        $seconds = 0,
        $microseconds = 0
    ): self {
        return $this->sub("
            $years years $months months $weeks weeks $days days
            $hours hours $minutes minutes $seconds seconds $microseconds microseconds
        ");
    }

    public function times($factor)
    {
        if ($factor < 0) {
            $this->invert = $this->invert ? 0 : 1;
            $factor = -$factor;
        }

        $this->years = (int) round($this->years * $factor);
        $this->months = (int) round($this->months * $factor);
        $this->dayz = (int) round($this->dayz * $factor);
        $this->hours = (int) round($this->hours * $factor);
        $this->minutes = (int) round($this->minutes * $factor);
        $this->seconds = (int) round($this->seconds * $factor);
        $this->microseconds = (int) round($this->microseconds * $factor);

        return $this;
    }

    public function shares($divider)
    {
        return $this->times(1 / $divider);
    }

    protected function copyProperties(self $interval, $ignoreSign = false)
    {
        $this->years = $interval->years;
        $this->months = $interval->months;
        $this->dayz = $interval->dayz;
        $this->hours = $interval->hours;
        $this->minutes = $interval->minutes;
        $this->seconds = $interval->seconds;
        $this->microseconds = $interval->microseconds;

        if (!$ignoreSign) {
            $this->invert = $interval->invert;
        }

        return $this;
    }

    public function multiply($factor)
    {
        if ($factor < 0) {
            $this->invert = $this->invert ? 0 : 1;
            $factor = -$factor;
        }

        $yearPart = (int) floor($this->years * $factor); // Split calculation to prevent imprecision

        if ($yearPart) {
            $this->years -= $yearPart / $factor;
        }

        return $this->copyProperties(
            static::create($yearPart)
                ->microseconds(abs($this->totalMicroseconds) * $factor)
                ->cascade(),
            true
        );
    }

    public function divide($divider)
    {
        return $this->multiply(1 / $divider);
    }

    public static function getDateIntervalSpec(DateInterval $interval, bool $microseconds = false, array $skip = [])
    {
        $date = array_filter([
            static::PERIOD_YEARS => abs($interval->y),
            static::PERIOD_MONTHS => abs($interval->m),
            static::PERIOD_DAYS => abs($interval->d),
        ]);

        if (
            $interval->days >= CarbonInterface::DAYS_PER_WEEK * CarbonInterface::WEEKS_PER_MONTH &&
            (!isset($date[static::PERIOD_YEARS]) || \count(array_intersect(['y', 'year', 'years'], $skip))) &&
            (!isset($date[static::PERIOD_MONTHS]) || \count(array_intersect(['m', 'month', 'months'], $skip)))
        ) {
            $date = [
                static::PERIOD_DAYS => abs($interval->days),
            ];
        }

        $seconds = abs($interval->s);
        if ($microseconds && $interval->f > 0) {
            $seconds = sprintf('%d.%06d', $seconds, abs($interval->f) * 1000000);
        }

        $time = array_filter([
            static::PERIOD_HOURS => abs($interval->h),
            static::PERIOD_MINUTES => abs($interval->i),
            static::PERIOD_SECONDS => $seconds,
        ]);

        $specString = static::PERIOD_PREFIX;

        foreach ($date as $key => $value) {
            $specString .= $value.$key;
        }

        if (\count($time) > 0) {
            $specString .= static::PERIOD_TIME_PREFIX;
            foreach ($time as $key => $value) {
                $specString .= $value.$key;
            }
        }

        return $specString === static::PERIOD_PREFIX ? 'PT0S' : $specString;
    }

    public function spec(bool $microseconds = false)
    {
        return static::getDateIntervalSpec($this, $microseconds);
    }

    public static function compareDateIntervals(DateInterval $first, DateInterval $second)
    {
        $current = Carbon::now();
        $passed = $current->avoidMutation()->add($second);
        $current->add($first);

        if ($current < $passed) {
            return -1;
        }
        if ($current > $passed) {
            return 1;
        }

        return 0;
    }

    public function compare(DateInterval $interval)
    {
        return static::compareDateIntervals($this, $interval);
    }

    private function invertCascade(array $values)
    {
        return $this->set(array_map(function ($value) {
            return -$value;
        }, $values))->doCascade(true)->invert();
    }

    private function doCascade(bool $deep)
    {
        $originalData = $this->toArray();
        $originalData['milliseconds'] = (int) ($originalData['microseconds'] / static::getMicrosecondsPerMillisecond());
        $originalData['microseconds'] = $originalData['microseconds'] % static::getMicrosecondsPerMillisecond();
        $originalData['weeks'] = (int) ($this->d / static::getDaysPerWeek());
        $originalData['daysExcludeWeeks'] = fmod($this->d, static::getDaysPerWeek());
        unset($originalData['days']);
        $newData = $originalData;
        $previous = [];

        foreach (self::getFlipCascadeFactors() as $source => [$target, $factor]) {
            foreach (['source', 'target'] as $key) {
                if ($$key === 'dayz') {
                    $$key = 'daysExcludeWeeks';
                }
            }

            $value = $newData[$source];
            $modulo = fmod($factor + fmod($value, $factor), $factor);
            $newData[$source] = $modulo;
            $newData[$target] += ($value - $modulo) / $factor;

            $decimalPart = fmod($newData[$source], 1);

            if ($decimalPart !== 0.0) {
                $unit = $source;

                foreach ($previous as [$subUnit, $subFactor]) {
                    $newData[$unit] -= $decimalPart;
                    $newData[$subUnit] += $decimalPart * $subFactor;
                    $decimalPart = fmod($newData[$subUnit], 1);

                    if ($decimalPart === 0.0) {
                        break;
                    }

                    $unit = $subUnit;
                }
            }

            array_unshift($previous, [$source, $factor]);
        }

        $positive = null;

        if (!$deep) {
            foreach ($newData as $value) {
                if ($value) {
                    if ($positive === null) {
                        $positive = ($value > 0);

                        continue;
                    }

                    if (($value > 0) !== $positive) {
                        return $this->invertCascade($originalData)
                            ->solveNegativeInterval();
                    }
                }
            }
        }

        return $this->set($newData)
            ->solveNegativeInterval();
    }

    public function cascade()
    {
        return $this->doCascade(false);
    }

    public function hasNegativeValues(): bool
    {
        foreach ($this->toArray() as $value) {
            if ($value < 0) {
                return true;
            }
        }

        return false;
    }

    public function hasPositiveValues(): bool
    {
        foreach ($this->toArray() as $value) {
            if ($value > 0) {
                return true;
            }
        }

        return false;
    }

    public function total($unit)
    {
        $realUnit = $unit = strtolower($unit);

        if (\in_array($unit, ['days', 'weeks'])) {
            $realUnit = 'dayz';
        } elseif (!\in_array($unit, ['microseconds', 'milliseconds', 'seconds', 'minutes', 'hours', 'dayz', 'months', 'years'])) {
            throw new UnknownUnitException($unit);
        }

        $result = 0;
        $cumulativeFactor = 0;
        $unitFound = false;
        $factors = self::getFlipCascadeFactors();
        $daysPerWeek = (int) static::getDaysPerWeek();

        $values = [
            'years' => $this->years,
            'months' => $this->months,
            'weeks' => (int) ($this->d / $daysPerWeek),
            'dayz' => fmod($this->d, $daysPerWeek),
            'hours' => $this->hours,
            'minutes' => $this->minutes,
            'seconds' => $this->seconds,
            'milliseconds' => (int) ($this->microseconds / Carbon::MICROSECONDS_PER_MILLISECOND),
            'microseconds' => $this->microseconds % Carbon::MICROSECONDS_PER_MILLISECOND,
        ];

        if (isset($factors['dayz']) && $factors['dayz'][0] !== 'weeks') {
            $values['dayz'] += $values['weeks'] * $daysPerWeek;
            $values['weeks'] = 0;
        }

        foreach ($factors as $source => [$target, $factor]) {
            if ($source === $realUnit) {
                $unitFound = true;
                $value = $values[$source];
                $result += $value;
                $cumulativeFactor = 1;
            }

            if ($factor === false) {
                if ($unitFound) {
                    break;
                }

                $result = 0;
                $cumulativeFactor = 0;

                continue;
            }

            if ($target === $realUnit) {
                $unitFound = true;
            }

            if ($cumulativeFactor) {
                $cumulativeFactor *= $factor;
                $result += $values[$target] * $cumulativeFactor;

                continue;
            }

            $value = $values[$source];

            $result = ($result + $value) / $factor;
        }

        if (isset($target) && !$cumulativeFactor) {
            $result += $values[$target];
        }

        if (!$unitFound) {
            throw new UnitNotConfiguredException($unit);
        }

        if ($this->invert) {
            $result *= -1;
        }

        if ($unit === 'weeks') {
            $result /= $daysPerWeek;
        }

        return fmod($result, 1) === 0.0 ? (int) $result : $result;
    }

    public function eq($interval): bool
    {
        return $this->equalTo($interval);
    }

    public function equalTo($interval): bool
    {
        $interval = $this->resolveInterval($interval);

        return $interval !== null && $this->totalMicroseconds === $interval->totalMicroseconds;
    }

    public function ne($interval): bool
    {
        return $this->notEqualTo($interval);
    }

    public function notEqualTo($interval): bool
    {
        return !$this->eq($interval);
    }

    public function gt($interval): bool
    {
        return $this->greaterThan($interval);
    }

    public function greaterThan($interval): bool
    {
        $interval = $this->resolveInterval($interval);

        return $interval === null || $this->totalMicroseconds > $interval->totalMicroseconds;
    }

    public function gte($interval): bool
    {
        return $this->greaterThanOrEqualTo($interval);
    }

    public function greaterThanOrEqualTo($interval): bool
    {
        return $this->greaterThan($interval) || $this->equalTo($interval);
    }

    public function lt($interval): bool
    {
        return $this->lessThan($interval);
    }

    public function lessThan($interval): bool
    {
        $interval = $this->resolveInterval($interval);

        return $interval !== null && $this->totalMicroseconds < $interval->totalMicroseconds;
    }

    public function lte($interval): bool
    {
        return $this->lessThanOrEqualTo($interval);
    }

    public function lessThanOrEqualTo($interval): bool
    {
        return $this->lessThan($interval) || $this->equalTo($interval);
    }

    public function between($interval1, $interval2, $equal = true): bool
    {
        return $equal
            ? $this->greaterThanOrEqualTo($interval1) && $this->lessThanOrEqualTo($interval2)
            : $this->greaterThan($interval1) && $this->lessThan($interval2);
    }

    public function betweenIncluded($interval1, $interval2): bool
    {
        return $this->between($interval1, $interval2, true);
    }

    public function betweenExcluded($interval1, $interval2): bool
    {
        return $this->between($interval1, $interval2, false);
    }

    public function isBetween($interval1, $interval2, $equal = true): bool
    {
        return $this->between($interval1, $interval2, $equal);
    }

    public function roundUnit($unit, $precision = 1, $function = 'round')
    {
        if (static::getCascadeFactors() !== static::getDefaultCascadeFactors()) {
            $value = $function($this->total($unit) / $precision) * $precision;
            $inverted = $value < 0;

            return $this->copyProperties(self::fromString(
                number_format(abs($value), 12, '.', '').' '.$unit
            )->invert($inverted)->cascade());
        }

        $base = CarbonImmutable::parse('2000-01-01 00:00:00', 'UTC')
            ->roundUnit($unit, $precision, $function);
        $next = $base->add($this);
        $inverted = $next < $base;

        if ($inverted) {
            $next = $base->sub($this);
        }

        $this->copyProperties(
            $next
                ->roundUnit($unit, $precision, $function)
                ->diffAsCarbonInterval($base)
        );

        return $this->invert($inverted);
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

    private function needsDeclension(string $mode, int $index, int $parts): bool
    {
        switch ($mode) {
            case 'last':
                return $index === $parts - 1;
            default:
                return true;
        }
    }

    private function checkIntegerValue(string $name, $value)
    {
        if (\is_int($value)) {
            return;
        }

        $this->assertSafeForInteger($name, $value);

        if (\is_float($value) && (((float) (int) $value) === $value)) {
            return;
        }

        if (!self::$floatSettersEnabled) {
            $type = \gettype($value);
            @trigger_error(
                "Since 2.70.0, it's deprecated to pass $type value for $name.\n".
                "It's truncated when stored as an integer interval unit.\n".
                "From 3.0.0, decimal part will no longer be truncated and will be cascaded to smaller units.\n".
                "- To maintain the current behavior, use explicit cast: $name((int) \$value)\n".
                "- To adopt the new behavior globally, call CarbonInterval::enableFloatSetters()\n",
                \E_USER_DEPRECATED
            );
        }
    }

    private function assertSafeForInteger(string $name, $value)
    {
        if ($value && !\is_int($value) && ($value >= 0x7fffffffffffffff || $value <= -0x7fffffffffffffff)) {
            throw new OutOfRangeException($name, -0x7fffffffffffffff, 0x7fffffffffffffff, $value);
        }
    }

    private function handleDecimalPart(string $unit, $value, $integerValue)
    {
        if (self::$floatSettersEnabled) {
            $floatValue = (float) $value;
            $base = (float) $integerValue;

            if ($floatValue === $base) {
                return;
            }

            $units = [
                'y' => 'year',
                'm' => 'month',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            ];
            $upper = true;

            foreach ($units as $property => $name) {
                if ($name === $unit) {
                    $upper = false;

                    continue;
                }

                if (!$upper && $this->$property !== 0) {
                    throw new RuntimeException(
                        "You cannot set $unit to a float value as $name would be overridden, ".
                        'set it first to 0 explicitly if you really want to erase its value'
                    );
                }
            }

            $this->add($unit, $floatValue - $base);
        }
    }
}
