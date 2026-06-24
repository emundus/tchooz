<?php
/**
 * @package     Tchooz\Factories\OrganizationFactory
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Contacts;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Factories\EmundusFactory;
use Tchooz\Repositories\Contacts\AddressRepository;
use Tchooz\Repositories\Contacts\ContactOrganizationRepository;
use Tchooz\Repositories\Contacts\OrganizationFileRepository;

class OrganizationFactory extends EmundusFactory implements DBFactory
{
	private const ADDRESS = 'address';
	private const REFERENT_CONTACTS = 'referent_contacts';
	private const OTHER_CONTACTS = 'other_contacts';
	private const APPLICATION_FILES = 'application_files';
	protected const RELATIONS = [
		self::ADDRESS,
		self::REFERENT_CONTACTS,
		self::OTHER_CONTACTS,
		self::APPLICATION_FILES,
	];

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): OrganizationEntity
	{
		if (is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		$relations = $this->loadRequestedRelations($dbObject, $withRelations, $exceptRelations);

		return new OrganizationEntity(
			id: $dbObject['id'],
			name: $dbObject['name'],
			description: $dbObject['description'] ?? null,
			url_website: $dbObject['url_website'] ?? null,
			address: $relations[self::ADDRESS] ?? null,
			identifier_code: $dbObject['identifier_code'] ?? null,
			logo: $dbObject['logo'] ?? null,
			referent_contacts: $relations[self::REFERENT_CONTACTS] ?? [],
			other_contacts: $relations[self::OTHER_CONTACTS] ?? [],
			published: (bool) $dbObject['published'],
			status: $dbObject['status'] ? VerifiedStatusEnum::from($dbObject['status']) : VerifiedStatusEnum::VERIFIED,
			application_files: $relations[self::APPLICATION_FILES] ?? [],
		);
	}

	protected function loadRelation(string $relation, array $object): array|AddressEntity|null
	{
		return match ($relation)
		{
			self::ADDRESS => $this->loadAddress($object['address']),
			self::REFERENT_CONTACTS => $this->loadReferentContacts($object['id']),
			self::OTHER_CONTACTS => $this->loadOtherContacts($object['id']),
			self::APPLICATION_FILES => $this->loadApplicationFiles($object['id'] ?? null),
			default => [],
		};
	}

	private function loadAddress(?int $addressId): ?AddressEntity
	{
		if (empty($addressId))
		{
			return null;
		}
		$addressRepository = new AddressRepository();

		return $addressRepository->getById($addressId);
	}

	private function loadReferentContacts(int $contactId): array
	{
		$contactOrganizationRepository = new ContactOrganizationRepository(false);

		return $contactOrganizationRepository->getContactsByOrganizationId($contactId, 1);
	}

	private function loadOtherContacts(int $contactId): array
	{
		$contactOrganizationRepository = new ContactOrganizationRepository(false);

		return $contactOrganizationRepository->getContactsByOrganizationId($contactId, 0);
	}

	private function loadApplicationFiles(?int $organizationId): array
	{
		if (empty($organizationId))
		{
			return [];
		}

		$organizationFileRepository = new OrganizationFileRepository(false);

		return $organizationFileRepository->getFilesByOrganizationId($organizationId);
	}


}