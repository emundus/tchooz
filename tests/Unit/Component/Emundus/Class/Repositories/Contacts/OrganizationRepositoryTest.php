<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Contacts;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Contacts\AddressRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\OrganizationFileRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Contacts
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\OrganizationRepository
 */
class OrganizationRepositoryTest extends UnitTestCase
{
	private array $organizationsFixtures = [];

	public function setUp(): void
	{
		parent::setUp();
		$this->model = new OrganizationRepository();
		$this->initDataSet();
	}

	public function createFixtures(): void
	{
		$addressEntity = new AddressEntity(
			id: 0,
			locality: 'La Rochelle',
			region: 'Nouvelle-Aquitaine',
			street_address: '1 Rue de la Paix',
			extended_address: 'Bâtiment A',
			postal_code: '17000',
			description: 'Siège social',
			country: 77 // France
		);

		$organizationEntity1 = new OrganizationEntity(
			id: 0,
			name: 'Organization 1',
			description: 'Description 1',
			url_website: 'https://www.organization1.com',
			address: null,
			identifier_code: 'ORG001',
			logo: null
		);
		$organizationEntity2 = new OrganizationEntity(
			id: 0,
			name: 'Organization 2',
			description: 'Description 2',
			url_website: 'https://www.organization2.com',
			address: $addressEntity,
			identifier_code: 'ORG002',
			logo: null
		);

		$organizations = [$organizationEntity1, $organizationEntity2];

		foreach ($organizations as $organization) {
			$this->model->flush($organization);
			$this->organizationsFixtures[] = $organization;
		}
	}

	public function clearFixtures(): void
	{
		if (!empty($this->organizationsFixtures)) {
			foreach ($this->organizationsFixtures as $organization) {
				$this->model->delete($organization->getId());
			}
			$this->organizationsFixtures = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::getAllOrganizations
	 * @return void
	 */
	public function testGetAllOrganizations()
	{
		$this->createFixtures();

		$organizations = $this->model->getAllOrganizations();

		$this->assertIsArray($organizations, 'The result is an array');
		$this->assertNotEmpty($organizations, 'The result is not empty');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::getById
	 * @return void
	 */
	public function testGetOrganizationById()
	{
		$this->createFixtures();

		$result = $this->model->getById($this->organizationsFixtures[0]->getId());
		$this->assertInstanceOf(OrganizationEntity::class, $result, 'The result is an instance of OrganizationEntity');
		$this->assertEquals($this->organizationsFixtures[0]->getId(), $result->getId(), 'The organization ID matches');
		$this->assertEquals($this->organizationsFixtures[0]->getName(), $result->getName(), 'The organization name matches');

		// Test with non-existing ID
		$result = $this->model->getById(999999);
		$this->assertNull($result, 'The result is null for non-existing ID');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::flush
	 * @return void
	 */
	public function testFlush()
	{
		// Valid organization
		$organizationEntity1 = new OrganizationEntity(
			id: 0,
			name: 'Organization 1',
			description: 'Description 1',
			url_website: 'https://www.organization1.com',
			address: null,
			identifier_code: 'ORG001',
			logo: null
		);
		$result = $this->model->flush($organizationEntity1);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $organizationEntity1->getId(), 'The organization has been created with an ID greater than 0');
		$this->model->delete($organizationEntity1->getId());
		//

		// Valid organization with address
		$addressEntity = new AddressEntity(
			id: 0,
			locality: 'La Rochelle',
			region: 'Nouvelle-Aquitaine',
			street_address: '1 Rue de la Paix',
			extended_address: 'Bâtiment A',
			postal_code: '17000',
			description: 'Siège social',
			country: 77 // France
		);
		$organizationEntity2 = new OrganizationEntity(
			id: 0,
			name: 'Organization 2',
			description: 'Description 2',
			url_website: 'https://www.organization2.com',
			address: $addressEntity,
			identifier_code: 'ORG002',
			logo: null
		);
		$result = $this->model->flush($organizationEntity2);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $organizationEntity2->getId(), 'The organization has been created with an ID greater than 0');
		$this->model->delete($organizationEntity2->getId());
		//

		// Valid organizations with contacts
		$contactEntity1 = new ContactEntity(
			email: 'contact1@emundus.fr',
			lastname: 'Doe',
			firstname: 'John'
		);
		$contactEntity2 = new ContactEntity(
			email: 'contact2@emundus.fr',
			lastname: 'Smith',
			firstname: 'Jane'
		);
		$contactRepository = new ContactRepository();
		$contactRepository->flush($contactEntity1);
		$contactRepository->flush($contactEntity2);

		$organizationEntity3 = new OrganizationEntity(
			id: 0,
			name: 'Organization 3',
			description: 'Description 3',
			url_website: 'https://www.organization2.com',
			address: null,
			identifier_code: 'ORG002',
			logo: null,
			referent_contacts: [$contactEntity1],
			other_contacts: [$contactEntity2]
		);
		$result = $this->model->flush($organizationEntity3);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $organizationEntity3->getId(), 'The organization has been created with an ID greater than 0');
		$this->model->delete($organizationEntity3->getId());
		//

		// Valid organizations with a referent
		$organizationEntity4 = new OrganizationEntity(
			id: 0,
			name: 'Organization 4',
			description: 'Description 4',
			url_website: 'https://www.organization4.com',
			address: null,
			identifier_code: 'ORG004',
			logo: null
		);
		$result = $this->model->flush($organizationEntity4);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $organizationEntity4->getId(), 'The organization has been created with an ID greater than 0');
		$this->model->delete($organizationEntity4->getId());

		$fnum                      = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$applicationFileRepository = new ApplicationFileRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($fnum);

		$organizationEntity5 = new OrganizationEntity(
			id: 0,
			name: 'Organization 5',
			description: 'Description 5',
			url_website: 'https://www.organization5.com',
			address: null,
			identifier_code: 'ORG005',
			logo: null,
			application_files: [$applicationFile]
		);
		$result = $this->model->flush($organizationEntity5);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $organizationEntity5->getId(), 'The organization has been created with an ID greater than 0');

