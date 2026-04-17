<?php

namespace Tchooz\Factories\ApplicationFile;

use Tchooz\Entities\ApplicationFile\Actions\CustomApplicationFileAction;
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

	public static function customApplicationActionsFromConfig(object $customActionConfig, string $id): ?CustomApplicationFileAction
	{
		$action = null;

		if (!empty($customActionConfig->label) && !empty($customActionConfig->action))
		{
			if (is_string($customActionConfig->action))
			{
				$customActionConfig->action = json_decode($customActionConfig->action, true);
			}

			$actionsRegistry = new ActionRegistry();
			$actionInstance = $actionsRegistry->getActionInstance($customActionConfig->action['type'], $customActionConfig->action['parameter_values']);

			$action = new CustomApplicationFileAction(
				$id,
				$customActionConfig->label,
				$customActionConfig->icon ?? '',
				null,
				$actionInstance
			);
		}

		return $action;
	}
}