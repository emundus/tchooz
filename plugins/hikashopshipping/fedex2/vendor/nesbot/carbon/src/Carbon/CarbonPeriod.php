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

use Carbon\Exceptions\EndLessPeriodException;
use Carbon\Exceptions\InvalidCastException;
use Carbon\Exceptions\InvalidIntervalException;
use Carbon\Exceptions\InvalidPeriodDateException;
use Carbon\Exceptions\InvalidPeriodParameterException;
use Carbon\Exceptions\NotACarbonClassException;
use Carbon\Exceptions\NotAPeriodException;
use Carbon\Exceptions\UnknownGetterException;
use Carbon\Exceptions\UnknownMethodException;
use Carbon\Exceptions\UnreachableException;
use Carbon\Traits\IntervalRounding;
use Carbon\Traits\Mixin;
use Carbon\Traits\Options;
use Carbon\Traits\ToStringFormat;
use Closure;
use Countable;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use ReflectionException;
use ReturnTypeWillChange;
use RuntimeException;

class CarbonPeriod implements Iterator, Countable, JsonSerializable
{
    use IntervalRounding;
    use Mixin {
        Mixin::mixin as baseMixin;
    }
    use Options;
    use ToStringFormat;

    public const RECURRENCES_FILTER = [self::class, 'filterRecurrences'];

    public const END_DATE_FILTER = [self::class, 'filterEndDate'];

    public const END_ITERATION = [self::class, 'endIteration'];

    public const EXCLUDE_START_DATE = 1;

    public const EXCLUDE_END_DATE = 2;

    public const IMMUTABLE = 4;

    public const NEXT_MAX_ATTEMPTS = 1000;

    public const END_MAX_ATTEMPTS = 10000;

    protected const DEFAULT_DATE_CLASS = Carbon::class;

    protected static $macros = [];

    protected $dateClass = Carbon::class;

    protected $dateInterval;

    protected $constructed = false;

    protected $isDefaultInterval;

    protected $filters = [];

    protected $startDate;

    protected $endDate;

    protected $recurrences;

    protected $options;

    protected $key;

    protected $current;

    protected $timezone;

    protected $validationResult;

    protected $tzName;

    public static function make($var)
    {
        try {
            return static::instance($var);
        } catch (NotAPeriodException $e) {
            return static::create($var);
        }
    }

    public static function instance($period)
    {
        if ($period instanceof static) {
            return $period->copy();
        }

        if ($period instanceof self) {
            return new static(
                $period->getStartDate(),
                $period->getEndDate() ?: $period->getRecurrences(),
                $period->getDateInterval(),
                $period->getOptions()
            );
        }

        if ($period instanceof DatePeriod) {
            return new static(
                $period->start,
                $period->end ?: ($period->recurrences - 1),
                $period->interval,
                $period->include_start_date ? 0 : static::EXCLUDE_START_DATE
            );
        }

        $class = static::class;
        $type = \gettype($period);

        throw new NotAPeriodException(
            'Argument 1 passed to '.$class.'::'.__METHOD__.'() '.
            'must be an instance of DatePeriod or '.$class.', '.
            ($type === 'object' ? 'instance of '.\get_class($period) : $type).' given.'
        );
    }

    public static function create(...$params)
    {
        return static::createFromArray($params);
    }

    public static function createFromArray(array $params)
    {
        return new static(...$params);
    }

    public static function createFromIso($iso, $options = null)
    {
        $params = static::parseIso8601($iso);

        $instance = static::createFromArray($params);

        if ($options !== null) {
            $instance->setOptions($options);
        }

        return $instance;
    }

    protected static function intervalHasTime(DateInterval $interval)
    {
        return $interval->h || $interval->i || $interval->s || $interval->f;
    }

    protected static function isIso8601($var)
    {
        if (!\is_string($var)) {
            return false;
        }

        $part = '[a-z]+(?:[_-][a-z]+)*';

        preg_match("#\b$part/$part\b|(/)#i", $var, $match);

        return isset($match[1]);
    }

    protected static function parseIso8601($iso)
    {
        $result = [];

        $interval = null;
        $start = null;
        $end = null;
        $dateClass = static::DEFAULT_DATE_CLASS;

        foreach (explode('/', $iso) as $key => $part) {
            if ($key === 0 && preg_match('/^R(\d*|INF)$/', $part, $match)) {
                $parsed = \strlen($match[1]) ? (($match[1] !== 'INF') ? (int) $match[1] : INF) : null;
            } elseif ($interval === null && $parsed = CarbonInterval::make($part)) {
                $interval = $part;
            } elseif ($start === null && $parsed = $dateClass::make($part)) {
                $start = $part;
            } elseif ($end === null && $parsed = $dateClass::make(static::addMissingParts($start ?? '', $part))) {
                $end = $part;
            } else {
                throw new InvalidPeriodParameterException("Invalid ISO 8601 specification: $iso.");
            }

            $result[] = $parsed;
        }

        return $result;
    }

