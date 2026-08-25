<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Entity
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Entity;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Services\Import\Entity\ContactImporter;
use Tchooz\Services\Import\ImportContext;

/**
 * How an update() carries the row's organization onto an existing contact.
 *
 * ContactRepository::flush() has SET semantics on that collection — anything
 * missing from the entity is detached — so these tests pin the additive
 * behaviour that keeps an update from dropping links it was not asked about.
 *
 * @covers \Tchooz\Services\Import\Entity\ContactImporter
 */
class ContactImporterTest extends TestCase
{
	private ContactRepository      $contactRepo;
	private CountryRepository      $countryRepo;
	private OrganizationRepository $orgRepo;
	private ContactImporter        $importer;
	private ImportContext          $context;

	protected function setUp(): void
	{
		$this->contactRepo = $this->createMock(ContactRepository::class);
		$this->countryRepo = $this->createMock(CountryRepository::class);
		$this->orgRepo     = $this->createMock(OrganizationRepository::class);
		$this->importer    = new ContactImporter($this->contactRepo, $this->countryRepo, $this->orgRepo);
		$this->context     = new ImportContext('Contacts', 2);
	}

	public function testUpdateAttachesTheRowOrganizationToTheContact(): void
	{
		$existing = $this->existingContact();
		$this->contactRepo->method('getByEmail')->willReturn($existing);
		$this->orgRepo->method('getById')->with(12)->willReturn($this->organization(12, 'eMundus'));

		$flushed = null;
		$this->contactRepo->method('flush')->willReturnCallback(
			static function (ContactEntity $contact) use (&$flushed): bool {
				$flushed = $contact;

				return true;
			}
		);

		// The referential decoder has already turned the name into its id.
		$this->importer->update($this->row(['organization' => '12']), $this->context);

		$this->assertNotNull($flushed);
		$this->assertSame([12], $this->organizationIds($flushed));
	}

	public function testUpdateKeepsOrganizationsTheRowDoesNotMention(): void
	{
		$existing = $this->existingContact([$this->organization(34, 'HEC Paris')]);
		$this->contactRepo->method('getByEmail')->willReturn($existing);
		$this->orgRepo->method('getById')->with(12)->willReturn($this->organization(12, 'eMundus'));

		$flushed = null;
		$this->contactRepo->method('flush')->willReturnCallback(
			static function (ContactEntity $contact) use (&$flushed): bool {
				$flushed = $contact;

				return true;
			}
		);

		$this->importer->update($this->row(['organization' => '12']), $this->context);

		$this->assertSame(
			[34, 12],
			$this->organizationIds($flushed),
			'An update must add the imported organization, never replace the existing ones'
		);
	}

	public function testUpdateWithAnEmptyOrganizationCellChangesNothing(): void
	{
		$existing = $this->existingContact([$this->organization(34, 'HEC Paris')]);
		$this->contactRepo->method('getByEmail')->willReturn($existing);
		$this->orgRepo->expects($this->never())->method('getById');

		$flushed = null;
		$this->contactRepo->method('flush')->willReturnCallback(
			static function (ContactEntity $contact) use (&$flushed): bool {
				$flushed = $contact;

				return true;
			}
		);

		// An empty cell means "no opinion", not "detach".
		$this->importer->update($this->row(['organization' => '']), $this->context);

		$this->assertSame([34], $this->organizationIds($flushed));
	}

	public function testUpdateDoesNotDuplicateAnAlreadyAssociatedOrganization(): void
	{
		$existing = $this->existingContact([$this->organization(12, 'eMundus')]);
		$this->contactRepo->method('getByEmail')->willReturn($existing);
		$this->orgRepo->method('getById')->with(12)->willReturn($this->organization(12, 'eMundus'));

		$flushed = null;
		$this->contactRepo->method('flush')->willReturnCallback(
			static function (ContactEntity $contact) use (&$flushed): bool {
				$flushed = $contact;

				return true;
			}
		);

		$this->importer->update($this->row(['organization' => '12']), $this->context);

		$this->assertSame([12], $this->organizationIds($flushed));
	}

	/**
	 * @param OrganizationEntity[] $organizations
	 */
	private function existingContact(array $organizations = []): ContactEntity
	{
		return new ContactEntity(
			email: 'jean.dupont@example.org',
			lastname: 'DUPONT',
			firstname: 'Jean',
			id: 7,
			organizations: $organizations
		);
	}

	private function organization(int $id, string $name): OrganizationEntity
	{
		return new OrganizationEntity(name: $name, id: $id);
	}

	/**
	 * @return int[]
	 */
	private function organizationIds(ContactEntity $contact): array
	{
		return array_map(
			static fn (OrganizationEntity $organization): int => $organization->getId(),
			$contact->getOrganizations() ?? []
		);
	}

	private function row(array $overrides = []): array
	{
		return array_merge([
			'email'     => 'jean.dupont@example.org',
			'lastname'  => 'DUPONT',
			'firstname' => 'Jean',
		], $overrides);
	}
}
