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
		$contact_object = $contact->__serialize();
		$contact_object = (object) $contact_object;

		if(empty($contact_object->email))
		{
			throw new \Exception('Contact email not set.', 400);
		}

		if(empty($contact->getId()))
		{
			$contact_object->user_id = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($contact_object->email)->id;
			if(empty($contact_object->user_id)) {
				$contact_object->user_id = null;
			}

			if($this->db->insertObject($this->getTableName(self::class), $contact_object))
			{
				return $this->db->insertid();
			}
			else
			{
				throw new \Exception('Failed to insert contact.', 500);
			}
		}

		if(!$this->db->updateObject($this->getTableName(self::class), $contact_object, 'id'))
		{
			throw new \Exception('Failed to update contact.', 500);
		}

		return $contact->getId();
	}

	public function getByEmail(string $email): ?ContactEntity
	{
		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->getTableName(self::class))
			->where('email = ' . $this->db->quote($email));
		$this->db->setQuery($query);
		$contact = $this->db->loadAssoc();

		if(!empty($contact))
		{
			return new ContactEntity($contact['email'], $contact['lastname'], $contact['firstname'], $contact['phone_1'], $contact['id']);
		}

		return null;
	}

	public function getById(int $id): ?ContactEntity
	{
		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->getTableName(self::class))
			->where('id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$contact = $this->db->loadAssoc();

		if(!empty($contact))
		{
			return new ContactEntity($contact['email'], $contact['lastname'], $contact['firstname'], $contact['phone_1'], $contact['id']);
		}

		return null;
	}
}