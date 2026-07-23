<?php

namespace Tchooz\Repositories\Poll;

use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Factories\Groups\GroupFactory;
use Tchooz\Factories\Poll\PollFactory;
use Tchooz\Factories\Poll\PollParticipantsFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\Event\SlotRepository;
use Tchooz\Repositories\Join;
use Tchooz\Traits\TraitDispatcher;

#[TableAttribute(
	table: 'jos_emundus_setup_polls_participants',
	alias: 'polls_participants',
	columns: [
		'id',
		'poll',
		'email',
		'firstname',
		'lastname',
		'user'
	]
)]
class PollParticipantsRepository extends EmundusRepository
{
	private PollParticipantsFactory $factory;
	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'poll_participants', self::class);

		$this->factory = new PollParticipantsFactory();
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName('#__emundus_poll_answers'))
			->where($this->db->quoteName('participant') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$this->db->execute();

		$query->clear()
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		return $this->db->execute();
	}

	public function getFactory(): PollParticipantsFactory
	{
		return $this->factory;
	}

	/**
	 * Resolve the participant row id for a given user within a specific poll.
	 *
	 * @param   int  $pollId  The poll id.
	 * @param   int  $userId  The Joomla user id.
	 *
	 * @return  int|null  The participant id, or null when the user is not a participant of the poll.
	 */
	public function getIdByPollAndUser(int $pollId, int $userId): ?int
	{
		if ($pollId <= 0 || $userId <= 0)
		{
			return null;
		}

		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('id'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('poll') . ' = :poll')
			->where($this->db->quoteName('user') . ' = :user')
			->bind(':poll', $pollId, ParameterType::INTEGER)
			->bind(':user', $userId, ParameterType::INTEGER);
		$this->db->setQuery($query);

		$id = $this->db->loadResult();

		return !empty($id) ? (int) $id : null;
	}
}