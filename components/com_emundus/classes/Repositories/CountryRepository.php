<?php
/**
 * @package     Tchooz\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Country;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: 'data_country')]
class CountryRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.country.php'], Log::ALL, ['com_emundus.repository.country']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function getAllCountries(): array
	{
		$countries = [];

		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->getTableName(self::class));

		try
		{
			$this->db->setQuery($query);
			$countries = $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add('Error on get all countries : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.country');
		}

		return $countries;
	}

	public function getByIso2(string $iso2): ?Country
	{
		$country = null;

		$query = $this->db->createQuery();

		$query->select('id, label_fr as label, iso2, iso3, country_nb, continent, member, flag, flag_img')
			->from($this->getTableName(self::class))
			->where('iso2 = ' . $this->db->quote($iso2));

		try
		{
			$this->db->setQuery($query);
			$country = $this->db->loadObject();

			if ($country)
			{
				$country = new Country(
					(int) $country->id,
					(string) $country->label,
					(string) $country->iso2,
					(string) $country->iso3,
					(int) $country->country_nb,
					$country->continent ? (string) $country->continent : null,
					(bool) $country->member,
					$country->flag ? (string) $country->flag : null,
					$country->flag_img ? (string) $country->flag_img : null
				);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on get country by iso2 : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.country');
		}

		return $country;
	}

	public function getById(int $id): ?Country
	{
		$country = null;

		$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus']);
		$cache_key = 'country_' . md5($id);

		if($cache->contains($cache_key))
		{
			return $cache->get($cache_key);
		}

		$query = $this->db->createQuery();

		$query->select('id, label_fr as label, iso2, iso3, country_nb, continent, member, flag, flag_img')
			->from($this->getTableName(self::class))
			->where('id = ' . $this->db->quote($id));

		try
		{
			$this->db->setQuery($query);
			$country = $this->db->loadObject();

			if ($country)
			{
				$country = new Country(
					(int) $country->id,
					(string) $country->label,
					(string) $country->iso2,
					(string) $country->iso3,
					(int) $country->country_nb,
					$country->continent ? (string) $country->continent : null,
					(bool) $country->member,
					$country->flag ? (string) $country->flag : null,
					$country->flag_img ? (string) $country->flag_img : null
				);

				$cache->store($country, $cache_key);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on get country by iso2 : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.country');
		}

		return $country;
	}
}