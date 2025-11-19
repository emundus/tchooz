<?php

namespace Tchooz\Factories\Workflow;

use Tchooz\Entities\Workflow\StepTypeEntity;

class StepTypeFactory
{
	public static function fromDbObjects(array $dbObjects): array
	{
		$stepTypes = [];

		if (!empty($dbObjects))
		{
			foreach ($dbObjects as $dbObject)
			{
				$stepTypes[] = new StepTypeEntity(
					id: $dbObject->id,
					parent_id: $dbObject->parent_id,
					label: $dbObject->label,
					code: $dbObject->code,
					action_id: $dbObject->action_id ?? 0,
					system: (int)$dbObject->system === 1,
					published: (int)$dbObject->published === 1,
					class: $dbObject->class ?? '',
				);
			}
		}

		return $stepTypes;
	}
}