    protected static function addMissingParts($source, $target)
    {
        $pattern = '/'.preg_replace('/\d+/', '[0-9]+', preg_quote($target, '/')).'$/';

        $result = preg_replace($pattern, $target, $source, 1, $count);

        return $count ? $result : $target;
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

    public static function __callStatic($method, $parameters)
    {
        $date = new static();

        if (static::hasMacro($method)) {
            return static::bindMacroContext(null, function () use (&$method, &$parameters, &$date) {
                return $date->callMacro($method, $parameters);
            });
        }

        return $date->$method(...$parameters);
    }

    public function __construct(...$arguments)
    {
        if (is_a($this->dateClass, DateTimeImmutable::class, true)) {
            $this->options = static::IMMUTABLE;
        }


        $argumentsCount = \count($arguments);

        if ($argumentsCount && static::isIso8601($iso = $arguments[0])) {
            array_splice($arguments, 0, 1, static::parseIso8601($iso));
        }

        if ($argumentsCount === 1) {
            if ($arguments[0] instanceof DatePeriod) {
                $arguments = [
                    $arguments[0]->start,
                    $arguments[0]->end ?: ($arguments[0]->recurrences - 1),
                    $arguments[0]->interval,
                    $arguments[0]->include_start_date ? 0 : static::EXCLUDE_START_DATE,
                ];
            } elseif ($arguments[0] instanceof self) {
                $arguments = [
                    $arguments[0]->getStartDate(),
                    $arguments[0]->getEndDate() ?: $arguments[0]->getRecurrences(),
                    $arguments[0]->getDateInterval(),
                    $arguments[0]->getOptions(),
                ];
            }
        }

        $optionsSet = false;

        foreach ($arguments as $argument) {
            $parsedDate = null;

            if ($argument instanceof DateTimeZone) {
                $this->setTimezone($argument);
            } elseif ($this->dateInterval === null &&
                (
                    (\is_string($argument) && preg_match(
                        '/^(-?\d(\d(?![\/-])|[^\d\/-]([\/-])?)*|P[T\d].*|(?:\h*\d+(?:\.\d+)?\h*[a-z]+)+)$/i',
                        $argument
                    )) ||
                    $argument instanceof DateInterval ||
                    $argument instanceof Closure
                ) &&
                $parsedInterval = @CarbonInterval::make($argument)
            ) {
                $this->setDateInterval($parsedInterval);
            } elseif ($this->startDate === null && $parsedDate = $this->makeDateTime($argument)) {
                $this->setStartDate($parsedDate);
            } elseif ($this->endDate === null && ($parsedDate = $parsedDate ?? $this->makeDateTime($argument))) {
                $this->setEndDate($parsedDate);
            } elseif ($this->recurrences === null && $this->endDate === null && is_numeric($argument)) {
                $this->setRecurrences($argument);
            } elseif (!$optionsSet && (\is_int($argument) || $argument === null)) {
                $optionsSet = true;
                $this->setOptions(((int) $this->options) | ((int) $argument));
            } else {
                throw new InvalidPeriodParameterException('Invalid constructor parameters.');
            }
        }

        if ($this->startDate === null) {
            $dateClass = $this->dateClass;
            $this->setStartDate($dateClass::now());
        }

        if ($this->dateInterval === null) {
            $this->setDateInterval(CarbonInterval::day());

            $this->isDefaultInterval = true;
        }

        if ($this->options === null) {
            $this->setOptions(0);
        }

        $this->constructed = true;
    }

    public function copy()
    {
        return clone $this;
    }

    protected function copyIfImmutable()
    {
        return $this;
    }

    protected function getGetter(string $name)
    {
        switch (strtolower(preg_replace('/[A-Z]/', '_$0', $name))) {
            case 'start':
            case 'start_date':
                return [$this, 'getStartDate'];
            case 'end':
            case 'end_date':
                return [$this, 'getEndDate'];
            case 'interval':
            case 'date_interval':
                return [$this, 'getDateInterval'];
            case 'recurrences':
                return [$this, 'getRecurrences'];
            case 'include_start_date':
                return [$this, 'isStartIncluded'];
            case 'include_end_date':
                return [$this, 'isEndIncluded'];
            case 'current':
                return [$this, 'current'];
            default:
                return null;
        }
    }

    public function get(string $name)
    {
        $getter = $this->getGetter($name);

        if ($getter) {
            return $getter();
        }

        throw new UnknownGetterException($name);
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        return $this->getGetter($name) !== null;
    }

    public function clone()
    {
        return clone $this;
    }

    public function setDateClass(string $dateClass)
    {
        if (!is_a($dateClass, CarbonInterface::class, true)) {
            throw new NotACarbonClassException($dateClass);
        }

        $self = $this->copyIfImmutable();
        $self->dateClass = $dateClass;

        if (is_a($dateClass, Carbon::class, true)) {
            $self->options = $self->options & ~static::IMMUTABLE;
        } elseif (is_a($dateClass, CarbonImmutable::class, true)) {
            $self->options = $self->options | static::IMMUTABLE;
        }

        return $self;
    }

    public function getDateClass(): string
    {
        return $this->dateClass;
    }

    public function setDateInterval($interval)
    {
        if (!$interval = CarbonInterval::make($interval)) {
            throw new InvalidIntervalException('Invalid interval.');
        }

        if ($interval->spec() === 'PT0S' && !$interval->f && !$interval->getStep()) {
            throw new InvalidIntervalException('Empty interval is not accepted.');
        }

        $self = $this->copyIfImmutable();
        $self->dateInterval = $interval;

        $self->isDefaultInterval = false;

        $self->handleChangedParameters();

        return $self;
    }

    public function invertDateInterval()
    {
        return $this->setDateInterval($this->dateInterval->invert());
    }

    public function setDates($start, $end)
    {
        return $this->setStartDate($start)->setEndDate($end);
    }

    public function setOptions($options)
    {
        if (!\is_int($options) && $options !== null) {
            throw new InvalidPeriodParameterException('Invalid options.');
        }

        $self = $this->copyIfImmutable();
        $self->options = $options ?: 0;

        $self->handleChangedParameters();

        return $self;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function toggleOptions($options, $state = null)
    {
        if ($state === null) {
            $state = ($this->options & $options) !== $options;
        }

        return $this->setOptions(
            $state ?
            $this->options | $options :
            $this->options & ~$options
        );
    }

    public function excludeStartDate($state = true)
    {
        return $this->toggleOptions(static::EXCLUDE_START_DATE, $state);
    }

    public function excludeEndDate($state = true)
    {
        return $this->toggleOptions(static::EXCLUDE_END_DATE, $state);
    }

    public function getDateInterval()
    {
        return $this->dateInterval->copy();
    }

    public function getStartDate(string $rounding = null)
    {
        $date = $this->startDate->avoidMutation();

        return $rounding ? $date->round($this->getDateInterval(), $rounding) : $date;
    }

    public function getEndDate(string $rounding = null)
    {
        if (!$this->endDate) {
            return null;
        }

        $date = $this->endDate->avoidMutation();

        return $rounding ? $date->round($this->getDateInterval(), $rounding) : $date;
    }

    public function getRecurrences()
    {
        return $this->recurrences;
    }

    public function isStartExcluded()
    {
        return ($this->options & static::EXCLUDE_START_DATE) !== 0;
    }

    public function isEndExcluded()
    {
        return ($this->options & static::EXCLUDE_END_DATE) !== 0;
    }

    public function isStartIncluded()
    {
        return !$this->isStartExcluded();
    }

    public function isEndIncluded()
    {
        return !$this->isEndExcluded();
    }

    public function getIncludedStartDate()
    {
        $start = $this->getStartDate();

        if ($this->isStartExcluded()) {
            return $start->add($this->getDateInterval());
        }

        return $start;
    }

    public function getIncludedEndDate()
    {
        $end = $this->getEndDate();

        if (!$end) {
            return $this->calculateEnd();
        }

        if ($this->isEndExcluded()) {
            return $end->sub($this->getDateInterval());
        }

        return $end;
    }

    public function addFilter($callback, $name = null)
    {
        $self = $this->copyIfImmutable();
        $tuple = $self->createFilterTuple(\func_get_args());

        $self->filters[] = $tuple;

        $self->handleChangedParameters();

        return $self;
    }

    public function prependFilter($callback, $name = null)
    {
        $self = $this->copyIfImmutable();
        $tuple = $self->createFilterTuple(\func_get_args());

        array_unshift($self->filters, $tuple);

        $self->handleChangedParameters();

        return $self;
    }

    public function removeFilter($filter)
    {
        $self = $this->copyIfImmutable();
        $key = \is_callable($filter) ? 0 : 1;

        $self->filters = array_values(array_filter(
            $this->filters,
            function ($tuple) use ($key, $filter) {
                return $tuple[$key] !== $filter;
            }
        ));

        $self->updateInternalState();

        $self->handleChangedParameters();

        return $self;
    }

    public function hasFilter($filter)
    {
        $key = \is_callable($filter) ? 0 : 1;

        foreach ($this->filters as $tuple) {
            if ($tuple[$key] === $filter) {
                return true;
            }
        }

        return false;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function setFilters(array $filters)
    {
        $self = $this->copyIfImmutable();
        $self->filters = $filters;

        $self->updateInternalState();

        $self->handleChangedParameters();

        return $self;
    }

    public function resetFilters()
    {
        $self = $this->copyIfImmutable();
        $self->filters = [];

        if ($self->endDate !== null) {
            $self->filters[] = [static::END_DATE_FILTER, null];
        }

        if ($self->recurrences !== null) {
            $self->filters[] = [static::RECURRENCES_FILTER, null];
        }

        $self->handleChangedParameters();

        return $self;
    }

    public function setRecurrences($recurrences)
    {
        if ((!is_numeric($recurrences) && $recurrences !== null) || $recurrences < 0) {
            throw new InvalidPeriodParameterException('Invalid number of recurrences.');
        }

        if ($recurrences === null) {
            return $this->removeFilter(static::RECURRENCES_FILTER);
        }


        $self = $this->copyIfImmutable();
        $self->recurrences = $recurrences === INF ? INF : (int) $recurrences;

        if (!$self->hasFilter(static::RECURRENCES_FILTER)) {
            return $self->addFilter(static::RECURRENCES_FILTER);
        }

        $self->handleChangedParameters();

        return $self;
    }

    public function setStartDate($date, $inclusive = null)
    {
        if (!$this->isInfiniteDate($date) && !($date = ([$this->dateClass, 'make'])($date))) {
            throw new InvalidPeriodDateException('Invalid start date.');
        }

        $self = $this->copyIfImmutable();
        $self->startDate = $date;

        if ($inclusive !== null) {
            $self = $self->toggleOptions(static::EXCLUDE_START_DATE, !$inclusive);
        }

        return $self;
    }

    public function setEndDate($date, $inclusive = null)
    {
        if ($date !== null && !$this->isInfiniteDate($date) && !$date = ([$this->dateClass, 'make'])($date)) {
            throw new InvalidPeriodDateException('Invalid end date.');
        }

        if (!$date) {
            return $this->removeFilter(static::END_DATE_FILTER);
        }

        $self = $this->copyIfImmutable();
        $self->endDate = $date;

        if ($inclusive !== null) {
            $self = $self->toggleOptions(static::EXCLUDE_END_DATE, !$inclusive);
        }

        if (!$self->hasFilter(static::END_DATE_FILTER)) {
            return $self->addFilter(static::END_DATE_FILTER);
        }

        $self->handleChangedParameters();

        return $self;
    }

    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->validateCurrentDate() === true;
    }

    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->valid()
            ? $this->key
            : null;
    }

    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->valid()
            ? $this->prepareForReturn($this->current)
            : null;
    }

