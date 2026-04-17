<?php

namespace Tchooz\Factories\ApplicationFile;

use Tchooz\Entities\ApplicationFile\Actions\CustomApplicationFileAction;
use Tchooz\Factories\Automation\ActionFactory;
use Tchooz\Factories\Automation\ConditionGroupFactory;
use Tchooz\Services\Automation\ActionRegistry;

class ApplicationFileActionFactory
{
	/**
	 * @param   string  $json
	 *
	 * @return array<CustomApplicationFileAction>
	 */
	public static function customApplicationActionsFromJson(string $json): array
	{
		$actions = [];

		if (!empty($json))
		{
			$json = json_decode($json);


		}

		return $actions;
	}

	/**
	 * @param   object  $customActionConfig
	 * @param   string  $id
	 *
	 * @return CustomApplicationFileAction|null
	 */
	public static function customApplicationActionsFromConfig(object $customActionConfig, string $id): ?CustomApplicationFileAction
	{
		$action = null;

		if (!empty($customActionConfig->label) && !empty($customActionConfig->action))
		{
			if (is_string($customActionConfig->action))
			{
				$customActionConfig->action = json_decode($customActionConfig->action, true);
			}

			$actionInstance = ActionFactory::fromSerialized($customActionConfig->action);

			$conditionGroup = null;
			if (!empty($customActionConfig->conditions))
			{
				if (is_string($customActionConfig->conditions))
				{
					$customActionConfig->conditions = json_decode($customActionConfig->conditions);
				}

				$conditionFactory = new ConditionGroupFactory();
				$conditionGroup = $conditionFactory->fromJson($customActionConfig->conditions, []);
			}

			$action = new CustomApplicationFileAction(
				$id,
				$customActionConfig->label,
				$customActionConfig->icon ?? '',
				$conditionGroup,
				$actionInstance
			);
		}

		return $action;
	}
}