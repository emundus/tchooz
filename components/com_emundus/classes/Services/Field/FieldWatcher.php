<?php

namespace Tchooz\Services\Field;

use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Field\FieldEventsEnum;
use Tchooz\Enums\Field\FieldWatcherActionEnum;

class FieldWatcher
{
	/**
	 * @param   string                  $watchedField
	 * @param   array<FieldEventsEnum>  $events
	 * @param   FieldWatcherActionEnum  $action
	 */
	public function __construct(
		private string                 $watchedField,
		private array                  $events = [FieldEventsEnum::ON_CHANGE],
		private FieldWatcherActionEnum $action = FieldWatcherActionEnum::RELOAD,
	)
	{
	}
	public function getWatchedField(): string
	{
		return $this->watchedField;
	}

	public function getEvents(): array
	{
		return $this->events;
	}

	public function getAction(): FieldWatcherActionEnum
	{
		return $this->action;
	}

	public function toSchema(): array
	{
		return [
			'field'  => $this->getWatchedField(),
			'events' => array_map(fn($event) => $event->value, $this->getEvents()),
			'action' => $this->getAction()->value,
		];
	}
}