    #[ReturnTypeWillChange]
    public function next()
    {
        if ($this->current === null) {
            $this->rewind();
        }

        if ($this->validationResult !== static::END_ITERATION) {
            $this->key++;

            $this->incrementCurrentDateUntilValid();
        }
    }

    #[ReturnTypeWillChange]
    public function rewind()
    {
        $this->key = 0;
        $this->current = ([$this->dateClass, 'make'])($this->startDate);
        $settings = $this->getSettings();

        if ($this->hasLocalTranslator()) {
            $settings['locale'] = $this->getTranslatorLocale();
        }

        $this->current->settings($settings);
        $this->timezone = static::intervalHasTime($this->dateInterval) ? $this->current->getTimezone() : null;

        if ($this->timezone) {
            $this->current = $this->current->utc();
        }

        $this->validationResult = null;

        if ($this->isStartExcluded() || $this->validateCurrentDate() === false) {
            $this->incrementCurrentDateUntilValid();
        }
    }

    public function skip($count = 1)
    {
        for ($i = $count; $this->valid() && $i > 0; $i--) {
            $this->next();
        }

        return $this->valid();
    }

    public function toIso8601String()
    {
        $parts = [];

        if ($this->recurrences !== null) {
            $parts[] = 'R'.$this->recurrences;
        }

        $parts[] = $this->startDate->toIso8601String();

        $parts[] = $this->dateInterval->spec();

        if ($this->endDate !== null) {
            $parts[] = $this->endDate->toIso8601String();
        }

        return implode('/', $parts);
    }

