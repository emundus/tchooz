<?php

namespace Tchooz\Repositories\Poll;

use Joomla\Database\ParameterType;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Factories\Poll\PollFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\Event\SlotRepository;
use Tchooz\Repositories\Join;
use Tchooz\Traits\TraitDispatcher;

#[TableAttribute(
	table: 'jos_emundus_setup_polls',
	alias: 'polls',
	columns: [
		'id',
		'name',
		'description',
		'color',
		'status',
		'start_date',
		'end_date',
		'can_edit_answers',
		'created_by',
		'GROUP_CONCAT(DISTINCT eses.id) AS slots',
		'GROUP_CONCAT(DISTINCT espp.id) AS participants',
		'GROUP_CONCAT(DISTINCT espr.program) AS programs'
	]
)]
class PollRepository extends EmundusRepository
{
	use TraitDispatcher;

	private PollFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'poll', self::class);

		$this->factory = new PollFactory();

		$this->joins = [
			'espp' => new Join(
				fromTable: $this->tableName,
				fromAlias: $this->alias,
				toTable: '#__emundus_setup_polls_participants',
				toAlias: 'espp',
				fromKey: 'id',
				toKey: 'poll'
			),
			'eses' => new Join(
				fromTable: $this->tableName,
				fromAlias: $this->alias,
				toTable: '#__emundus_setup_event_slots',
				toAlias: 'eses',
				fromKey: 'id',
				toKey: 'poll'
			),
			'espr' => new Join(
				fromTable: $this->tableName,
				fromAlias: $this->alias,
				toTable: '#__emundus_setup_polls_programs',
				toAlias: 'espr',
				fromKey: 'id',
				toKey: 'poll'
			),
		];

		$this->searchableColumns = [
			'name',
			'description'
		];
	}

	public function flush(PollEntity $poll): bool
	{
		if (empty($poll->getName()))
		{
			throw new \InvalidArgumentException('Poll name is required to flush PollEntity');
		}

		$data = (object) [
			'name'        => $poll->getName(),
			'description' => $poll->getDescription(),
			'color'       => $poll->getColor()->value,
			'status'      => $poll->getStatus()->value,
			'start_date'  => $poll->getStartDate()?->format('Y-m-d H:i:s'),
			'end_date'    => $poll->getEndDate()?->format('Y-m-d H:i:s'),
			'can_edit_answers' => $poll->canEditAnswers() ? 1 : 0,
		];

		$this->dispatchJoomlaEvent('onBeforePollSave', ['poll' => $poll]);

		$existingParticipants       = [];
		$existingParticipantsEmails = [];
		$newParticipantsEmails      = [];
		$pollParticipantsRepository = new PollParticipantsRepository();

		if (empty($poll->getId()))
		{
			// The creator is only recorded on creation, never overwritten on update.
			$data->created_by = $poll->getCreatedBy();

			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Error while inserting poll: ' . $this->db->getErrorMsg());
			}

			$poll->setId($this->db->insertid());
		}
		else
		{
			$data->id = $poll->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \RuntimeException('Error while updating poll: ' . $this->db->getErrorMsg());
			}

			$existingParticipants = $pollParticipantsRepository->getItemsByFields(['poll' => $poll->getId()]);
			foreach ($existingParticipants as $participant)
			{
				$existingParticipantsEmails[] = $participant->email;
			}
		}

		if (!empty($poll->getSlots()))
		{
			$slotRepository = new SlotRepository();
			foreach ($poll->getSlots() as $slot)
			{
				$slotRepository->flush($slot, 0, $poll->getId());
			}
		}

		$this->flushPrograms($poll);

		foreach ($poll->getParticipants() as $participant)
		{
			assert($participant instanceof PollParticipantsEntity);

			$newParticipantsEmails[] = $participant->getEmail();
			if (in_array($participant->getEmail(), $existingParticipantsEmails))
			{
				continue;
			}

			$participant->setPoll($poll);
			$data = (object) [
				'poll'      => $participant->getPoll()->getId(),
				'email'     => $participant->getEmail(),
				'firstname' => $participant->getFirstname(),
				'lastname'  => $participant->getLastname(),
				'user'      => $participant->getUser()?->id,
			];
			$this->db->insertObject('#__emundus_setup_polls_participants', $data);
		}

		foreach ($existingParticipants as $existingParticipant)
		{
			if (!in_array($existingParticipant->email, $newParticipantsEmails))
			{
				$pollParticipantsRepository->delete($existingParticipant->id);
			}
		}

		$this->dispatchJoomlaEvent('onAfterPollSave', ['poll' => $poll]);

		return true;
	}

	public function getFactory(): PollFactory
	{
		return $this->factory;
	}

	/**
	 * Ids of upcoming polls whose start date has been reached and that should now be opened.
	 *
	 * @return int[]
	 */
	public function getPollIdsToOpen(): array
	{
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__emundus_setup_polls'))
			->where($this->db->quoteName('status') . ' = ' . $this->db->quote(StatusEnum::UPCCOMING->value))
			->where($this->db->quoteName('start_date') . ' IS NOT NULL')
			->where($this->db->quoteName('start_date') . ' <= NOW()');
		$this->db->setQuery($query);

		return array_map('intval', $this->db->loadColumn() ?: []);
	}

	/**
	 * Ids of open polls whose end date has passed and that should now be closed.
	 *
	 * @return int[]
	 */
	public function getPollIdsToClose(): array
	{
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__emundus_setup_polls'))
			->where($this->db->quoteName('status') . ' = ' . $this->db->quote(StatusEnum::OPEN->value))
			->where($this->db->quoteName('end_date') . ' IS NOT NULL')
			->where($this->db->quoteName('end_date') . ' < CURDATE()');
		$this->db->setQuery($query);

		return array_map('intval', $this->db->loadColumn() ?: []);
	}

	public function deleteAllParticipants(int $pollId): bool
	{
		if (empty($pollId))
		{
			throw new \InvalidArgumentException('poll_id is required to delete all participants');
		}

		$query = $this->db->getQuery(true);
		$query->delete($this->db->quoteName('#__emundus_setup_polls_participants'))
			->where($this->db->quoteName('poll') . ' = :pollId')
			->bind(':pollId', $pollId, ParameterType::INTEGER);
		$this->db->setQuery($query);

		return $this->db->execute();
	}

	/**
	 * Synchronise the programme associations of a poll (delete-then-insert).
	 *
	 * @param   PollEntity  $poll  The poll whose programmes must be persisted.
	 *
	 * @return  bool  True on success.
	 */
	public function flushPrograms(PollEntity $poll): bool
	{
		if (empty($poll->getId()))
		{
			throw new \InvalidArgumentException('Poll id is required to flush programme associations');
		}

		$pollId = $poll->getId();

		$deleteQuery = $this->db->getQuery(true);
		$deleteQuery->delete($this->db->quoteName('#__emundus_setup_polls_programs'))
			->where($this->db->quoteName('poll') . ' = :pollId')
			->bind(':pollId', $pollId, ParameterType::INTEGER);
		$this->db->setQuery($deleteQuery);
		$this->db->execute();

		foreach ($poll->getPrograms() as $programId)
		{
			$data = (object) [
				'poll'    => $pollId,
				'program' => (int) $programId,
			];

			if (!$this->db->insertObject('#__emundus_setup_polls_programs', $data))
			{
				throw new \RuntimeException('Error while inserting poll programme association: ' . $this->db->getErrorMsg());
			}
		}

		return true;
	}

	/**
	 * Ids of polls a user may manage: the ones they created or that are shared
	 * with a programme they manage.
	 *
	 * @param   int        $userId      The user id.
	 * @param   int[]      $programIds  Ids of the programmes the user manages.
	 *
	 * @return  int[]
	 */
	public function getAccessiblePollIds(int $userId, array $programIds = []): array
	{
		if (empty($userId) && empty($programIds))
		{
			return [];
		}

		$query = $this->db->getQuery(true);
		$query->select('DISTINCT ' . $this->db->quoteName('p.id'))
			->from($this->db->quoteName('#__emundus_setup_polls', 'p'))
			->leftJoin(
				$this->db->quoteName('#__emundus_setup_polls_programs', 'pp')
				. ' ON ' . $this->db->quoteName('pp.poll') . ' = ' . $this->db->quoteName('p.id')
			);

		$conditions = [];
		if (!empty($userId))
		{
			$conditions[] = $this->db->quoteName('p.created_by') . ' = ' . (int) $userId;
		}
		if (!empty($programIds))
		{
			$programIds   = array_map('intval', $programIds);
			$conditions[] = $this->db->quoteName('pp.program') . ' IN (' . implode(',', $programIds) . ') OR pp.id IS NULL';
		}

		$query->where('(' . implode(' OR ', $conditions) . ')');
		$this->db->setQuery($query);

		return array_map('intval', $this->db->loadColumn() ?: []);
	}

	public function saveSlot(int $pollId, ?int $slotId, string $startDate, string $endDate, int $slotCapacity = 1, ?string $locationText = null): object
	{
		if (empty($pollId) || empty($startDate) || empty($endDate))
		{
			throw new \InvalidArgumentException('poll_id, start_date and end_date are required to save a poll slot');
		}

		if ($slotCapacity < 1)
		{
			$slotCapacity = 1;
		}

		$timezone = \Joomla\CMS\Factory::getApplication()->get('offset', 'Europe/Paris');

		$data = (object) [
			'poll'          => $pollId,
			'start_date'    => \Joomla\CMS\Factory::getDate($startDate, $timezone)->toSql(),
			'end_date'      => \Joomla\CMS\Factory::getDate($endDate, $timezone)->toSql(),
			'location_text' => $locationText,
			'slot_capacity' => $slotCapacity,
		];

		if (empty($slotId))
		{
			if (!$this->db->insertObject('#__emundus_setup_event_slots', $data))
			{
				throw new \RuntimeException('Error while inserting poll slot');
			}

			$data->id = $this->db->insertid();
		}
		else
		{
			$data->id = $slotId;
			if (!$this->db->updateObject('#__emundus_setup_event_slots', $data, 'id'))
			{
				throw new \RuntimeException('Error while updating poll slot');
			}
		}

		return $data;
	}

	/**
	 * Delete a poll and all of its related data (answers, slots, participants) atomically.
	 *
	 * @param   int  $pollId  The poll id to delete.
	 *
	 * @return  bool  True on success.
	 *
	 * @throws \InvalidArgumentException  When no poll id is provided.
	 * @throws \RuntimeException          When the deletion fails.
	 */
	public function delete(int $pollId): bool
	{
		if (empty($pollId))
		{
			throw new \InvalidArgumentException('poll_id is required to delete a poll');
		}

		$this->dispatchJoomlaEvent('onBeforePollDelete', ['pollId' => $pollId]);

		$this->db->transactionStart();

		try
		{
			// Collect slot ids of the poll to remove their answers first.
			$slotQuery = $this->db->getQuery(true);
			$slotQuery->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_setup_event_slots'))
				->where($this->db->quoteName('poll') . ' = :pollId')
				->bind(':pollId', $pollId, ParameterType::INTEGER);
			$this->db->setQuery($slotQuery);
			$slotIds = array_map('intval', $this->db->loadColumn() ?: []);

			if (!empty($slotIds))
			{
				$answerQuery = $this->db->getQuery(true);
				$answerQuery->delete($this->db->quoteName('#__emundus_poll_answers'))
					->whereIn($this->db->quoteName('slot'), $slotIds);
				$this->db->setQuery($answerQuery);
				$this->db->execute();

				$slotsDeleteQuery = $this->db->getQuery(true);
				$slotsDeleteQuery->delete($this->db->quoteName('#__emundus_setup_event_slots'))
					->where($this->db->quoteName('poll') . ' = :pollId')
					->bind(':pollId', $pollId, ParameterType::INTEGER);
				$this->db->setQuery($slotsDeleteQuery);
				$this->db->execute();
			}

			$this->deleteAllParticipants($pollId);

			$programsDeleteQuery = $this->db->getQuery(true);
			$programsDeleteQuery->delete($this->db->quoteName('#__emundus_setup_polls_programs'))
				->where($this->db->quoteName('poll') . ' = :pollId')
				->bind(':pollId', $pollId, ParameterType::INTEGER);
			$this->db->setQuery($programsDeleteQuery);
			$this->db->execute();

			$pollDeleteQuery = $this->db->getQuery(true);
			$pollDeleteQuery->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('id') . ' = :pollId')
				->bind(':pollId', $pollId, ParameterType::INTEGER);
			$this->db->setQuery($pollDeleteQuery);
			$this->db->execute();

			$this->db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$this->db->transactionRollback();

			throw new \RuntimeException('Error while deleting poll: ' . $e->getMessage());
		}

		$this->dispatchJoomlaEvent('onAfterPollDelete', ['pollId' => $pollId]);

		return true;
	}

	public function deleteSlot(int $slotId): bool
	{
		if (empty($slotId))
		{
			throw new \InvalidArgumentException('slot_id is required to delete a poll slot');
		}

		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName('#__emundus_poll_answers'))
			->where($this->db->quoteName('slot') . ' = :slotId')
			->bind(':slotId', $slotId, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$this->db->execute();

		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_event_slots'))
			->where($this->db->quoteName('id') . ' = ' . (int) $slotId);
		$this->db->setQuery($query);
		return (bool) $this->db->execute();
	}

	public function getStatusByPoll(int $id): ?StatusEnum
	{
		$query = $this->db->getQuery(true);

		$query->select('status')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$status = $this->db->loadResult();

		return StatusEnum::tryFrom($status);
	}
}