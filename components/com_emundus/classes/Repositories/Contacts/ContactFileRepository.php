<?php
/**
 * @package     Tchooz\Repositories\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Contacts;

use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Contacts\ContactFileAssociationEntity;
use Tchooz\Factories\Contacts\ContactFileAssociationFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_contacts_files', alias: 'ecf', columns: [
	'id',
	'contact_id',
	'fnum',
])]
class ContactFileRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	private ContactFileAssociationFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		$this->factory = new ContactFileAssociationFactory();
		parent::__construct($withRelations, $exceptRelations, 'contact_file', self::class);
	}

	public function getFactory(): ?object
	{
		return $this->factory;
	}

	public function getContactsIdsByFileFnum(string $fnum): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('contact_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));

		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array<ContactFileAssociationEntity>
	 */
	public function getContactAssociationsByFnum(string $fnum): array
	{
		$associations = [];

		if (!empty($fnum))
		{
			// Single JOIN: every contact column is aliased with the `contact_` prefix so the factory
			// can build the full ContactEntity inline, without an extra query per association.
			$select = [$this->alias . '.*'];
			foreach ($this->getTableColumnsNoPrefix(ContactRepository::class) as $column)
			{
				$select[] = $this->db->quoteName('ec.' . $column, 'contact_' . $column);
			}

			$query = $this->db->getQuery(true)
				->select($select)
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName('#__emundus_contacts', 'ec') . ' ON ' . $this->db->quoteName('ec.id') . ' = ' . $this->db->quoteName($this->alias . '.contact_id'))
				->where($this->db->quoteName($this->alias . '.fnum') . ' = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$objects = $this->db->loadObjectList();
				$associations = $this->factory->fromDbObjects($objects, $this->withRelations, $this->exceptRelations);
			} catch (\Exception $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus.repository.contact_file');
			}
		}

		return $associations;
	}
	public function getFilesFnumByContactId(int $contactId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('fnum'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function getFilesByContactId(int $contactId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('fnum'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);
		$files_fnum = $this->db->loadColumn();

		$files = [];
		if (!empty($files_fnum))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/ApplicationFile/ApplicationFileRepository.php';
			$applicationFileRepository = new ApplicationFileRepository();

			foreach ($files_fnum as $file_fnum)
			{
				$file = $applicationFileRepository->getByFnum($file_fnum);
				if ($file !== null)
				{
					$files[] = $file;
				}
			}
		}

		return $files;
	}

	/**
	 * Synchronizes the contacts associated to a file: attaches the missing ones and detaches the others.
	 *
	 * @param   string  $fnum        Target file fnum
	 * @param   int[]   $contactIds  Desired set of contact ids associated to the file
	 *
	 * @throws \Exception when an attach/detach query fails
	 */
	public function syncContactsForFnum(string $fnum, array $contactIds): void
	{
		$contactIds = array_map('intval', $contactIds);

		$currentIds = array_map('intval', $this->getContactsIdsByFileFnum($fnum));
		$toDetach   = array_diff($currentIds, $contactIds);
		$toAttach   = array_diff($contactIds, $currentIds);

		foreach ($toDetach as $contactId)
		{
			$this->detachContactFromFileFnum($contactId, $fnum);
		}
		foreach ($toAttach as $contactId)
		{
			$this->associateContactToFileFnum($contactId, $fnum);
		}
	}

	/**
	 * Synchronizes the files associated to a contact: attaches the missing fnums and detaches the others.
	 *
	 * @param   int       $contactId  Target contact id
	 * @param   string[]  $fnums      Desired set of file fnums associated to the contact
	 *
	 * @throws \Exception when an attach/detach query fails
	 */
	public function syncFilesForContact(int $contactId, array $fnums): void
	{
		$currentFnums = $this->getFilesFnumByContactId($contactId);
		$toDetach     = array_diff($currentFnums, $fnums);
		$toAttach     = array_diff($fnums, $currentFnums);

		foreach ($toDetach as $fnum)
		{
			$this->detachContactFromFileFnum($contactId, $fnum);
		}
		foreach ($toAttach as $fnum)
		{
			$this->associateContactToFileFnum($contactId, $fnum);
		}
	}

	public function associateContactToFileFnum(int $contactId, string $fnum): bool
	{
		$association = (object) [
			'contact_id' => $contactId,
			'fnum'       => $fnum,
		];

		return $this->db->insertObject($this->getTableName(self::class), $association);
	}

	public function detachContactFromFileFnum(int $contactId, string $fnum): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId)
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
		$this->db->setQuery($query);

		return (bool) $this->db->execute();
	}

	public function detachAllFilesFnumFromContact(int $contactId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('contact_id') . ' = ' . $contactId);

		$this->db->setQuery($query);

		return (bool) $this->db->execute();
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);
		$this->db->setQuery($query);

		return (bool) $this->db->execute();
	}

	public function getById(int $id): ?ContactFileAssociationEntity
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($this->alias . '.*'))
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName($this->alias . '.id') . ' = ' . (int) $id);
		$this->db->setQuery($query);

		$object = $this->db->loadObject();

		return !empty($object) ? $this->factory->fromDbObject($object) : null;
	}
}
