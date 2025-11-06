<?php

namespace Tchooz\Factories\Automation;

use Joomla\CMS\Factory;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Entities\Automation\EventEntity;
use Tchooz\Repositories\Automation\ActionRepository;
use Tchooz\Repositories\Automation\ConditionRepository;
use Joomla\Database\DatabaseDriver;

class AutomationFactory
{
	public static function fromDbObjects(array $dbObjects, ?DatabaseDriver $db = null): array
	{
		$automations = [];

		if (!empty($dbObjects))
		{
			$db = $db ?? Factory::getContainer()->get('DatabaseDriver');
			$conditionRepo = new ConditionRepository($db);
			$actionRepo = new ActionRepository($db);

			foreach ($dbObjects as $obj) {
				$event = new EventEntity($obj->event_id, $obj->event_label, $obj->event_description);
				$automation = new AutomationEntity(
					$obj->id,
					$obj->name,
					$obj->description,
					$event,
					$conditionRepo->getConditionsGroupsByAutomationId($obj->id),
					$actionRepo->getActionsByAutomationId($obj->id),
					$obj->published == 1
				);

				$automations[] = $automation;
			}
		}

		return $automations;
	}
}