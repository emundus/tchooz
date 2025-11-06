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
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_contacts_organizations')]
class ContactOrganizationRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'contact_organization');
	}

	public function getContactsIdsByOrganizationId(int $organizationId, ?int $isReferent = null): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('contact_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('organization_id') . ' = ' . $organizationId);

		if ($isReferent !== null)
		{
			$query->where($this->db->quoteName('is_referent_contact') . ' = ' . $isReferent);
		}

		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function getOrganizationsIdsByContactId(int $contactId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('organization_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function getOrganizationsByContactId(int $contactId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('organization_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		$organizations_id = $this->db->loadColumn();

		$organizations = [];
		if (!empty($organizations_id))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/OrganizationRepository.php';
			$organizationRepository = new OrganizationRepository($this->withRelations, $this->exceptRelations);

			foreach ($organizations_id as $organization_id)
			{
				$organization = $organizationRepository->getById($organization_id);
				if ($organization !== null)
				{
					$organizations[] = $organization;
				}
			}
		}

		return $organizations;
	}

	public function getContactsByOrganizationId(int $organizationId, ?int $isReferent = null): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('contact_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('organization_id') . ' = ' . $organizationId);

		if ($isReferent !== null)
		{
			$query->where($this->db->quoteName('is_referent_contact') . ' = ' . $isReferent);
		}

		$this->db->setQuery($query);
		$contacts_id = $this->db->loadColumn();

		$contacts = [];
		if (!empty($contacts_id))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/ContactRepository.php';
			$contactRepository = new ContactRepository($this->withRelations, $this->exceptRelations);

			foreach ($contacts_id as $contact_id)
			{
				$contact = $contactRepository->getById($contact_id);
				if ($contact !== null)
				{
					$contact->value = $contact->getId();
					$contact->name  = $contact->getFullName();
					$contacts[]     = $contact;
				}
			}
		}

		return $contacts;
	}

	public function associateContactToOrganization(int $contactId, int $organizationId, int $isReferent): bool
	{
		$association = (object) [
			'contact_id'          => $contactId,
			'organization_id'     => $organizationId,
			'is_referent_contact' => $isReferent,
		];

		return $this->db->insertObject($this->getTableName(self::class), $association);
	}

	public function detachContactFromOrganization(int $contactId, int $organizationId, int $isReferent = 0): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId)
			->where($this->db->quoteName('organization_id') . ' = ' . $organizationId)
			->where($this->db->quoteName('is_referent_contact') . ' = ' . $isReferent);

		$this->db->setQuery($query);

		return (bool) $this->db->execute();
	}

	public function detachAllContactsFromOrganization(int $organizationId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('organization_id') . ' = ' . $organizationId);

		$this->db->setQuery($query);

		return (bool) $this->db->execute();
	}

	public function detachAllOrganizationsFromContact(int $contactId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);

		return (bool) $this->db->execute();
	}

	public function flush($entity): mixed
	{
		return null;
	}

	public function delete(int $id): bool
	{
		return false;
	}

	public function getById(int $id): mixed
	{
		return null;
	}
}