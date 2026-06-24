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
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Entities\Contacts\OrganizationFileAssociationEntity;
use Tchooz\Repositories\Contacts\OrganizationFileRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Contacts
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\OrganizationFileRepository
 */
class OrganizationFileRepositoryTest extends UnitTestCase
{
	private OrganizationRepository $organizationRepository;

	private array $organizations = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->model                  = new OrganizationFileRepository();
		$this->organizationRepository = new OrganizationRepository();
	}

	/**
	 * Removes any organization left over from a previous run, matched by its (unique) identifier code.
	 */
	private function deleteOrganizationsByIdentifierCode(string $identifierCode): void
	{
		$existing = $this->organizationRepository->getAllOrganizations('DESC', '', 'all', 0, 't.id', null, [], $identifierCode);
		foreach ($existing['datas'] as $organization)
		{
			if ($organization instanceof OrganizationEntity && !empty($organization->getId()))
			{
				$this->model->detachAllFilesFnumFromOrganization($organization->getId());
				$this->organizationRepository->delete($organization->getId());
			}
		}
	}

	public function createFixtures(): void
	{
		$this->deleteOrganizationsByIdentifierCode('ORGFILETEST01');
		$this->deleteOrganizationsByIdentifierCode('ORGFILETEST02');

		$organization = new OrganizationEntity(
			id: 0,
			name: 'Test Organization File',
			description: 'A test organization for file associations',
			url_website: 'https://www.example.org',
			address: null,
			identifier_code: 'ORGFILETEST01',
			logo: null
		);
		$this->organizationRepository->flush($organization);
		$this->organizations[] = $organization;

		$this->model->associateOrganizationToFileFnum(
			$this->organizations[0]->getId(),
			$this->dataset['fnum']
		);
	}

	public function clearFixtures(): void
	{
		if (!empty($this->organizations))
		{
			foreach ($this->organizations as $organization)
			{
				$this->model->detachAllFilesFnumFromOrganization($organization->getId());
				$this->organizationRepository->delete($organization->getId());
			}
			$this->organizations = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationFileRepository::associateOrganizationToFileFnum
	 */
	public function testAssociateOrganizationToFileFnum(): void
	{
		$this->createFixtures();

		$anotherFnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$result      = $this->model->associateOrganizationToFileFnum(
			$this->organizations[0]->getId(),
			$anotherFnum
		);

		$this->assertTrue($result, 'Organization should be successfully associated to file fnum');

		$this->model->detachOrganizationFromFileFnum($this->organizations[0]->getId(), $anotherFnum);

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationFileRepository::getOrganizationsIdsByFileFnum
	 */
	public function testGetOrganizationsIdsByFileFnum(): void
	{
		$this->createFixtures();

		$result = $this->model->getOrganizationsIdsByFileFnum($this->dataset['fnum']);

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertNotEmpty($result, 'Result should not be empty');
		$this->assertContains(
			(string) $this->organizations[0]->getId(),
			array_map('strval', $result),
			'Organization ID should be found'
		);

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationFileRepository::getFilesFnumByOrganizationId
	 */
	public function testGetFilesFnumByOrganizationId(): void
	{
		$this->createFixtures();

		$result = $this->model->getFilesFnumByOrganizationId($this->organizations[0]->getId());

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertNotEmpty($result, 'Result should not be empty');
		$this->assertContains($this->dataset['fnum'], $result, 'Test fnum should be found');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationFileRepository::getOrganizationAssociationsByFnum
	 */
	public function testGetOrganizationAssociationsByFnum(): void
	{
		$this->createFixtures();

		$result = $this->model->getOrganizationAssociationsByFnum($this->dataset['fnum']);

		$this->assertIsArray($result, 'Result should be an array');
		$this->assertNotEmpty($result, 'Result should not be empty');

		$association = $result[0];
		$this->assertInstanceOf(OrganizationFileAssociationEntity::class, $association, 'Result items should be association entities');
		$this->assertEquals($this->organizations[0]->getId(), $association->getOrganizationId(), 'Association should reference the organization id');
		$this->assertEquals($this->dataset['fnum'], $association->getApplicationFileFnum(), 'Association should reference the file fnum');

		// Organization is hydrated inline from the columns joined by the single query.
		$organization = $association->getOrganization();
		$this->assertInstanceOf(OrganizationEntity::class, $organization, 'Organization should be hydrated inline from the joined columns');
		$this->assertEquals($this->organizations[0]->getId(), $organization->getId(), 'Hydrated organization id should match');
		$this->assertEquals('Test Organization File', $organization->getName(), 'Hydrated organization name should match');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationFileRepository::getOrganizationAssociationsByFnum
	 */
	public function testGetOrganizationAssociationsByFnumWithoutRelations(): void
	{
		$this->createFixtures();

		// Mirrors the controller usage: with $withRelations = false the organization is still hydrated inline
		// (its columns are joined), but the application_file relation must not be loaded.
		$repository = new OrganizationFileRepository(false);
		$result     = $repository->getOrganizationAssociationsByFnum($this->dataset['fnum']);

		$this->assertNotEmpty($result, 'Result should not be empty');
		$this->assertInstanceOf(OrganizationEntity::class, $result[0]->getOrganization(), 'Organization must still be hydrated inline');
		$this->assertNull($result[0]->getApplicationFile(), 'Application file relation must not be loaded when withRelations is false');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationFileRepository::syncOrganizationsForFnum
	 */
	public function testSyncOrganizationsForFnum(): void
	{
		$this->createFixtures();

		$secondOrganization = new OrganizationEntity(
			id: 0,
			name: 'Test Organization File 2',
			identifier_code: 'ORGFILETEST02'
		);
		$this->organizationRepository->flush($secondOrganization);
		$this->organizations[] = $secondOrganization;

		// Replace the current set with only the second organization: the first one is detached, the second attached.
		$this->model->syncOrganizationsForFnum($this->dataset['fnum'], [$secondOrganization->getId()]);

		$ids = array_map('intval', $this->model->getOrganizationsIdsByFileFnum($this->dataset['fnum']));
		$this->assertContains((int) $secondOrganization->getId(), $ids, 'Second organization should be attached');
		$this->assertNotContains((int) $this->organizations[0]->getId(), $ids, 'First organization should be detached');

		// An empty target set detaches everything.
		$this->model->syncOrganizationsForFnum($this->dataset['fnum'], []);
		$this->assertEmpty($this->model->getOrganizationsIdsByFileFnum($this->dataset['fnum']), 'Empty sync should detach all organizations');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationFileRepository::detachOrganizationFromFileFnum
	 */
	public function testDetachOrganizationFromFileFnum(): void
	{
		$this->createFixtures();

		$organizationId = $this->organizations[0]->getId();

		$result = $this->model->detachOrganizationFromFileFnum($organizationId, $this->dataset['fnum']);
		$this->assertTrue($result, 'Should detach the organization from file fnum');

		$result2 = $this->model->detachOrganizationFromFileFnum($organizationId, $this->dataset['fnum']);
		$this->assertTrue($result2, 'Repeated detach should not fail');

		$remainingFnums = $this->model->getFilesFnumByOrganizationId($organizationId);
		$this->assertNotContains($this->dataset['fnum'], $remainingFnums, 'Test fnum should no longer be associated');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\OrganizationFileRepository::detachAllFilesFnumFromOrganization
	 */
	public function testDetachAllFilesFnumFromOrganization(): void
	{
		$this->createFixtures();

		$organizationId = $this->organizations[0]->getId();

		$secondFnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$this->model->associateOrganizationToFileFnum($organizationId, $secondFnum);

		$result = $this->model->detachAllFilesFnumFromOrganization($organizationId);

		$this->assertTrue($result, 'All files should be detached from organization');
		$this->assertEmpty(
			$this->model->getFilesFnumByOrganizationId($organizationId),
			'No files should remain linked'
		);

		$this->clearFixtures();
	}
}
