<?php

namespace Tchooz\Factories\Automation;

use Tchooz\Entities\Automation\EventEntity;
use Tchooz\Enums\Automation\EventCategoryEnum;

class EventFactory
{
	/**
	 * @param object[] $dbObjects
	 * @param DatabaseDriver|null $db
	 * @return EventEntity[]
	 */
	public static function fromDbObjects(array $dbObjects, ?DatabaseDriver $db = null): array
	{
		$events = [];

		if (!empty($dbObjects)) {
			foreach ($dbObjects as $dbObject)
			{
				$events[] = new EventEntity(
					(int) $dbObject->id,
					(string) $dbObject->label,
					(string) $dbObject->description,
					!empty($dbObject->category) ? EventCategoryEnum::from($dbObject->category) : null
				);
			}
		}

		return $events;
	}
}