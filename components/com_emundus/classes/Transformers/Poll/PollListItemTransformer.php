<?php

namespace Tchooz\Transformers\Poll;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Entities\List\AdditionalColumnTag;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\List\ListColumnTypesEnum;
use Tchooz\Enums\List\ListDisplayEnum;

/**
 * Build the list-view DTO for a poll: serialized entity + view flags + computed AdditionalColumns
 * (date range, status tag, participants count, participation rate, slot count, creator name).
 *
 * Keeps PollController::getpolls() focused on transport and lets the aggregation be tested
 * directly against PollEntity fixtures.
 */
class PollListItemTransformer
{
	private UserFactoryInterface $userFactory;

	public function __construct(?UserFactoryInterface $userFactory = null)
	{
		$this->userFactory = $userFactory ?? Factory::getContainer()->get(UserFactoryInterface::class);
	}

	/**
	 * @param   PollEntity         $poll
	 * @param   bool               $isReplyLayout  When true, hide management columns and surface reply-specific flags.
	 * @param   array<int, mixed>  $myAnswers      Current user's answers map (empty when not in reply layout).
	 *
	 * @return  object  Plain object ready to be JSON-encoded by EmundusResponse.
	 */
	public function transform(PollEntity $poll, bool $isReplyLayout, array $myAnswers = []): object
	{
		$dto                     = (object) $poll->__serialize();
		$dto->label              = ['fr' => $poll->getName(), 'en' => $poll->getName()];
		$dto->can_reply          = $isReplyLayout && $poll->getStatus() === StatusEnum::OPEN;
		$dto->can_export         = !$isReplyLayout;
		$dto->my_answers         = $myAnswers;
		$dto->additional_columns = $this->buildAdditionalColumns($poll, $isReplyLayout, $dto, $myAnswers);

		return $dto;
	}

	/**
	 * @param   array<int, mixed>  $myAnswers  Current user's answers map (empty when not in reply layout).
	 *
	 * @return AdditionalColumn[]
	 */
	private function buildAdditionalColumns(PollEntity $poll, bool $isReplyLayout, object $dto, array $myAnswers = []): array
	{
		$columns = [
			$this->buildDateRangeColumn($dto),
			$this->buildStatusColumn($poll),
		];

		if ($isReplyLayout)
		{
			$columns[] = $this->buildAnsweredColumn($poll, $myAnswers);
		}
		else
		{
			$columns[] = $this->buildParticipantsCountColumn($poll);
			$columns[] = $this->buildParticipationRateColumn($poll);
			$columns[] = $this->buildSlotCountColumn($poll);
			$columns[] = $this->buildCreatorColumn($dto);
		}

		return $columns;
	}

	/**
	 * Reply layout only: number of slots the current user has answered out of the total (e.g. "8/8").
	 *
	 * @param   array<int, mixed>  $myAnswers  Current user's answers map keyed by slot id.
	 */
	private function buildAnsweredColumn(PollEntity $poll, array $myAnswers): AdditionalColumn
	{
		$totalSlots    = count($poll->getSlots());
		$answeredSlots = 0;
		foreach ($myAnswers as $answer)
		{
			if (!empty($answer['answer']))
			{
				$answeredSlots++;
			}
		}

		$isComplete = $totalSlots > 0 && $answeredSlots === $totalSlots;
		$baseClass  = 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm';
		$tagClass   = $isComplete
			? $baseClass . ' tw-bg-green-500 tw-text-white'
			: $baseClass . ' tw-bg-neutral-300';

		return new AdditionalColumn(
			Text::_('COM_EMUNDUS_POLL_ANSWERED_COLUMN'),
			'',
			ListDisplayEnum::ALL,
			'',
			'',
			[
				new AdditionalColumnTag(
					Text::_('COM_EMUNDUS_POLL_ANSWERED_COLUMN'),
					$answeredSlots . '/' . $totalSlots,
					'',
					$tagClass,
				)
			],
			ListColumnTypesEnum::TAGS
		);
	}

