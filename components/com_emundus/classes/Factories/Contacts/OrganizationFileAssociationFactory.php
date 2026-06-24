<?php

namespace Tchooz\Factories\Contacts;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Contacts\OrganizationFileAssociationEntity;
use Tchooz\Factories\ApplicationFile\ApplicationFileFactory;
use Tchooz\Factories\DBFactory;
use Tchooz\Factories\EmundusFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;

class OrganizationFileAssociationFactory extends EmundusFactory implements DBFactory
{
	public const ORGANIZATION     = 'organization';
	public const APPLICATION_FILE = 'application_file';

	protected const RELATIONS = [
		self::ORGANIZATION,
		self::APPLICATION_FILE,
	];

	/**
	 * Builds an association entity from a database row.
	 *
	 * The related organization / application file are built inline from the columns prefixed with
	 * `organization_` / `application_file_` when they are present in the row (single JOIN, no extra query).
	 * They are only loaded through their repository as a fallback, when the prefixed columns are
	 * absent AND the relation is requested via $withRelations.
	 */
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): ?OrganizationFileAssociationEntity
	{
		$dbObject = (array) $dbObject;

		if (empty($dbObject['id']) || empty($dbObject['organization_id']) || empty($dbObject['fnum']))
		{
			return null;
		}

		$entity = new OrganizationFileAssociationEntity(
			id: (int) $dbObject['id'],
			organization_id: (int) $dbObject['organization_id'],
			application_file_fnum: $dbObject['fnum'],
		);

		$relationsToLoad = $this->buildRelationsToLoad($withRelations, $exceptRelations);

		// Organization: 'organization_id' is the association FK, not an organization column, so it is excluded.
		$organizationObject = $this->extractPrefixed($dbObject, 'organization_', ['organization_id']);
		if (!empty($organizationObject))
		{
			$organizationObject['id'] = (int) $dbObject['organization_id'];
			$entity->setOrganization((new OrganizationFactory())->fromDbObject((object) $organizationObject, false));
		}
		elseif (in_array(self::ORGANIZATION, $relationsToLoad, true))
		{
			$entity->setOrganization($this->loadRelation(self::ORGANIZATION, $dbObject));
		}

		$applicationFileObject = $this->extractPrefixed($dbObject, 'application_file_');
		if (!empty($applicationFileObject))
		{
			$entity->setApplicationFile((new ApplicationFileFactory())->buildEntity((object) $applicationFileObject, false));
		}
		elseif (in_array(self::APPLICATION_FILE, $relationsToLoad, true))
		{
			$entity->setApplicationFile($this->loadRelation(self::APPLICATION_FILE, $dbObject));
		}

		return $entity;
	}

	/**
	 * @param   object|array         $dbObjects
	 * @param   bool|array           $withRelations
	 * @param   array                $exceptRelations
	 * @param   DatabaseDriver|null  $db
	 *
	 * @return array<OrganizationFileAssociationEntity>
	 */
	public function fromDbObjects(object|array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entity = $this->fromDbObject($dbObject, $withRelations, $exceptRelations, $db);
			if ($entity !== null)
			{
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	protected function loadRelation(string $relation, array $object): mixed
	{
		return match ($relation)
		{
			self::ORGANIZATION     => (new OrganizationRepository(false))->getById((int) $object['organization_id']),
			self::APPLICATION_FILE => (new ApplicationFileRepository())->getByFnum($object['fnum']),
			default                => null,
		};
	}

	/**
	 * Extracts the columns prefixed with $prefix from a row, stripping the prefix from the keys.
	 *
	 * @param   array     $row
	 * @param   string    $prefix
	 * @param   string[]  $exclude  Fully-qualified keys to ignore even if they match the prefix.
	 *
	 * @return array
	 */
	private function extractPrefixed(array $row, string $prefix, array $exclude = []): array
	{
		$extracted = [];
		foreach ($row as $key => $value)
		{
			if (in_array($key, $exclude, true))
			{
				continue;
			}
			if (str_starts_with($key, $prefix))
			{
				$extracted[substr($key, strlen($prefix))] = $value;
			}
		}

		return $extracted;
	}
}
