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

use BadMethodCallException;
use Carbon\Exceptions\BadComparisonUnitException;
use Carbon\Exceptions\ImmutableException;
use Carbon\Exceptions\InvalidDateException;
use Carbon\Exceptions\InvalidFormatException;
use Carbon\Exceptions\UnknownGetterException;
use Carbon\Exceptions\UnknownMethodException;
use Carbon\Exceptions\UnknownSetterException;
use Closure;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use ReflectionException;
use ReturnTypeWillChange;
use Symfony\Component\Translation\TranslatorInterface;
use Throwable;

interface CarbonInterface extends DateTimeInterface, JsonSerializable
{
    public const NO_ZERO_DIFF = 01;
    public const JUST_NOW = 02;
    public const ONE_DAY_WORDS = 04;
    public const TWO_DAY_WORDS = 010;
    public const SEQUENTIAL_PARTS_ONLY = 020;
    public const ROUND = 040;
    public const FLOOR = 0100;
    public const CEIL = 0200;

    public const DIFF_ABSOLUTE = 1; // backward compatibility with true
    public const DIFF_RELATIVE_AUTO = 0; // backward compatibility with false
    public const DIFF_RELATIVE_TO_NOW = 2;
    public const DIFF_RELATIVE_TO_OTHER = 3;

    public const TRANSLATE_MONTHS = 1;
    public const TRANSLATE_DAYS = 2;
    public const TRANSLATE_UNITS = 4;
    public const TRANSLATE_MERIDIEM = 8;
    public const TRANSLATE_DIFF = 0x10;
    public const TRANSLATE_ALL = self::TRANSLATE_MONTHS | self::TRANSLATE_DAYS | self::TRANSLATE_UNITS | self::TRANSLATE_MERIDIEM | self::TRANSLATE_DIFF;

    public const SUNDAY = 0;
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;

    public const JANUARY = 1;
    public const FEBRUARY = 2;
    public const MARCH = 3;
    public const APRIL = 4;
    public const MAY = 5;
    public const JUNE = 6;
    public const JULY = 7;
    public const AUGUST = 8;
    public const SEPTEMBER = 9;
    public const OCTOBER = 10;
    public const NOVEMBER = 11;
    public const DECEMBER = 12;

    public const YEARS_PER_MILLENNIUM = 1000;
    public const YEARS_PER_CENTURY = 100;
    public const YEARS_PER_DECADE = 10;
    public const MONTHS_PER_YEAR = 12;
    public const MONTHS_PER_QUARTER = 3;
    public const QUARTERS_PER_YEAR = 4;
    public const WEEKS_PER_YEAR = 52;
    public const WEEKS_PER_MONTH = 4;
    public const DAYS_PER_YEAR = 365;
    public const DAYS_PER_WEEK = 7;
    public const HOURS_PER_DAY = 24;
    public const MINUTES_PER_HOUR = 60;
    public const SECONDS_PER_MINUTE = 60;
    public const MILLISECONDS_PER_SECOND = 1000;
    public const MICROSECONDS_PER_MILLISECOND = 1000;
    public const MICROSECONDS_PER_SECOND = 1000000;

    public const WEEK_DAY_AUTO = 'auto';

    public const RFC7231_FORMAT = 'D, d M Y H:i:s \G\M\T';

    public const DEFAULT_TO_STRING_FORMAT = 'Y-m-d H:i:s';

    public const MOCK_DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    public const ISO_FORMAT_REGEXP = '(O[YMDHhms]|[Hh]mm(ss)?|Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|Qo?|YYYYYY|YYYYY|YYYY|YY?|g{1,5}|G{1,5}|e|E|a|A|hh?|HH?|kk?|mm?|ss?|S{1,9}|x|X|zz?|ZZ?)';


    public function __call($method, $parameters);

    public static function __callStatic($method, $parameters);

    public function __clone();

    public function __construct($time = null, $tz = null);

    public function __debugInfo();

    public function __get($name);

    public function __isset($name);

    public function __set($name, $value);

    #[ReturnTypeWillChange]
    public static function __set_state($dump);

    public function __sleep();

    public function __toString();

    #[ReturnTypeWillChange]
    public function add($unit, $value = 1, $overflow = null);

    public function addRealUnit($unit, $value = 1);

