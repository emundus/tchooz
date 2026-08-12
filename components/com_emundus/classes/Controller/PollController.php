<?php

namespace Tchooz\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Event\SlotEntity;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\ColorEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\Poll\AnswerTypeEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Poll\PollParticipantsRepository;
use Tchooz\Repositories\Poll\PollRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Services\Poll\PollExportService;
use Tchooz\Services\Poll\PollService;
use Tchooz\Transformers\Poll\PollListItemTransformer;

class PollController extends EmundusController
{
	private ActionEntity $pollAction;

	private PollRepository $pollRepository;

	private PollParticipantsRepository $pollParticipantsRepository;

	function __construct($config = array())
	{
		parent::__construct($config);

		$actionRepository = new ActionRepository();
		$this->pollAction = $actionRepository->getByName('poll');

		$this->pollRepository             = new PollRepository();
		$this->pollParticipantsRepository = new PollParticipantsRepository();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getpolls(): EmundusResponse
	{
		$pollLayout = Factory::getApplication()->getUserState('com_emundus.poll.layout');

		$sort      = $this->input->getString('sort', 'ASC');
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$order_by  = $this->input->getString('order_by', 'id');
		$order_by  = $order_by == 'label' ? 'label' : $order_by;

		$filters       = [];
		$hasReadAccess = \EmundusHelperAccess::asAccessAction($this->pollAction->getId(), 'r', $this->user->id);

		if ($pollLayout === 'reply')
		{
			$filters['espp.user'] = $this->user->id;
			$filters['status']    = [StatusEnum::OPEN->value, StatusEnum::CLOSED->value];
		}
		else
		{
			// user created or that are shared with a programme they manage.
			$emundusUserRepository = new EmundusUserRepository();
			$managedProgramIds     = $emundusUserRepository->getUserProgramsIds((int) $this->user->id);
			$accessiblePollIds     = $this->pollRepository->getAccessiblePollIds((int) $this->user->id, $managedProgramIds);

			// Force an empty result set when the user has no accessible poll.
			$filters['id'] = !empty($accessiblePollIds) ? $accessiblePollIds : [0];
		}

		$order_by = $this->pollRepository->buildOrderBy($order_by, $sort);
		$polls    = $this->pollRepository->getList($filters, $lim, $page, [], $order_by, $recherche);

		$isReplyLayout = $pollLayout === 'reply';
		$transformer   = new PollListItemTransformer();
		$pollService   = new PollService();

		$datas = [];
		foreach ($polls->getItems() as $key => $poll)
		{
			assert($poll instanceof PollEntity);

			$myAnswers = $isReplyLayout
				? $pollService->getParticipantAnswers($poll->getId(), (int) $this->user->id)
				: [];

			$datas[$key] = $transformer->transform($poll, $isReplyLayout, $myAnswers);
		}

		return EmundusResponse::ok(
			['datas' => $datas, 'count' => $polls->getTotalItems()],
			Text::_('COM_EMUNDUS_POLLS_RETRIEVED')
		);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::CREATE_OR_UPDATE, 'entityIdParam' => 'id']
	])]
	public function savepoll(): EmundusResponse
	{
		$this->checkToken();

		$id = $this->input->getInt('id', 0);

		$name         = $this->input->getString('name', '');
		$description  = $this->input->getString('description', '');
		$slots        = $this->input->getRaw('slots', 0);
		$participants = $this->input->getString('participants', 0);
		$programs     = $this->input->getString('programs', '');
		$startDate    = $this->input->getString('start_date');
		if ($startDate === 'null')
		{
			$startDate = null;
		}
		$endDate = $this->input->getString('end_date');
		if ($endDate === 'null')
		{
			$endDate = null;
		}
		$canEditAnswers = $this->input->getInt('can_edit_answers', 0) === 1;

		if (empty($name))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'));
		}

		$slotsEntities = [];
		if (!empty($slots))
		{
			$slots = json_decode($slots);

			// User input is expressed in the platform timezone; slots are stored in UTC.
			$platformTimezone = new \DateTimeZone(Factory::getApplication()->get('offset', 'Europe/Paris'));
			$utcTimezone      = new \DateTimeZone('UTC');

			foreach ($slots as $slot)
			{
				if (!$slot->id)
				{
					$slotStart = \DateTime::createFromFormat('Y-m-d H:i:s', $slot->start_date, $platformTimezone);
					$slotEnd   = \DateTime::createFromFormat('Y-m-d H:i:s', $slot->end_date, $platformTimezone);

					if ($slotStart === false || $slotEnd === false)
					{
						throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLLS_INVALID_SLOT_DATE'));
					}

					$slotStart->setTimezone($utcTimezone);
					$slotEnd->setTimezone($utcTimezone);

					$slotEntity = new SlotEntity($slot->id, $slotStart, $slotEnd, $slot->slot_capacity);
					$slotEntity->setLocationText(isset($slot->location_text) ? (string) $slot->location_text : null);
					$slotsEntities[] = $slotEntity;
				}
			}
		}

		$participantsEntities = [];
		if (!empty($participants))
		{
			$participants = explode(',', $participants);
			foreach ($participants as $participant)
			{
				$participantUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($participant);
				if (!empty($participantUser))
				{
					assert($participantUser instanceof User);
					$nameParts              = explode(' ', trim($participantUser->name));
					$firstname              = $nameParts[0] ?? '';
					$lastname               = $nameParts[1] ?? '';
					$participantsEntities[] = new PollParticipantsEntity(0, null, $participantUser->email, $firstname, $lastname, $participantUser);
				}
			}
		}

		$programIds = [];
		if (!empty($programs))
		{
			$programIds = array_map('intval', array_filter(explode(',', $programs)));
		}

		$status = StatusEnum::UPCCOMING;
		if(!empty($id))
		{
			$status = $this->pollRepository->getStatusByPoll($id);
		}

		$pollEntity = new PollEntity(
			$id,
			$name,
			$description,
			ColorEnum::BLUE,
			$status,
			!empty($startDate) ? new \DateTime($startDate) : null,
			!empty($endDate) ? new \DateTime($endDate) : null,
			$participantsEntities,
			$slotsEntities,
			$canEditAnswers,
			(int) $this->user->id,
			$programIds
		);

		try
		{
			$this->pollRepository->flush($pollEntity);
		}
		catch (\Exception $e)
		{
			Log::add(
				'Error while saving poll: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine(),
				Log::ERROR,
				'com_emundus.poll'
			);
			throw new \RuntimeException(Text::_('COM_EMUNDUS_POLLS_SAVE_ERROR'));
		}

		return EmundusResponse::ok($pollEntity->__serialize(), Text::_('COM_EMUNDUS_POLLS_SAVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::UPDATE]
	])]
	public function runpoll(): EmundusResponse
	{
		$this->checkToken();

		$ids = [];
		$id  = $this->input->getInt('id', 0);
		if (empty($id))
		{
			$ids = $this->input->getString('ids');
			$ids = array_filter(array_map('intval', explode(',', $ids)));
		}
		if (empty($ids))
		{
			$ids[] = $id;
		}
		if (empty($ids))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$notify = $this->input->getInt('notify', 0);
		$subject = $this->input->getString('subject', '');
		$body    = $this->input->getRaw('body', '');

		$replyTo = trim($this->input->getString('reply_to', ''));
		if ($replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_CONTACT_REPLY_TO_INVALID'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$results = (new PollService())->runPoll($ids, StatusEnum::OPEN, $subject, $body, $notify === 1, $replyTo);

		return EmundusResponse::ok(['results' => $results], Text::_('COM_EMUNDUS_POLLS_RUN'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::UPDATE]
	])]
	public function closepoll(): EmundusResponse
	{
		$this->checkToken();

		$ids = [];
		$id  = $this->input->getInt('id', 0);
		if (empty($id))
		{
			$ids = $this->input->getString('ids');
			$ids = array_filter(array_map('intval', explode(',', $ids)));
		}
		if (empty($ids))
		{
			$ids[] = $id;
		}
		if (empty($ids))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$notify  = $this->input->getInt('notify', 0);
		$subject = $this->input->getString('subject', '');
		$body    = $this->input->getRaw('body', '');

		$replyTo = trim($this->input->getString('reply_to', ''));
		if ($replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_CONTACT_REPLY_TO_INVALID'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$results = (new PollService())->closePoll($ids, $subject, $body, $notify === 1, $replyTo);

		return EmundusResponse::ok(['results' => $results], Text::_('COM_EMUNDUS_POLLS_CLOSE'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::DELETE]
	])]
	public function delete(): EmundusResponse
	{
		$this->checkToken();

		$id  = $this->input->getInt('id', 0);
		$ids = [];
		if (empty($id))
		{
			$ids = $this->input->getString('ids', '');
			$ids = array_filter(array_map('intval', explode(',', $ids)));
		}
		if (empty($ids) && !empty($id))
		{
			$ids[] = $id;
		}
		if (empty($ids))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		foreach ($ids as $pollId)
		{
			$this->pollRepository->delete((int) $pollId);
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_POLLS_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::UPDATE]
	])]
	public function contactparticipants(): EmundusResponse
	{
		$this->checkToken();

		$ids = [];
		$id  = $this->input->getInt('id', 0);
		if (empty($id))
		{
			$ids = $this->input->getString('ids');
			$ids = array_filter(array_map('intval', explode(',', $ids)));
		}
		if (empty($ids) && !empty($id))
		{
			$ids[] = $id;
		}
		if (empty($ids))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$recipients = $this->input->getString('recipients');
		if(!empty($recipients))
		{
			$recipients = explode(',', $recipients);
		}

		$subject = $this->input->getString('subject', '');
		$body    = $this->input->getRaw('body', '');

		$replyTo = trim($this->input->getString('reply_to', ''));
		if ($replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_CONTACT_REPLY_TO_INVALID'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$pollService = new PollService();
		$results = $pollService->contactParticipants($ids, $subject, $body, $recipients, $replyTo !== '' ? $replyTo : null);

		$contactedAll = true;
		foreach($results as $result)
		{
			if (empty($result)) {
				$contactedAll = false;
			}
		}

		if (!$contactedAll)
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_POLL_CONTACT_PARTICIPANTS_FAILED'));
		}

		return EmundusResponse::ok(['results' => $results], Text::_('COM_EMUNDUS_POLL_CONTACT_PARTICIPANTS_SENT'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::UPDATE]
	])]
	public function savepollslot(): EmundusResponse
	{
		$this->checkToken();

		$pollId       = $this->input->getInt('poll_id', 0);
		$slotId       = $this->input->getInt('id', 0);
		$startDate    = $this->input->getString('start_date', '');
		$endDate      = $this->input->getString('end_date', '');
		$slotCapacity = $this->input->getInt('slot_capacity', 1);
		$locationText = $this->input->getString('location_text', '');

		if (empty($pollId) || empty($startDate) || empty($endDate))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$slot = $this->pollRepository->saveSlot($pollId, $slotId ?: null, $startDate, $endDate, $slotCapacity, $locationText !== '' ? $locationText : null);

		return EmundusResponse::ok($slot, Text::_('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_SAVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::UPDATE]
	])]
	public function deletepollslot(): EmundusResponse
	{
		$this->checkToken();

		$slotId = $this->input->getInt('id', 0);

		if (empty($slotId))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->pollRepository->deleteSlot($slotId);

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function savepollanswers(): EmundusResponse
	{
		$this->checkToken();

		$pollId     = $this->input->getInt('poll_id', 0);
		$answersRaw = $this->input->getRaw('answers', '');

		if (empty($pollId) || empty($answersRaw))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$decoded = json_decode($answersRaw, true);
		if (!is_array($decoded) || empty($decoded))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_EXCEPTION_ANSWERS_INVALID'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$answers = [];
		foreach ($decoded as $row)
		{
			$slotId      = isset($row['slot']) ? (int) $row['slot'] : 0;
			$answerValue = isset($row['answer']) ? (string) $row['answer'] : '';
			$comment     = isset($row['comment']) ? (string) $row['comment'] : '';

			if (empty($slotId) || empty($answerValue))
			{
				continue;
			}

			$answerType = AnswerTypeEnum::tryFrom($answerValue);
			if ($answerType === null)
			{
				continue;
			}

			$answers[] = [
				'slot'    => $slotId,
				'answer'  => $answerType,
				'comment' => $comment,
			];
		}

		if (empty($answers))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_EXCEPTION_ANSWERS_EMPTY'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$saved = (new PollService())->savePollAnswers($pollId, (int) $this->user->id, $answers);

		return EmundusResponse::ok(['saved' => $saved], Text::_('COM_EMUNDUS_POLL_ANSWERS_SAVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::READ]
	])]
	public function getavailableparticipants(): EmundusResponse
	{
		$emundusUserRepository = new EmundusUserRepository();
		$users                 = $emundusUserRepository->getUsersNoApplicants();
		$options               = [];

		foreach ($users as $user)
		{
			assert($user instanceof EmundusUserEntity);

			if($user->getId() === $this->user->id)
			{
				continue;
			}

			$options[] = [
				'value' => $user->getId(),
				'label' => $user->getFullname()
			];
		}

		return EmundusResponse::ok($options, Text::_('COM_EMUNDUS_POLLS_AVAILABLE_PARTICIPANTS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::READ]
	])]
	public function getavailableprograms(): EmundusResponse
	{
		$emundusUserRepository = new EmundusUserRepository();
		$programIds            = $emundusUserRepository->getUserProgramsIds((int) $this->user->id);

		$options = [];
		if (!empty($programIds))
		{
			$programRepository = new ProgramRepository();
			foreach ($programIds as $programId)
			{
				$program = $programRepository->getById((int) $programId);
				if (empty($program))
				{
					continue;
				}

				$options[] = [
					'value' => $program->getId(),
					'label' => $program->getLabel(),
				];
			}
		}

		return EmundusResponse::ok($options, Text::_('COM_EMUNDUS_POLLS_AVAILABLE_PROGRAMS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::POLLS, 'mode' => CrudEnum::READ]
	])]
	public function exportexcel(): EmundusResponse
	{
		$idsRaw = $this->input->getString('ids', '');
		$id     = $this->input->getInt('id', 0);

		$ids = [];
		if (!empty($idsRaw))
		{
			$ids = array_filter(array_map('intval', explode(',', $idsRaw)));
		}
		if (empty($ids) && !empty($id))
		{
			$ids[] = $id;
		}

		if (empty($ids))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$filepath = (new PollExportService())->exportToExcel($ids);

		return EmundusResponse::ok(
			['download_file' => Uri::root() . 'tmp/' . basename($filepath)],
			Text::_('COM_EMUNDUS_POLLS_EXPORTS_EXCEL')
		);
	}
}