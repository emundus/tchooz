<?php

namespace Tchooz\Factories\ApplicationFile;

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\CMS\Factory;
use Tchooz\Entities\ApplicationFile\Actions\CustomApplicationFileAction;
use Tchooz\Factories\Automation\ActionFactory;
use Tchooz\Factories\Automation\ConditionGroupFactory;

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

			if (!class_exists('HtmlSanitizerSingleton'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
			}
			$sanitizer = HtmlSanitizerSingleton::getInstance();

			$label = $customActionConfig->label;
			if (is_string($label))
			{
				$decodedLabel = json_decode($label, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decodedLabel))
				{
					$label = $decodedLabel;
				}
			}

			// The label can be a per-language object (keyed by sef) or a legacy plain string.
			if (is_array($label))
			{
				$currentLanguage = substr(Factory::getApplication()->getLanguage()->getTag(), 0, 2);
				$label           = $label[$currentLanguage] ?? reset($label);
				if ($label === false)
				{
					$label = '';
				}
			}

			$action = new CustomApplicationFileAction(
				$id,
				$sanitizer->sanitizeNoHtml($label),
				$sanitizer->sanitizeNoHtml($customActionConfig->icon) ?? '',
				$conditionGroup,
				$actionInstance
			);
		}

		return $action;
	}
}