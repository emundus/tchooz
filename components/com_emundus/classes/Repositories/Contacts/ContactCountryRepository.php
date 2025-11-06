<?php
/**
 * @package     Tchooz\Repositories\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Contacts;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Traits\TraitTable;

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_contacts_countries')]
readonly class ContactCountryRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.contact_country.php'], Log::ALL, ['com_emundus.repository.contact_country']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function getContactsIdsByCountryId(int $countryId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('contact_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('country_id') . ' = ' . $countryId);

		$this->db->setQuery($query);
		return $this->db->loadColumn();
	}

	public function getCountriesIdsByContactId(int $contactId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('country_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		return $this->db->loadColumn();
	}

	public function getCountriesByContactId(int $contactId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('country_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		$countries_id = $this->db->loadColumn();

		$countries = [];
		if (!empty($countries_id))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/CountryRepository.php';
			$countryRepository = new CountryRepository();

			foreach ($countries_id as $country_id)
			{
				$country = $countryRepository->getById($country_id);
				if ($country !== null)
				{
					$countries[] = $country;
				}
			}
		}

		return $countries;
	}

	public function associateContactToCountry(int $contactId, int $countryId): bool
	{
		$association = (object) [
			'contact_id' => $contactId,
			'country_id' => $countryId,
		];

		return $this->db->insertObject($this->getTableName(self::class), $association);
	}

	public function detachContactFromCountry(int $contactId, int $countryId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId)
			->where($this->db->quoteName('country_id') . ' = ' . $countryId);

		$this->db->setQuery($query);
		return (bool) $this->db->execute();
	}

	public function detachAllCountriesFromContact(int $contactId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		return (bool) $this->db->execute();
	}
}