    public function addUnit($unit, $value = 1, $overflow = null);

    public function addUnitNoOverflow($valueUnit, $value, $overflowUnit);

    public function ago($syntax = null, $short = false, $parts = 1, $options = null);

    public function average($date = null);

    public function avoidMutation();

    public function between($date1, $date2, $equal = true): bool;

    public function betweenExcluded($date1, $date2): bool;

    public function betweenIncluded($date1, $date2): bool;

    public function calendar($referenceTime = null, array $formats = []);

    public static function canBeCreatedFromFormat($date, $format);

    public function carbonize($date = null);

    public function cast(string $className);

    public function ceil($precision = 1);

    public function ceilUnit($unit, $precision = 1);

    public function ceilWeek($weekStartsAt = null);

    public function change($modifier);

    public function cleanupDumpProperties();

    public function clone();

    public function closest($date1, $date2);

    public function copy();

    public static function create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null);

    public static function createFromDate($year = null, $month = null, $day = null, $tz = null);

    #[ReturnTypeWillChange]
    public static function createFromFormat($format, $time, $tz = null);

    public static function createFromIsoFormat($format, $time, $tz = null, $locale = 'en', $translator = null);

    public static function createFromLocaleFormat($format, $locale, $time, $tz = null);

    public static function createFromLocaleIsoFormat($format, $locale, $time, $tz = null);

    public static function createFromTime($hour = 0, $minute = 0, $second = 0, $tz = null);

    public static function createFromTimeString($time, $tz = null);

    public static function createFromTimestamp($timestamp, $tz = null);

    public static function createFromTimestampMs($timestamp, $tz = null);

    public static function createFromTimestampMsUTC($timestamp);

    public static function createFromTimestampUTC($timestamp);

    public static function createMidnightDate($year = null, $month = null, $day = null, $tz = null);

    public static function createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null);

    public static function createStrict(?int $year = 0, ?int $month = 1, ?int $day = 1, ?int $hour = 0, ?int $minute = 0, ?int $second = 0, $tz = null);

    public function dayOfYear($value = null);

    public function diffAsCarbonInterval($date = null, $absolute = true, array $skip = []);

    public function diffFiltered(CarbonInterval $ci, Closure $callback, $date = null, $absolute = true);

    public function diffForHumans($other = null, $syntax = null, $short = false, $parts = 1, $options = null);

    public function diffInDays($date = null, $absolute = true);

    public function diffInDaysFiltered(Closure $callback, $date = null, $absolute = true);

    public function diffInHours($date = null, $absolute = true);

    public function diffInHoursFiltered(Closure $callback, $date = null, $absolute = true);

    public function diffInMicroseconds($date = null, $absolute = true);

    public function diffInMilliseconds($date = null, $absolute = true);

    public function diffInMinutes($date = null, $absolute = true);

    public function diffInMonths($date = null, $absolute = true);

    public function diffInQuarters($date = null, $absolute = true);

    public function diffInRealHours($date = null, $absolute = true);

    public function diffInRealMicroseconds($date = null, $absolute = true);

    public function diffInRealMilliseconds($date = null, $absolute = true);

    public function diffInRealMinutes($date = null, $absolute = true);

    public function diffInRealSeconds($date = null, $absolute = true);

    public function diffInSeconds($date = null, $absolute = true);

    public function diffInWeekdays($date = null, $absolute = true);

    public function diffInWeekendDays($date = null, $absolute = true);

    public function diffInWeeks($date = null, $absolute = true);

    public function diffInYears($date = null, $absolute = true);

    public static function disableHumanDiffOption($humanDiffOption);

    public static function enableHumanDiffOption($humanDiffOption);

    public function endOf($unit, ...$params);

    public function endOfCentury();

    public function endOfDay();

    public function endOfDecade();

    public function endOfHour();

    public function endOfMillennium();

    public function endOfMinute();

    public function endOfMonth();

    public function endOfQuarter();

    public function endOfSecond();

    public function endOfWeek($weekEndsAt = null);

    public function endOfYear();

    public function eq($date): bool;

    public function equalTo($date): bool;

    public static function executeWithLocale($locale, $func);

    public function farthest($date1, $date2);

    public function firstOfMonth($dayOfWeek = null);

    public function firstOfQuarter($dayOfWeek = null);

    public function firstOfYear($dayOfWeek = null);

    public function floatDiffInDays($date = null, $absolute = true);

    public function floatDiffInHours($date = null, $absolute = true);

    public function floatDiffInMinutes($date = null, $absolute = true);

    public function floatDiffInMonths($date = null, $absolute = true);

    public function floatDiffInRealDays($date = null, $absolute = true);

    public function floatDiffInRealHours($date = null, $absolute = true);

    public function floatDiffInRealMinutes($date = null, $absolute = true);

    public function floatDiffInRealMonths($date = null, $absolute = true);

    public function floatDiffInRealSeconds($date = null, $absolute = true);

    public function floatDiffInRealWeeks($date = null, $absolute = true);

    public function floatDiffInRealYears($date = null, $absolute = true);

    public function floatDiffInSeconds($date = null, $absolute = true);

    public function floatDiffInWeeks($date = null, $absolute = true);

    public function floatDiffInYears($date = null, $absolute = true);

    public function floor($precision = 1);

    public function floorUnit($unit, $precision = 1);

    public function floorWeek($weekStartsAt = null);

    public function formatLocalized($format);

    public function from($other = null, $syntax = null, $short = false, $parts = 1, $options = null);

    public function fromNow($syntax = null, $short = false, $parts = 1, $options = null);

    public static function fromSerialized($value);

    public static function genericMacro($macro, $priority = 0);

    public function get($name);

    public function getAltNumber(string $key): string;

    public static function getAvailableLocales();

    public static function getAvailableLocalesInfo();

    public function getCalendarFormats($locale = null);

    public static function getDays();

    public function getDaysFromStartOfWeek(?int $weekStartsAt = null): int;

    public static function getFallbackLocale();

    public static function getFormatsToIsoReplacements();

    public static function getHumanDiffOptions();

    public function getIsoFormats($locale = null);

    public static function getIsoUnits();

    #[ReturnTypeWillChange]
    public static function getLastErrors();

    public function getLocalMacro($name);

    public function getLocalTranslator();

    public static function getLocale();

    public static function getMacro($name);

    public static function getMidDayAt();

    public function getOffsetString($separator = ':');

    public function getPaddedUnit($unit, $length = 2, $padString = '0', $padType = 0);

    public function getPreciseTimestamp($precision = 6);

    public function getSettings();

    public static function getTestNow();

    public static function getTimeFormatByPrecision($unitPrecision);

    public function getTimestampMs();

    public function getTranslatedDayName($context = null, $keySuffix = '', $defaultValue = null);

    public function getTranslatedMinDayName($context = null);

    public function getTranslatedMonthName($context = null, $keySuffix = '', $defaultValue = null);

    public function getTranslatedShortDayName($context = null);

    public function getTranslatedShortMonthName($context = null);

    public function getTranslationMessage(string $key, ?string $locale = null, ?string $default = null, $translator = null);

    public static function getTranslationMessageWith($translator, string $key, ?string $locale = null, ?string $default = null);

    public static function getTranslator();

    public static function getWeekEndsAt();

    public static function getWeekStartsAt();

    public static function getWeekendDays();

    public function greaterThan($date): bool;

    public function greaterThanOrEqualTo($date): bool;

    public function gt($date): bool;

    public function gte($date): bool;

    public static function hasFormat($date, $format);

    public static function hasFormatWithModifiers($date, $format): bool;

    public function hasLocalMacro($name);

    public function hasLocalTranslator();

    public static function hasMacro($name);

    public static function hasRelativeKeywords($time);

    public static function hasTestNow();

    public static function instance($date);

    public function is(string $tester);

    public function isAfter($date): bool;

    public function isBefore($date): bool;

    public function isBetween($date1, $date2, $equal = true): bool;

    public function isBirthday($date = null);

    public function isCurrentUnit($unit);

    public function isDayOfWeek($dayOfWeek);

    public function isEndOfDay($checkMicroseconds = false);

    public function isEndOfTime(): bool;

    public function isFuture();

    public static function isImmutable();

    public function isLastOfMonth();

    public function isLeapYear();

    public function isLongIsoYear();

    public function isLongYear();

    public function isMidday();

    public function isMidnight();

    public static function isModifiableUnit($unit);

    public static function isMutable();

    public function isPast();

    public function isSameAs($format, $date = null);

    public function isSameMonth($date = null, $ofSameYear = true);

    public function isSameQuarter($date = null, $ofSameYear = true);

    public function isSameUnit($unit, $date = null);

    public function isStartOfDay($checkMicroseconds = false);

    public function isStartOfTime(): bool;

    public static function isStrictModeEnabled();

    public function isToday();

    public function isTomorrow();

    public function isWeekday();

    public function isWeekend();

    public function isYesterday();

    public function isoFormat(string $format, ?string $originalFormat = null): string;

    public function isoWeek($week = null, $dayOfWeek = null, $dayOfYear = null);

    public function isoWeekYear($year = null, $dayOfWeek = null, $dayOfYear = null);

    public function isoWeekday($value = null);

    public function isoWeeksInYear($dayOfWeek = null, $dayOfYear = null);

    #[ReturnTypeWillChange]
    public function jsonSerialize();

    public function lastOfMonth($dayOfWeek = null);

    public function lastOfQuarter($dayOfWeek = null);

    public function lastOfYear($dayOfWeek = null);

    public function lessThan($date): bool;

    public function lessThanOrEqualTo($date): bool;

    public function locale(?string $locale = null, ...$fallbackLocales);

    public static function localeHasDiffOneDayWords($locale);

    public static function localeHasDiffSyntax($locale);

    public static function localeHasDiffTwoDayWords($locale);

    public static function localeHasPeriodSyntax($locale);

    public static function localeHasShortUnits($locale);

    public function lt($date): bool;

    public function lte($date): bool;

    public static function macro($name, $macro);

    public static function make($var);

    public function max($date = null);

    public static function maxValue();

    public function maximum($date = null);

    public function meridiem(bool $isLower = false): string;

    public function midDay();

    public function min($date = null);

    public static function minValue();

    public function minimum($date = null);

    public static function mixin($mixin);

    #[ReturnTypeWillChange]
    public function modify($modify);

    public function ne($date): bool;

    public function next($modifier = null);

    public function nextWeekday();

    public function nextWeekendDay();

    public function notEqualTo($date): bool;

    public static function now($tz = null);

    public function nowWithSameTz();

    public function nthOfMonth($nth, $dayOfWeek);

    public function nthOfQuarter($nth, $dayOfWeek);

    public function nthOfYear($nth, $dayOfWeek);

    public function ordinal(string $key, ?string $period = null): string;

    public static function parse($time = null, $tz = null);

    public static function parseFromLocale($time, $locale = null, $tz = null);

    public static function pluralUnit(string $unit): string;

    public function previous($modifier = null);

    public function previousWeekday();

    public function previousWeekendDay();

    public function range($end = null, $interval = null, $unit = null);

    public function rawAdd(DateInterval $interval);

    public static function rawCreateFromFormat($format, $time, $tz = null);

    public function rawFormat($format);

    public static function rawParse($time = null, $tz = null);

    public function rawSub(DateInterval $interval);

    public static function resetMacros();

    public static function resetMonthsOverflow();

    public static function resetToStringFormat();

    public static function resetYearsOverflow();

    public function round($precision = 1, $function = 'round');

    public function roundUnit($unit, $precision = 1, $function = 'round');

    public function roundWeek($weekStartsAt = null);

    public function secondsSinceMidnight();

    public function secondsUntilEndOfDay();

    public function serialize();

    public static function serializeUsing($callback);

    public function set($name, $value = null);

    #[ReturnTypeWillChange]
    public function setDate($year, $month, $day);

    public function setDateFrom($date = null);

    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0, $microseconds = 0);

    public function setDateTimeFrom($date = null);

    public function setDaysFromStartOfWeek(int $numberOfDays, ?int $weekStartsAt = null);

    public static function setFallbackLocale($locale);

    public static function setHumanDiffOptions($humanDiffOptions);

    #[ReturnTypeWillChange]
    public function setISODate($year, $week, $day = 1);

    public function setLocalTranslator(TranslatorInterface $translator);

    public static function setLocale($locale);

    public static function setMidDayAt($hour);

    public static function setTestNow($testNow = null);

    public static function setTestNowAndTimezone($testNow = null, $tz = null);

    #[ReturnTypeWillChange]
    public function setTime($hour, $minute, $second = 0, $microseconds = 0);

    public function setTimeFrom($date = null);

    public function setTimeFromTimeString($time);

    #[ReturnTypeWillChange]
    public function setTimestamp($unixTimestamp);

    #[ReturnTypeWillChange]
    public function setTimezone($value);

    public static function setToStringFormat($format);

    public static function setTranslator(TranslatorInterface $translator);

    public function setUnit($unit, $value = null);

    public function setUnitNoOverflow($valueUnit, $value, $overflowUnit);

    public static function setUtf8($utf8);

    public static function setWeekEndsAt($day);

    public static function setWeekStartsAt($day);

    public static function setWeekendDays($days);

    public function settings(array $settings);

    public function shiftTimezone($value);

    public static function shouldOverflowMonths();

    public static function shouldOverflowYears();

    public function since($other = null, $syntax = null, $short = false, $parts = 1, $options = null);

    public static function singularUnit(string $unit): string;

    public function startOf($unit, ...$params);

    public function startOfCentury();

    public function startOfDay();

    public function startOfDecade();

    public function startOfHour();

    public function startOfMillennium();

    public function startOfMinute();

    public function startOfMonth();

    public function startOfQuarter();

    public function startOfSecond();

    public function startOfWeek($weekStartsAt = null);

    public function startOfYear();

    #[ReturnTypeWillChange]
    public function sub($unit, $value = 1, $overflow = null);

    public function subRealUnit($unit, $value = 1);

    public function subUnit($unit, $value = 1, $overflow = null);

    public function subUnitNoOverflow($valueUnit, $value, $overflowUnit);

    public function subtract($unit, $value = 1, $overflow = null);

    public function timespan($other = null, $timezone = null);

    public function timestamp($unixTimestamp);

    public function timezone($value);

    public function to($other = null, $syntax = null, $short = false, $parts = 1, $options = null);

    public function toArray();

    public function toAtomString();

    public function toCookieString();

    public function toDate();

    public function toDateString();

    public function toDateTime();

    public function toDateTimeImmutable();

    public function toDateTimeLocalString($unitPrecision = 'second');

    public function toDateTimeString($unitPrecision = 'second');

    public function toDayDateTimeString();

    public function toFormattedDateString();

    public function toFormattedDayDateString(): string;

    public function toISOString($keepOffset = false);

    public function toImmutable();

    public function toIso8601String();

    public function toIso8601ZuluString($unitPrecision = 'second');

    public function toJSON();

    public function toMutable();

    public function toNow($syntax = null, $short = false, $parts = 1, $options = null);

    public function toObject();

    public function toPeriod($end = null, $interval = null, $unit = null);

    public function toRfc1036String();

    public function toRfc1123String();

    public function toRfc2822String();

    public function toRfc3339String($extended = false);

    public function toRfc7231String();

    public function toRfc822String();

    public function toRfc850String();

    public function toRssString();

    public function toString();

    public function toTimeString($unitPrecision = 'second');

    public function toW3cString();

    public static function today($tz = null);

    public static function tomorrow($tz = null);

    public function translate(string $key, array $parameters = [], $number = null, ?TranslatorInterface $translator = null, bool $altNumbers = false): string;

    public function translateNumber(int $number): string;

    public static function translateTimeString($timeString, $from = null, $to = null, $mode = self::TRANSLATE_ALL);

    public function translateTimeStringTo($timeString, $to = null);

    public static function translateWith(TranslatorInterface $translator, string $key, array $parameters = [], $number = null): string;

    public function translatedFormat(string $format): string;

    public function tz($value = null);

    public function unix();

    public function until($other = null, $syntax = null, $short = false, $parts = 1, $options = null);

    public static function useMonthsOverflow($monthsOverflow = true);

    public static function useStrictMode($strictModeEnabled = true);

    public static function useYearsOverflow($yearsOverflow = true);

    public function utc();

    public function utcOffset(?int $minuteOffset = null);

    public function valueOf();

    public function week($week = null, $dayOfWeek = null, $dayOfYear = null);

    public function weekYear($year = null, $dayOfWeek = null, $dayOfYear = null);

    public function weekday($value = null);

    public function weeksInYear($dayOfWeek = null, $dayOfYear = null);

    public static function withTestNow($testNow, $callback);

    public static function yesterday($tz = null);

}