    public function toString()
    {
        $format = $this->localToStringFormat ?? static::$toStringFormat;

        if ($format instanceof Closure) {
            return $format($this);
        }

        $translator = ([$this->dateClass, 'getTranslator'])();

        $parts = [];

        $format = $format ?? (
            !$this->startDate->isStartOfDay() || ($this->endDate && !$this->endDate->isStartOfDay())
                ? 'Y-m-d H:i:s'
                : 'Y-m-d'
        );

        if ($this->recurrences !== null) {
            $parts[] = $this->translate('period_recurrences', [], $this->recurrences, $translator);
        }

        $parts[] = $this->translate('period_interval', [':interval' => $this->dateInterval->forHumans([
            'join' => true,
        ])], null, $translator);

        $parts[] = $this->translate('period_start_date', [':date' => $this->startDate->rawFormat($format)], null, $translator);

        if ($this->endDate !== null) {
            $parts[] = $this->translate('period_end_date', [':date' => $this->endDate->rawFormat($format)], null, $translator);
        }

        $result = implode(' ', $parts);

        return mb_strtoupper(mb_substr($result, 0, 1)).mb_substr($result, 1);
    }

    public function spec()
    {
        return $this->toIso8601String();
    }

    public function cast(string $className)
    {
        if (!method_exists($className, 'instance')) {
            if (is_a($className, DatePeriod::class, true)) {
                return new $className(
                    $this->rawDate($this->getStartDate()),
                    $this->getDateInterval(),
                    $this->getEndDate() ? $this->rawDate($this->getIncludedEndDate()) : $this->getRecurrences(),
                    $this->isStartExcluded() ? DatePeriod::EXCLUDE_START_DATE : 0
                );
            }

            throw new InvalidCastException("$className has not the instance() method needed to cast the date.");
        }

        return $className::instance($this);
    }