	private function buildDateRangeColumn(object $dto): AdditionalColumn
	{
		$rangeStartDate = !empty($dto->start_date) ? (new \DateTime($dto->start_date))->format('d/m/Y') : '';
		$rangeEndDate   = !empty($dto->end_date) ? (new \DateTime($dto->end_date))->format('d/m/Y') : '';

		$rangeValue = '-';
		if (!empty($rangeStartDate) && empty($rangeEndDate))
		{
			$rangeValue = Text::_('COM_EMUNDUS_POLL_DATES_RANGE_START_AT') . ' ' . $rangeStartDate;
		}
		elseif (empty($rangeStartDate) && !empty($rangeEndDate))
		{
			$rangeValue = Text::_('COM_EMUNDUS_POLL_DATES_RANGE_END_AT') . ' ' . $rangeEndDate;
		}
		elseif (!empty($rangeStartDate) && !empty($rangeEndDate))
		{
			$rangeValue = ucfirst(Text::_('COM_EMUNDUS_ONBOARD_FROM')) . ' ' . $rangeStartDate
				. ' ' . ucfirst(Text::_('COM_EMUNDUS_ONBOARD_TO')) . ' ' . $rangeEndDate;
		}

		return new AdditionalColumn(
			Text::_('COM_EMUNDUS_POLL_DATES_RANGE'),
			'',
			ListDisplayEnum::TABLE,
			'',
			$rangeValue
		);
	}

	private function buildStatusColumn(PollEntity $poll): AdditionalColumn
	{
		return new AdditionalColumn(
			Text::_('COM_EMUNDUS_POLL_FIELD_STATUS_LABEL'),
			'',
			ListDisplayEnum::ALL,
			'',
			'',
			[
				new AdditionalColumnTag(
					Text::_('COM_EMUNDUS_ONBOARD_GROUPS_PUBLISHED'),
					$poll->getStatus()->getLabel(),
					'',
					$poll->getStatus()->getClass(),
				)
			],
			ListColumnTypesEnum::TAGS
		);
	}

	private function buildParticipantsCountColumn(PollEntity $poll): AdditionalColumn
	{
		return new AdditionalColumn(
			Text::_('COM_EMUNDUS_POLL_PARTICIPANTS_COUNT'),
			'',
			ListDisplayEnum::TABLE,
			'',
			count($poll->getParticipants())
		);
	}

	private function buildParticipationRateColumn(PollEntity $poll): AdditionalColumn
	{
		$totalParticipants = count($poll->getParticipants());
		$respondentIds     = [];
		foreach ($poll->getSlots() as $slot)
		{
			foreach ($slot->getAnswers() as $answer)
			{
				$participantId = $answer->getParticipant()?->getId();
				if (!empty($participantId))
				{
					$respondentIds[$participantId] = true;
				}
			}
		}

		$rate = $totalParticipants > 0
			? (int) round(count($respondentIds) / $totalParticipants * 100)
			: 0;

		return new AdditionalColumn(
			Text::_('COM_EMUNDUS_POLL_PARTICIPATION_RATE'),
			'',
			ListDisplayEnum::TABLE,
			'',
			$rate . ' %'
		);
	}

	private function buildSlotCountColumn(PollEntity $poll): AdditionalColumn
	{
		return new AdditionalColumn(
			Text::_('COM_EMUNDUS_POLL_SLOT_COUNT'),
			'',
			ListDisplayEnum::TABLE,
			'',
			count($poll->getSlots())
		);
	}

	private function buildCreatorColumn(object $dto): AdditionalColumn
	{
		$creatorName = '-';
		if (!empty($dto->created_by))
		{
			$creatorUser = $this->userFactory->loadUserById((int) $dto->created_by);
			if (!empty($creatorUser) && !empty($creatorUser->name))
			{
				$creatorName = $creatorUser->name;
			}
		}

		return new AdditionalColumn(
			Text::_('COM_EMUNDUS_POLL_CREATOR'),
			'',
			ListDisplayEnum::TABLE,
			'',
			$creatorName
		);
	}
}
