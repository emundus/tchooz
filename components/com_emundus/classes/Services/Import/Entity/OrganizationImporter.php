<?php
/**
 * @package     Tchooz\Services\Import\Entity
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Entity;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Enums\Import\FieldTypeEnum;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Services\Import\AbstractEntityImporter;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\Mapping\AliasColumnMap;
use Tchooz\Services\Import\Mapping\ColumnMap;
use Tchooz\Services\Import\UpdatableEntityImporter;

/**
 * Imports organizations from a tabular source.
 *
 * Persistence is delegated to OrganizationRepository::flush(), which already
 * handles its own address upsert. The pipeline wraps this call in a database
 * transaction, so any throw rolls back both the organization row and its
 * address row.
 *
 * Required canonical fields: name.
 * The organization is considered an existing duplicate when either:
 *   - a row with the same identifier_code is found, or
 *   - a row with the same name is found.
 */
final class OrganizationImporter extends AbstractEntityImporter implements UpdatableEntityImporter
{
	private ?ColumnMap $columnMap = null;

	public function __construct(
		private readonly OrganizationRepository $organizationRepository,
		private readonly CountryRepository      $countryRepository
	) {}

	public static function create(): self
	{
		return new self(new OrganizationRepository(), new CountryRepository());
	}

	public function getType(): string
	{
		return ActionEnum::ORGANIZATION->value;
	}

	public function getColumnMap(): ColumnMap
	{
		if ($this->columnMap === null)
		{
			$this->columnMap = AliasColumnMap::create()
				->field('name', aliases: ['Nom', 'Name', 'Organisation', 'Organization'], required: true, examples: ['Organisation 1'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_NAME'))
				->field('description', aliases: ['Description'], examples: ['Entreprise technologique spécialisée dans le développement de solutions logicielles sur mesure.'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_DESCRIPTION'))
				->field('identifier_code', aliases: ['Code identifiant', 'Identifier code', 'SIRET', 'SIREN'], examples: ['841 256 789 00012'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_IDENTIFIER_CODE'))
				->field('url_website', aliases: ['Site web', 'Website', 'URL'], type: FieldTypeEnum::URL, examples: ['https://example.com'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_URL_WEBSITE'))
				->field('street_address', aliases: ['Adresse', 'Street address', 'Rue', 'Address'], examples: ['12 Rue des Innovateurs'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_STREET_ADDRESS'))
				->field('extended_address', aliases: ['Complément d\'adresse', 'Extended address'], examples: ['Entrée D'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_EXTENDED_ADDRESS'))
				->field('postal_code', aliases: ['Code postal', 'Postal code', 'CP', 'Zip'], examples: ['75011'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_POSTAL_CODE'))
				->field('locality', aliases: ['Ville', 'Locality', 'City'], examples: ['Paris'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_LOCALITY'))
				->field('region', aliases: ['Région', 'Region'], examples: ['Île-de-France'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_REGION'))
				->field('country', aliases: ['Pays', 'Country'], format: 'iso-3166-1-alpha-2', examples: ['FR' => 'France', 'GB' => 'United Kingdom', 'US' => 'United States'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_COUNTRY'))
				->field('address_description', aliases: ['Description de l\'adresse', 'Address description'], examples: ['Au bout de l’impasse'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_ADDRESS_DESCRIPTION'))
				->field('contact_person', aliases: ['Contact'], examples: ['Contact 1'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_CONTACT_PERSON'))
				->field('other_contact', aliases: ['Autre contacts', 'Other contacts'], examples: ['Contact 3'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_OTHER_CONTACT'))
				->field('published', aliases: ['Publié', 'Published'], type: FieldTypeEnum::BOOLEAN, examples: ['Oui'], label: Text::_('COM_EMUNDUS_IMPORT_ORGANIZATION_PUBLISHED'))
				->build();
		}

		return $this->columnMap;
	}

	public function validate(array $row, ImportContext $context): array
	{
		// URL and country-code checks are handled generically by the pipeline's
		// TypeValidator, driven by the FieldTypeEnum::URL type and the
		// `iso-3166-1-alpha-2` format declared on the column map. No business
		// rule beyond those at the moment — kept as a hook for future ones
		// (e.g. customer-specific blacklists).
		return [];
	}

	public function exists(array $row, ImportContext $context): bool
	{
		$identifierCode = $this->stringOrNull($row['identifier_code'] ?? null);
		if ($identifierCode !== null && $this->organizationRepository->getByIdentifierCode($identifierCode) !== null)
		{
			return true;
		}

		$name = $this->stringOrNull($row['name'] ?? null);
		if ($name === null)
		{
			return false;
		}

		return $this->organizationRepository->getByName($name) !== null;
	}

	public function persist(array $row, ImportContext $context): void
	{
		$this->organizationRepository->flush($this->buildEntity($row, id: 0));
	}

	public function update(array $row, ImportContext $context): void
	{
		// Resolve the existing record with the same priority as exists():
		// identifier_code first (more specific), then name.
		$identifierCode = $this->stringOrNull($row['identifier_code'] ?? null);
		$existing       = $identifierCode !== null
			? $this->organizationRepository->getByIdentifierCode($identifierCode)
			: null;

		if ($existing === null)
		{
			$name     = $this->stringOrNull($row['name'] ?? null);
			$existing = $name !== null ? $this->organizationRepository->getByName($name) : null;
		}

		if ($existing === null)
		{
			// exists() returned true a moment ago — the record vanished in between.
			// Treat as a transient inconsistency and fail this row.
			throw new \RuntimeException(sprintf(
				'Cannot update organization "%s": no matching record found.',
				$this->stringOrNull($row['name'] ?? null) ?? '?'
			));
		}

		// SET semantics: scalar fields are fully overwritten by the incoming row.
		// The existing primary key is preserved so OrganizationRepository::flush()
		// routes to UPDATE rather than INSERT.
		$this->organizationRepository->flush($this->buildEntity($row, id: $existing->getId()));
	}

	private function buildEntity(array $row, int $id): OrganizationEntity
	{
		return new OrganizationEntity(
			id:              $id,
			name:            trim((string) $row['name']),
			description:     $this->stringOrNull($row['description']      ?? null),
			url_website:     $this->stringOrNull($row['url_website']      ?? null),
			address:         $this->buildAddress($row),
			identifier_code: $this->stringOrNull($row['identifier_code']  ?? null),
			logo:            null,
			published:       (bool)($row['published'] ?? true),
			status:          $this->resolveStatus($row['status']          ?? null),
		);
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

	private function resolveStatus(mixed $value): VerifiedStatusEnum
	{
		$status = $this->stringOrNull($value);

		return $status === VerifiedStatusEnum::VERIFIED->value
			? VerifiedStatusEnum::VERIFIED
			: VerifiedStatusEnum::TO_BE_VERIFIED;
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
