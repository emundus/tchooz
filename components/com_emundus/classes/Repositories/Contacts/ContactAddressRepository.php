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
use Tchooz\Entities\Contacts\ContactAddressEntity;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Traits\TraitTable;

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_contacts_address')]
class ContactAddressRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.contact_address.php'], Log::ALL, ['com_emundus.repository.contact_address']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function flush(ContactAddressEntity $relation): bool
	{
		$flushed = false;

		if(!empty($relation->getContact()) && !empty($relation->getAddress()))
		{
			$insert = (object) [
				'contact_id' => $relation->getContact()->getId(),
				'address_id' => $relation->getAddress()->getId(),
			];

			$flushed = $this->db->insertObject($this->getTableName(self::class), $insert);
		}

		return $flushed;
	}

	public function getAllAddressesIdsByContactId(int $contactId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('address_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		return $this->db->loadColumn();
	}

	public function getAddressesByContactId(int $contactId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('address_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		$addresses_id = $this->db->loadColumn();

		$addresses = [];
		if (!empty($addresses_id))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/AddressRepository.php';
			$addressRepository = new AddressRepository();

			foreach ($addresses_id as $address_id)
			{
				$address = $addressRepository->getById($address_id);
				if ($address !== null)
				{
					$addresses[] = $address;
				}
			}
		}

		return $addresses;
	}

	public function detachAllAddressesFromContact(int $contactId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		return (bool) $this->db->execute();
	}

	public function detachAddressIdFromContact(int $contactId, int $addressId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('address_id') . ' = ' . $addressId)
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		return (bool) $this->db->execute();
	}

}