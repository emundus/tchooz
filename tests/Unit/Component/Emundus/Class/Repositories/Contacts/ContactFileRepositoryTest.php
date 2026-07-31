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
use Tchooz\Entities\Contacts\ContactFileAssociationEntity;
use Tchooz\Enums\Contacts\GenderEnum;
use Tchooz\Repositories\Contacts\ContactFileRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\CountryRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Contacts
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\ContactFileRepository
 */
class ContactFileRepositoryTest extends UnitTestCase
{
	private ContactRepository $contactRepository;
	private CountryRepository $countryRepository;

	private array $contacts = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->model             = new ContactFileRepository();
		$this->contactRepository = new ContactRepository();
		$this->countryRepository = new CountryRepository();
	}

	public function createFixtures(): void
	{
		$fr = $this->countryRepository->getByIso2('FR');

		$existingContact = $this->contactRepository->getByEmail('contact-file@emundus.fr');
		if ($existingContact && !empty($existingContact->getId()))
		{
			$this->model->detachAllFilesFnumFromContact($existingContact->getId());
			$this->contactRepository->delete($existingContact->getId());
		}

		$contact = new ContactEntity(
			email: 'contact-file@emundus.fr',
			lastname: 'File',
			firstname: 'Tester',
			phone_1: '0606060606',
			birth: '1992-04-02',
			gender: GenderEnum::MAN,
			countries: [$fr]
		);
		$this->contactRepository->flush($contact);
		$this->contacts[] = $contact;

		$this->model->associateContactToFileFnum(
			$this->contacts[0]->getId(),
			$this->dataset['fnum']
		);
	}

	public function clearFixtures(): void
	{
		if (!empty($this->contacts))
		{
			foreach ($this->contacts as $contact)
			{
				$this->model->detachAllFilesFnumFromContact($contact->getId());
				$this->contactRepository->delete($contact->getId());
			}
			$this->contacts = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactFileRepository::associateContactToFileFnum
	 */
	public function testAssociateContactToFileFnum(): void
	{
		$this->createFixtures();

		$anotherFnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$result      = $this->model->associateContactToFileFnum(
			$this->contacts[0]->getId(),
			$anotherFnum
		);

		$this->assertTrue($result, 'Contact should be successfully associated to file fnum');

		$this->model->detachContactFromFileFnum($this->contacts[0]->getId(), $anotherFnum);

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactFileRepository::getContactsIdsByFileFnum
	 */
	public function testGetContactsIdsByFileFnum(): void
	{
		$this->createFixtures();

		$result = $this->model->getContactsIdsByFileFnum($this->dataset['fnum']);

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertNotEmpty($result, 'Result should not be empty');
		$this->assertContains(
			(string) $this->contacts[0]->getId(),
			array_map('strval', $result),
			'Contact ID should be found'
		);

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactFileRepository::getFilesFnumByContactId
	 */
	public function testGetFilesFnumByContactId(): void
	{
		$this->createFixtures();

		$result = $this->model->getFilesFnumByContactId($this->contacts[0]->getId());

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertNotEmpty($result, 'Result should not be empty');
		$this->assertContains($this->dataset['fnum'], $result, 'Test fnum should be found');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactFileRepository::getContactAssociationsByFnum
	 */
	public function testGetContactAssociationsByFnum(): void
	{
		$this->createFixtures();

		$result = $this->model->getContactAssociationsByFnum($this->dataset['fnum']);

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertNotEmpty($result, 'Result should not be empty');

		$association = $result[0];
		$this->assertInstanceOf(ContactFileAssociationEntity::class, $association, 'Result items should be association entities');
		$this->assertEquals($this->contacts[0]->getId(), $association->getContactId(), 'Association should reference the contact id');
		$this->assertEquals($this->dataset['fnum'], $association->getApplicationFileFnum(), 'Association should reference the file fnum');

		// Contact is hydrated inline from the columns joined by the single query.
		$contact = $association->getContact();
		$this->assertInstanceOf(ContactEntity::class, $contact, 'Contact should be hydrated inline from the joined columns');
		$this->assertEquals($this->contacts[0]->getId(), $contact->getId(), 'Hydrated contact id should match');
		$this->assertStringContainsString('Tester', $contact->getFullName(), 'Full name should contain firstname');
		$this->assertStringContainsString('File', $contact->getFullName(), 'Full name should contain lastname');
		$this->assertEquals('contact-file@emundus.fr', $contact->getEmail(), 'Hydrated contact email should match');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactFileRepository::getContactAssociationsByFnum
	 */
	public function testGetContactAssociationsByFnumWithoutRelations(): void
	{
		$this->createFixtures();

		// Mirrors the controller usage: with $withRelations = false the contact is still hydrated inline
		// (its columns are joined), but the application_file relation must not be loaded.
		$repository = new ContactFileRepository(false);
		$result     = $repository->getContactAssociationsByFnum($this->dataset['fnum']);

		$this->assertNotEmpty($result, 'Result should not be empty');
		$this->assertInstanceOf(ContactEntity::class, $result[0]->getContact(), 'Contact must still be hydrated inline');
		$this->assertNull($result[0]->getApplicationFile(), 'Application file relation must not be loaded when withRelations is false');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactFileRepository::syncContactsForFnum
	 */
	public function testSyncContactsForFnum(): void
	{
		$this->createFixtures();

		$fr            = $this->countryRepository->getByIso2('FR');
		$secondContact = new ContactEntity(
			email: 'contact-file-2@emundus.fr',
			lastname: 'Sync',
			firstname: 'Tester',
			countries: [$fr]
		);
		$this->contactRepository->flush($secondContact);
		$this->contacts[] = $secondContact;

		// Replace the current set with only the second contact: the first one is detached, the second attached.
		$this->model->syncContactsForFnum($this->dataset['fnum'], [$secondContact->getId()]);

		$ids = array_map('intval', $this->model->getContactsIdsByFileFnum($this->dataset['fnum']));
		$this->assertContains((int) $secondContact->getId(), $ids, 'Second contact should be attached');
		$this->assertNotContains((int) $this->contacts[0]->getId(), $ids, 'First contact should be detached');

		// An empty target set detaches everything.
		$this->model->syncContactsForFnum($this->dataset['fnum'], []);
		$this->assertEmpty($this->model->getContactsIdsByFileFnum($this->dataset['fnum']), 'Empty sync should detach all contacts');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactFileRepository::detachContactFromFileFnum
	 */
	public function testDetachContactFromFileFnum(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();

		$result = $this->model->detachContactFromFileFnum($contactId, $this->dataset['fnum']);
		$this->assertTrue($result, 'Should detach the contact from file fnum');

		$result2 = $this->model->detachContactFromFileFnum($contactId, $this->dataset['fnum']);
		$this->assertTrue($result2, 'Repeated detach should not fail');

		$remainingFnums = $this->model->getFilesFnumByContactId($contactId);
		$this->assertNotContains($this->dataset['fnum'], $remainingFnums, 'Test fnum should no longer be associated');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\ContactFileRepository::detachAllFilesFnumFromContact
	 */
	public function testDetachAllFilesFnumFromContact(): void
	{
		$this->createFixtures();

		$contactId = $this->contacts[0]->getId();

		$secondFnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$this->model->associateContactToFileFnum($contactId, $secondFnum);

		$result = $this->model->detachAllFilesFnumFromContact($contactId);

		$this->assertTrue($result, 'All files should be detached from contact');
		$this->assertEmpty(
			$this->model->getFilesFnumByContactId($contactId),
			'No files should remain linked'
		);

		$this->clearFixtures();
	}
}