    public function toDatePeriod()
    {
        return $this->cast(DatePeriod::class);
    }

    public function isUnfilteredAndEndLess(): bool
    {
        foreach ($this->filters as $filter) {
            switch ($filter) {
                case [static::RECURRENCES_FILTER, null]:
                    if ($this->recurrences !== null && is_finite($this->recurrences)) {
                        return false;
                    }

                    break;

                case [static::END_DATE_FILTER, null]:
                    if ($this->endDate !== null && !$this->endDate->isEndOfTime()) {
                        return false;
                    }

                    break;

                default:
                    return false;
            }
        }

        return true;
    }

    public function toArray()
    {
        if ($this->isUnfilteredAndEndLess()) {
            throw new EndLessPeriodException("Endless period can't be converted to array nor counted.");
        }

        $state = [
            $this->key,
            $this->current ? $this->current->avoidMutation() : null,
            $this->validationResult,
        ];

        $result = iterator_to_array($this);

        [$this->key, $this->current, $this->validationResult] = $state;

        return $result;
    }

    #[ReturnTypeWillChange]
    public function count()
    {
        return \count($this->toArray());
    }

    public function first()
    {
        if ($this->isUnfilteredAndEndLess()) {
            foreach ($this as $date) {
                $this->rewind();

                return $date;
            }

            return null;
        }

        return ($this->toArray() ?: [])[0] ?? null;
    }

    public function last()
    {
        $array = $this->toArray();

        return $array ? $array[\count($array) - 1] : null;
    }

