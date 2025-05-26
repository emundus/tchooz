<?php
/**
 * @package     Tchooz\Repositories\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Contacts;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Traits\TraitTable;
use Tchooz\Entities\Contacts\ContactAddressEntity;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;

if (!class_exists('ContactEntity'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/ContactEntity.php';
}

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_contacts')]
readonly class ContactRepository
{
	use TraitTable;

	public function __construct(private DatabaseInterface $db)
	{}

	public function flush(ContactEntity $contact): int
	{
		$contact_id = null;

		$contact_object = $contact->__serialize();
		$contact_object = (object) $contact_object;

		if(empty($contact_object->email))
		{
			throw new \Exception('Contact email not set.', 400);
		}

		if (empty($contact->getId()))
		{
			$contact_object->user_id = empty($contact_object->user_id) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($contact_object->email)->id : $contact_object->user_id;
			if(empty($contact_object->user_id)) {
				$contact_object->user_id = null;
			}

			if ($this->db->insertObject($this->getTableName(self::class), $contact_object))
			{
				$contact_id = $this->db->insertid();
				$contact->setId($contact_id);
			}
			else
			{
				throw new \Exception('Failed to insert contact.', 500);
			}
		}

		if (!$this->db->updateObject($this->getTableName(self::class), $contact_object, 'id'))
		{
			throw new \Exception('Failed to update contact.', 500);
		} else {
			$contact_id = $contact->getId();
		}

		if (!empty($contact->getAddress()))
		{
			$address = $contact->getAddress();
			$address_object = $address->__serialize();
			$address_object = (object) $address_object;

			if (empty($address->getId()))
			{
				$address_object->contact_id = $contact_id;
				if ($this->db->insertObject('#__emundus_contacts_address', $address_object))
				{
					$address_id = $this->db->insertid();
				}
				else
				{
					throw new \Exception('Failed to insert contact address.', 500);
				}
			}

			if (!$this->db->updateObject('#__emundus_contacts_address', $address_object, 'id'))
			{
				throw new \Exception('Failed to update contact address.', 500);
			}
		}

		return $contact_id;
	}

	public function getByEmail(string $email): ?ContactEntity
	{
		$contact_entity = null;

		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->getTableName(self::class))
			->where('email = ' . $this->db->quote($email));
		$this->db->setQuery($query);
		$contact = $this->db->loadAssoc();

		if(!empty($contact))
		{
			$contact_entity = new ContactEntity($contact['email'], $contact['lastname'], $contact['firstname'], $contact['phone_1'], $contact['id'], $contact['user_id']);

			$address = $this->getContactAddress($contact['id']);
			if($address)
			{
				$contact_entity->setAddress($address);
			}
		}

		return $contact_entity;
	}

	public function getById(int $id): ?ContactEntity
	{
		$contact_entity = null;

		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->getTableName(self::class))
			->where('id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$contact = $this->db->loadAssoc();

		if (!empty($contact))
		{
			$contact_entity = new ContactEntity($contact['email'], $contact['lastname'], $contact['firstname'], $contact['phone_1'], $contact['id'], $contact['user_id']);

			$address = $this->getContactAddress($contact['id']);
			if ($address)
			{
				$contact_entity->setAddress($address);
			}
		}

		return $contact_entity;
	}

	public function getByUserId(int $user_id): ?ContactEntity
	{
		$contact_entity = null;

		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->getTableName(self::class))
			->where('user_id = ' . $this->db->quote($user_id));
		$this->db->setQuery($query);
		$contact = $this->db->loadAssoc();

		if (!empty($contact))
		{
			$contact_entity = new ContactEntity($contact['email'], $contact['lastname'], $contact['firstname'], $contact['phone_1'], $contact['id'], $contact['user_id']);

			$address = $this->getContactAddress($contact['id']);
			if ($address)
			{
				$contact_entity->setAddress($address);
			}
		}

		return $contact_entity;
	}

	public function getContactAddress(int $contact_id): ?ContactAddressEntity
	{
		$contact_address_entity = null;

		if (!empty($contact_id)) {
			$query = $this->db->getQuery(true);

			$query->select('*')
				->from('#__emundus_contacts_address')
				->where('contact_id = ' . $this->db->quote($contact_id));

			$this->db->setQuery($query);
			$address = $this->db->loadAssoc();
			if (!empty($address))
			{
				if (!class_exists('ContactAddressEntity'))
				{
					require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/ContactAddressEntity.php';
				}
				$contact_address_entity = new ContactAddressEntity($contact_id, $address['address1'], $address['address2'], $address['city'], $address['state'], $address['zip'], $address['country'], $address['id']);
			}
		}

		return $contact_address_entity;
	}
}