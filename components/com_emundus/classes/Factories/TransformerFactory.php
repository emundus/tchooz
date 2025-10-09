<?php
/**
 * @package     Tchooz\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

use Tchooz\Enums\Fabrik\ElementPlugin;
use Tchooz\Interfaces\FabrikTransformerInterface;
use Tchooz\Transformers\BirthdayTransformer;
use Tchooz\Transformers\CheckboxTransformer;
use Tchooz\Transformers\ChoicesTransformer;
use Tchooz\Transformers\CurrencyTransformer;
use Tchooz\Transformers\DefaultTransformer;
use Tchooz\Transformers\PhoneTransformer;
use Tchooz\Transformers\YesNoTransformer;

class TransformerFactory
{
	public static function make(string $plugin, array $fabrikElementParams = [], array $groupParams = []): FabrikTransformerInterface
	{
		$normalized = strtolower(trim($plugin));
		if(!$pluginElement = ElementPlugin::tryFromString($normalized))
		{
			return new DefaultTransformer();
		}

		return match ($normalized) {
			ElementPlugin::CHECKBOX->value, ElementPlugin::DROPDOWN->value, ElementPlugin::RADIO->value => new ChoicesTransformer($fabrikElementParams, $pluginElement),
			ElementPlugin::BIRTHDAY->value => new BirthdayTransformer($fabrikElementParams['details_date_format'] ?? ($fabrikElementParams['list_date_format'] ?? 'Y-m-d')),
			ElementPlugin::PHONENUMBER->value => new PhoneTransformer(),
			ElementPlugin::YESNO->value => new YesNoTransformer(),
			default => new DefaultTransformer(),
		};
	}
}