    public function __toString()
    {
        return $this->toString();
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

        switch ($method) {
            case 'start':
            case 'since':
                self::setDefaultParameters($parameters, [
                    [0, 'date', null],
                ]);

                return $this->setStartDate(...$parameters);

            case 'sinceNow':
                return $this->setStartDate(new Carbon(), ...$parameters);

            case 'end':
            case 'until':
                self::setDefaultParameters($parameters, [
                    [0, 'date', null],
                ]);

                return $this->setEndDate(...$parameters);

            case 'untilNow':
                return $this->setEndDate(new Carbon(), ...$parameters);

            case 'dates':
            case 'between':
                self::setDefaultParameters($parameters, [
                    [0, 'start', null],
                    [1, 'end', null],
                ]);

                return $this->setDates(...$parameters);

            case 'recurrences':
            case 'times':
                self::setDefaultParameters($parameters, [
                    [0, 'recurrences', null],
                ]);

                return $this->setRecurrences(...$parameters);

            case 'options':
                self::setDefaultParameters($parameters, [
                    [0, 'options', null],
                ]);

                return $this->setOptions(...$parameters);

            case 'toggle':
                self::setDefaultParameters($parameters, [
                    [0, 'options', null],
                ]);

                return $this->toggleOptions(...$parameters);

            case 'filter':
            case 'push':
                return $this->addFilter(...$parameters);

            case 'prepend':
                return $this->prependFilter(...$parameters);

            case 'filters':
                self::setDefaultParameters($parameters, [
                    [0, 'filters', []],
                ]);

                return $this->setFilters(...$parameters);

            case 'interval':
            case 'each':
            case 'every':
            case 'step':
            case 'stepBy':
                return $this->setDateInterval(...$parameters);

            case 'invert':
                return $this->invertDateInterval();

            case 'years':
            case 'year':
            case 'months':
            case 'month':
            case 'weeks':
            case 'week':
            case 'days':
            case 'dayz':
            case 'day':
            case 'hours':
            case 'hour':
            case 'minutes':
            case 'minute':
            case 'seconds':
            case 'second':
            case 'milliseconds':
            case 'millisecond':
            case 'microseconds':
            case 'microsecond':
                return $this->setDateInterval((
                    [$this->isDefaultInterval ? new CarbonInterval('PT0S') : $this->dateInterval, $method]
                )(...$parameters));
        }

        $dateClass = $this->dateClass;

        if ($this->localStrictModeEnabled ?? $dateClass::isStrictModeEnabled()) {
            throw new UnknownMethodException($method);
        }

        return $this;
    }

    public function setTimezone($timezone)
    {
        $self = $this->copyIfImmutable();
        $self->tzName = $timezone;
        $self->timezone = $timezone;

        if ($self->startDate) {
            $self = $self->setStartDate($self->startDate->setTimezone($timezone));
        }

        if ($self->endDate) {
            $self = $self->setEndDate($self->endDate->setTimezone($timezone));
        }

        return $self;
    }

    public function shiftTimezone($timezone)
    {
        $self = $this->copyIfImmutable();
        $self->tzName = $timezone;
        $self->timezone = $timezone;

        if ($self->startDate) {
            $self = $self->setStartDate($self->startDate->shiftTimezone($timezone));
        }

        if ($self->endDate) {
            $self = $self->setEndDate($self->endDate->shiftTimezone($timezone));
        }

        return $self;
    }

    public function calculateEnd(string $rounding = null)
    {
        if ($end = $this->getEndDate($rounding)) {
            return $end;
        }

        if ($this->dateInterval->isEmpty()) {
            return $this->getStartDate($rounding);
        }

        $date = $this->getEndFromRecurrences() ?? $this->iterateUntilEnd();

        if ($date && $rounding) {
            $date = $date->avoidMutation()->round($this->getDateInterval(), $rounding);
        }

        return $date;
    }

    private function getEndFromRecurrences()
    {
        if ($this->recurrences === null) {
            throw new UnreachableException(
                "Could not calculate period end without either explicit end or recurrences.\n".
                "If you're looking for a forever-period, use ->setRecurrences(INF)."
            );
        }

        if ($this->recurrences === INF) {
            $start = $this->getStartDate();

            return $start < $start->avoidMutation()->add($this->getDateInterval())
                ? CarbonImmutable::endOfTime()
                : CarbonImmutable::startOfTime();
        }

        if ($this->filters === [[static::RECURRENCES_FILTER, null]]) {
            return $this->getStartDate()->avoidMutation()->add(
                $this->getDateInterval()->times(
                    $this->recurrences - ($this->isStartExcluded() ? 0 : 1)
                )
            );
        }

        return null;
    }

    private function iterateUntilEnd()
    {
        $attempts = 0;
        $date = null;

        foreach ($this as $date) {
            if (++$attempts > static::END_MAX_ATTEMPTS) {
                throw new UnreachableException(
                    'Could not calculate period end after iterating '.static::END_MAX_ATTEMPTS.' times.'
                );
            }
        }

        return $date;
    }

    public function overlaps($rangeOrRangeStart, $rangeEnd = null)
    {
        $range = $rangeEnd ? static::create($rangeOrRangeStart, $rangeEnd) : $rangeOrRangeStart;

        if (!($range instanceof self)) {
            $range = static::create($range);
        }

        [$start, $end] = $this->orderCouple($this->getStartDate(), $this->calculateEnd());
        [$rangeStart, $rangeEnd] = $this->orderCouple($range->getStartDate(), $range->calculateEnd());

        return $end > $rangeStart && $rangeEnd > $start;
    }

    public function forEach(callable $callback)
    {
        foreach ($this as $date) {
            $callback($date);
        }
    }

    public function map(callable $callback)
    {
        foreach ($this as $date) {
            yield $callback($date);
        }
    }

