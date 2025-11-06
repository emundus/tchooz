<?php
/**
 * @package     Tchooz\Repositories\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Contacts;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Factories\Contacts\OrganizationFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Services\UploadService;
use Tchooz\Traits\TraitTable;

if (!class_exists('OrganizationEntity'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/OrganizationEntity.php';
}

if (!class_exists('AddressRepository'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/AddressRepository.php';
}

if (!class_exists('ContactOrganizationRepository'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Contacts/ContactOrganizationRepository.php';
}

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_organizations')]
class OrganizationRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	private OrganizationFactory $factory;

	private const COLUMNS = [
		't.id',
		't.name',
		't.description',
		't.url_website',
		't.published',
		't.address',
		't.identifier_code',
		't.logo',
		't.status'
	];

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'organization');
		$this->factory = new OrganizationFactory();
	}

	/**
	 * @throws \Exception
	 */
	public function flush(OrganizationEntity $entity): bool
	{
		$organization_object = $entity->__serialize();
		$organization_object = (object) $organization_object;

		if (empty($organization_object->name))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ONBOARD_CRC_ORGANIZATION_NAME_NOT_SET'), 400);
		}

		// First, flush the address if exists
		if (!empty($entity->getAddress()))
		{
			$addressRepository = new AddressRepository();

			if (!$addressRepository->flush($entity->getAddress()))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_ORGANIZATION_ADDRESS_INSERT_FAILED'), 500);
			}

			$organization_object->address = $entity->getAddress()->getId();
		}
		if (!empty($entity->getStatus()))
		{
			$organization_object->status = $entity->getStatus()->value;
		}
		//

		// Then, flush the organization
		if (empty($entity->getId()))
		{
			if ($this->db->insertObject($this->getTableName(self::class), $organization_object))
			{
				$organization_id = $this->db->insertid();
				$entity->setId($organization_id);
			}
			else
			{
				throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_ORGANIZATION_INSERT_FAILED'), 500);
			}
		}
		else
		{
			if (!$this->db->updateObject($this->getTableName(self::class), $organization_object, 'id'))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_ORGANIZATION_UPDATE_FAILED'), 500);
			}
		}
		//

		// Finally, associate contacts if any
		$contactOrgRepository                 = new ContactOrganizationRepository();
		$alreadyAssociatedReferentContactsIds = $contactOrgRepository->getContactsIdsByOrganizationId($entity->getId(), 1);
		foreach ($entity->getReferentContacts() as $contact)
		{
			if (in_array($contact->getId(), $alreadyAssociatedReferentContactsIds))
			{
				continue;
			}

			if(!$contactOrgRepository->associateContactToOrganization($contact->getId(), $entity->getId(), 1))
			{
				throw new \Exception('Failed to associate contact to organization.', 500);
			}
		}

		// Detach contacts that are no longer associated
		foreach ($alreadyAssociatedReferentContactsIds as $existingContactId)
		{
			$found = false;
			foreach ($entity->getReferentContacts() as $contact)
			{
				if ($contact->getId() === $existingContactId)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				if(!$contactOrgRepository->detachContactFromOrganization($existingContactId, $entity->getId(), 1))
				{
					throw new \Exception('Failed to detach organization to contact.', 500);
				}
			}
		}

		$contactOrgRepository              = new ContactOrganizationRepository();
		$alreadyAssociatedOtherContactsIds = $contactOrgRepository->getContactsIdsByOrganizationId($entity->getId(), 0);
		foreach ($entity->getOtherContacts() as $contact)
		{
			if (in_array($contact->getId(), $alreadyAssociatedOtherContactsIds))
			{
				continue;
			}

			if(!$contactOrgRepository->associateContactToOrganization($contact->getId(), $entity->getId(), 0))
			{
				throw new \Exception('Failed to associate contact to organization.', 500);
			}
		}

		// Detach contacts that are no longer associated
		foreach ($alreadyAssociatedOtherContactsIds as $existingContactId)
		{
			$found = false;
			foreach ($entity->getOtherContacts() as $contact)
			{
				if ($contact->getId() === $existingContactId)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				if(!$contactOrgRepository->detachContactFromOrganization($existingContactId, $entity->getId(), 0))
				{
					throw new \Exception('Failed to detach organization to contact.', 500);
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

			// First, delete the address if exists
			$query->select('address')
				->from($this->getTableName(self::class))
				->where('id = ' . $id);
			$this->db->setQuery($query);
			$address_id = $this->db->loadResult();

			if (!empty($address_id))
			{
				$addressRepository = new AddressRepository();
				$addressRepository->delete($address_id);
			}
			//

			// Then, detach all contacts associated if foreign keys with cascade delete are not set
			$contactOrgRepository = new ContactOrganizationRepository();
			$contactOrgRepository->detachAllContactsFromOrganization($id);
			//

			if ($this->deleteLogo($id))
			{

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
					Log::add('Error on delete organization : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.organization');
				}
			}
		}

		return $deleted;
	}

	public function deleteLogo(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->createQuery();

			$query->select('logo')
				->from($this->getTableName(self::class))
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$logo_path = $this->db->loadResult();

				if (!empty($logo_path))
				{
					$uploader = new UploadService('images/emundus/organizations/');
					$deleted  = $uploader->deleteFile($logo_path);
				}
				else
				{
					$deleted = true;
				}

				if ($deleted)
				{
					$update = $this->db->createQuery();
					$update->update($this->getTableName(self::class))
						->set($this->db->quoteName('logo') . ' = ' . $this->db->quote(''))
						->where('id = ' .  $id);

					$this->db->setQuery($update)->execute();
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error on delete organization logo : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.organization');
			}
		}

		return $deleted;
	}

	public function togglePublished(int $organization_id, bool $published): bool
	{
		$toggled = false;

		if (!empty($organization_id))
		{
			$query = $this->db->createQuery();

			$query->update($this->getTableName(self::class))
				->set('published = ' . (int) $published)
				->where('id = ' . $organization_id);

			try
			{
				$this->db->setQuery($query);
				$toggled = (bool) $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error on toggle published organization : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.organization');
			}
		}

		return $toggled;
	}

	public function getAllOrganizations(
		$sort = 'DESC',
		$search = '',
		$lim = 25,
		$page = 0,
		$order_by = 't.id',
		$published = null,
		$ids = [],
		$identifier_code = null,
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
			->from($this->db->quoteName($this->getTableName(self::class), 't'));

		// Apply filters if needed
		if (!empty($search))
		{
			$search     = $this->db->quote('%' . $this->db->escape($search, true) . '%', false);
			$conditions = [
				$this->db->quoteName('t.name') . ' LIKE ' . $search,
				$this->db->quoteName('t.description') . ' LIKE ' . $search,
				$this->db->quoteName('t.url_website') . ' LIKE ' . $search,
				$this->db->quoteName('t.identifier_code') . ' LIKE ' . $search,
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

		if (!empty($identifier_code))
		{
			if ($identifier_code === 'no_identifier_code')
			{
				$query->having('(t.identifier_code IS NULL OR t.identifier_code = "")');
			}
			else
			{
				$query->where('t.identifier_code = ' . $this->db->quote($identifier_code));
			}
		}

		// Apply orders and limits if needed
		$query->group('t.id')
			->order($order_by . ' ' . $sort);

		try
		{
			$this->db->setQuery($query);
			$organizations_count = sizeof($this->db->loadObjectList());

			$this->db->setQuery($query, $offset, $limit);
			$organizations = $this->db->loadObjectList();

			foreach ($organizations as $key => $organization)
			{
				$organizations[$key] = $this->factory->fromDbObject($organization, $this->withRelations, $this->exceptRelations);
			}

			$result = array('datas' => $organizations, 'count' => $organizations_count);
		}
		catch (\Exception $e)
		{
			Log::add('Error on get all organizations : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.organization');
		}

		return $result;
	}

	public function getById(int $id): ?OrganizationEntity
	{
		$organization_entity = null;

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where($this->db->quoteName('id') . ' = ' . $id);

		$this->db->setQuery($query);
		$organization = $this->db->loadAssoc();

		if (!empty($organization))
		{
			$organization_entity = $this->factory->fromDbObject($organization, $this->withRelations, $this->exceptRelations);
		}

		return $organization_entity;
	}

	public function getByIds(array $ids): array
	{
		$ids = array_filter($ids, fn($id) => !empty($id));

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where($this->db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');

		$this->db->setQuery($query);
		$organizations = $this->db->loadAssocList();

		return array_map(
			fn($org) => $this->factory->fromDbObject($org, $this->withRelations, $this->exceptRelations),
			$organizations
		);
	}


	public function getFilteredOrganizations(): array
	{
		$organizations   = [];
		$organizations[] = (object) [
			'value' => 'no_organization',
			'label' => Text::_('COM_EMUNDUS_ONBOARD_ORG_FILTER_NO_ORGANIZATION')
		];

		$query = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select([
					$this->db->quoteName('eo.id', 'value'),
					'eo.name AS label'
				])
				->from($this->db->quoteName('#__emundus_organizations', 'eo'))
				->order('eo.name ASC');

			$this->db->setQuery($query);
			$dbResults = $this->db->loadObjectList();

			if (!empty($dbResults))
			{
				$organizations = array_merge($organizations, $dbResults);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error while getting organizations: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.organization');
		}

		return $organizations;
	}

	public function getFilteredOrganizationsByIdentifierCode(): array
	{
		$identifiers = [];
		$query       = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select([
					$this->db->quoteName('eo.identifier_code', 'value'),
					'eo.identifier_code AS label'
				])
				->from($this->db->quoteName('#__emundus_organizations', 'eo'))
				->where($this->db->quoteName('eo.identifier_code') . ' IS NOT NULL')
				->where($this->db->quoteName('eo.identifier_code') . " != ''")
				->group($this->db->quoteName('eo.identifier_code'));

			$this->db->setQuery($query);
			$identifiers = $this->db->loadObjectList();

			$identifiers[] = (object) [
				'value' => 'no_identifier_code',
				'label' => Text::_('COM_EMUNDUS_ONBOARD_ORG_FILTER_NO_IDENTIFIER_CODE')
			];
		}
		catch (\Exception $e)
		{
			Log::add('Error while getting organizations identifier: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.organization');
		}

		return $identifiers;
	}
}