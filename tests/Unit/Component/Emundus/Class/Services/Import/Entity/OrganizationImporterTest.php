<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Entity
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Entity;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Entities\Country;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Services\Import\Entity\OrganizationImporter;
use Tchooz\Services\Import\ImportContext;

/**
 * @covers \Tchooz\Services\Import\Entity\OrganizationImporter
 */
class OrganizationImporterTest extends TestCase
{
	private OrganizationRepository $orgRepo;
	private CountryRepository      $countryRepo;
	private OrganizationImporter   $importer;
	private ImportContext          $context;

	protected function setUp(): void
	{
		$this->orgRepo     = $this->createMock(OrganizationRepository::class);
		$this->countryRepo = $this->createMock(CountryRepository::class);
		$this->importer    = new OrganizationImporter($this->orgRepo, $this->countryRepo);
		$this->context     = new ImportContext('Organisations', 2);
	}

	public function testGetTypeReturnsOrganization(): void
	{
		$this->assertSame('organization', $this->importer->getType());
	}

	public function testColumnMapDeclaresExpectedCanonicalFields(): void
	{
		$canonical = $this->importer->getColumnMap()->canonicalFields();

		$this->assertContains('name', $canonical);
		$this->assertContains('description', $canonical);
		$this->assertContains('identifier_code', $canonical);
		$this->assertContains('country', $canonical);
		$this->assertContains('address_description', $canonical);
		$this->assertSame(['name'], $this->importer->getColumnMap()->requiredFields());
	}

	public function testColumnMapResolvesAliasesCaseAndAccentInsensitively(): void
	{
		$map = $this->importer->getColumnMap();

		$this->assertSame('name', $map->resolve('Nom'));
		$this->assertSame('name', $map->resolve('NOM'));
		$this->assertSame('name', $map->resolve(' nom '));
		$this->assertSame('region', $map->resolve('Région'));
		$this->assertSame('region', $map->resolve('region'));
		$this->assertSame('extended_address', $map->resolve("Complément d'adresse"));
		$this->assertNull($map->resolve('Champ inconnu'));
	}

	public function testColumnMapDistinguishesOrgDescriptionFromAddressDescription(): void
	{
		$map = $this->importer->getColumnMap();

		$this->assertSame('description', $map->resolve('Description'));
		$this->assertSame('address_description', $map->resolve("Description de l'adresse"));
		$this->assertSame('address_description', $map->resolve('Address description'));
	}

	public function testExistsReturnsTrueWhenIdentifierCodeMatches(): void
	{
		$existing = new OrganizationEntity(id: 42, name: 'Other Name');
		$this->orgRepo->method('getByIdentifierCode')->with('SIRET-123')->willReturn($existing);
		$this->orgRepo->expects($this->never())->method('getByName');

		$this->assertTrue($this->importer->exists([
			'identifier_code' => 'SIRET-123',
			'name'            => 'Acme',
		], $this->context));
	}

	public function testExistsReturnsTrueWhenNameMatchesAndNoIdentifierCode(): void
	{
		$existing = new OrganizationEntity(id: 7, name: 'Acme');
		$this->orgRepo->method('getByName')->with('Acme')->willReturn($existing);

		$this->assertTrue($this->importer->exists(['name' => 'Acme'], $this->context));
	}

	public function testExistsReturnsFalseWhenNothingMatches(): void
	{
		$this->orgRepo->method('getByIdentifierCode')->willReturn(null);
		$this->orgRepo->method('getByName')->willReturn(null);

		$this->assertFalse($this->importer->exists([
			'name'            => 'Acme',
			'identifier_code' => 'X',
		], $this->context));
	}

	public function testExistsReturnsFalseWhenNameIsBlank(): void
	{
		$this->orgRepo->method('getByIdentifierCode')->willReturn(null);
		$this->orgRepo->expects($this->never())->method('getByName');

		$this->assertFalse($this->importer->exists(['name' => '   '], $this->context));
	}

	public function testPersistFlushesOrganizationWithMappedFields(): void
	{
		$captured = null;

		$this->countryRepo->method('getByIso2')->willReturn(null);

		$this->orgRepo
			->expects($this->once())
			->method('flush')
			->with($this->callback(function (OrganizationEntity $org) use (&$captured) {
				$captured = $org;
				return true;
			}))
			->willReturn(true);

		$this->importer->persist([
			'name'            => '  Acme  ',
			'description'     => 'World leader in anvils',
			'url_website'     => 'https://acme.example',
			'identifier_code' => '12345',
			'status'          => VerifiedStatusEnum::VERIFIED->value,
		], $this->context);

		$this->assertNotNull($captured);
		$this->assertSame('Acme', $captured->getName());
		$this->assertSame('World leader in anvils', $captured->getDescription());
		$this->assertSame('https://acme.example', $captured->getUrlWebsite());
		$this->assertSame('12345', $captured->getIdentifierCode());
		$this->assertSame(VerifiedStatusEnum::VERIFIED, $captured->getStatus());
	}

	public function testPersistDefaultsStatusToToBeVerifiedWhenAbsent(): void
	{
		$captured = null;
		$this->orgRepo
			->method('flush')
			->with($this->callback(function (OrganizationEntity $org) use (&$captured) {
				$captured = $org;
				return true;
			}))
			->willReturn(true);

		$this->importer->persist(['name' => 'Acme'], $this->context);

		$this->assertSame(VerifiedStatusEnum::TO_BE_VERIFIED, $captured->getStatus());
	}

	public function testPersistBuildsAddressWithResolvedCountryId(): void
	{
		$france = new Country(id: 99, label: 'France', iso2: 'FR', iso3: 'FRA', country_nb: 250);
		$this->countryRepo->method('getByIso2')->with('FR')->willReturn($france);

		$captured = null;
		$this->orgRepo
			->method('flush')
			->with($this->callback(function (OrganizationEntity $org) use (&$captured) {
				$captured = $org;
				return true;
			}))
			->willReturn(true);

		$this->importer->persist([
			'name'           => 'Acme',
			'street_address' => '1 rue de la Paix',
			'postal_code'    => '75001',
			'locality'       => 'Paris',
			'country'        => 'fr',
		], $this->context);

		$this->assertInstanceOf(AddressEntity::class, $captured->getAddress());
		$this->assertSame('1 rue de la Paix', $captured->getAddress()->getStreetAddress());
		$this->assertSame('75001', $captured->getAddress()->getPostalCode());
		$this->assertSame('Paris', $captured->getAddress()->getLocality());
		$this->assertSame(99, $captured->getAddress()->getCountry());
	}

	public function testPersistOmitsAddressWhenAllAddressFieldsAreEmpty(): void
	{
		$captured = null;
		$this->orgRepo
			->method('flush')
			->with($this->callback(function (OrganizationEntity $org) use (&$captured) {
				$captured = $org;
				return true;
			}))
			->willReturn(true);

		$this->importer->persist([
			'name'        => 'Acme',
			'description' => 'just a description',
		], $this->context);

		$this->assertNull($captured->getAddress());
	}

	public function testPersistPropagatesRepositoryException(): void
	{
		$this->orgRepo->method('flush')->willThrowException(new \RuntimeException('insert failed'));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('insert failed');

		$this->importer->persist(['name' => 'Acme'], $this->context);
	}
}
