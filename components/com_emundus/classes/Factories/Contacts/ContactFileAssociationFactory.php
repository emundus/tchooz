<?php

namespace Tchooz\Factories\Contacts;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Contacts\ContactFileAssociationEntity;
use Tchooz\Factories\ApplicationFile\ApplicationFileFactory;
use Tchooz\Factories\DBFactory;
use Tchooz\Factories\EmundusFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Contacts\ContactRepository;

class ContactFileAssociationFactory extends EmundusFactory implements DBFactory
{
	public const CONTACT          = 'contact';
	public const APPLICATION_FILE = 'application_file';

	protected const RELATIONS = [
		self::CONTACT,
		self::APPLICATION_FILE,
	];

	/**
	 * Builds an association entity from a database row.
	 *
	 * The related contact / application file are built inline from the columns prefixed with
	 * `contact_` / `application_file_` when they are present in the row (single JOIN, no extra query).
	 * They are only loaded through their repository as a fallback, when the prefixed columns are
	 * absent AND the relation is requested via $withRelations.
	 */
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): ?ContactFileAssociationEntity
	{
		$dbObject = (array) $dbObject;

		if (empty($dbObject['id']) || empty($dbObject['contact_id']) || empty($dbObject['fnum']))
		{
			return null;
		}

		$entity = new ContactFileAssociationEntity(
			id: (int) $dbObject['id'],
			contact_id: (int) $dbObject['contact_id'],
			application_file_fnum: $dbObject['fnum'],
		);

		$relationsToLoad = $this->buildRelationsToLoad($withRelations, $exceptRelations);

		// Contact: 'contact_id' is the association FK, not a contact column, so it is excluded.
		$contactObject = $this->extractPrefixed($dbObject, 'contact_', ['contact_id']);
		if (!empty($contactObject))
		{
			$contactObject['id'] = (int) $dbObject['contact_id'];
			$entity->setContact((new ContactFactory())->fromDbObject((object) $contactObject, false));
		}
		elseif (in_array(self::CONTACT, $relationsToLoad, true))
		{
			$entity->setContact($this->loadRelation(self::CONTACT, $dbObject));
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
	 * @return array<ContactFileAssociationEntity>
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
			self::CONTACT          => (new ContactRepository(false))->getById((int) $object['contact_id']),
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
