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
use Tchooz\Entities\Contacts\OrganizationFileAssociationEntity;
use Tchooz\Factories\Contacts\OrganizationFileAssociationFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_organizations_files', alias: 'eof', columns: [
	'id',
	'organization_id',
	'fnum',
])]
class OrganizationFileRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	private OrganizationFileAssociationFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		$this->factory = new OrganizationFileAssociationFactory();
		parent::__construct($withRelations, $exceptRelations, 'organization_file', self::class);
	}

	public function getFactory(): ?object
	{
		return $this->factory;
	}

	public function getOrganizationsIdsByFileFnum(string $fnum): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('organization_id'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array<OrganizationFileAssociationEntity>
	 */
	public function getOrganizationAssociationsByFnum(string $fnum): array
	{
		$associations = [];

		if (!empty($fnum))
		{
			// Single JOIN: every organization column is aliased with the `organization_` prefix so the factory
			// can build the full OrganizationEntity inline, without an extra query per association.
			$select = [$this->alias . '.*'];
			foreach ($this->getTableColumnsNoPrefix(OrganizationRepository::class) as $column)
			{
				$select[] = $this->db->quoteName('eo.' . $column, 'organization_' . $column);
			}

			$query = $this->db->getQuery(true)
				->select($select)
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName('#__emundus_organizations', 'eo') . ' ON ' . $this->db->quoteName('eo.id') . ' = ' . $this->db->quoteName($this->alias . '.organization_id'))
				->where($this->db->quoteName($this->alias . '.fnum') . ' = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$objects = $this->db->loadObjectList();

				// Relations loading (e.g. application_file) is decided by the caller via the repository's $withRelations.
				$associations = $this->factory->fromDbObjects($objects, $this->withRelations, $this->exceptRelations);
			} catch (\Exception $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus.repository.organization_file');
			}
		}

		return $associations;
	}

	public function getFilesFnumByOrganizationId(int $organizationId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('fnum'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('organization_id') . ' = ' . $organizationId);

		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	public function getFilesByOrganizationId(int $organizationId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('fnum'))
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('organization_id') . ' = ' . $organizationId);

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
	 * Synchronizes the organizations associated to a file: attaches the missing ones and detaches the others.
	 *
	 * @param   string  $fnum             Target file fnum
	 * @param   int[]   $organizationIds  Desired set of organization ids associated to the file
	 *
	 * @throws \Exception when an attach/detach query fails
	 */
	public function syncOrganizationsForFnum(string $fnum, array $organizationIds): void
	{
		$organizationIds = array_map('intval', $organizationIds);

		$currentIds = array_map('intval', $this->getOrganizationsIdsByFileFnum($fnum));
		$toDetach   = array_diff($currentIds, $organizationIds);
		$toAttach   = array_diff($organizationIds, $currentIds);

		foreach ($toDetach as $organizationId)
		{
			$this->detachOrganizationFromFileFnum($organizationId, $fnum);
		}
		foreach ($toAttach as $organizationId)
		{
			$this->associateOrganizationToFileFnum($organizationId, $fnum);
		}
	}

	/**
	 * Synchronizes the files associated to an organization: attaches the missing fnums and detaches the others.
	 *
	 * @param   int       $organizationId  Target organization id
	 * @param   string[]  $fnums           Desired set of file fnums associated to the organization
	 *
	 * @throws \Exception when an attach/detach query fails
	 */
	public function syncFilesForOrganization(int $organizationId, array $fnums): void
	{
		$currentFnums = $this->getFilesFnumByOrganizationId($organizationId);
		$toDetach     = array_diff($currentFnums, $fnums);
		$toAttach     = array_diff($fnums, $currentFnums);

		foreach ($toDetach as $fnum)
		{
			$this->detachOrganizationFromFileFnum($organizationId, $fnum);
		}
		foreach ($toAttach as $fnum)
		{
			$this->associateOrganizationToFileFnum($organizationId, $fnum);
		}
	}

	public function associateOrganizationToFileFnum(int $organizationId, string $fnum): bool
	{
		$association = (object) [
			'organization_id' => $organizationId,
			'fnum'            => $fnum,
		];

		return $this->db->insertObject($this->getTableName(self::class), $association);
	}

	public function detachOrganizationFromFileFnum(int $organizationId, string $fnum): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('organization_id') . ' = ' . $organizationId)
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
		$this->db->setQuery($query);

		return (bool) $this->db->execute();
	}

	public function detachAllFilesFnumFromOrganization(int $organizationId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('organization_id') . ' = ' . $organizationId);

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

	public function getById(int $id): ?OrganizationFileAssociationEntity
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
