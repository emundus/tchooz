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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactAddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Country;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Factories\Contacts\ContactFactory;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Services\UploadService;
use Tchooz\Traits\TraitTable;

if (!class_exists('ContactEntity'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/ContactEntity.php';
}

if (!class_exists('ContactOrganizationRepository'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/ContactOrganizationRepository.php';
}

if (!class_exists('ContactAddressRepository'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/ContactAddressRepository.php';
}

if (!class_exists('ContactAddressEntity'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/ContactAddressEntity.php';
}

if (!class_exists('AddressRepository'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/AddressRepository.php';
}

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_contacts')]
class ContactRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	private ContactFactory $factory;

	private const COLUMNS = [
		't.id',
		't.lastname',
		't.firstname',
		't.email',
		't.phone_1',
		't.user_id',
		't.birthdate',
		't.gender',
		't.fonction',
		't.service',
		't.published',
		't.profile_picture',
		't.status'
	];

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'contact');
		$this->factory = new ContactFactory();
	}

	/**
	 * @throws \Exception
	 */
	public function flush(ContactEntity $entity): bool
	{
		$contact_object = $entity->__serialize();
		$contact_object = (object) $contact_object;

		if (empty($contact_object->email))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_EMAIL_NOT_SET'), 400);
		}

		if (empty($contact_object->user_id))
		{
			$contact_object->user_id = null;
		}

		// Add control to avoid duplicate email
		$existing_contact = $this->getByEmail($contact_object->email);
		if ($existing_contact && $existing_contact->getId() != $entity->getId())
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_EMAIL_ALREADY_EXISTS'), 400);
		}

		if (!empty($entity->getGender()))
		{
			$contact_object->gender = $entity->getGender()->value;
		}

		if (!empty($entity->getStatus()))
		{
			$contact_object->status = $entity->getStatus()->value;
		}
		else {
			$contact_object->status = VerifiedStatusEnum::TO_BE_VERIFIED->value;
		}

		if (empty($entity->getId()))
		{
			$contact_object->user_id = empty($contact_object->user_id) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($contact_object->email)->id : $contact_object->user_id;
			if (empty($contact_object->user_id))
			{
				$contact_object->user_id = null;
			}

			if ($this->db->insertObject($this->getTableName(self::class), $contact_object))
			{
				$contact_id = $this->db->insertid();
				$entity->setId($contact_id);
			}
			else
			{
				throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_INSERT_FAILED'), 500);
			}
		}
		else
		{
			if (!$this->db->updateObject($this->getTableName(self::class), $contact_object, 'id'))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_UPDATE_FAILED'), 500);
			}
		}

		// First, flush the address if exists
		$addressRepository        = new AddressRepository();
		$contactAddressRepository = new ContactAddressRepository();
		$contact_addresses        = $contactAddressRepository->getAllAddressesIdsByContactId($entity->getId());

		if (!empty($entity->getAddresses()))
		{
			foreach ($entity->getAddresses() as $key => $address)
			{
				if (!($address instanceof AddressEntity))
				{
					continue;
				}

				if (!$addressRepository->flush($address))
				{
					throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_ADDRESS_INSERT_FAILED'), 500);
				}

				$entity->getAddresses()[$key]->setId($address->getId());

				if (!in_array($address->getId(), $contact_addresses))
				{
					$contact_address = new ContactAddressEntity($entity, $address);
					if (!$contactAddressRepository->flush($contact_address))
					{
						throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_ASSOCIATE_TO_ADDRESS_FAILED'), 500);
					}
				}
			}
		}

		// Delete addresses that are no longer associated
		foreach ($contact_addresses as $existingAddress)
		{
			$found = false;
			foreach ($entity->getAddresses() as $address)
			{
				if ($address->getId() === $existingAddress)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				if(!$contactAddressRepository->detachAddressIdFromContact($entity->getId(), $existingAddress))
				{
					throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_ADDRESS_DETACH_TO_CONTACT_FAILED'), 500);
				}
				else
				{
					if(!$addressRepository->delete($existingAddress))
					{
						throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_ADDRESS_DELETE_FAILED'), 500);
					}
				}
			}
		}

		// Then, flush countries if any
		$countryRepository        = new CountryRepository();
		$contactCountryrepository = new ContactCountryRepository();
		$contact_countries        = $contactCountryrepository->getCountriesIdsByContactId($entity->getId());

		if (!empty($entity->getCountries()))
		{
			foreach ($entity->getCountries() as $country)
			{
				if (!($country instanceof Country))
				{
					continue;
				}

				$country_in_db = $countryRepository->getByIso2($country->getIso2());
				if ($country_in_db && !in_array($country_in_db->getId(), $contact_countries))
				{
					if (!$contactCountryrepository->associateContactToCountry($entity->getId(), $country_in_db->getId()))
					{
						throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_ASSOCIATE_TO_COUNTRY_FAILED'), 500);
					}
				}
			}
		}

		// Detach countries that are no longer associated
		foreach ($contact_countries as $existingCountryId)
		{
			$found = false;
			foreach ($entity->getCountries() as $country)
			{
				if ($country->getId() === $existingCountryId)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				if(!$contactCountryrepository->detachContactFromCountry($entity->getId(), $existingCountryId))
				{
					throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_COUNTRY_DETACH_TO_CONTACT_FAILED'), 500);
				}
			}
		}

		// Finally, flush organizations if any
		$contactOrganizationRepository     = new ContactOrganizationRepository();
		$alreadyAssociatedOrganizationsIds = $contactOrganizationRepository->getOrganizationsIdsByContactId($entity->getId());

		if (!empty($entity->getOrganizations()))
		{
			foreach ($entity->getOrganizations() as $organization)
			{
				if (!in_array($organization->getId(), $alreadyAssociatedOrganizationsIds))
				{
					if(!$contactOrganizationRepository->associateContactToOrganization($entity->getId(), $organization->getId(), 0))
					{
						throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_CONTACT_ASSOCIATE_TO_ORGANIZATION_FAILED'), 500);
					}
				}
			}
		}

		// Detach organizations that are no longer associated
		foreach ($alreadyAssociatedOrganizationsIds as $existingOrganizationId)
		{
			$found = false;
			foreach ($entity->getOrganizations() as $organization)
			{
				if ($organization->getId() === $existingOrganizationId)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				if(!$contactOrganizationRepository->detachContactFromOrganization($entity->getId(), $existingOrganizationId))
				{
					throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_ORGANIZATION_DETACH_TO_CONTACT_FAILED'), 500);
				}
			}
		}

		// If false, an exception is throw before
		return true;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->createQuery();

			$addressRepository        = new AddressRepository();
			$contactAddressRepository = new ContactAddressRepository();

			// First, delete addresses associated
			$addresses = $contactAddressRepository->getAllAddressesIdsByContactId($id);
			foreach ($addresses as $address)
			{
				$addressRepository->delete($address);
			}

			// If foreign keys with cascade delete are not set, we need to detach all addresses associated
			$contactAddressRepository->detachAllAddressesFromContact($id);
			//

			// Then, detach all organizations associated if foreign keys with cascade delete are not set
			$contactOrgRepository = new ContactOrganizationRepository();
			$contactOrgRepository->detachAllOrganizationsFromContact($id);
			//

			// Then, detach all countries associated if foreign keys with cascade delete are not set
			$contactCountryRepository = new ContactCountryRepository();
			$contactCountryRepository->detachAllCountriesFromContact($id);
			//

			$query->clear()
				->delete($this->getTableName(self::class))
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$deleted = (bool) $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error on delete contact : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.contact');
			}
		}

		return $deleted;
	}

	public function togglePublished(int $contact_id, bool $published): bool
	{
		$toggled = false;

		if (!empty($contact_id))
		{
			$query = $this->db->createQuery();

			$query->update($this->getTableName(self::class))
				->set('published = ' . (int) $published)
				->where('id = ' . $contact_id);

			try
			{
				$this->db->setQuery($query);
				$toggled = (bool) $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error on toggle published contact : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.contact');
			}
		}

		return $toggled;
	}

	/**
	 * @param   int  $user_id
	 *
	 * @return ContactEntity if found, just return it, else create a new contact from user data and return it
	 * @throws \Exception
	 */
	public function getOrCreateContactFromUserId(int $user_id): ContactEntity
	{
		$contact = $this->getByUserId($user_id);

		if (empty($contact))
		{
			$query = $this->db->createQuery();

			$query->select($this->db->quoteName(['jeu.user_id', 'jeu.firstname', 'jeu.lastname', 'ju.email']))
				->from($this->db->quoteName('jos_emundus_users', 'jeu'))
				->leftJoin($this->db->quoteName('jos_users', 'ju') . ' ON ' . $this->db->quoteName('jeu.user_id') . ' = ' . $this->db->quoteName('ju.id'))
				->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));

			$this->db->setQuery($query);
			$contactObject = $this->db->loadObject();

			if (!empty($contactObject))
			{
				$contact = new ContactEntity($contactObject->email, $contactObject->lastname, $contactObject->firstname, null, 0, $user_id);
				$this->flush($contact);
			}
		}

		return $contact;
	}

	public function getAllContacts(
		$sort = 'DESC',
		$search = '',
		$lim = 25,
		$page = 0,
		$order_by = 't.id',
		$published = null,
		$ids = [],
		$phone_number = '',
		$organizations = [],
		$nationalities = []
	): array
	{
		$result = [
			'datas' => [],
			'count' => 0,
		];

		if (empty($lim) || $lim == 'all')
		{
			$limit = '';
		}
		else
		{
			$limit = $lim;
		}

		if (empty($page) || empty($limit))
		{
			$offset = 0;
		}
		else
		{
			$offset = ($page - 1) * $limit;
		}

		if (empty($sort))
		{
			$sort = 'DESC';
		}

		$query = $this->db->createQuery();

		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->leftJoin($this->db->quoteName('#__emundus_contacts_organizations', 'eco') . ' ON ' . $this->db->quoteName('eco.contact_id') . ' = ' . $this->db->quoteName('t.id'))
			->leftJoin($this->db->quoteName('#__emundus_contacts_countries', 'ecc') . ' ON ' . $this->db->quoteName('ecc.contact_id') . ' = ' . $this->db->quoteName('t.id'));
		// Apply filters if needed
		if (!empty($search))
		{
			$search     = $this->db->quote('%' . $this->db->escape($search, true) . '%', false);
			$conditions = [
				$this->db->quoteName('t.firstname') . ' LIKE ' . $search,
				$this->db->quoteName('t.lastname') . ' LIKE ' . $search,
				$this->db->quoteName('t.email') . ' LIKE ' . $search,
				$this->db->quoteName('t.phone_1') . ' LIKE ' . $search,
				$this->db->quoteName('t.fonction') . ' LIKE ' . $search,
				$this->db->quoteName('t.service') . ' LIKE ' . $search,
			];
			$query->where('(' . implode(' OR ', $conditions) . ')');
		}

		if (!empty($published) && $published !== 'all')
		{
			$published = $published == 'true' ? 1 : 0;
			$query->where($this->db->quoteName('t.published') . ' = ' . $published);
		}

		if (!empty($ids) && is_array($ids))
		{
			$query->where($this->db->quoteName('t.id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
		}

		if (!empty($organizations) && is_array($organizations))
		{
			$has_no_organization = in_array('no_organization', $organizations, true);
			$org_ids             = array_filter($organizations, fn($id) => $id !== 'no_organization');

			$org_conditions = [];

			if (!empty($org_ids))
			{
				$org_conditions[] = $this->db->quoteName('eco.organization_id') . ' IN (' . implode(',', array_map('intval', $org_ids)) . ')';
			}

			if ($has_no_organization)
			{
				$org_conditions[] = $this->db->quoteName('eco.organization_id') . ' IS NULL';
			}

			if (!empty($org_conditions))
			{
				$query->where('(' . implode(' OR ', $org_conditions) . ')');
			}
		}

		if (!empty($nationalities) && is_array($nationalities))
		{
			$has_no_nationality = in_array('no_nationality', $nationalities, true);
			$nat_ids            = array_filter($nationalities, fn($id) => $id !== 'no_nationality');

			$nat_conditions = [];

			if (!empty($nat_ids))
			{
				$nat_conditions[] = $this->db->quoteName('ecc.country_id') . ' IN (' . implode(',', array_map('intval', $nat_ids)) . ')';
			}

			if ($has_no_nationality)
			{
				$nat_conditions[] = $this->db->quoteName('ecc.country_id') . ' IS NULL';
			}

			if (!empty($nat_conditions))
			{
				$query->where('(' . implode(' OR ', $nat_conditions) . ')');
			}
		}


		if (!empty($phone_number))
		{
			if ($phone_number === 'no_phone_number')
			{
				$query->having('(t.phone_1 IS NULL OR t.phone_1 = "")');
			}
			else
			{
				$phone_number = preg_replace('/\s+/', '', $phone_number);
				$phone_number = $this->db->quote('%' . $phone_number . '%');
				$query->where('REPLACE(t.phone_1, " ", "") LIKE ' . $phone_number);
			}
		}

		// Apply orders and limits if needed
		$query->group('t.id')
			->order($order_by . ' ' . $sort);

		try
		{
			$this->db->setQuery($query);
			$contacts_count = sizeof($this->db->loadObjectList());

			$this->db->setQuery($query, $offset, $limit);
			$contacts = $this->db->loadObjectList();

			foreach ($contacts as $key => $contact)
			{
				$contacts[$key] = $this->factory->fromDbObject($contact, $this->withRelations, $this->exceptRelations);
			}

			$result = array('datas' => $contacts, 'count' => $contacts_count);
		}
		catch (\Exception $e)
		{
			Log::add('Error on get all contacts : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.contacts');
		}

		return $result;
	}

	public function getById(int $id): ?ContactEntity
	{
		$contact_entity = null;

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->leftJoin($this->db->quoteName($this->getTableName(ContactAddressRepository::class), 'j1') . ' ON ' . $this->db->quoteName('j1.contact_id') . ' = ' . $this->db->quoteName('t.id'))
			->where('t.id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$contact = $this->db->loadAssoc();

		if (!empty($contact))
		{
			$contact_entity = $this->factory->fromDbObject($contact, $this->withRelations, $this->exceptRelations);
		}

		return $contact_entity;
	}

	public function getByEmail(string $email): ?ContactEntity
	{
		$contact_entity = null;

		$query = $this->db->getQuery(true);

		$query->select('id')
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where('email = ' . $this->db->quote($email));
		$this->db->setQuery($query);
		$contact_id = $this->db->loadResult();

		if (!empty($contact_id))
		{
			$contact_entity = $this->getById($contact_id);
		}

		return $contact_entity;
	}

	public function getByUserId(int $user_id): ?ContactEntity
	{
		$contact_entity = null;

		$query = $this->db->getQuery(true);

		$query->select('id')
			->from($this->getTableName(self::class))
			->where('user_id = ' . $this->db->quote($user_id));
		$this->db->setQuery($query);
		$contact_id = $this->db->loadResult();


		if (!empty($contact_id))
		{
			$contact_entity = $this->getById($contact_id);
		}

		return $contact_entity;
	}

	public function getFilteredContacts(): array
	{
		$contacts = [];
		$query    = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select([$this->db->quoteName('ec.id', 'value'), 'CONCAT(ec.lastname," ",ec.firstname) as label'])
				->from($this->db->quoteName('#__emundus_contacts', 'ec'));
			$this->db->setQuery($query);
			$contacts = $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			Log::add('Error while getting contacts: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.contact');
		}

		return $contacts;
	}

	public function getFilteredContactsByPhoneNumber(): array
	{
		$phone_numbers = [];
		$query         = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select([
					$this->db->quoteName('ec.phone_1', 'value'),
					'ec.phone_1 AS label'
				])
				->from($this->db->quoteName('#__emundus_contacts', 'ec'))
				->where($this->db->quoteName('ec.phone_1') . ' IS NOT NULL')
				->where($this->db->quoteName('ec.phone_1') . " != ''")
				->group($this->db->quoteName('ec.phone_1'));

			$this->db->setQuery($query);
			$phone_numbers = $this->db->loadObjectList();

			$phone_numbers[] = (object) [
				'value' => 'no_phone_number',
				'label' => Text::_('COM_EMUNDUS_ONBOARD_CONTACT_FILTER_NO_PHONE_NUMBER')
			];
		}
		catch (\Exception $e)
		{
			Log::add('Error while getting contacts phone numbers: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.contact');
		}

		return $phone_numbers;
	}

	public function deleteProfilePicture(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->createQuery();

			$query->select('profile_picture')
				->from($this->getTableName(self::class))
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$profile_picture_path = $this->db->loadResult();

				if (!empty($profile_picture_path))
				{
					$uploader = new UploadService('images/emundus/contacts/');
					$deleted  = $uploader->deleteFile($profile_picture_path);
				}
				else
				{
					$deleted = true;
				}

				if ($deleted)
				{
					$update = $this->db->createQuery();
					$update->update($this->getTableName(self::class))
						->set('profile_picture = NULL')
						->where('id = ' .  $id);

					$this->db->setQuery($update)->execute();
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error on delete contact profile picture : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.contact');
			}
		}

		return $deleted;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return ContactEntity|null
	 */
	public function getByFnum(string $fnum): ?ContactEntity
	{
		$contact_entity = null;

		if (!empty($fnum))
		{
			$query = $this->db->getQuery(true);
			$query->select('c.*')
				->from($this->db->quoteName($this->getTableName(self::class), 'c'))
				->join('inner', $this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.applicant_id') . ' = ' . $this->db->quoteName('c.user_id'))
				->where('ecc.fnum = ' . $this->db->quote($fnum));

			$this->db->setQuery($query);
			$contact = $this->db->loadAssoc();

			if (!empty($contact))
			{
				$contact_entity = $this->factory->fromDbObject($contact);
			}
		}

		return $contact_entity;
	}
}