<?php

namespace Unit\Component\Emundus\Class\Repositories\User;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\List\ListResult;
use Tchooz\Repositories\User\EmundusUserRepository;

class EmundusUserRepositoryTest extends UnitTestCase
{
	private EmundusUserRepository $userRepository;

	public function setUp(): void
	{
		parent::setUp();
		$this->userRepository = new EmundusUserRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::__construct
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getByUserId
	 * @return void
	 */
	public function testGetByUserId(): void
	{
		$emundusUser = $this->userRepository->getByUserId(1);
		$this->assertNotNull($emundusUser, 'EmundusUser should not be null.');
		$this->assertEquals(1, $emundusUser->getUser()->id, 'User ID should match the requested ID.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::flush
	 * @covers \Tchooz\Factories\User\EmundusUserFactory::__construct
	 * @covers \Tchooz\Factories\User\EmundusUserFactory::fromDbObjects
	 * @return void
	 * @throws \Exception
	 */
	public function testFlush(): void
	{
		$applicantUser = $this->userRepository->getByUserId($this->dataset['applicant']);
		$applicantUser->setFirstname('UpdatedFirstName');
		$flushed = $this->userRepository->flush($applicantUser);

		$this->assertTrue($flushed, 'Flush operation should return true.');
		$updatedUser = $this->userRepository->getByUserId($this->dataset['applicant']);
		$this->assertEquals('UpdatedFirstName', $updatedUser->getFirstname(), 'Firstname should be updated.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getApplicants
	 */
	public function testGetApplicants(): void
	{
		$applicants = $this->userRepository->getApplicants();

		$this->assertIsArray($applicants, 'getApplicants should return an array.');
		$this->assertNotEmpty($applicants, 'There should be at least one applicant.');
		foreach ($applicants as $applicant) {
			$this->assertNotNull($applicant->getUser(), 'Each applicant should have a user associated.');
			$this->assertNotEmpty($applicant->getUser()->id, 'Each applicant\'s user should have an ID.');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getApplicants
	 */
	public function testGetApplicantsSearch(): void
	{
		$testApplicant = $this->userRepository->getByUserId($this->dataset['applicant']);
		$this->assertNotNull($testApplicant, 'Applicant user should not be null.');

		$searchTerm = substr($testApplicant->getFirstname(), 0, 3);
		$applicants = $this->userRepository->getApplicants($searchTerm);

		$this->assertIsArray($applicants, 'getApplicants should return an array.');
		$this->assertNotEmpty($applicants, 'There should be at least one applicant matching the search term.');
		foreach ($applicants as $applicant) {
			// lastname or firstname or id should match the search term
			$this->assertTrue(str_contains($applicant->getFirstname(), $searchTerm) ||
				str_contains($applicant->getLastname(), $searchTerm) ||
				str_contains((string) $applicant->getUser()->id, $searchTerm), 'Each applicant should match the search term in either firstname, lastname or user ID.');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getByFnum
	 * @covers \Tchooz\Factories\User\EmundusUserFactory::fromDbObject
	 */
	public function testGetUserByFnum(): void
	{
		$testApplicant = $this->userRepository->getByUserId($this->dataset['applicant']);
		$this->assertNotNull($testApplicant, 'Applicant user should not be null.');

		$applicantByFnum = $this->userRepository->getByFnum($this->dataset['fnum']);

		$this->assertNotNull($applicantByFnum, 'Applicant retrieved by fnum should not be null.');
		$this->assertEquals($testApplicant->getUser()->id, $applicantByFnum->getUser()->id, 'User ID of the applicant retrieved by fnum should match the original applicant.');
		$this->assertEquals($testApplicant->getFullname(), $applicantByFnum->getFullname(), 'Fullname of the applicant retrieved by fnum should match the original applicant.');
		$this->assertEquals($testApplicant->getUser()->email, $applicantByFnum->getUser()->email, 'Email of the applicant retrieved by fnum should match the original applicant.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsCodes
	 * @return void
	 */
	public function testGetUserProgramsCodes(): void
	{
		$codes = $this->userRepository->getUserProgramsCodes(1);
		$this->assertIsArray($codes, 'getUserProgramsCodes should return an array.');
		$this->assertNotEmpty($codes, 'There should be at least one program code for the user.');

		foreach ($codes as $code)
		{
			$this->assertIsString($code, 'Each program code should be a string.');
			$this->assertNotEmpty($code, 'Each program code should not be empty.');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsIds
	 * @return void
	 */
	public function testGetUserProgramsIds(): void
	{
		$ids = $this->userRepository->getUserProgramsIds(1);
		$this->assertIsArray($ids, 'getUserProgramsIds should return an array.');
		$this->assertNotEmpty($ids, 'There should be at least one program id for the user.');

		foreach ($ids as $id)
		{
			$this->assertIsNumeric($id, 'Each program id should be a numeric string.');
			$this->assertNotEmpty($id, 'Each program id should not be empty.');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::addException
	 * @return void
	 */
	public function testAddException(): void
	{
		$exceptionAdded = $this->userRepository->addException($this->dataset['applicant']);
		$this->assertTrue($exceptionAdded, 'addException should return true when adding a new exception.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::deleteExceptions
	 * @return void
	 */
	public function testDeleteExceptions(): void
	{
		$this->userRepository->addException($this->dataset['applicant']);
		$deleted = $this->userRepository->deleteExceptions([$this->dataset['applicant']]);
		$this->assertTrue($deleted, 'deleteExceptions should return true when deleting exceptions for a user.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getExceptions
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::addException
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::deleteExceptions
	 * @return void
	 * @throws \Exception
	 */
	public function testGetUserExceptions(): void
	{
		$this->userRepository->addException($this->dataset['applicant']);
		$exceptions = $this->userRepository->getExceptions();
		assert($exceptions instanceof ListResult, 'getExceptions should return an instance of ListResult.');

		$this->assertNotEmpty($exceptions->getItems(), 'There should be at least one exception in the list.');
		$found = false;
		foreach ($exceptions->getItems() as $exception) {
			if ($exception->getUser()->id === $this->dataset['applicant']) {
				$found = true;
				break;
			}
		}

		$this->assertTrue($found, 'The exception for the applicant should be found in the list of exceptions.');

		$this->userRepository->deleteExceptions([$this->dataset['applicant']]);
		$exceptions = $this->userRepository->getExceptions();
		$found = false;
		foreach ($exceptions->getItems() as $exception)
		{
			if ($exception->getUser()->id === $this->dataset['applicant'])
			{
				$found = true;
				break;
			}
		}

		$this->assertFalse($found, 'The exception for the applicant should not be found in the list of exceptions after deletion.');
	}
}