<?php

namespace Tchooz\Repositories\Event;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Event\SlotEntity;
use Tchooz\Factories\Event\SlotFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\Join;
use Tchooz\Traits\TraitDispatcher;

#[TableAttribute(
	table: 'jos_emundus_setup_event_slots',
	alias: 'eses',
	columns: [
		'id',
		'event',
		'parent_slot_id',
		'start_date',
		'end_date',
		'room',
		'location_text',
		'slot_capacity',
		'more_infos',
		'link',
		'teams_id',
		'poll',
		'GROUP_CONCAT(DISTINCT epa.id) AS answers'
	]
)]
class SlotRepository extends EmundusRepository
{
	use TraitDispatcher;

	private SlotFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'slot', self::class);

		$this->factory = new SlotFactory();

		$this->joins = [
			'epa' => new Join(
				fromTable: $this->tableName,
				fromAlias: $this->alias,
				toTable: '#__emundus_poll_answers',
				toAlias: 'epa',
				fromKey: 'id',
				toKey: 'slot'
			),
		];
	}

	public function flush(SlotEntity $slot, int $eventId = 0, int $pollId = 0): bool
	{
		if(empty($slot->getStart()) || empty($slot->getEnd()))
		{
			throw new \InvalidArgumentException('Slot start and end date are required to flush SlotEntity');
		}

		$data = (object) [
			'parent_slot_id' => $slot->getParent()?->getId() ?? 0,
			'start_date' => $slot->getStart()->format('Y-m-d H:i:s'),
			'end_date' => $slot->getEnd()->format('Y-m-d H:i:s'),
			'room' => $slot->getRoom()?->getId(),
			'location_text' => $slot->getLocationText(),
			'slot_capacity' => $slot->getCapacity(),
			'more_infos' => $slot->getMoreInformations(),
			'link' => $slot->getLink(),
			'teams_id' => $slot->getTeamsId(),
		];

		$this->dispatchJoomlaEvent('onBeforeSlotSave', ['slot' => $slot]);

		if(empty($slot->getId()))
		{
			$data->event = !empty($eventId) ? $eventId : null;
			$data->poll = !empty($pollId) ? $pollId : null;
			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Error while inserting slot');
			}
			$slot->setId((int)$this->db->insertid());
		}
		else {
			$data->id = $slot->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \RuntimeException('Error while updating slot');
			}
		}

		$this->dispatchJoomlaEvent('onAfterSlotSave', ['slot' => $slot]);

		return true;
	}

	public function getFactory(): SlotFactory
	{
		return $this->factory;
	}
}