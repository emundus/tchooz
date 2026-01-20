<?php
/**
 * @package     Tchooz\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Interfaces\FabrikTransformerInterface;
use Tchooz\Transformers\BirthdayTransformer;
use Tchooz\Transformers\ChoicesTransformer;
use Tchooz\Transformers\CurrencyTransformer;
use Tchooz\Transformers\DateTransformer;
use Tchooz\Transformers\DefaultTransformer;
use Tchooz\Transformers\IbanTransformer;
use Tchooz\Transformers\PhoneTransformer;
use Tchooz\Transformers\YesNoTransformer;

class TransformerFactory
{
	public static function make(string $plugin, array $fabrikElementParams = [], array $groupParams = [], array $translations = []): FabrikTransformerInterface
	{
		$normalized = strtolower(trim($plugin));
		if (!$pluginElement = ElementPluginEnum::tryFromString($normalized))
		{
			return new DefaultTransformer($fabrikElementParams);
		}

		return match ($normalized)
		{
			ElementPluginEnum::CHECKBOX->value, ElementPluginEnum::DROPDOWN->value, ElementPluginEnum::RADIO->value => new ChoicesTransformer($fabrikElementParams, $pluginElement, $translations),
			ElementPluginEnum::BIRTHDAY->value => new BirthdayTransformer($fabrikElementParams['details_date_format'] ?? ($fabrikElementParams['list_date_format'] ?? 'Y-m-d')),
			ElementPluginEnum::DATE->value => new DateTransformer($fabrikElementParams['date_format'] ?? 'd/m/Y H:i:s'),
			ElementPluginEnum::PHONENUMBER->value => new PhoneTransformer(),
			ElementPluginEnum::YESNO->value => new YesNoTransformer($translations),
			ElementPluginEnum::CURRENCY->value => new CurrencyTransformer(),
			ElementPluginEnum::IBAN->value => new IbanTransformer(),
			default => new DefaultTransformer($fabrikElementParams),
		};
	}
}