<?php
/**
 * @package     Tchooz\Factories\ContactFactory
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Contacts;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;
use Tchooz\Enums\Contacts\GenderEnum;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Factories\EmundusFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Comments\CommentRepository;
use Tchooz\Repositories\Contacts\ContactAddressRepository;
use Tchooz\Repositories\Contacts\ContactCountryRepository;
use Tchooz\Repositories\Contacts\ContactFileRepository;
use Tchooz\Repositories\Contacts\ContactOrganizationRepository;

class ContactFactory extends EmundusFactory implements DBFactory
{
	private const ADDRESSES = 'addresses';
	private const COUNTRIES = 'countries';
	private const ORGANIZATIONS = 'organizations';
	private const APPLICATION_FILES = 'application_files';

	private const COMMENTS = 'comments';

	/**
	 * Id of the user requesting the contacts, used to filter visible comments.
	 * Must be transmitted by the caller (controller) — never resolved from the global application here.
	 */
	private int $currentUserId = 0;

	protected const RELATIONS = [
		self::ADDRESSES,
		self::COUNTRIES,
		self::ORGANIZATIONS,
		self::APPLICATION_FILES,
		self::COMMENTS,
	];

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): ContactEntity
	{
		if (is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		$relations = $this->loadRequestedRelations($dbObject, $withRelations, $exceptRelations);

		return new ContactEntity(
			email: $dbObject['email'],
			lastname: $dbObject['lastname'],
			firstname: $dbObject['firstname'],
			phone_1: $dbObject['phone_1'] ?? null,
			id: $dbObject['id'],
			user_id: $dbObject['user_id'] ?? null,
			addresses: $relations[self::ADDRESSES] ?? [],
			birth: $dbObject['birthdate'] ?? null,
			gender: !empty($dbObject['gender']) ? GenderEnum::from($dbObject['gender']) : null,
			fonction: $dbObject['fonction'] ?? null,
			service: $dbObject['service'] ?? null,
			countries: $relations[self::COUNTRIES] ?? [],
			organizations: $relations[self::ORGANIZATIONS] ?? [],
			application_files: $relations[self::APPLICATION_FILES] ?? [],
			profile_picture: $dbObject['profile_picture'] ?? null,
			published: (bool) $dbObject['published'],
			status: !empty($dbObject['status']) ? VerifiedStatusEnum::from($dbObject['status']) : null,
			comments: $relations[self::COMMENTS] ?? []
		);
	}

	protected function loadRelationFromArray(string $relation, array $object): array
	{
		return match ($relation)
		{
			self::ADDRESSES => $this->loadAddresses($object['id']),
			self::COUNTRIES => $this->loadCountries($object['id']),
			self::ORGANIZATIONS => $this->loadOrganizations($object['id']),
			self::APPLICATION_FILES => $this->loadApplicationFiles($object['id'] ?? null),
			self::COMMENTS => $this->loadComments($object['id'] ?? null),
			default => [],
		};
	}

	private function loadAddresses(int $contactId): array
	{
		$contactAddressRepository = new ContactAddressRepository();

		return $contactAddressRepository->getAddressesByContactId($contactId);
	}

	private function loadCountries(int $contactId): array
	{
		$contactCountryRepository = new ContactCountryRepository();

		return $contactCountryRepository->getCountriesByContactId($contactId);
	}

	private function loadOrganizations(int $contactId): array
	{
		$contactOrganizationRepository = new ContactOrganizationRepository(false);

		return $contactOrganizationRepository->getOrganizationsByContactId($contactId);
	}

	private function loadApplicationFiles(?int $contactId): array
	{
		if (empty($contactId))
		{
			return [];
		}

		$contactFileRepository = new ContactFileRepository(false);

		return $contactFileRepository->getFilesByContactId($contactId);
	}

	public function setCurrentUserId(int $currentUserId): void
	{
		$this->currentUserId = $currentUserId;
	}

	private function loadComments(?int $contactId): array
	{
		if (empty($contactId) || empty($this->currentUserId))
		{
			return [];
		}

		$commentRepository = new CommentRepository();

		return $commentRepository->getCommentsByTarget($contactId, CommentTargetTypeEnum::CONTACT, $this->currentUserId);
	}
}
