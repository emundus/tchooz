<?php
/**
 * @package     Tchooz\Transformers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Transformers;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberTransformer
{
	public static function toE164(?string $phoneNumber, ?string $defaultRegion = null): ?string
	{
		$parsedNumber = self::parse($phoneNumber, $defaultRegion);

		if ($parsedNumber === null)
		{
			return null;
		}

		return PhoneNumberUtil::getInstance()->format($parsedNumber, PhoneNumberFormat::E164);
	}

	/**
	 * National format of the number, digits only (a french number becomes 0612345678).
	 * Meant for third party APIs refusing international formats.
	 */
	public static function toNational(?string $phoneNumber, ?string $defaultRegion = null): ?string
	{
		$parsedNumber = self::parse($phoneNumber, $defaultRegion);

		if ($parsedNumber === null)
		{
			return null;
		}

		// Extensions have no place in a national number and would be glued to it once separators are dropped
		$parsedNumber->clearExtension();

		$national = PhoneNumberUtil::getInstance()->format($parsedNumber, PhoneNumberFormat::NATIONAL);

		return preg_replace('/\D/', '', $national);
	}

	/**
	 * Region the number belongs to (FR, BE, ...), null when it cannot be determined.
	 */
	public static function getRegionCode(?string $phoneNumber, ?string $defaultRegion = null): ?string
	{
		$parsedNumber = self::parse($phoneNumber, $defaultRegion);

		if ($parsedNumber === null)
		{
			return null;
		}

		return PhoneNumberUtil::getInstance()->getRegionCodeForNumber($parsedNumber);
	}

	public static function isValid(?string $phoneNumber, ?string $defaultRegion = null): bool
	{
		return self::parse($phoneNumber, $defaultRegion) !== null;
	}

	/**
	 * Parse and validate a number. The region can be carried by the value itself as a two letter
	 * prefix (FR0612345678), it then takes precedence over $defaultRegion.
	 */
	private static function parse(?string $phoneNumber, ?string $defaultRegion = null): ?PhoneNumber
	{
		$phoneNumber = trim((string) $phoneNumber);

		if ($phoneNumber === '')
		{
			return null;
		}

		$region = $defaultRegion;

		if (preg_match('/^[A-Za-z]{2}/', $phoneNumber))
		{
			$region      = substr($phoneNumber, 0, 2);
			$phoneNumber = substr($phoneNumber, 2);
		}

		if (!class_exists(PhoneNumberUtil::class))
		{
			require_once JPATH_LIBRARIES . '/emundus/vendor/autoload.php';
		}

		$phoneUtil = PhoneNumberUtil::getInstance();

		try
		{
			$parsedNumber = $phoneUtil->parse($phoneNumber, $region);
		}
		catch (NumberParseException $e)
		{
			return null;
		}

		if (!$phoneUtil->isValidNumber($parsedNumber))
		{
			return null;
		}

		return $parsedNumber;
	}
}
