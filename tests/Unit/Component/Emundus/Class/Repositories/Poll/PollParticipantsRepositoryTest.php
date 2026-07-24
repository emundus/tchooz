<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories\Poll
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Poll;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Factories\Poll\PollParticipantsFactory;
use Tchooz\Repositories\Poll\PollParticipantsRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Poll
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Poll\PollParticipantsRepository
 */
class PollParticipantsRepositoryTest extends UnitTestCase
{
	private PollParticipantsRepository $repository;

	protected function setUp(): void
	{
		parent::setUp();

		$this->repository = new PollParticipantsRepository();
	}

	private function pollParticipantsTableExists(): bool
	{
		try
		{
			$db = Factory::getContainer()->get('DatabaseDriver');

			return !empty($db->setQuery('SHOW TABLES LIKE ' . $db->quote('jos_emundus_setup_polls_participants'))->loadResult());
		}
		catch (\Throwable)
		{
			return false;
		}
	}

	// -------------------------------------------------------------------------
	// getFactory — wiring
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollParticipantsRepository::getFactory
	 * @return void
	 */
	public function testGetFactoryReturnsPollParticipantsFactory(): void
	{
		$this->assertInstanceOf(
			PollParticipantsFactory::class,
			$this->repository->getFactory(),
			'getFactory should return a PollParticipantsFactory instance'
		);
	}

	// -------------------------------------------------------------------------
	// getIdByPollAndUser — early returns without DB
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollParticipantsRepository::getIdByPollAndUser
	 * @return void
	 */
	public function testGetIdByPollAndUserReturnsNullWhenPollIdIsZeroOrNegative(): void
	{
		$this->assertNull($this->repository->getIdByPollAndUser(0, 5), 'poll id 0 should short-circuit to null');
		$this->assertNull($this->repository->getIdByPollAndUser(-2, 5), 'negative poll id should short-circuit to null');
	}

	/**
	 * @covers \Tchooz\Repositories\Poll\PollParticipantsRepository::getIdByPollAndUser
	 * @return void
	 */
	public function testGetIdByPollAndUserReturnsNullWhenUserIdIsZeroOrNegative(): void
	{
		$this->assertNull($this->repository->getIdByPollAndUser(5, 0), 'user id 0 should short-circuit to null');
		$this->assertNull($this->repository->getIdByPollAndUser(5, -1), 'negative user id should short-circuit to null');
	}

	// -------------------------------------------------------------------------
	// getIdByPollAndUser — unknown ids against the live schema
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollParticipantsRepository::getIdByPollAndUser
	 * @return void
	 */
	public function testGetIdByPollAndUserReturnsNullWhenNoMatch(): void
	{
		if (!$this->pollParticipantsTableExists())
		{
			$this->markTestSkipped('Poll participants schema is not present in the test database.');
		}

		$result = $this->repository->getIdByPollAndUser(999999, 999999);

		$this->assertNull($result, 'getIdByPollAndUser should return null when no participant row matches the (poll, user) pair');
	}

	// -------------------------------------------------------------------------
	// delete — silent no-op when the participant does not exist
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollParticipantsRepository::delete
	 * @return void
	 */
	public function testDeleteAnUnknownParticipantReturnsTruthyWithoutThrowing(): void
	{
		if (!$this->pollParticipantsTableExists())
		{
			$this->markTestSkipped('Poll participants schema is not present in the test database.');
		}

		$result = $this->repository->delete(999999);

		$this->assertNotFalse($result, 'delete should not return false when the participant id does not exist');
	}
}
