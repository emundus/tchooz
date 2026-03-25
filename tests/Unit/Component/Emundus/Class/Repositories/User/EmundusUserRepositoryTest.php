<?php

namespace Unit\Component\Emundus\Class\Repositories\User;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\List\ListResult;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Repositories\User\EmundusUserRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\User
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\User\EmundusUserRepository
 */
class EmundusUserRepositoryTest extends UnitTestCase
{
	private EmundusUserRepository $userRepository;

	public function setUp(): void
	{
		parent::setUp();
		$this->userRepository = new EmundusUserRepository();
	}

	// =====================
	// getByUserId tests
	// =====================

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
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getByUserId
	 */
	public function testGetByUserIdReturnsCompleteEntity(): void
	{
		$emundusUser = $this->userRepository->getByUserId($this->dataset['applicant']);
		$this->assertNotNull($emundusUser);
		$this->assertInstanceOf(EmundusUserEntity::class, $emundusUser);
		$this->assertNotEmpty($emundusUser->getId());
		$this->assertNotEmpty($emundusUser->getFirstname());
		$this->assertNotEmpty($emundusUser->getLastname());
		$this->assertInstanceOf(User::class, $emundusUser->getUser());
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getByUserId
	 */
	public function testGetByUserIdReturnsNullForNonExistent(): void
	{
		$emundusUser = $this->userRepository->getByUserId(999999);
		$this->assertNull($emundusUser, 'EmundusUser should be null for a non-existent user ID.');
	}

	// =====================
	// flush tests
	// =====================

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
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::flush
	 */
	public function testFlushUpdateMultipleFields(): void
	{
		$user = $this->userRepository->getByUserId($this->dataset['applicant']);
		$this->assertNotNull($user);

		$originalFirstname = $user->getFirstname();
		$originalLastname = $user->getLastname();

		$user->setFirstname('MultiUpdateFirst');
		$user->setLastname('MultiUpdateLast');
		$user->setAnonym(true);

		$flushed = $this->userRepository->flush($user);
		$this->assertTrue($flushed);

		$updated = $this->userRepository->getByUserId($this->dataset['applicant']);
		$this->assertTrue($updated->isAnonym());

		// Restore
		$updated->setFirstname($originalFirstname);
		$updated->setLastname($originalLastname);
		$updated->setAnonym(false);
		$this->userRepository->flush($updated);
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::flush
	 */
	public function testFlushThrowsExceptionWithoutUser(): void
	{
		$entity = new EmundusUserEntity(0, new User(), 'Test', 'User');

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('EmundusUserEntity must have a valid User associated to flush.');

		$this->userRepository->flush($entity);
	}

	// =====================
	// getByFnum tests
	// =====================

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
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getByFnum
	 */
	public function testGetUserByFnumReturnsNullForNonExistent(): void
	{
		$result = $this->userRepository->getByFnum('0000000000-non-existent');
		$this->assertNull($result, 'getByFnum should return null for a non-existent fnum.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getByFnum
	 */
	public function testGetUserByFnumReturnsNullForEmptyFnum(): void
	{
		$result = $this->userRepository->getByFnum('');
		$this->assertNull($result, 'getByFnum should return null for an empty fnum.');
	}

	// =====================
	// getApplicants tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getApplicants
	 */
	public function testGetApplicants(): void
	{
		$applicants = $this->userRepository->getApplicants();

		$this->assertIsArray($applicants, 'getApplicants should return an array.');
		$this->assertNotEmpty($applicants, 'There should be at least one applicant.');
		foreach ($applicants as $applicant) {
			$this->assertInstanceOf(EmundusUserEntity::class, $applicant);
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
			$contains = stripos($applicant->getFirstname(), $searchTerm) !== false ||
				stripos($applicant->getLastname(), $searchTerm) !== false ||
				stripos((string) $applicant->getUser()->id, $searchTerm) !== false;
			$this->assertTrue($contains, 'Each applicant should match the search term in either firstname, lastname or user ID.');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getApplicants
	 */
	public function testGetApplicantsWithLimit(): void
	{
		$applicants = $this->userRepository->getApplicants('', 1);

		$this->assertIsArray($applicants);
		$this->assertLessThanOrEqual(1, count($applicants), 'getApplicants with limit 1 should return at most 1 result.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getApplicants
	 */
	public function testGetApplicantsSearchNoResult(): void
	{
		$applicants = $this->userRepository->getApplicants('zzzzzznonexistentuserxyz');

		$this->assertIsArray($applicants);
		$this->assertEmpty($applicants, 'getApplicants should return empty array for a search with no match.');
	}

	// =====================
	// associateGroup tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::associateGroup
	 */
	public function testAssociateGroupReturnsTrue(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		// Get an existing group
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__emundus_setup_groups'))
			->setLimit(1);
		$db->setQuery($query);
		$groupId = (int) $db->loadResult();

		if (empty($groupId)) {
			$this->markTestSkipped('No group found in the database');
		}

		$userId = $this->dataset['coordinator'];

		// Clean up any existing association first
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_groups'))
			->where($db->quoteName('group_id') . ' = ' . $groupId)
			->where($db->quoteName('user_id') . ' = ' . $userId);
		$db->setQuery($query);
		$db->execute();

		$result = $this->userRepository->associateGroup($groupId, $userId);
		$this->assertTrue($result, 'associateGroup should return true.');

		// Verify in database
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__emundus_groups'))
			->where($db->quoteName('group_id') . ' = ' . $groupId)
			->where($db->quoteName('user_id') . ' = ' . $userId);
		$db->setQuery($query);
		$count = (int) $db->loadResult();

		$this->assertGreaterThan(0, $count, 'Association should exist in database.');

		// Clean up
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_groups'))
			->where($db->quoteName('group_id') . ' = ' . $groupId)
			->where($db->quoteName('user_id') . ' = ' . $userId);
		$db->setQuery($query);
		$db->execute();
	}

	// =====================
	// getUsersByGroup tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUsersByGroup
	 */
	public function testGetUsersByGroupReturnsArray(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		// Get a group that has users
		$query = $db->getQuery(true)
			->select('group_id')
			->from($db->quoteName('#__emundus_groups'))
			->setLimit(1);
		$db->setQuery($query);
		$groupId = (int) $db->loadResult();

		if (empty($groupId)) {
			$this->markTestSkipped('No group with users found in the database');
		}

		$users = $this->userRepository->getUsersByGroup($groupId);

		$this->assertIsArray($users);
		$this->assertNotEmpty($users, 'getUsersByGroup should return at least one user for an existing group with users.');
		$this->assertInstanceOf(EmundusUserEntity::class, $users[0]);
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUsersByGroup
	 */
	public function testGetUsersByGroupReturnsEmptyForNonExistentGroup(): void
	{
		$users = $this->userRepository->getUsersByGroup(999999);

		$this->assertIsArray($users);
		$this->assertEmpty($users, 'getUsersByGroup should return empty array for a non-existent group.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUsersByGroup
	 */
	public function testAssociateGroupThenGetUsersByGroup(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__emundus_setup_groups'))
			->setLimit(1);
		$db->setQuery($query);
		$groupId = (int) $db->loadResult();

		if (empty($groupId)) {
			$this->markTestSkipped('No group found in the database');
		}

		$userId = $this->dataset['coordinator'];

		// Clean up first
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_groups'))
			->where($db->quoteName('group_id') . ' = ' . $groupId)
			->where($db->quoteName('user_id') . ' = ' . $userId);
		$db->setQuery($query);
		$db->execute();

		$this->userRepository->associateGroup($groupId, $userId);

		$users = $this->userRepository->getUsersByGroup($groupId);
		$found = false;
		foreach ($users as $user) {
			if ($user->getUser()->id == $userId) {
				$found = true;
				break;
			}
		}

		$this->assertTrue($found, 'The associated user should be found in getUsersByGroup results.');

		// Clean up
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_groups'))
			->where($db->quoteName('group_id') . ' = ' . $groupId)
			->where($db->quoteName('user_id') . ' = ' . $userId);
		$db->setQuery($query);
		$db->execute();
	}

	// =====================
	// getUserProgramsCodes tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsCodes
	 * @return void
	 */
	public function testGetUserProgramsCodes(): void
	{
		$codes = $this->userRepository->getUserProgramsCodes(1);
		$this->assertIsArray($codes, 'getUserProgramsCodes should return an array.');
		$this->assertNotEmpty($codes, 'There should be at least one program code for the user.');

		foreach ($codes as $code) {
			$this->assertIsString($code, 'Each program code should be a string.');
			$this->assertNotEmpty($code, 'Each program code should not be empty.');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsCodes
	 */
	public function testGetUserProgramsCodesReturnsEmptyForUserWithNoPrograms(): void
	{
		$codes = $this->userRepository->getUserProgramsCodes(999999);
		$this->assertIsArray($codes);
		$this->assertEmpty($codes, 'getUserProgramsCodes should return empty for a user with no programs.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsCodes
	 */
	public function testGetUserProgramsCodesReturnsEmptyForZeroUserId(): void
	{
		$codes = $this->userRepository->getUserProgramsCodes(0);
		$this->assertIsArray($codes);
		$this->assertEmpty($codes, 'getUserProgramsCodes should return empty for user_id 0.');
	}

	// =====================
	// getUserProgramsIds tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsIds
	 * @return void
	 */
	public function testGetUserProgramsIds(): void
	{
		$ids = $this->userRepository->getUserProgramsIds(1);
		$this->assertIsArray($ids, 'getUserProgramsIds should return an array.');
		$this->assertNotEmpty($ids, 'There should be at least one program id for the user.');

		foreach ($ids as $id) {
			$this->assertIsInt($id, 'Each program id should be an integer.');
			$this->assertGreaterThan(0, $id, 'Each program id should be greater than 0.');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsIds
	 */
	public function testGetUserProgramsIdsReturnsEmptyForUserWithNoPrograms(): void
	{
		$ids = $this->userRepository->getUserProgramsIds(999999);
		$this->assertIsArray($ids);
		$this->assertEmpty($ids, 'getUserProgramsIds should return empty for a user with no programs.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsIds
	 */
	public function testGetUserProgramsIdsReturnsEmptyForZeroUserId(): void
	{
		$ids = $this->userRepository->getUserProgramsIds(0);
		$this->assertIsArray($ids);
		$this->assertEmpty($ids, 'getUserProgramsIds should return empty for user_id 0.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsIds
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getUserProgramsCodes
	 */
	public function testGetUserProgramsIdsAndCodesAreConsistent(): void
	{
		$codes = $this->userRepository->getUserProgramsCodes(1);
		$ids = $this->userRepository->getUserProgramsIds(1);

		$this->assertEquals(count($codes), count($ids), 'The number of program codes and IDs should match.');
	}

	// =====================
	// getExceptionByUserId tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getExceptionByUserId
	 */
	public function testGetExceptionByUserIdReturnsObjectWhenExists(): void
	{
		$this->userRepository->addException($this->dataset['applicant']);

		$exception = $this->userRepository->getExceptionByUserId($this->dataset['applicant']);

		$this->assertNotNull($exception, 'getExceptionByUserId should return an object when exception exists.');
		$this->assertEquals($this->dataset['applicant'], $exception->user, 'Exception user should match.');

		$this->userRepository->deleteExceptions([$this->dataset['applicant']]);
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getExceptionByUserId
	 */
	public function testGetExceptionByUserIdReturnsNullWhenNotExists(): void
	{
		$exception = $this->userRepository->getExceptionByUserId(999999);

		$this->assertNull($exception, 'getExceptionByUserId should return null for a non-existent exception.');
	}

	// =====================
	// addException tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::addException
	 * @return void
	 */
	public function testAddException(): void
	{
		$exceptionAdded = $this->userRepository->addException($this->dataset['applicant']);
		$this->assertTrue($exceptionAdded, 'addException should return true when adding a new exception.');

		// Clean up
		$this->userRepository->deleteExceptions([$this->dataset['applicant']]);
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::addException
	 */
	public function testAddExceptionAlreadyExistsReturnsTrue(): void
	{
		$this->userRepository->addException($this->dataset['applicant']);

		// Adding the same exception again should return true (already exists)
		$result = $this->userRepository->addException($this->dataset['applicant']);
		$this->assertTrue($result, 'addException should return true when exception already exists.');

		$this->userRepository->deleteExceptions([$this->dataset['applicant']]);
	}

	// =====================
	// deleteExceptions tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::deleteExceptions
	 * @return void
	 */
	public function testDeleteExceptions(): void
	{
		$this->userRepository->addException($this->dataset['applicant']);
		$deleted = $this->userRepository->deleteExceptions([$this->dataset['applicant']]);
		$this->assertTrue($deleted, 'deleteExceptions should return true when deleting exceptions for a user.');

		$exception = $this->userRepository->getExceptionByUserId($this->dataset['applicant']);
		$this->assertNull($exception, 'Exception should not exist after deletion.');
	}

	// =====================
	// getExceptions tests
	// =====================

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
		$this->assertInstanceOf(ListResult::class, $exceptions, 'getExceptions should return an instance of ListResult.');

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
		foreach ($exceptions->getItems() as $exception) {
			if ($exception->getUser()->id === $this->dataset['applicant']) {
				$found = true;
				break;
			}
		}

		$this->assertFalse($found, 'The exception for the applicant should not be found in the list of exceptions after deletion.');
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getExceptions
	 */
	public function testGetExceptionsReturnsListResult(): void
	{
		$exceptions = $this->userRepository->getExceptions();

		$this->assertInstanceOf(ListResult::class, $exceptions);
		$this->assertIsArray($exceptions->getItems());
		$this->assertIsInt($exceptions->getTotalItems());
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getExceptions
	 */
	public function testGetExceptionsWithLimit(): void
	{
		$this->userRepository->addException($this->dataset['applicant']);

		$exceptions = $this->userRepository->getExceptions('DESC', '', 1);

		$this->assertInstanceOf(ListResult::class, $exceptions);
		$this->assertLessThanOrEqual(1, count($exceptions->getItems()), 'getExceptions with limit 1 should return at most 1 item.');

		$this->userRepository->deleteExceptions([$this->dataset['applicant']]);
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getExceptions
	 */
	public function testGetExceptionsWithSearchFindsUser(): void
	{
		$this->userRepository->addException($this->dataset['applicant']);

		$applicant = $this->userRepository->getByUserId($this->dataset['applicant']);
		$this->assertNotNull($applicant);

		$searchTerm = substr($applicant->getFirstname(), 0, 3);
		$exceptions = $this->userRepository->getExceptions('DESC', $searchTerm);

		$this->assertInstanceOf(ListResult::class, $exceptions);
		$this->assertNotEmpty($exceptions->getItems(), 'getExceptions with search should find the matching exception.');

		$this->userRepository->deleteExceptions([$this->dataset['applicant']]);
	}

	/**
	 * @covers \Tchooz\Repositories\User\EmundusUserRepository::getExceptions
	 */
	public function testGetExceptionsWithSortASC(): void
	{
		$this->userRepository->addException($this->dataset['applicant']);

		$exceptionsAsc = $this->userRepository->getExceptions('ASC');
		$exceptionsDesc = $this->userRepository->getExceptions('DESC');

		$this->assertInstanceOf(ListResult::class, $exceptionsAsc);
		$this->assertInstanceOf(ListResult::class, $exceptionsDesc);

		// Total items should be the same regardless of sort order
		$this->assertEquals($exceptionsAsc->getTotalItems(), $exceptionsDesc->getTotalItems());

		$this->userRepository->deleteExceptions([$this->dataset['applicant']]);
	}
}