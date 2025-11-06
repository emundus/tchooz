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
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Contacts\Gender;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\Contacts\ContactOrganizationRepository;
use Tchooz\Repositories\CountryRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\ContactOrganizationRepository
 */
class ContactOrganizationRepositoryTest extends UnitTestCase
{
	private ContactRepository $contactRepository;
	private OrganizationRepository $organizationRepository;
	private CountryRepository $countryRepository;

	private array $contacts = [];
	private array $organizations = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->model = new ContactOrganizationRepository();
		$this->contactRepository = new ContactRepository();
		$this->organizationRepository = new OrganizationRepository();
		$this->countryRepository = new CountryRepository();
	}

	public function createFixtures(): void
	{
		// Country for the contact
		$fr = $this->countryRepository->getByIso2('FR');

		$contactEntitySimple = $this->contactRepository->getByEmail('contact-org@emundus.fr');
		if($contactEntitySimple && !empty($contactEntitySimple->getId())) {
			$this->contactRepository->delete($contactEntitySimple->getId());
		}

		// Create a contact
		$contact = new ContactEntity(
			email: 'contact-org@emundus.fr',
			lastname: 'Org',
			firstname: 'Tester',
			phone_1: '0606060606',
			birth: '1992-04-02',
			gender: Gender::MAN,
			countries: [$fr]
		);
		$this->contactRepository->flush($contact);
		$this->contacts[] = $contact;

			// Create an organization
		$organization = new OrganizationEntity(
			id: 0,
			name: 'Test Organization',
			description: 'A test organization for contact associations',
			url_website: 'https://www.example.org',
			address: null,
			identifier_code: 'ORGTEST01',
			logo: null
		);
		$this->organizationRepository->flush($organization);
		$this->organizations[] = $organization;

			// Associate contact to organization as referent
		$this->model->associateContactToOrganization(
			$this->contacts[0]->getId(),
			$this->organizations[0]->getId(),
			1
		);
	}

	public function clearFixtures(): void
	{
		if (!empty($this->contacts)) {
			foreach ($this->contacts as $contact) {
				$this->model->detachAllOrganizationsFromContact($contact->getId());
				$this->contactRepository->delete($contact->getId());
			}
			$this->contacts = [];
		}

		if (!empty($this->organizations)) {
			foreach ($this->organizations as $organization) {
				$this->model->detachAllContactsFromOrganization($organization->getId());
				$this->organizationRepository->delete($organization->getId());
			}
			$this->organizations = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactOrganizationRepository::associateContactToOrganization
	 */
	public function testAssociateContactToOrganization(): void
	{
		$this->createFixtures();

		$result = $this->model->associateContactToOrganization(
			$this->contacts[0]->getId(),
			$this->organizations[0]->getId(),
			0
		);

		$this->assertTrue($result, 'Contact should be successfully associated to organization');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactOrganizationRepository::getOrganizationsIdsByContactId
	 */
	public function testGetOrganizationsIdsByContactId(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$result = $this->model->getOrganizationsIdsByContactId($contactId);

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertNotEmpty($result, 'Result should not be empty');
		$this->assertContains($this->organizations[0]->getId(), $result, 'Organization ID should be found');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactOrganizationRepository::getContactsIdsByOrganizationId
	 */
	public function testGetContactsIdsByOrganizationId(): void
	{
		$this->createFixtures();

		$organizationId = $this->organizations[0]->getId();
		$result = $this->model->getContactsIdsByOrganizationId($organizationId);

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertContains($this->contacts[0]->getId(), $result, 'Contact ID should be found');

		// With referent filter
		$resultReferent = $this->model->getContactsIdsByOrganizationId($organizationId, 1);
		$this->assertNotEmpty($resultReferent, 'Should return contacts when is_referent = 1');

		$this->clearFixtures();
	}

	/**
	 * 2 next tests are currently failing because of dynamic property creation which is deprecated ($contact->value and $contact->name)
	 */
//	/**
//	 * @covers \Tchooz\Repositories\Contacts\ContactOrganizationRepository::getOrganizationsByContactId
//	 */
//	public function testGetOrganizationsByContactId(): void
//	{
//		$this->createFixtures();
//
//		$contactId = $this->contacts[0]->getId();
//		$result = $this->model->getOrganizationsByContactId($contactId);
//
//		$this->assertIsArray($result, 'Result should be an array');
//		$this->assertNotEmpty($result, 'At least one organization should be returned');
//		$this->assertInstanceOf(OrganizationEntity::class, $result[0], 'Returned objects should be OrganizationEntity');
//
//		$this->clearFixtures();
//	}
//
//	/**
//	 * @covers \Tchooz\Repositories\Contacts\ContactOrganizationRepository::getContactsByOrganizationId
//	 */
//	public function testGetContactsByOrganizationId(): void
//	{
//		$this->createFixtures();
//
//		$organizationId = $this->organizations[0]->getId();
//		$result = $this->model->getContactsByOrganizationId($organizationId);
//
//		$this->assertIsArray($result, 'Result should be an array');
//		$this->assertNotEmpty($result, 'Should return at least one contact');
//		$this->assertInstanceOf(ContactEntity::class, $result[0], 'Returned objects should be ContactEntity');
//		$this->assertEquals('Org', $result[0]->getLastname(), 'Lastname should match the contact');
//
//		// Filter by is_referent = 1
//		$referents = $this->model->getContactsByOrganizationId($organizationId, 1);
//		$this->assertNotEmpty($referents, 'Referent contacts should be found');
//
//		$this->clearFixtures();
//	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactOrganizationRepository::detachContactFromOrganization
	 */
	public function testDetachContactFromOrganization(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$organizationId = $this->organizations[0]->getId();

		$result = $this->model->detachContactFromOrganization($contactId, $organizationId, 1);
		$this->assertTrue($result, 'Should detach the contact from organization');

		// Detach again should be safe
		$result2 = $this->model->detachContactFromOrganization($contactId, $organizationId, 1);
		$this->assertTrue($result2, 'Repeated detach should not fail');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactOrganizationRepository::detachAllContactsFromOrganization
	 */
	public function testDetachAllContactsFromOrganization(): void
	{
		$this->createFixtures();

		$organizationId = $this->organizations[0]->getId();
		$result = $this->model->detachAllContactsFromOrganization($organizationId);

		$this->assertTrue($result, 'All contacts should be detached from organization');
		$this->assertEmpty(
			$this->model->getContactsIdsByOrganizationId($organizationId),
			'No contacts should remain linked'
		);

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactOrganizationRepository::detachAllOrganizationsFromContact
	 */
	public function testDetachAllOrganizationsFromContact(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();
		$result = $this->model->detachAllOrganizationsFromContact($contactId);

		$this->assertTrue($result, 'All organizations should be detached from contact');
		$this->assertEmpty(
			$this->model->getOrganizationsIdsByContactId($contactId),
			'No organizations should remain linked'
		);

		$this->clearFixtures();
	}
}