    public function eq($period): bool
    {
        return $this->equalTo($period);
    }

    public function equalTo($period): bool
    {
        if (!($period instanceof self)) {
            $period = self::make($period);
        }

        $end = $this->getEndDate();

        return $period !== null
            && $this->getDateInterval()->eq($period->getDateInterval())
            && $this->getStartDate()->eq($period->getStartDate())
            && ($end ? $end->eq($period->getEndDate()) : $this->getRecurrences() === $period->getRecurrences())
            && ($this->getOptions() & (~static::IMMUTABLE)) === ($period->getOptions() & (~static::IMMUTABLE));
    }

    public function ne($period): bool
    {
        return $this->notEqualTo($period);
    }

    public function notEqualTo($period): bool
    {
        return !$this->eq($period);
    }

    public function startsBefore($date = null): bool
    {
        return $this->getStartDate()->lessThan($this->resolveCarbon($date));
    }

    public function startsBeforeOrAt($date = null): bool
    {
        return $this->getStartDate()->lessThanOrEqualTo($this->resolveCarbon($date));
    }

    public function startsAfter($date = null): bool
    {
        return $this->getStartDate()->greaterThan($this->resolveCarbon($date));
    }

    public function startsAfterOrAt($date = null): bool
    {
        return $this->getStartDate()->greaterThanOrEqualTo($this->resolveCarbon($date));
    }

    public function startsAt($date = null): bool
    {
        return $this->getStartDate()->equalTo($this->resolveCarbon($date));
    }

    public function endsBefore($date = null): bool
    {
        return $this->calculateEnd()->lessThan($this->resolveCarbon($date));
    }

    public function endsBeforeOrAt($date = null): bool
    {
        return $this->calculateEnd()->lessThanOrEqualTo($this->resolveCarbon($date));
    }

    public function endsAfter($date = null): bool
    {
        return $this->calculateEnd()->greaterThan($this->resolveCarbon($date));
    }

    public function endsAfterOrAt($date = null): bool
    {
        return $this->calculateEnd()->greaterThanOrEqualTo($this->resolveCarbon($date));
    }

    public function endsAt($date = null): bool
    {
        return $this->calculateEnd()->equalTo($this->resolveCarbon($date));
    }

    public function isStarted(): bool
    {
        return $this->startsBeforeOrAt();
    }

    public function isEnded(): bool
    {
        return $this->endsBeforeOrAt();
    }

    public function isInProgress(): bool
    {
        return $this->isStarted() && !$this->isEnded();
    }

    public function roundUnit($unit, $precision = 1, $function = 'round')
    {
        $self = $this->copyIfImmutable();
        $self = $self->setStartDate($self->getStartDate()->roundUnit($unit, $precision, $function));

        if ($self->endDate) {
            $self = $self->setEndDate($self->getEndDate()->roundUnit($unit, $precision, $function));
        }

        return $self->setDateInterval($self->getDateInterval()->roundUnit($unit, $precision, $function));
    }

    public function floorUnit($unit, $precision = 1)
    {
        return $this->roundUnit($unit, $precision, 'floor');
    }

    public function ceilUnit($unit, $precision = 1)
    {
        return $this->roundUnit($unit, $precision, 'ceil');
    }

    public function round($precision = null, $function = 'round')
    {
        return $this->roundWith(
            $precision ?? $this->getDateInterval()->setLocalTranslator(TranslatorImmutable::get('en'))->forHumans(),
            $function
        );
    }

    public function floor($precision = null)
    {
        return $this->round($precision, 'floor');
    }

