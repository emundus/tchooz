<?php

namespace Tchooz\Repositories\Poll;

use Joomla\CMS\Language\Text;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Poll\PollAnswerEntity;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Traits\TraitDispatcher;

#[TableAttribute(
	table: 'jos_emundus_poll_answers',
	alias: 'poll_answers',
	columns: [
		'id',
		'answer',
		'slot',
		'comment',
		'participant'
	]
)]
class PollAnswerRepository extends EmundusRepository
{
	use TraitDispatcher;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'poll_answers', self::class);
	}

	public function flush(PollAnswerEntity $answer): bool
	{
		$data = (object) [
			'answer'      => $answer->getAnswer()->value,
			'slot'        => $answer->getSlot()->getId(),
			'comment'     => $answer->getComment(),
			'participant' => $answer->getParticipant()->getId()
		];


		$this->dispatchJoomlaEvent('onBeforePollAnswerSend', ['answer' => $answer]);

		if (empty($answer->getId()))
		{
			$existingAnswer = $this->getItemsByFields([
				'slot' => $answer->getSlot()->getId(),
				'participant' => $answer->getParticipant()->getId()
			]);
			if(!empty($existingAnswer))
			{
				throw new \RuntimeException(Text::_('COM_EMUNDUS_POLL_EXCEPTION_SLOT_ANSWER_ALREADY_EXISTS'));
			}

			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Error while inserting poll: ' . $this->db->getErrorMsg());
			}

			$answer->setId($this->db->insertid());
		}
		else
		{
			$data->id = $answer->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \RuntimeException('Error while updating poll: ' . $this->db->getErrorMsg());
			}
		}

		$this->dispatchJoomlaEvent('onAfterPollAnswerSend', ['answer' => $answer]);

		return true;
	}

	/**
	 * Build a map of the answers already submitted by a participant, keyed by slot id.
	 *
	 * @param   int  $participantId  The participant row id.
	 *
	 * @return  array<int, array{answer: string, comment: string}>
	 */
	public function getAnswersMapByParticipant(int $participantId): array
	{
		if ($participantId <= 0)
		{
			return [];
		}

		$query = $this->db->getQuery(true);
		$query->select([
				$this->db->quoteName('slot'),
				$this->db->quoteName('answer'),
				$this->db->quoteName('comment'),
			])
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('participant') . ' = :participant')
			->bind(':participant', $participantId, \Joomla\Database\ParameterType::INTEGER);
		$this->db->setQuery($query);

		$rows = $this->db->loadObjectList();

		$map = [];
		foreach ($rows as $row)
		{
			$map[(int) $row->slot] = [
				'answer'  => $row->answer,
				'comment' => $row->comment ?? '',
			];
		}

		return $map;
	}

	/**
	 * Return the id of an existing answer for a (slot, participant) pair, or null when none exists.
	 *
	 * @param   int  $slotId         The slot id.
	 * @param   int  $participantId  The participant row id.
	 *
	 * @return  int|null
	 */
	public function getAnswerId(int $slotId, int $participantId): ?int
	{
		if ($slotId <= 0 || $participantId <= 0)
		{
			return null;
		}

		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('id'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('slot') . ' = :slot')
			->where($this->db->quoteName('participant') . ' = :participant')
			->bind(':slot', $slotId, \Joomla\Database\ParameterType::INTEGER)
			->bind(':participant', $participantId, \Joomla\Database\ParameterType::INTEGER);
		$this->db->setQuery($query);

		$id = $this->db->loadResult();

		return !empty($id) ? (int) $id : null;
	}
}