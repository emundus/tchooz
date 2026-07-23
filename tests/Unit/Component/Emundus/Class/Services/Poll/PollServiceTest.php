<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Poll
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Poll;

use Joomla\Database\DatabaseDriver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\ColorEnum;
use Tchooz\Repositories\Poll\PollAnswerRepository;
use Tchooz\Repositories\Poll\PollParticipantsRepository;
use Tchooz\Repositories\Poll\PollRepository;
use Tchooz\Services\Poll\PollNotificationService;
use Tchooz\Services\Poll\PollService;

/**
 * @package     Unit\Component\Emundus\Class\Services\Poll
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Poll\PollService
 */
class PollServiceTest extends TestCase
{
	/** @var PollRepository&MockObject */
	private $pollRepository;

	/** @var PollNotificationService&MockObject */
	private $notificationService;

	/** @var PollParticipantsRepository&MockObject */
	private $participantsRepository;

	/** @var PollAnswerRepository&MockObject */
	private $answerRepository;

	/** @var DatabaseDriver&MockObject */
	private $db;

	private PollService $service;

	protected function setUp(): void
	{
		parent::setUp();

		$this->pollRepository         = $this->getMockBuilder(PollRepository::class)->disableOriginalConstructor()->getMock();
		$this->notificationService    = $this->getMockBuilder(PollNotificationService::class)->disableOriginalConstructor()->getMock();
		$this->participantsRepository = $this->getMockBuilder(PollParticipantsRepository::class)->disableOriginalConstructor()->getMock();
		$this->answerRepository       = $this->getMockBuilder(PollAnswerRepository::class)->disableOriginalConstructor()->getMock();
		$this->db                     = $this->getMockBuilder(DatabaseDriver::class)->disableOriginalConstructor()->getMock();

		$this->service = new PollService(
			$this->pollRepository,
			$this->notificationService,
			$this->participantsRepository,
			$this->answerRepository,
			$this->db
		);
	}

	private function makePoll(int $id = 5, array $participants = [], StatusEnum $status = StatusEnum::OPEN): PollEntity
	{
		return new PollEntity($id, 'Sondage', '', ColorEnum::BLUE, $status, null, null, $participants, []);
	}

	private function makeParticipant(int $id = 1): PollParticipantsEntity
	{
		return new PollParticipantsEntity($id, null, 'participant@example.com', 'First', 'Last', null);
	}

	// -------------------------------------------------------------------------
	// getParticipantAnswers
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Poll\PollService::getParticipantAnswers
	 * @return void
	 */
	public function testGetParticipantAnswersReturnsEmptyWhenArgumentsInvalid(): void
	{
		$this->participantsRepository->expects($this->never())->method('getIdByPollAndUser');

		$this->assertSame([], $this->service->getParticipantAnswers(0, 10), 'Should return [] when pollId is invalid');
		$this->assertSame([], $this->service->getParticipantAnswers(10, 0), 'Should return [] when userId is invalid');
	}

	/**
	 * @covers \Tchooz\Services\Poll\PollService::getParticipantAnswers
	 * @return void
	 */
	public function testGetParticipantAnswersReturnsEmptyWhenUserIsNotParticipant(): void
	{
		$this->participantsRepository->method('getIdByPollAndUser')->with(5, 42)->willReturn(null);
		$this->answerRepository->expects($this->never())->method('getAnswersMapByParticipant');

		$this->assertSame([], $this->service->getParticipantAnswers(5, 42), 'Should return [] when user is not a participant');
	}

	/**
	 * @covers \Tchooz\Services\Poll\PollService::getParticipantAnswers
	 * @return void
	 */
	public function testGetParticipantAnswersReturnsAnswersMap(): void
	{
		$map = [
			12 => ['answer' => 'available', 'comment' => ''],
			13 => ['answer' => 'not_available', 'comment' => 'Indisponible'],
		];

		$this->participantsRepository->method('getIdByPollAndUser')->with(5, 42)->willReturn(7);
		$this->answerRepository->method('getAnswersMapByParticipant')->with(7)->willReturn($map);

		$this->assertSame($map, $this->service->getParticipantAnswers(5, 42), 'Should return the answers map keyed by slot id');
	}