    public function ceil($precision = null)
    {
        return $this->round($precision, 'ceil');
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function contains($date = null): bool
    {
        $startMethod = 'startsBefore'.($this->isStartIncluded() ? 'OrAt' : '');
        $endMethod = 'endsAfter'.($this->isEndIncluded() ? 'OrAt' : '');

        return $this->$startMethod($date) && $this->$endMethod($date);
    }

    public function follows($period, ...$arguments): bool
    {
        $period = $this->resolveCarbonPeriod($period, ...$arguments);

        return $this->getIncludedStartDate()->equalTo($period->getIncludedEndDate()->add($period->getDateInterval()));
    }

    public function isFollowedBy($period, ...$arguments): bool
    {
        $period = $this->resolveCarbonPeriod($period, ...$arguments);

        return $period->follows($this);
    }

    public function isConsecutiveWith($period, ...$arguments): bool
    {
        return $this->follows($period, ...$arguments) || $this->isFollowedBy($period, ...$arguments);
    }

    protected function updateInternalState()
    {
        if (!$this->hasFilter(static::END_DATE_FILTER)) {
            $this->endDate = null;
        }

        if (!$this->hasFilter(static::RECURRENCES_FILTER)) {
            $this->recurrences = null;
        }
    }

    protected function createFilterTuple(array $parameters)
    {
        $method = array_shift($parameters);

        if (!$this->isCarbonPredicateMethod($method)) {
            return [$method, array_shift($parameters)];
        }

        return [function ($date) use ($method, $parameters) {
            return ([$date, $method])(...$parameters);
        }, $method];
    }

    protected function isCarbonPredicateMethod($callable)
    {
        return \is_string($callable) && str_starts_with($callable, 'is') &&
            (method_exists($this->dateClass, $callable) || ([$this->dateClass, 'hasMacro'])($callable));
    }

    protected function filterRecurrences($current, $key)
    {
        if ($key < $this->recurrences) {
            return true;
        }

        return static::END_ITERATION;
    }

    protected function filterEndDate($current)
    {
        if (!$this->isEndExcluded() && $current == $this->endDate) {
            return true;
        }

        if ($this->dateInterval->invert ? $current > $this->endDate : $current < $this->endDate) {
            return true;
        }

        return static::END_ITERATION;
    }

    protected function endIteration()
    {
        return static::END_ITERATION;
    }

    protected function handleChangedParameters()
    {
        if (($this->getOptions() & static::IMMUTABLE) && $this->dateClass === Carbon::class) {
            $this->dateClass = CarbonImmutable::class;
        } elseif (!($this->getOptions() & static::IMMUTABLE) && $this->dateClass === CarbonImmutable::class) {
            $this->dateClass = Carbon::class;
        }

        $this->validationResult = null;
    }

    protected function validateCurrentDate()
    {
        if ($this->current === null) {
            $this->rewind();
        }

        return $this->validationResult ?? ($this->validationResult = $this->checkFilters());
    }

    protected function checkFilters()
    {
        $current = $this->prepareForReturn($this->current);

        foreach ($this->filters as $tuple) {
            $result = \call_user_func(
                $tuple[0],
                $current->avoidMutation(),
                $this->key,
                $this
            );

            if ($result === static::END_ITERATION) {
                return static::END_ITERATION;
            }

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    protected function prepareForReturn(CarbonInterface $date)
    {
        $date = ([$this->dateClass, 'make'])($date);

        if ($this->timezone) {
            $date = $date->setTimezone($this->timezone);
        }

        return $date;
    }

    protected function incrementCurrentDateUntilValid()
    {
        $attempts = 0;

        do {
            $this->current = $this->current->add($this->dateInterval);

            $this->validationResult = null;

            if (++$attempts > static::NEXT_MAX_ATTEMPTS) {
                throw new UnreachableException('Could not find next valid date.');
            }
        } while ($this->validateCurrentDate() === false);
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

    protected function resolveCarbon($date = null)
    {
        return $this->getStartDate()->nowWithSameTz()->carbonize($date);
    }

    protected function resolveCarbonPeriod($period, ...$arguments)
    {
        if ($period instanceof self) {
            return $period;
        }

        return $period instanceof DatePeriod
            ? static::instance($period)
            : static::create($period, ...$arguments);
    }

    private function orderCouple($first, $second): array
    {
        return $first > $second ? [$second, $first] : [$first, $second];
    }

    private function makeDateTime($value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (\is_string($value)) {
            $value = trim($value);

            if (!preg_match('/^P[\dT]/', $value) &&
                !preg_match('/^R\d/', $value) &&
                preg_match('/[a-z\d]/i', $value)
            ) {
                $dateClass = $this->dateClass;

                return $dateClass::parse($value, $this->tzName);
            }
        }

        return null;
    }

    private function isInfiniteDate($date): bool
    {
        return $date instanceof CarbonInterface && ($date->isEndOfTime() || $date->isStartOfTime());
    }

    private function rawDate($date): ?DateTimeInterface
    {
        if ($date === false || $date === null) {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date->isMutable()
                ? $date->toDateTime()
                : $date->toDateTimeImmutable();
        }

        if (\in_array(\get_class($date), [DateTime::class, DateTimeImmutable::class], true)) {
            return $date;
        }

        $class = $date instanceof DateTime ? DateTime::class : DateTimeImmutable::class;

        return new $class($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }

    private static function setDefaultParameters(array &$parameters, array $defaults): void
    {
        foreach ($defaults as [$index, $name, $value]) {
            if (!\array_key_exists($index, $parameters) && !\array_key_exists($name, $parameters)) {
                $parameters[$index] = $value;
            }
        }
    }
}
