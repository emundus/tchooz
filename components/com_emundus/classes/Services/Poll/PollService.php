<?php

namespace Tchooz\Services\Poll;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Poll\PollAnswerEntity;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\Poll\AnswerTypeEnum;
use Tchooz\Repositories\Poll\PollAnswerRepository;
use Tchooz\Repositories\Poll\PollParticipantsRepository;
use Tchooz\Repositories\Poll\PollRepository;

/**
 * Business operations on polls: status transitions, answer persistence, participant lookups.
 *
 * Email notifications are delegated to {@see PollNotificationService} and XLSX export to
 * {@see PollExportService} so this class stays focused on poll state and rules.
 */
class PollService
{
	private PollRepository $pollRepository;
	private PollNotificationService $notificationService;
	private PollParticipantsRepository $pollParticipantsRepository;
	private PollAnswerRepository $pollAnswerRepository;
	private DatabaseDriver $db;

	public function __construct(
		?PollRepository             $pollRepository = null,
		?PollNotificationService    $notificationService = null,
		?PollParticipantsRepository $pollParticipantsRepository = null,
		?PollAnswerRepository       $pollAnswerRepository = null,
		?DatabaseDriver             $db = null
	)
	{
		$this->pollRepository             = $pollRepository ?? new PollRepository();
		$this->notificationService        = $notificationService ?? new PollNotificationService();
		$this->pollParticipantsRepository = $pollParticipantsRepository ?? new PollParticipantsRepository();
		$this->pollAnswerRepository       = $pollAnswerRepository ?? new PollAnswerRepository();
		$this->db                         = $db ?? Factory::getContainer()->get('DatabaseDriver');

		Log::addLogger(['text_file' => 'com_emundus.poll.php'], Log::ALL, ['com_emundus.poll']);
	}

	/**
	 * Transition one or more polls to the target status, then notify every participant by email.
	 *
	 * @param   int|int[]   $pollIds       A single poll id or a list of ids.
	 * @param   StatusEnum  $targetStatus  Status the polls should move to. Defaults to OPEN.
	 *
	 * @return  array<int, int>  Map of pollId => number of emails successfully sent.
	 *
	 * @throws \InvalidArgumentException  When no valid poll id is provided.
	 * @throws \RuntimeException          When a poll is not found, has no participants, or fails to persist.
	 */
	public function runPoll(int|array $pollIds, StatusEnum $targetStatus = StatusEnum::OPEN, string $subject = 'COM_EMUNDUS_POLL_NOTIFICATION_SUBJECT', string $body = 'COM_EMUNDUS_POLL_NOTIFICATION_BODY', bool $notify = false, ?string $replyTo = null): array
	{
		$ids = $this->normalizePollIds($pollIds);

		if ($notify)
		{
			$subject = Text::_($subject);
			$body    = $this->notificationService->sanitizeBody(Text::_($body));
		}

		$results = [];
		foreach ($ids as $pollId)
		{
			$results[$pollId] = $this->runSinglePoll($pollId, $targetStatus, $subject, $body, $notify, $replyTo);
		}

		return $results;
	}

	/**
	 * @throws \RuntimeException
	 */
	private function runSinglePoll(int $pollId, StatusEnum $targetStatus, string $subject, string $body, bool $notify = false, ?string $replyTo = null): int
	{
		$poll = $this->loadPoll($pollId);

		if (empty($poll->getParticipants()))
		{
			throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_POLLS_ERROR_NO_PARTICIPANTS', $poll->getName()));
		}

		$poll->setStatus($targetStatus);
		$runned = $this->pollRepository->flush($poll);

		if ($notify)
		{
			$runned = $this->notificationService->notifyParticipants($poll, $subject, $body, [], $replyTo);

			// The creator gets a dedicated message: their poll was launched and participants were invited.
			$creatorSubject = Text::_('COM_EMUNDUS_POLL_CREATOR_RUN_NOTIFICATION_SUBJECT');
			$creatorBody    = $this->notificationService->sanitizeBody(Text::_('COM_EMUNDUS_POLL_CREATOR_RUN_NOTIFICATION_BODY'));
			$this->notificationService->notifyCreator($poll, $creatorSubject, $creatorBody);
		}

		return $runned;
	}

