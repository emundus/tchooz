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
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Enums\Contacts\Gender;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\ContactCountryRepository;
use Tchooz\Repositories\CountryRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\ContactCountryRepository
 */
class ContactCountryRepositoryTest extends UnitTestCase
{
	private ContactRepository $contactRepository;
	private CountryRepository $countryRepository;

	private array $contacts = [];
	private array $countries = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->model = new ContactCountryRepository();
		$this->contactRepository = new ContactRepository();
		$this->countryRepository = new CountryRepository();
	}

	public function createFixtures(): void
	{
		// Create one or two countries
		$fr = $this->countryRepository->getByIso2('FR');
		$de = $this->countryRepository->getByIso2('DE');

		$this->countries = [$fr, $de];

		$contactEntitySimple = $this->contactRepository->getByEmail('contact-country@emundus.fr');
		if($contactEntitySimple && !empty($contactEntitySimple->getId())) {
			$this->contactRepository->delete($contactEntitySimple->getId());
		}

		// Create a contact
		$contact = new ContactEntity(
			email: 'contact-country@emundus.fr',
			lastname: 'Country',
			firstname: 'Tester',
			phone_1: '0102030405',
			birth: '1988-05-14',
			gender: Gender::MAN,
			countries: []
		);
		$this->contactRepository->flush($contact);
		$this->contacts[] = $contact;

			// Associate contact with France
		$this->model->associateContactToCountry($this->contacts[0]->getId(), $this->countries[0]->getId());
	}

	public function clearFixtures(): void
	{
		if (!empty($this->contacts)) {
			foreach ($this->contacts as $contact) {
				$this->model->detachAllCountriesFromContact($contact->getId());
				$this->contactRepository->delete($contact->getId());
			}
			$this->contacts = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactCountryRepository::associateContactToCountry
	 */
	public function testAssociateContactToCountry(): void
	{
		$this->createFixtures();

		$result = $this->model->associateContactToCountry(
			$this->contacts[0]->getId(),
			$this->countries[1]->getId()
		);

		$this->assertTrue($result, 'The association between contact and country should be successful');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactCountryRepository::getCountriesIdsByContactId
	 */
	public function testGetCountriesIdsByContactId(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$result = $this->model->getCountriesIdsByContactId($contactId);

		$this->assertIsArray($result, 'The result should be an array');
		$this->assertNotEmpty($result, 'At least one country ID should be found');
		$this->assertContains($this->countries[0]->getId(), $result, 'FR country ID should be in the list');

		// Test with unknown contact
		$empty = $this->model->getCountriesIdsByContactId(999999);
		$this->assertEmpty($empty, 'Unknown contact should return an empty array');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactCountryRepository::getContactsIdsByCountryId
	 */
	public function testGetContactsIdsByCountryId(): void
	{
		$this->createFixtures();

		$countryId = $this->countries[0]->getId();
		$result = $this->model->getContactsIdsByCountryId($countryId);

		$this->assertIsArray($result, 'The result should be an array');
		$this->assertNotEmpty($result, 'At least one contact ID should be returned');
		$this->assertContains($this->contacts[0]->getId(), $result, 'The linked contact ID should be in the result');

		// Non-existent country ID
		$empty = $this->model->getContactsIdsByCountryId(999999);
		$this->assertEmpty($empty, 'Unknown country ID should return empty result');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactCountryRepository::getCountriesByContactId
	 */
	public function testGetCountriesByContactId(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$result = $this->model->getCountriesByContactId($contactId);

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertNotEmpty($result, 'Result should not be empty');
		$this->assertEquals('FR', $result[0]->getIso2(), 'First linked country should be France');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactCountryRepository::detachContactFromCountry
	 */
	public function testDetachContactFromCountry(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$countryId = $this->countries[0]->getId();

		$result = $this->model->detachContactFromCountry($contactId, $countryId);
		$this->assertTrue($result, 'Contact should be detached from the country successfully');

		// Detach again should return true but effectively nothing
		$result2 = $this->model->detachContactFromCountry($contactId, $countryId);
		$this->assertTrue($result2, 'Detaching a non-existing link should still return true');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactCountryRepository::detachAllCountriesFromContact
	 */
	public function testDetachAllCountriesFromContact(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$result = $this->model->detachAllCountriesFromContact($contactId);

		$this->assertTrue($result, 'All countries should be detached from contact');

		$remaining = $this->model->getCountriesIdsByContactId($contactId);
		$this->assertEmpty($remaining, 'After detaching, there should be no remaining country');

		$this->clearFixtures();
	}
}
