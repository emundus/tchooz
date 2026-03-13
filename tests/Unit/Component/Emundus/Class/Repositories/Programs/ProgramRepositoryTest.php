<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage  Repositories\Programs
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Programs;

use Joomla\Tests\Unit\UnitTestCase;
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

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->repository = new ProgramRepository();
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
}