	/**
	 * Transition one or more polls to the CLOSED status, optionally notifying participants.
	 *
	 * Unlike {@see self::runPoll()}, this does not require the poll to have participants when
	 * notifications are disabled — an empty poll can still be closed.
	 *
	 * @param   int|int[]  $pollIds  A single poll id or a list of ids.
	 * @param   string     $subject  Subject template (raw or language key).
	 * @param   string     $body     HTML body template (raw or language key).
	 * @param   bool       $notify   When true, send notification emails to participants.
	 *
	 * @return  array<int, int>  Map of pollId => number of emails successfully sent (0 when notify is false).
	 *
	 * @throws \InvalidArgumentException  When no valid poll id is provided.
	 * @throws \RuntimeException          When a poll is not found, fails to persist, or notify is true but no participant exists.
	 */
	public function closePoll(int|array $pollIds, string $subject = 'COM_EMUNDUS_POLL_CLOSE_NOTIFICATION_SUBJECT', string $body = 'COM_EMUNDUS_POLL_CLOSE_NOTIFICATION_BODY', bool $notify = false, ?string $replyTo = null): array
	{
		$ids = $this->normalizePollIds($pollIds);

		if ($notify)
		{
			$subject = Text::_($subject);
			$body    = $this->notificationService->sanitizeBody(Text::_($body));
		}

		$results = [];
		foreach ($ids as $pollId)
		{
			$results[$pollId] = $this->closeSinglePoll($pollId, $subject, $body, $notify, $replyTo);
		}

		return $results;
	}

	/**
	 * @throws \RuntimeException
	 */
	private function closeSinglePoll(int $pollId, string $subject, string $body, bool $notify, ?string $replyTo = null): int
	{
		$poll = $this->loadPoll($pollId);

		if ($notify && empty($poll->getParticipants()))
		{
			throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_POLLS_ERROR_NO_PARTICIPANTS', $poll->getName()));
		}

		$poll->setStatus(StatusEnum::CLOSED);
		$this->pollRepository->flush($poll);

		if ($notify)
		{
			$sent = $this->notificationService->notifyParticipants($poll, $subject, $body, [], $replyTo);

			// The creator gets a dedicated message: their poll was launched and participants were invited.
			$creatorSubject = Text::_('COM_EMUNDUS_POLL_CREATOR_CLOSE_NOTIFICATION_SUBJECT');
			$creatorBody    = $this->notificationService->sanitizeBody(Text::_('COM_EMUNDUS_POLL_CREATOR_CLOSE_NOTIFICATION_BODY'));
			$this->notificationService->notifyCreator($poll, $creatorSubject, $creatorBody);

			return $sent;
		}

		return 0;
	}

	/**
	 * Notify the participants of one or more polls without changing the poll status.
	 *
	 * @param   int|int[]  $pollIds  A single poll id or a list of ids.
	 * @param   string     $subject  Subject template (raw or language key).
	 * @param   string     $body     HTML body template (raw or language key).
	 *
	 * @return  array<int, int>  Map of pollId => number of emails successfully sent.
	 *
	 * @throws \InvalidArgumentException  When no valid poll id is provided.
	 * @throws \RuntimeException          When a poll is not found or has no participants.
	 */
	public function contactParticipants(int|array $pollIds, string $subject = 'COM_EMUNDUS_POLL_NOTIFICATION_SUBJECT', string $body = 'COM_EMUNDUS_POLL_NOTIFICATION_BODY', array $recipients = [], ?string $replyTo = null): array
	{
		$ids = $this->normalizePollIds($pollIds);

		$subject = Text::_($subject);
		$body    = $this->notificationService->sanitizeBody(Text::_($body));

		$results = [];
		foreach ($ids as $pollId)
		{
			$poll = $this->loadPoll($pollId);

			$participantsToNotify = [];
			if (!empty($recipients))
			{
				foreach ($poll->getParticipants() as $participant)
				{
					if (in_array($participant->getId(), $recipients))
					{
						$participantsToNotify[] = $participant;
					}
				}
			}
			else
			{
				$participantsToNotify = $poll->getParticipants();
			}

			if (empty($participantsToNotify))
			{
				throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_POLLS_ERROR_NO_PARTICIPANTS', $poll->getName()));
			}

			$results[$pollId] = $this->notificationService->notifyParticipants($poll, $subject, $body, $participantsToNotify, $replyTo);
		}

		return $results;
	}

