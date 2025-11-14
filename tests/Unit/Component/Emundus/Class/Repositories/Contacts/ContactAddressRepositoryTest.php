<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage  Repositories\Contacts
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Contacts;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\ContactAddressEntity;
use Tchooz\Enums\Contacts\GenderEnum;
use Tchooz\Repositories\Contacts\ContactAddressRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\AddressRepository;
use Tchooz\Repositories\CountryRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\ContactAddressRepository
 */
class ContactAddressRepositoryTest extends UnitTestCase
{
	private ContactRepository $contactRepository;
	private AddressRepository $addressRepository;

	private array $contacts = [];
	private array $addresses = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->model = new ContactAddressRepository();
		$this->contactRepository = new ContactRepository();
		$this->addressRepository = new AddressRepository();
	}

	public function createFixtures(): void
	{
		$countryRepository = new CountryRepository();
		$frCountry = $countryRepository->getByIso2('FR');

		$contactEntitySimple = $this->contactRepository->getByEmail('contact-relation@emundus.fr');
		if($contactEntitySimple && !empty($contactEntitySimple->getId())) {
			$this->contactRepository->delete($contactEntitySimple->getId());
		}

		// Create a contact
		$contact = new ContactEntity(
			email: 'contact-relation@emundus.fr',
			lastname: 'Relation',
			firstname: 'Tester',
			phone_1: '0123456789',
			birth: '1990-01-01',
			gender: GenderEnum::MAN,
			countries: [$frCountry]
		);
		$this->contactRepository->flush($contact);
		$this->contacts[] = $contact;

			// Create 2 addresses
		$address1 = new AddressEntity(
			id: 0,
			locality: 'Bordeaux',
			region: 'Nouvelle-Aquitaine',
			street_address: '10 Rue du Vin',
			extended_address: '',
			postal_code: '33000',
			description: 'Principal',
			country: 77
		);
		$address2 = new AddressEntity(
			id: 0,
			locality: 'Paris',
			region: 'Île-de-France',
			street_address: '22 Avenue des Champs-Élysées',
			extended_address: '',
			postal_code: '75008',
			description: 'Secondaire',
			country: 77
		);
		$this->addressRepository->flush($address1);
		$this->addressRepository->flush($address2);
		$this->addresses[] = $address1;
		$this->addresses[] = $address2;


		// Link contact to address1
		$relation = new ContactAddressEntity(
			contact: $this->contacts[0],
			address: $this->addresses[0]
		);
		$this->model->flush($relation);
	}

	public function clearFixtures(): void
	{
		if (!empty($this->contacts)) {
			foreach ($this->contacts as $contact) {
				$this->contactRepository->delete($contact->getId());
			}
			$this->contacts = [];
		}

		if (!empty($this->addresses)) {
			foreach ($this->addresses as $address) {
				$this->addressRepository->delete($address->getId());
			}
			$this->addresses = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactAddressRepository::flush
	 */
	public function testFlush(): void
	{
		$this->createFixtures();

		$relation = new ContactAddressEntity(
			contact: $this->contacts[0],
			address: $this->addresses[1]
		);
		$result = $this->model->flush($relation);

		$this->assertTrue($result, 'The contact-address relation should be inserted successfully');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactAddressRepository::getAllAddressesIdsByContactId
	 */
	public function testGetAllAddressesIdsByContactId(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$result = $this->model->getAllAddressesIdsByContactId($contactId);

		$this->assertIsArray($result, 'The result should be an array');
		$this->assertNotEmpty($result, 'The result should contain at least one address ID');
		$this->assertContains($this->addresses[0]->getId(), $result, 'The linked address ID should be in the result');

		// Test with unknown contact ID
		$empty = $this->model->getAllAddressesIdsByContactId(999999);
		$this->assertEmpty($empty, 'Unknown contact ID should return an empty array');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactAddressRepository::getAddressesByContactId
	 */
	public function testGetAddressesByContactId(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$result = $this->model->getAddressesByContactId($contactId);

		$this->assertIsArray($result, 'The result should be an array');
		$this->assertNotEmpty($result, 'At least one address should be returned');
		$this->assertInstanceOf(AddressEntity::class, $result[0], 'Returned elements should be instances of AddressEntity');
		$this->assertEquals('Bordeaux', $result[0]->getLocality(), 'The locality should match the expected value');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactAddressRepository::detachAllAddressesFromContact
	 */
	public function testDetachAllAddressesFromContact(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$result = $this->model->detachAllAddressesFromContact($contactId);

		$this->assertTrue($result, 'Addresses should be detached successfully');

		// Check that getAllAddressesIdsByContactId now returns empty
		$remaining = $this->model->getAllAddressesIdsByContactId($contactId);
		$this->assertEmpty($remaining, 'All addresses should have been detached');

		$this->clearFixtures();
	}
}
