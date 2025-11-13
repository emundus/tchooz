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
use Tchooz\Enums\Contacts\Gender;
use Tchooz\Repositories\Contacts\AddressRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\CountryRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\ContactRepository
 */
class ContactRepositoryTest extends UnitTestCase
{
	private array $contactFixtures = [];

	private array $organizationFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->initDataSet();

		$this->model = new ContactRepository();
	}

	public function createFixtures(): void
	{
		$countryRepository = new CountryRepository();
		$frCountry = $countryRepository->getByIso2('FR');

		$organizationRepository = new OrganizationRepository();

		// Very simple contact
		$contactEntity1 = $this->model->getByEmail('contact1@emundus.fr');
		if($contactEntity1 && !empty($contactEntity1->getId())) {
			$this->model->delete($contactEntity1->getId());
		}
		$contactEntity1 = new ContactEntity(
			email: 'contact1@emundus.fr',
			lastname: 'Doe',
			firstname: 'John',
			phone_1: '0123456789',
			id: 0,
			user_id: $this->dataset['coordinator'],
			addresses: null,
			birth: '1990-01-01',
			gender: Gender::MAN,
			fonction: '',
			service: '',
			countries: [],
			organizations: [],
			application_files: [],
			profile_picture: 'images/emundus/contacts/profile1.jpg'
		);

		// Contact with address and organizations
		$contactEntity2 = $this->model->getByEmail('contact2@emundus.fr');
		if($contactEntity2 && !empty($contactEntity2->getId())) {
			$this->model->delete($contactEntity2->getId());
		}

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
		$organizationEntity = new OrganizationEntity(
			id: 0,
			name: 'Organization 1',
			description: 'Description 1',
			url_website: 'https://www.organization1.com',
			address: null,
			identifier_code: 'ORG001',
			logo: null,
		);
		$organizationRepository->flush($organizationEntity);
		$this->organizationFixtures[$organizationEntity->getId()] = $organizationEntity;

		$contactEntity2 = new ContactEntity(
			email: 'contact2@emundus.fr',
			lastname: 'Smith',
			firstname: 'Jane',
			phone_1: '0123456789',
			user_id: $this->dataset['applicant'],
			addresses: [$addressEntity],
			birth: '1998-12-21',
			gender: Gender::WOMAN,
			fonction: 'Developer',
			service: 'IT',
			countries: [$frCountry],
			organizations: [$organizationEntity],
			application_files: [],
			profile_picture: 'images/emundus/contacts/profile2.jpg'
		);

		$contacts = [$contactEntity1, $contactEntity2];

		foreach ($contacts as $contact) {
			$this->model->flush($contact);
			$this->contactFixtures[] = $contact;
		}
	}

	public function clearFixtures(): void
	{
		if (!empty($this->contactFixtures)) {
			foreach ($this->contactFixtures as $contact) {
				$this->model->delete($contact->getId());
			}
			$this->contactFixtures = [];
		}

		if (!empty($this->organizationFixtures)) {
			$organizationRepository = new OrganizationRepository();
			foreach ($this->organizationFixtures as $organization) {
				$organizationRepository->delete($organization->getId());
			}
			$this->organizationFixtures = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::flush
	 * @return void
	 */
	public function testFlush()
	{
		$countryRepository = new CountryRepository();
		$frCountry = $countryRepository->getByIso2('FR');

		$organizationRepository = new OrganizationRepository();

		// Valid a very simple contact
		$contactEntitySimple = $this->model->getByEmail('contactsimple@emundus.fr');
		if($contactEntitySimple && !empty($contactEntitySimple->getId())) {
			$this->model->delete($contactEntitySimple->getId());
		}
		$contactEntitySimple = new ContactEntity(
			email: 'contactsimple@emundus.fr',
			lastname: 'Doe',
			firstname: 'John'
		);
		$result = $this->model->flush($contactEntitySimple);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $contactEntitySimple->getId(), 'The contact has been created with an ID greater than 0');
		$this->model->delete($contactEntitySimple->getId());
		//
		
		// Valid contact
		$contactEntity1 = $this->model->getByEmail('contact1@emundus.fr');
		if($contactEntity1 && !empty($contactEntity1->getId())) {
			$this->model->delete($contactEntity1->getId());
		}
		$contactEntity1 = new ContactEntity(
			email: 'contact1@emundus.fr',
			lastname: 'Doe',
			firstname: 'John',
			phone_1: '0123456789',
			id: 0,
			user_id: $this->dataset['coordinator'],
			addresses: null,
			birth: '1990-01-01',
			gender: Gender::MAN,
			fonction: 'Developer',
			service: 'IT',
			countries: [$frCountry],
			organizations: [],
			application_files: [],
			profile_picture: 'images/emundus/contacts/profile1.jpg'
		);
		$result = $this->model->flush($contactEntity1);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $contactEntity1->getId(), 'The contact has been created with an ID greater than 0');
		$this->model->delete($contactEntity1->getId());
		//

		// Valid contact with address
		$contactEntity2 = $this->model->getByEmail('contact2@emundus.fr');
		if($contactEntity2 && !empty($contactEntity2->getId())) {
			$this->model->delete($contactEntity2->getId());
		}
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
		$contactEntity2 = new ContactEntity(
			email: 'contact2@emundus.fr',
			lastname: 'Smith',
			firstname: 'Jane',
			phone_1: '0123456789',
			user_id: $this->dataset['coordinator'],
			addresses: [$addressEntity],
			birth: '1980-01-01',
			gender: Gender::WOMAN
		);
		$result = $this->model->flush($contactEntity2);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $contactEntity2->getId(), 'The contact has been created with an ID greater than 0');
		$this->model->delete($contactEntity2->getId());

		// Valid contact with organizations
		$contactEntity3 = $this->model->getByEmail('contact3@emundus.fr');
		if($contactEntity3 && !empty($contactEntity3->getId())) {
			$this->model->delete($contactEntity3->getId());
		}
		$addressEntity2 = new AddressEntity(
			id: 0,
			locality: 'Toulouse',
			region: 'Occitanie',
			street_address: '2 Rue du Capitole',
			extended_address: '',
			postal_code: '31000',
			description: 'Bureau secondaire',
			country: 77
		);
		$organizationEntity = new OrganizationEntity(
			id: 0,
			name: 'Organization 1',
			description: 'Description 1',
			url_website: 'https://www.organization1.com',
			address: null,
			identifier_code: 'ORG001',
			logo: null,
		);
		$organizationRepository->flush($organizationEntity);
		$contactEntity3 = new ContactEntity(
			email: 'contact3@emundus.fr',
			lastname: 'Dupont',
			firstname: 'Jean',
			phone_1: '0123456789',
			user_id: $this->dataset['coordinator'],
			addresses: [$addressEntity2],
			birth: '1980-01-01',
			gender: Gender::WOMAN,
			organizations: [$organizationEntity]
		);
		$result = $this->model->flush($contactEntity3);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $contactEntity3->getId(), 'The contact has been created with an ID greater than 0');
		$this->model->delete($contactEntity3->getId());
		$organizationRepository->delete($organizationEntity->getId());

		// Valid contact with multiple addresses
		$contactEntity2 = $this->model->getByEmail('contact2@emundus.fr');
		if($contactEntity2 && !empty($contactEntity2->getId())) {
			$this->model->delete($contactEntity2->getId());
		}
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
		$addressEntity2 = new AddressEntity(
			id: 0,
			locality: 'Paris',
			region: 'Île-de-France',
			street_address: '10 Avenu des Champs-Élysées',
			extended_address: '',
			postal_code: '75008',
			description: '',
			country: 77 // France
		);
		$contactEntity2 = new ContactEntity(
			email: 'contact2@emundus.fr',
			lastname: 'Smith',
			firstname: 'Jane',
			phone_1: '0123456789',
			user_id: $this->dataset['coordinator'],
			addresses: [$addressEntity, $addressEntity2],
			birth: '1980-01-01',
			gender: Gender::WOMAN
		);
		$result = $this->model->flush($contactEntity2);
		$this->assertTrue($result, 'The result should be true');
		$this->assertGreaterThan(0, $contactEntity2->getId(), 'The contact has been created with an ID greater than 0');
		$this->model->delete($contactEntity2->getId());

		// Invalid contact (missing email)
		$contactEntity3 = new ContactEntity(
			email: '',
			lastname: 'Doe',
			firstname: 'John'
		);
		// Test exception
		$this->expectException(\InvalidArgumentException::class);
		$this->model->flush($contactEntity3);
		//
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::delete
	 * @return void
	 */
	public function testDelete()
	{
		$this->createFixtures();

		foreach ($this->contactFixtures as $contact) {
			$result = $this->model->delete($contact->getId());
			$this->assertTrue($result, 'The contact has been deleted');
		}

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::getById
	 * @return void
	 */
	public function testGetById()
	{
		$this->createFixtures();

		$contact = $this->model->getById($this->contactFixtures[1]->getId());

		$this->assertInstanceOf(ContactEntity::class, $contact, 'The result is an instance of ContactEntity');
		$this->assertNotEmpty($contact->getId());
		$this->assertEquals('contact2@emundus.fr', $contact->getEmail());
		$this->assertEquals('Smith', $contact->getLastname());
		$this->assertEquals('Jane', $contact->getFirstname());
		$this->assertEquals('0123456789', $contact->getPhone1());
		$this->assertEquals('1998-12-21', $contact->getBirthdate());
		$this->assertNotEmpty($contact->getGender());
		$this->assertEquals('woman', $contact->getGender()?->value);
		$this->assertNotEmpty($contact->getAddresses());
		$this->assertEquals('La Rochelle', $contact->getAddresses()[0]?->getLocality());
		$this->assertNotEmpty($contact->getOrganizations());
		$this->assertEquals('Organization 1', $contact->getOrganizations()[0]->getName());
		$this->assertNotEmpty($contact->getCountries());
		$this->assertEquals('FR', $contact->getCountries()[0]->getIso2());
		$this->assertNotEmpty($contact->getApplicationFiles());
		$this->assertEquals($this->dataset['applicant'], $contact->getApplicationFiles()[0]->getUser()->id);

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::getByEmail
	 * @return void
	 */
	public function testGetByEmail()
	{
		$this->createFixtures();

		$contact = $this->model->getByEmail($this->contactFixtures[1]->getEmail());
		$this->assertInstanceOf(ContactEntity::class, $contact, 'The result is an instance of ContactEntity');
		$this->assertNotEmpty($contact->getId());
		$this->assertEquals('contact2@emundus.fr', $contact->getEmail());

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::getByUserId
	 * @return void
	 */
	public function testGetByUserId()
	{
		$this->createFixtures();

		$contact = $this->model->getByUserId($this->contactFixtures[1]->getUserId());
		$this->assertInstanceOf(ContactEntity::class, $contact, 'The result is an instance of ContactEntity');
		$this->assertNotEmpty($contact->getId());
		$this->assertEquals($this->dataset['applicant'], $contact->getUserId());

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::togglePublished
	 * @return void
	 */
	public function testTogglePublished()
	{
		$this->createFixtures();

		$toggled = $this->model->togglePublished($this->contactFixtures[1]->getId(), false);
		$this->assertTrue($toggled);

		$contact = $this->model->getById($this->contactFixtures[1]->getId());
		$this->assertInstanceOf(ContactEntity::class, $contact, 'The result is an instance of ContactEntity');
		$this->assertFalse($contact->isPublished());

		$toggled = $this->model->togglePublished($this->contactFixtures[1]->getId(), true);
		$this->assertTrue($toggled);

		$contact = $this->model->getById($this->contactFixtures[1]->getId());
		$this->assertInstanceOf(ContactEntity::class, $contact, 'The result is an instance of ContactEntity');
		$this->assertTrue($contact->isPublished());

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::getAllContacts
	 * @return void
	 */
	public function testGetAllContacts()
	{
		$this->createFixtures();

		$contacts = $this->model->getAllContacts();
		$this->assertIsArray($contacts, 'The result is an array');
		$this->assertGreaterThan(0, $contacts['count'], 'The result count is greater than 0');
		$this->assertNotEmpty($contacts['datas'], 'The result is not empty');
		$this->assertInstanceOf(ContactEntity::class, $contacts['datas'][0], 'The first item is an instance of ContactEntity');

		$contacts = $this->model->getAllContacts('DESC', 'contact2@emundus.fr');
		$this->assertIsArray($contacts, 'The result is an array');
		$this->assertEquals(1, $contacts['count'], 'The result count is 1');
		$this->assertNotEmpty($contacts['datas'], 'The result is not empty');
		$this->assertInstanceOf(ContactEntity::class, $contacts['datas'][0], 'The first item is an instance of ContactEntity');
		$this->assertEquals('contact2@emundus.fr', $contacts['datas'][0]->getEmail(), 'The email matches the search');

		// Unpublish contact 2 and test filter
		$this->model->togglePublished($this->contactFixtures[1]->getId(), false);
		$contacts = $this->model->getAllContacts('DESC', '', 0, 0, 't.id', 'false');
		$this->assertIsArray($contacts, 'The result is an array');
		$this->assertGreaterThan(0, $contacts['count'], 'The result count is greater than 0');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::getFilteredContacts
	 * @return void
	 */
	public function testGetFilteredContacts(): void
	{
		$this->createFixtures();

		$result = $this->model->getFilteredContacts();
		$this->assertIsArray($result, 'The result should be an array');
		$this->assertNotEmpty($result, 'The result should not be empty');

		$first = $result[0];
		$this->assertIsObject($first, 'Each item should be an object');
		$this->assertObjectHasProperty('value', $first, 'Each item should have a value property');
		$this->assertObjectHasProperty('label', $first, 'Each item should have a label property');

		$found = array_filter($result, fn($c) => $c->label === 'Smith Jane');
		$this->assertNotEmpty($found, 'Contact "Smith Jane" should be present in filtered contacts');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactRepository::getFilteredContactsByPhoneNumber
	 * @return void
	 */
	public function testGetFilteredContactsByPhoneNumber(): void
	{
		$this->createFixtures();

		$result = $this->model->getFilteredContactsByPhoneNumber();
		$this->assertIsArray($result, 'The result should be an array');
		$this->assertNotEmpty($result, 'The result should not be empty');

		foreach ($result as $item) {
			$this->assertIsObject($item, 'Each item should be an object');
			$this->assertObjectHasProperty('value', $item);
			$this->assertObjectHasProperty('label', $item);
		}

		$found = array_filter($result, fn($p) => $p->value === '0123456789');
		$this->assertNotEmpty($found, 'Phone number 0123456789 should be found in the list');
		$noPhone = array_filter($result, fn($p) => $p->value === 'no_phone_number');
		$this->assertNotEmpty($noPhone, 'The "no_phone_number" option should be present');

		$this->clearFixtures();
	}

}