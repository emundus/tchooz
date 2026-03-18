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
use Tchooz\Repositories\Contacts\AddressRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\OrganizationRepository
 */
class OrganizationRepositoryTest extends UnitTestCase
{
	private array $organizationsFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
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
		//

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
}