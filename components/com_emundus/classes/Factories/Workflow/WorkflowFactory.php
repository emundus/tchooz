<?php

namespace Tchooz\Factories\Workflow;

use Joomla\CMS\Factory;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Joomla\Database\DatabaseDriver;
use Tchooz\Repositories\Workflow\StepRepository;

class WorkflowFactory
{
	/**
	 * @param   array                $dbObjects
	 *
	 * @return array<WorkflowEntity>
	 */
	public static function fromDbObjects(array $dbObjects): array
	{
		$workflows = [];

		if (!empty($dbObjects))
		{
			$stepRepository = new StepRepository();

			foreach ($dbObjects as $dbObject)
			{
				// todo: check if steps are in dbObject, if so, use StepFactory to create them instead of querying again
				$steps = $stepRepository->getStepsByWorkflowId($dbObject->id);

				$programIds = !empty($dbObject->program_ids) ? explode(',', $dbObject->program_ids) : [];
				$programIds = array_map('intval', $programIds);
				$workflows[] = new WorkflowEntity(
					id: $dbObject->id,
					label: $dbObject->label,
					published: $dbObject->published,
					steps: $steps,
					program_ids: $programIds
				);
			}
		}

		return $workflows;
	}
}