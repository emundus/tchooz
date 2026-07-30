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
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberTransformer
{
	public static function toE164(?string $phoneNumber): ?string
	{
		$phoneNumber = trim((string) $phoneNumber);

		if ($phoneNumber === '')
		{
			return null;
		}

		$region = null;

		if (preg_match('/^[A-Za-z]{2}/', $phoneNumber))
		{
			$region      = substr($phoneNumber, 0, 2);
			$phoneNumber = substr($phoneNumber, 2);
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

		return $phoneUtil->format($parsedNumber, PhoneNumberFormat::E164);
	}
}