	public function savePollAnswers(int $pollId, int $userId, array $answers): int
	{
		if ($pollId <= 0 || $userId <= 0)
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'));
		}

		$poll = $this->loadPoll($pollId);

		$participants = $this->pollParticipantsRepository->getItemsByFields(['user' => $userId, 'poll' => $pollId], true);
		$participant  = !empty($participants) ? $participants[0] : null;
		if (empty($participant) || !assert($participant instanceof PollParticipantsEntity) || (int) $participant->getPoll()->getId() !== $pollId)
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_ANSWERS_NOT_PARTICIPANT'));
		}

		$validSlotIds = array_map(static fn($slot) => $slot->getId(), $poll->getSlots());
		$canEdit      = $poll->canEditAnswers();

		$saved = 0;
		foreach ($answers as $answer)
		{
			$slotId     = (int) ($answer['slot'] ?? 0);
			$answerType = $answer['answer'] ?? null;
			$comment    = (string) ($answer['comment'] ?? '');

			if (!$answerType instanceof AnswerTypeEnum || !in_array($slotId, $validSlotIds, true))
			{
				continue;
			}

			$slotEntity = null;
			foreach ($poll->getSlots() as $slot)
			{
				if ($slot->getId() === $slotId)
				{
					$slotEntity = $slot;
					break;
				}
			}

			if ($slotEntity === null)
			{
				continue;
			}

			$existingId = $this->pollAnswerRepository->getAnswerId($slotId, $participant->getId());

			// When the poll forbids editing, a slot already answered is locked: skip it silently.
			if (!$canEdit && !empty($existingId))
			{
				continue;
			}

			$pollAnswerEntity = new PollAnswerEntity(
				0,
				$answerType,
				$slotEntity,
				$comment,
				$participant
			);

			// When editing is allowed, reuse the existing row so the answer is updated instead of inserted.
			if ($canEdit && !empty($existingId))
			{
				$pollAnswerEntity->setId($existingId);
			}

			$this->pollAnswerRepository->flush($pollAnswerEntity);
			$saved++;
		}

		return $saved;
	}

	/**
	 * Return the answers already submitted by a user for a poll, keyed by slot id.
	 *
	 * @param   int  $pollId  The poll id.
	 * @param   int  $userId  The Joomla user id.
	 *
	 * @return  array<int, array{answer: string, comment: string}>  Empty when the user is not a participant.
	 */
	public function getParticipantAnswers(int $pollId, int $userId): array
	{
		if ($pollId <= 0 || $userId <= 0)
		{
			return [];
		}

		$participantId = $this->pollParticipantsRepository->getIdByPollAndUser($pollId, $userId);
		if (empty($participantId))
		{
			return [];
		}

		return $this->pollAnswerRepository->getAnswersMapByParticipant($participantId);
	}

	/**
	 * @param   int|int[]  $pollIds
	 *
	 * @return  int[]
	 *
	 * @throws \InvalidArgumentException  When no valid id remains after filtering.
	 */
	private function normalizePollIds(int|array $pollIds): array
	{
		$ids = is_array($pollIds) ? $pollIds : [$pollIds];
		$ids = array_values(array_unique(array_filter(
			array_map('intval', $ids),
			static fn(int $id): bool => $id > 0
		)));

		if (empty($ids))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_RUN_NO_IDS'));
		}

		return $ids;
	}

	/**
	 * @throws \RuntimeException  When the poll cannot be loaded.
	 */
	private function loadPoll(int $pollId): PollEntity
	{
		$poll = $this->pollRepository->getItemByField(
			'id',
			$pollId,
			true,
			$this->pollRepository->getTableColumns(PollRepository::class)
		);

		if (!$poll instanceof PollEntity)
		{
			throw new \RuntimeException(Text::sprintf('COM_EMUNDUS_POLLS_ERROR_NOT_FOUND', $pollId));
		}

		return $poll;
	}
}
