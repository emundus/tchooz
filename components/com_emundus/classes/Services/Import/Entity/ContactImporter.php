<?php

namespace Tchooz\Services\Import\Entity;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Contacts\GenderEnum;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Enums\Import\FieldTypeEnum;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Services\DateParser;
use Tchooz\Services\Import\AbstractEntityImporter;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\Mapping\AliasColumnMap;
use Tchooz\Services\Import\Mapping\ColumnMap;
use Tchooz\Services\Import\UpdatableEntityImporter;

final class ContactImporter extends AbstractEntityImporter implements UpdatableEntityImporter
{
	private ?ColumnMap $columnMap = null;

	public function __construct(
		private readonly ContactRepository $contactRepository,
		private readonly CountryRepository $countryRepository,
		private readonly OrganizationRepository $organizationRepository
	) {}

	public static function create(): self
	{
		return new self(
			new ContactRepository(),
			new CountryRepository(),
			new OrganizationRepository()
		);
	}

	public function getType(): string
	{
		return ActionEnum::CONTACT->value;
	}

	public function getColumnMap(): ColumnMap
	{
		if ($this->columnMap === null)
		{
			$this->columnMap = AliasColumnMap::create()
				->field('lastname', aliases: ['Nom', 'Lastname'], required: true, examples: ['Doe'], label: Text::_('COM_EMUNDUS_IMPORT_CONTACT_LASTNAME'))
				->field('firstname', aliases: ['Prénom', 'Firstname'], required: true, examples: ['John'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_FIRSTNAME'))
				->field('email', aliases: ['email', 'e-mail', 'Email address'], required: true, type: FieldTypeEnum::EMAIL, examples: ['contact@example.com'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_EMAIL'))
				->field('fonction', aliases: ['Fonction', 'Fonctions'], examples: ['Développeur'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_FONCTION'))
				->field('service', aliases: ['Service', 'Services'], examples: ['Informatique'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_SERVICE'))
				->field('gender', aliases: ['Sexe', 'Gender', 'Genre'], type: FieldTypeEnum::ENUM, values: GenderEnum::class, label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_GENDER'))
				->field('birthdate', aliases: ['Date de naissance', 'Birthdate', 'Birthday', 'Birth date'], type: FieldTypeEnum::DATE, format: 'YYYY-MM-DD', examples: ['14-02-1983'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_BIRTHDATE'))
				->field('nationality', aliases: ['Nationalité', 'Nationality'], examples: ['FR' => 'France', 'GB' => 'United Kingdom', 'US' => 'United States'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_NATIONALITY'))
				->field('street_address', aliases: ['Adresse', 'Street address', 'Rue', 'Address'], examples: ['12 Rue des Innovateurs'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_STREET_ADDRESS'))
				->field('extended_address', aliases: ['Complément d\'adresse', 'Extended address'], examples: ['Entrée D'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_EXTENDED_ADDRESS'))
				->field('postal_code', aliases: ['Code postal', 'Postal code', 'CP', 'Zip'], type: FieldTypeEnum::INTEGER, examples: ['75011'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_POSTAL_CODE'))
				->field('locality', aliases: ['Ville', 'Locality', 'City'], examples: ['Paris'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_LOCALITY'))
				->field('region', aliases: ['Région', 'Region'], examples: ['Île-de-France'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_REGION'))
				->field('country', aliases: ['Pays', 'Country'], format: 'iso-3166-1-alpha-2', examples: ['FR' => 'France', 'GB' => 'United Kingdom', 'US' => 'United States'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_COUNTRY'))
				->field('address_description', aliases: ['Description de l\'adresse', 'Address description'], examples: ['Au bout de l’impasse'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_ADDRESS_DESCRIPTION'))
				->field('phone_1', aliases: ['phone', 'Phone number', 'Téléphone'], format: 'E.164', examples: ['+33 1 02 03 04 05'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_PHONE_1'))
				->field('organization', aliases: ['Organisation', 'Organization'], examples: ['Organisation 1'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_ORGANIZATION'))
				->field('published', aliases: ['Publié', 'Published'], type: FieldTypeEnum::BOOLEAN, examples: ['Oui'], label:  Text::_('COM_EMUNDUS_IMPORT_CONTACT_PUBLISHED'))
				->build();
		}

		return $this->columnMap;
	}

	public function validate(array $row, ImportContext $context): array
	{
		// Gender (ENUM), birthdate (DATE), email (EMAIL) and country
		// (iso-3166-1-alpha-2) are all handled generically by the pipeline's
		// TypeValidator. Business-specific rules (e.g. blocked domains, age
		// thresholds) belong here when needed.
		return [];
	}

	public function exists(array $row, ImportContext $context): bool
	{
		return $this->contactRepository->getByEmail($row['email'] ?? '') !== null;
	}

	public function persist(array $row, ImportContext $context): void
	{
		$contact = new ContactEntity(
			email: $row['email'],
			lastname: $row['lastname'],
			firstname: $row['firstname'],
			phone_1: $row['phone_1'] ?? null,
			id: 0,
			user_id: 0,
			addresses: [$this->buildAddress($row)],
			birth: DateParser::normalize($row['birthdate'] ?? null),
			gender: $row['gender'] ?? null,
			fonction: $row['fonction'] ?? null,
			service: $row['service'] ?? null,
			countries: [],
			organizations: [$this->buildOrganization($row)],
			published: !isset($row['published']) || $row['published'] !== '0',
		);

		$this->contactRepository->flush($contact);
	}

	public function update(array $row, ImportContext $context): void
	{
		$existing = $this->contactRepository->getByEmail((string) ($row['email'] ?? ''));

		if ($existing === null)
		{
			// exists() returned true a moment ago — race condition or external delete.
			throw new \RuntimeException(sprintf(
				'Cannot update contact "%s": no matching record found.',
				$row['email'] ?? '?'
			));
		}

		// SET semantics on the contact's own scalar fields. The existing entity
		// is mutated in place so its associations (addresses, countries,
		// organizations) are carried over verbatim — ContactRepository::flush()
		// re-syncs collections, and feeding it the already-loaded relations
		// keeps them untouched.
		//
		// TODO(import-update): decide how the import row should affect related
		// collections. Currently the imported "Adresse", "Pays", "Organisation"
		// columns are IGNORED on update — only the contact's own scalar fields
		// are overwritten. Open questions before changing this:
		//   - merge addresses (append the new one) or replace the set entirely?
		//   - same for countries and organizations?
		//   - what happens to existing addresses tied to other contacts?
		// Until that's decided, update() is intentionally narrow on scalars
		// only — safe but partial.
		$existing->setLastname((string) $row['lastname']);
		$existing->setFirstname((string) $row['firstname']);
		$existing->setEmail((string) $row['email']);
		$existing->setPhone1($this->stringOrNull($row['phone_1'] ?? null));
		$existing->setBirthdate(DateParser::normalize($row['birthdate'] ?? null));
		$existing->setGender(!empty($row['gender']) ? GenderEnum::from((string) $row['gender']) : null);
		$existing->setFonction($this->stringOrNull($row['fonction'] ?? null));
		$existing->setService($this->stringOrNull($row['service'] ?? null));
		$existing->setPublished(!isset($row['published']) || $row['published'] !== '0');

		if (!empty($row['status']))
		{
			$existing->setStatus(VerifiedStatusEnum::from((string) $row['status']));
		}

		$this->contactRepository->flush($existing);
	}

	private function buildAddress(array $row): ?AddressEntity
	{
		$locality       = $this->stringOrNull($row['locality']         ?? null);
		$region         = $this->stringOrNull($row['region']           ?? null);
		$streetAddress  = $this->stringOrNull($row['street_address']   ?? null);
		$extendedAddr   = $this->stringOrNull($row['extended_address'] ?? null);
		$postalCode     = $this->stringOrNull($row['postal_code']      ?? null);
		$description    = $this->stringOrNull($row['address_description'] ?? null);
		$countryIso2    = $this->stringOrNull($row['country']          ?? null);

		$hasAnyValue = $locality || $region || $streetAddress || $extendedAddr || $postalCode || $countryIso2;
		if (!$hasAnyValue)
		{
			return null;
		}

		$countryId = null;
		if ($countryIso2 !== null)
		{
			$country   = $this->countryRepository->getByIso2(strtoupper($countryIso2));
			$countryId = $country?->getId();
		}

		return new AddressEntity(
			id:               0,
			locality:         $locality,
			region:           $region,
			street_address:   $streetAddress,
			extended_address: $extendedAddr,
			postal_code:      $postalCode,
			description:      $description,
			country:          $countryId
		);
	}

	public function buildOrganization(array $row): ?OrganizationEntity
	{
		$organization = null;

		if (!empty($row['organization']))
		{
			$organization = $this->organizationRepository->getByName($row['organization']);
		}

		return $organization;
	}

	private function stringOrNull(mixed $value): ?string
	{
		if ($value === null)
		{
			return null;
		}

		$str = trim((string) $value);

		return $str === '' ? null : $str;
	}
}