<?php

namespace Tchooz\Factories\Automation;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Services\Automation\ActionRegistry;
use Tchooz\Services\Automation\TargetPredefinitionRegistry;

class ActionFactory
{
	public function fromDbObjects(array $dbObjects): array
	{
		// todo: implement later if needed
		return [];
	}


	public static function fromSerialized(array $serialized): ?ActionEntity
	{
		$action = null;

		if (!empty($serialized) && !empty($serialized['type']))
		{
			$actionRegistry = new ActionRegistry();
			$action = $actionRegistry->getActionInstance($serialized['type'], $serialized['parameter_values']);
		}

		return $action;
	}

	/**
	 * @param   object  $json
	 *
	 * @return ActionEntity
	 */
	public function fromJson(object $json): ActionEntity
	{
		$actionRegistry = new ActionRegistry();

		// Convert parameter values to associative array
		$parameterValues = json_decode(json_encode($json->parameter_values), true);
		$action = $actionRegistry->getActionInstance($json->type, $parameterValues?? []);

		if (empty($action))
		{
			throw new \InvalidArgumentException(Text::sprintf('COM_EMUNDUS_AUTOMATION_INVALID_ACTION_TYPE', $json->type));
		}

		$action->setId($json->id);

		if (!empty($json->targets))
		{
			$conditionFactory = new ConditionFactory();
			$predefinitionsRegistry = new TargetPredefinitionRegistry();

			foreach ($json->targets as $target)
			{
				$conditions = [];
				foreach ($target->conditions as $condition)
				{
					$conditions[] = $conditionFactory->fromJson($condition);
				}

				$action->addTarget(new TargetEntity(
						$target->id,
						TargetTypeEnum::from($target->type),
						!empty($target->predefinition) ? $predefinitionsRegistry->getTargetPredefinitionInstance($target->predefinition) : null,
						$conditions
					)
				);
			}
		}

		return $action;
	}
}