	// -------------------------------------------------------------------------
	// savePollAnswers — validation
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Poll\PollService::savePollAnswers
	 * @return void
	 */
	public function testSavePollAnswersThrowsWhenPollIdInvalid(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->service->savePollAnswers(0, 42, [['slot' => 1, 'answer' => 'available']]);
	}

	/**
	 * @covers \Tchooz\Services\Poll\PollService::savePollAnswers
	 * @return void
	 */
	public function testSavePollAnswersThrowsWhenUserIdInvalid(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->service->savePollAnswers(5, 0, [['slot' => 1, 'answer' => 'available']]);
	}

	/**
	 * @covers \Tchooz\Services\Poll\PollService::savePollAnswers
	 * @return void
	 */
	public function testSavePollAnswersThrowsWhenPollNotFound(): void
	{
		$this->pollRepository->method('getItemByField')->willReturn(null);

		$this->expectException(\RuntimeException::class);

		$this->service->savePollAnswers(5, 42, [['slot' => 1, 'answer' => 'available']]);
	}

	// -------------------------------------------------------------------------
	// runPoll
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Poll\PollService::runPoll
	 * @return void
	 */
	public function testRunPollThrowsWhenNoValidId(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->service->runPoll([0, -1]);
	}

	/**
	 * @covers \Tchooz\Services\Poll\PollService::runPoll
	 * @return void
	 */
	public function testRunPollThrowsWhenPollNotFound(): void
	{
		$this->pollRepository->method('getItemByField')->willReturn(null);

		$this->expectException(\RuntimeException::class);

		$this->service->runPoll([5]);
	}

	/**
	 * @covers \Tchooz\Services\Poll\PollService::runPoll
	 * @return void
	 */
	public function testRunPollThrowsWhenPollHasNoParticipants(): void
	{
		$this->pollRepository->method('getItemByField')->willReturn($this->makePoll(5, []));

		$this->expectException(\RuntimeException::class);

		$this->service->runPoll([5]);
	}

	/**
	 * @covers \Tchooz\Services\Poll\PollService::runPoll
	 * @return void
	 */
	public function testRunPollWithoutNotificationFlushesAndMovesStatus(): void
	{
		$poll = $this->makePoll(5, [$this->makeParticipant()]);
		$this->pollRepository->method('getItemByField')->willReturn($poll);
		$this->pollRepository->expects($this->once())->method('flush')->with($poll)->willReturn(true);
		$this->notificationService->expects($this->never())->method('notifyParticipants');
		$this->notificationService->expects($this->never())->method('notifyCreator');

		$results = $this->service->runPoll([5], StatusEnum::OPEN, 'subject', 'body', false);

		$this->assertArrayHasKey(5, $results, 'Result should be keyed by poll id');
		$this->assertSame(StatusEnum::OPEN, $poll->getStatus(), 'Poll status should be moved to OPEN before flush');
	}

	// -------------------------------------------------------------------------
	// closePoll
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Poll\PollService::closePoll
	 * @return void
	 */
	public function testClosePollThrowsWhenNoValidId(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->service->closePoll([]);
	}

	/**
	 * @covers \Tchooz\Services\Poll\PollService::closePoll
	 * @return void
	 */
	public function testClosePollWithoutNotificationClosesPollAndReturnsZero(): void
	{
		$poll = $this->makePoll(5, [], StatusEnum::OPEN);
		$this->pollRepository->method('getItemByField')->willReturn($poll);
		$this->pollRepository->expects($this->once())->method('flush')->with($poll)->willReturn(true);
		$this->notificationService->expects($this->never())->method('notifyParticipants');
		$this->notificationService->expects($this->never())->method('notifyCreator');

		$results = $this->service->closePoll([5], 'subject', 'body', false);

		$this->assertSame([5 => 0], $results, 'closePoll without notify should return 0 emails sent per poll');
		$this->assertSame(StatusEnum::CLOSED, $poll->getStatus(), 'Poll status should be moved to CLOSED');
	}
}
