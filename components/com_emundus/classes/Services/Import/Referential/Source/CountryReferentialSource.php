<?php
/**
 * @package     Tchooz\Services\Import\Referential\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Referential\Source;

use Joomla\CMS\Language\Text;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Services\Import\Referential\AbstractReferentialProvider;

/**
 * Referential of countries, keyed by ISO 3166-1 alpha-2 code and labeled by
 * country name — matching how importers resolve a country (getByIso2).
 */
final class CountryReferentialSource extends AbstractReferentialProvider
{
	public const KEY = 'countries';

	public static function create(): self
	{
		return new self();
	}

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_IMPORT_REFERENTIAL_COUNTRIES');
	}

	protected function loadEntries(): array
	{
		$entries = [];

		foreach ((new CountryRepository())->getAllCountries() as $country)
		{
			if (empty($country->iso2))
			{
				continue;
			}

			$entries[] = [
				'value' => (string) $country->iso2,
				'label' => (string) ($country->label_fr ?? $country->iso2),
			];
		}

		return $entries;
	}
}