		$organizationFileRepository = new OrganizationFileRepository();
		$associatedFnums            = $organizationFileRepository->getFilesFnumByOrganizationId($organizationEntity5->getId());
		$this->assertContains($fnum, $associatedFnums, 'The fnum should be associated to the organization');

		$this->model->delete($organizationEntity5->getId());

		// Invalid organization (missing name)
		$organizationEntity1 = new OrganizationEntity(
			id: 0,
			name: '',
			description: 'Description 1',
			url_website: 'https://www.organization1.com',
			address: null,
			identifier_code: 'ORG001',
			logo: null
		);
		// Test exception
		$this->expectException(\InvalidArgumentException::class);
		$this->model->flush($organizationEntity1);
		//
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::delete
	 * @return void
	 */
	public function testDelete()
	{
		$this->createFixtures();

		foreach ($this->organizationsFixtures as $organization) {
			$result = $this->model->delete($organization->getId());
			if($organization->getAddress()?->getId()) {
				// Address should be deleted too
				$addressRepo = new AddressRepository();
				$address = $addressRepo->getById($organization->getAddress()->getId());
				$this->assertNull($address, 'The address has been deleted');
			}
			$this->assertTrue($result, 'The organization has been deleted');
		}

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::getFilteredOrganizations
	 * @return void
	 */
	public function testGetFilteredOrganizations(): void
	{
		$this->createFixtures();

		$result = $this->model->getFilteredOrganizations();
		$this->assertIsArray($result, 'The result should be an array');
		$this->assertNotEmpty($result, 'The result should not be empty');

		$first = $result[0];
		$this->assertIsObject($first, 'Each item should be an object');
		$this->assertObjectHasProperty('value', $first, 'Each item should have a value property');
		$this->assertObjectHasProperty('label', $first, 'Each item should have a label property');

		$found = array_filter($result, fn($c) => $c->label === 'Organization 2');
		$this->assertNotEmpty($found, 'Organization "Organization 2" should be present in filtered contacts');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::getFilteredOrganizationsByIdentifierCode
	 * @return void
	 */
	public function testGetFilteredOrganizationsByIdentifierCode(): void
	{
		$this->createFixtures();

		$result = $this->model->getFilteredOrganizationsByIdentifierCode();
		$this->assertIsArray($result, 'The result should be an array');
		$this->assertNotEmpty($result, 'The result should not be empty');

		foreach ($result as $item) {
			$this->assertIsObject($item, 'Each item should be an object');
			$this->assertObjectHasProperty('value', $item);
			$this->assertObjectHasProperty('label', $item);
		}

		$found = array_filter($result, fn($p) => $p->value === 'ORG001');
		$this->assertNotEmpty($found, 'Identifier code ORG001 should be found in the list');
		$noIdentifierCode = array_filter($result, fn($p) => $p->value === 'no_identifier_code');
		$this->assertNotEmpty($noIdentifierCode, 'The "no_identifier_code" option should be present');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::updateOrganizationFilesByFnums
	 * @return void
	 */
	public function testUpdateOrganizationFilesByFnums()
	{
		$organizationEntity = $this->model->getByName('Org Update Files By Fnums');
		if ($organizationEntity && !empty($organizationEntity->getId())) {
			$this->model->delete($organizationEntity->getId());
		}
		$organizationEntity = new OrganizationEntity(
			id: 0,
			name: 'Org Update Files By Fnums',
			description: 'Test description',
			url_website: 'https://test.com',
			address: null,
			identifier_code: 'ORGUFBF01',
			logo: null
		);
		$this->model->flush($organizationEntity);

		$fnum1 = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$fnum2 = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);

		$organizationFileRepository = new OrganizationFileRepository();

		$result = $this->model->updateOrganizationFilesByFnums($organizationEntity->getId(), [$fnum1, $fnum2]);
		$this->assertTrue($result, 'updateOrganizationFilesByFnums should return true');
		$associatedFnums = $organizationFileRepository->getFilesFnumByOrganizationId($organizationEntity->getId());
		$this->assertCount(2, $associatedFnums);
		$this->assertContains($fnum1, $associatedFnums);
		$this->assertContains($fnum2, $associatedFnums);

		$result = $this->model->updateOrganizationFilesByFnums($organizationEntity->getId(), [$fnum1]);
		$this->assertTrue($result);
		$associatedFnums = $organizationFileRepository->getFilesFnumByOrganizationId($organizationEntity->getId());
		$this->assertCount(1, $associatedFnums);
		$this->assertContains($fnum1, $associatedFnums);
		$this->assertNotContains($fnum2, $associatedFnums);

		$result = $this->model->updateOrganizationFilesByFnums($organizationEntity->getId(), []);
		$this->assertTrue($result);
		$associatedFnums = $organizationFileRepository->getFilesFnumByOrganizationId($organizationEntity->getId());
		$this->assertEmpty($associatedFnums);

		$result = $this->model->updateOrganizationFilesByFnums(0, []);
		$this->assertFalse($result);

		$this->model->delete($organizationEntity->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::updateOrganizationFiles
	 * @return void
	 */
	public function testUpdateOrganizationFilesWithEmptyOrganizationId()
	{
		$result = $this->model->updateOrganizationFiles(0, []);
		$this->assertFalse($result, 'Should return false with empty organization id');
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::updateOrganizationFiles
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::updateOrganizationFilesByFnums
	 * @return void
	 */
	public function testUpdateOrganizationFilesEmptiesAssociations()
	{
		$organizationEntity = $this->model->getByName('Org Update Files Empty');
		if ($organizationEntity && !empty($organizationEntity->getId())) {
			$this->model->delete($organizationEntity->getId());
		}
		$organizationEntity = new OrganizationEntity(
			id: 0,
			name: 'Org Update Files Empty',
			description: 'Test',
			url_website: 'https://test.com',
			address: null,
			identifier_code: 'ORGUFE01',
			logo: null
		);
		$this->model->flush($organizationEntity);

		$fnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$this->model->updateOrganizationFilesByFnums($organizationEntity->getId(), [$fnum]);

		$organizationFileRepository = new OrganizationFileRepository();
		$this->assertCount(1, $organizationFileRepository->getFilesFnumByOrganizationId($organizationEntity->getId()));

		$result = $this->model->updateOrganizationFiles($organizationEntity->getId(), []);
		$this->assertTrue($result);
		$this->assertEmpty($organizationFileRepository->getFilesFnumByOrganizationId($organizationEntity->getId()));

		$this->model->delete($organizationEntity->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::getByName
	 * @return void
	 */
	public function testGetByName()
	{
		$this->createFixtures();

		$result = $this->model->getByName('Organization 1');
		$this->assertInstanceOf(OrganizationEntity::class, $result, 'The result should be an OrganizationEntity');
		$this->assertEquals($this->organizationsFixtures[0]->getId(), $result->getId(), 'The organization ID matches');
		$this->assertEquals('Organization 1', $result->getName(), 'The name matches');

		$result = $this->model->getByName('Non Existing Organization');
		$this->assertNull($result, 'The result should be null for an unknown name');

		$result = $this->model->getByName('');
		$this->assertNull($result, 'The result should be null for an empty name');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationRepository::getByIdentifierCode
	 * @return void
	 */
	public function testGetByIdentifierCode()
	{
		$this->createFixtures();

		$result = $this->model->getByIdentifierCode('ORG001');
		$this->assertInstanceOf(OrganizationEntity::class, $result, 'The result should be an OrganizationEntity');
		$this->assertEquals($this->organizationsFixtures[0]->getId(), $result->getId(), 'The organization ID matches');
		$this->assertEquals('ORG001', $result->getIdentifierCode(), 'The identifier code matches');

		$result = $this->model->getByIdentifierCode('UNKNOWN_CODE');
		$this->assertNull($result, 'The result should be null for an unknown identifier code');

		$result = $this->model->getByIdentifierCode('');
		$this->assertNull($result, 'The result should be null for an empty identifier code');

		$this->clearFixtures();
	}
}