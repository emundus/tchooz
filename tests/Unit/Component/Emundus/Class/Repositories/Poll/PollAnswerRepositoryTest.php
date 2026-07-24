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
use Tchooz\Repositories\Poll\PollAnswerRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Poll
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Poll\PollAnswerRepository
 */
class PollAnswerRepositoryTest extends UnitTestCase
{
	private PollAnswerRepository $repository;

	protected function setUp(): void
	{
		parent::setUp();

		$this->repository = new PollAnswerRepository();
	}

	private function pollAnswerTableExists(): bool
	{
		try
		{
			$db = Factory::getContainer()->get('DatabaseDriver');

			return !empty($db->setQuery('SHOW TABLES LIKE ' . $db->quote('jos_emundus_poll_answers'))->loadResult());
		}
		catch (\Throwable)
		{
			return false;
		}
	}

	// -------------------------------------------------------------------------
	// getAnswerId — early returns without DB
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollAnswerRepository::getAnswerId
	 * @return void
	 */
	public function testGetAnswerIdReturnsNullWhenSlotIdIsZeroOrNegative(): void
	{
		$this->assertNull($this->repository->getAnswerId(0, 5), 'slot id 0 should short-circuit to null');
		$this->assertNull($this->repository->getAnswerId(-1, 5), 'negative slot id should short-circuit to null');
	}

	/**
	 * @covers \Tchooz\Repositories\Poll\PollAnswerRepository::getAnswerId
	 * @return void
	 */
	public function testGetAnswerIdReturnsNullWhenParticipantIdIsZeroOrNegative(): void
	{
		$this->assertNull($this->repository->getAnswerId(5, 0), 'participant id 0 should short-circuit to null');
		$this->assertNull($this->repository->getAnswerId(5, -3), 'negative participant id should short-circuit to null');
	}

	// -------------------------------------------------------------------------
	// getAnswersMapByParticipant — early returns without DB
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollAnswerRepository::getAnswersMapByParticipant
	 * @return void
	 */
	public function testGetAnswersMapByParticipantReturnsEmptyWhenIdIsZeroOrNegative(): void
	{
		$this->assertSame([], $this->repository->getAnswersMapByParticipant(0), 'participant id 0 should return []');
		$this->assertSame([], $this->repository->getAnswersMapByParticipant(-1), 'negative participant id should return []');
	}

	// -------------------------------------------------------------------------
	// getAnswerId / getAnswersMapByParticipant — unknown ids against live schema
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollAnswerRepository::getAnswerId
	 * @return void
	 */
	public function testGetAnswerIdReturnsNullWhenNoRowMatches(): void
	{
		if (!$this->pollAnswerTableExists())
		{
			$this->markTestSkipped('Poll answers schema is not present in the test database.');
		}

		$result = $this->repository->getAnswerId(999999, 999999);

		$this->assertNull($result, 'getAnswerId should return null when no (slot, participant) row matches');
	}

	/**
	 * @covers \Tchooz\Repositories\Poll\PollAnswerRepository::getAnswersMapByParticipant
	 * @return void
	 */
	public function testGetAnswersMapByParticipantReturnsEmptyWhenParticipantHasNoAnswers(): void
	{
		if (!$this->pollAnswerTableExists())
		{
			$this->markTestSkipped('Poll answers schema is not present in the test database.');
		}

		$result = $this->repository->getAnswersMapByParticipant(999999);

		$this->assertSame([], $result, 'getAnswersMapByParticipant should return [] for an unknown participant');
	}
}
