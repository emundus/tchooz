<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage  Repositories\Programs
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Programs;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\Programs\ProgramFactory;
use Tchooz\Repositories\Programs\ProgramRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Programs\ProgramRepository
 */
class ProgramRepositoryTest extends UnitTestCase
{
	private ProgramRepository $repository;

	/**
	 * IDs of programs inserted directly through the repository (not via the dataset helper),
	 * tracked so they can be removed in tearDown.
	 *
	 * @var int[]
	 */
	private array $createdProgramIds = [];

	private User $user;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->repository = new ProgramRepository();
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
	}

	protected function tearDown(): void
	{
		foreach ($this->createdProgramIds as $id) {
			try {
				$this->h_dataset->deleteSampleProgram($id);
			} catch (\Exception) {
				// best-effort cleanup
			}
		}
		$this->createdProgramIds = [];

		parent::tearDown();
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getById
	 * @return void
	 */
	public function testGetById()
	{
		$program = $this->repository->getById($this->dataset['program']['programme_id']);
		$this->assertNotNull($program, 'The getById method should return a program entity');
		$this->assertEquals($this->dataset['program']['programme_id'], $program->getId(), 'The program entity found should have the same ID as the original');
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getByCode
	 * @return void
	 */
	public function testGetByCode()
	{
		$program = $this->repository->getById($this->dataset['program']['programme_id']);

		$programFound = $this->repository->getByCode($program->getCode());
		$this->assertNotNull($programFound, 'The getByCode method should return a program entity');
		$this->assertEquals($programFound->getId(), $program->getId(), 'The program entity found should have the same ID as the original');
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getCodesByIds
	 * @return void
	 */
	public function testGetCodesByIds()
	{
		$program1 = $this->repository->getById($this->h_dataset->createSampleProgram("Test program 1")['programme_id']);
		$program2 = $this->repository->getById($this->h_dataset->createSampleProgram("Test program 2")['programme_id']);
		$program3 = $this->repository->getById($this->h_dataset->createSampleProgram("Test program 3")['programme_id']);

		$programsIds = $this->repository->getCodesByIds([$program1->getId(), $program2->getId(), $program3->getId()]);
		$this->assertIsArray($programsIds, 'The getCodesByIds method should return an array');
		$this->assertNotEmpty($programsIds, 'The getCodesByIds method should return an array with at least one element');
		$this->assertContains($program1->getCode(), $programsIds, 'The program1 code should be in the array');
		$this->assertContains($program2->getCode(), $programsIds, 'The program2 code should be in the array');
		$this->assertContains($program3->getCode(), $programsIds, 'The program3 code should be in the array');
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getCategories
	 * @return void
	 */
	public function testGetCategories()
	{
		$program1 = $this->repository->getById($this->h_dataset->createSampleProgram("Test program 1", 1, 'Test méthode getCategories program1')['programme_id']);
		$program2 = $this->repository->getById($this->h_dataset->createSampleProgram("Test program 2", 1, 'Test méthode getCategories program2')['programme_id']);

		$programCategories = $this->repository->getCategories();
		$this->assertIsArray($programCategories, 'The getCategories method should return an array');
		$this->assertNotEmpty($programCategories, 'The getCategories method should not be empty');
		$this->assertContains($program1->getProgrammes(), $programCategories, 'The programCategories found should contain the category of the program1');
		$this->assertContains($program2->getProgrammes(), $programCategories, 'The programCategories found should contain the category of the program2');
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getFactory
	 * @return void
	 */
	public function testGetFactory()
	{
		$factory = $this->repository->getFactory();
		$this->assertInstanceOf(ProgramFactory::class, $factory, 'The getFactory method should return an instance of ProgramFactory');
	}

	// -------------------------------------------------------------------------
	// Not-found paths
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getById
	 * @return void
	 */
	public function testGetByIdReturnsNullWhenProgramDoesNotExist()
	{
		$program = $this->repository->getById(999999999);
		$this->assertNull($program, 'getById should return null when no program matches the given ID');
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getByCode
	 * @return void
	 */
	public function testGetByCodeReturnsNullWhenProgramDoesNotExist()
	{
		$program = $this->repository->getByCode('non_existing_code_' . uniqid());
		$this->assertNull($program, 'getByCode should return null when no program matches the given code');
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getCodesByIds
	 * @return void
	 */
	public function testGetCodesByIdsReturnsEmptyArrayWhenIdsAreEmpty()
	{
		$codes = $this->repository->getCodesByIds([]);
		$this->assertIsArray($codes, 'getCodesByIds should return an array');
		$this->assertEmpty($codes, 'getCodesByIds should return an empty array when no IDs are provided');
	}

	// -------------------------------------------------------------------------
	// flush — insert / update
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::flush
	 * @return void
	 */
	public function testFlushInsertsNewProgram()
	{
		$code   = 'flush_insert_' . uniqid();
		$entity = new ProgramEntity($code, 'Flush insert program', 0, true, 'Some notes', 'Cat insert');

		$result = $this->repository->flush($entity, $this->user);
		$this->assertTrue($result, 'flush should return true after inserting a new program');

		$found = $this->repository->getByCode($code);
		$this->assertNotNull($found, 'The inserted program should be retrievable by its code');
		$this->assertSame('Flush insert program', $found->getLabel(), 'The inserted program should keep the label that was flushed');
		$this->assertTrue($found->isPublished(), 'The inserted program should keep the published flag that was flushed');

		$this->createdProgramIds[] = $found->getId();
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::flush
	 * @return void
	 */
	public function testFlushUpdatesExistingProgram()
	{
		$program = $this->repository->getById($this->dataset['program']['programme_id']);
		$this->assertNotNull($program, 'The dataset program should exist before updating it');

		$program->setLabel('Updated label ' . uniqid());
		// The dataset program is created without notes; ProgramEntity::getNotes() is typed string,
		// so a value is set before flush() reads it.
		$program->setNotes('Updated notes');

		$result = $this->repository->flush($program, $this->user);
		$this->assertTrue($result, 'flush should return true after updating an existing program');

		$updated = $this->repository->getById($program->getId());
		$this->assertNotNull($updated, 'The updated program should still be retrievable');
		$this->assertSame($program->getLabel(), $updated->getLabel(), 'flush should persist the updated label without changing the ID');
		$this->assertSame($program->getId(), $updated->getId(), 'flush should update in place and keep the same ID');
	}

	// -------------------------------------------------------------------------
	// getGroupsByProgramCode
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getGroupsByProgramCode
	 * @return void
	 */
	public function testGetGroupsByProgramCodeReturnsGroupEntities()
	{
		// The dataset sample program is attached to group 1 by the dataset helper.
		$code = $this->dataset['program']['programme_code'];

		$groups = $this->repository->getGroupsByProgramCode($code);
		$this->assertIsArray($groups, 'getGroupsByProgramCode should return an array');
		$this->assertNotEmpty($groups, 'getGroupsByProgramCode should return the groups linked to the program code');
		$this->assertContainsOnlyInstancesOf(GroupEntity::class, $groups, 'getGroupsByProgramCode should return GroupEntity instances');
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::getGroupsByProgramCode
	 * @return void
	 */
	public function testGetGroupsByProgramCodeReturnsEmptyArrayForUnknownCode()
	{
		$groups = $this->repository->getGroupsByProgramCode('unknown_code_' . uniqid());
		$this->assertIsArray($groups, 'getGroupsByProgramCode should return an array');
		$this->assertEmpty($groups, 'getGroupsByProgramCode should return an empty array when no group is linked to the code');
	}

	// -------------------------------------------------------------------------
	// deleteLogo
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::deleteLogo
	 * @return void
	 */
	public function testDeleteLogoReturnsFalseWhenIdIsEmpty()
	{
		$deleted = $this->repository->deleteLogo(0);
		$this->assertFalse($deleted, 'deleteLogo should return false when no ID is provided');
	}

	/**
	 * @covers \Tchooz\Repositories\Programs\ProgramRepository::deleteLogo
	 * @return void
	 */
	public function testDeleteLogoSucceedsWhenProgramHasNoLogo()
	{
		// The dataset sample program is created without a logo.
		$id = $this->dataset['program']['programme_id'];

		$deleted = $this->repository->deleteLogo($id);
		$this->assertTrue($deleted, 'deleteLogo should return true when the program has no logo to remove');

		$program = $this->repository->getById($id);
		$this->assertNotNull($program, 'The program should still exist after deleting its (absent) logo');
		$this->assertEmpty($program->getLogo(), 'The program logo should be empty after deleteLogo');
	}
}
