<?php

namespace Unit\Component\Emundus\Class\Repositories\User;

use Joomla\Tests\Unit\UnitTestCase;
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
	 * @return void
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
}