<?php

namespace Tchooz\Factories\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Entities\Automation\EventEntity;
use Tchooz\Exception\EmundusUnknownActionException;
use Tchooz\Repositories\Automation\ActionRepository;
use Tchooz\Repositories\Automation\ConditionRepository;
use Joomla\Database\DatabaseDriver;
use Tchooz\Repositories\Automation\EventsRepository;

class AutomationFactory
{
	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.automation.factory.log.php'], Log::ALL, ['com_emundus.automation.factory']);
	}


	public static function fromDbObjects(array $dbObjects, ?DatabaseDriver $db = null): array
	{
		$automations = [];

		if (!empty($dbObjects))
		{
			$db = $db ?? Factory::getContainer()->get('DatabaseDriver');
			$conditionRepo = new ConditionRepository($db);
			$actionRepo = new ActionRepository($db);

			foreach ($dbObjects as $obj) {
				try
				{
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
				catch (EmundusUnknownActionException $e)
				{
					Log::add('Failed to create AutomationEntity from DB object ID ' . $obj->id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.automation.factory');
					continue;
				}
			}
		}

		return $automations;
	}

	/**
	 * @param   object  $json
	 *
	 * @return ?AutomationEntity
	 */
	public function fromJson(object $json): ?AutomationEntity
	{
		$automation = null;

		if (!empty($json->event) && !empty($json->name) && !empty($json->actions))
		{
			$eventRepository = new EventsRepository();
			$event = $eventRepository->getEventById($json->event);

			$conditionGroups = [];
			if (!empty($json->conditions_groups)) {
				$conditionGroupFactory = new ConditionGroupFactory();

				foreach ($json->conditions_groups as $conditionGroup)
				{
					if (isset($conditionGroup->parent_id) && $conditionGroup->parent_id != 0) {
						continue;
					}

					$conditionGroups[] = $conditionGroupFactory->fromJson($conditionGroup, $json->conditions_groups);
				}
			}

			$actions = [];
			$actionFactory = new ActionFactory();

			foreach ($json->actions as $action)
			{
				$actions[] = $actionFactory->fromJson($action);
			}

			$automation = new AutomationEntity(
				$json->id,
				$json->name,
				$json->description,
				$event,
				$conditionGroups,
				$actions,
				$json->published
			);
		}

		return $automation;
	}
}