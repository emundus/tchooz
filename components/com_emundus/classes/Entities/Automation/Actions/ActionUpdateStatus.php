<?php

namespace Tchooz\Entities\Automation\Actions;

use EmundusModelFiles;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ActionUpdateStatus extends ActionEntity
{
	private array $statusChoices = [];

	public const STATUS_PARAMETER = 'status';

	public static function getType(): string
	{
		return 'update_status';
	}

	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_UPDATE_STATUS_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_UPDATE_STATUS_DESCRIPTION');
	}

	public function getParameters(): array
	{
		if (empty($this->parameters)) {
			$this->parameters = [
				new ChoiceField(self::STATUS_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_STATUS_PARAMETER_STATUS_LABEL'), $this->getStatusChoices(), true)
			];
		}

		return $this->parameters;
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$actionStatus = ActionExecutionStatusEnum::FAILED;

		$status = $this->getParameterValue(self::STATUS_PARAMETER);
		if (!isset($status))
		{
			throw new \RuntimeException(Text::_('TCHOOZ_AUTOMATION_ACTION_UPDATE_STATUS_ERROR_MISSING_STATUS_PARAMETER'));
		}

		if (!is_array($context))
		{
			$context = [$context];
		}

		foreach($context as $target)
		{
			if (empty($target->getFile()))
			{
				throw new \RuntimeException(Text::_('TCHOOZ_AUTOMATION_ACTION_UPDATE_STATUS_ERROR_NO_FILES_IN_CONTEXT'));
			}

			if (!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files      = new EmundusModelFiles();
			$result       = $m_files->updateState($target->getFile(), $status, $target->getTriggeredBy()->id, $executionContext);
			$resultStatus = is_bool($result) ? $result : $result['status'];

			if ($resultStatus)
			{
				$actionStatus = ActionExecutionStatusEnum::COMPLETED;
			}
			else
			{
				$actionStatus = ActionExecutionStatusEnum::FAILED;
			}
		}

		return $actionStatus;
	}

	/**
	 *
	 * @return ChoiceFieldValue[]
	 */
	private function getStatusChoices(): array
	{
		if (!empty($this->statusChoices))
		{
			return $this->statusChoices;
		} else {
			if (!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files = new EmundusModelFiles();
			$states  = $m_files->getAllStatus($this->getAutomatedTaskUserId());

			$choices = [];
			foreach ($states as $state)
			{
				$choices[] = new ChoiceFieldValue($state['step'], $state['value']);
			}

			$this->statusChoices = $choices;
		}

		return $this->statusChoices;
	}

	public static function getIcon(): ?string
	{
		return 'label';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	public function getLabelForLog(): string
	{
		$labelForLog = $this->getLabel();

		$parameterValue = $this->getParameterValue(self::STATUS_PARAMETER);
		if (!empty($parameterValue))
		{
			// get label from status choices
			$statusChoices = $this->getStatusChoices();
			$selectedStatuses = array_filter($statusChoices, function ($item) use ($parameterValue) {
				if ($item->getValue() == $parameterValue) {
					return true;
				}

				return false;
			});

			if (!empty($selectedStatuses))
			{
				$selectedStatus = array_shift($selectedStatuses);
				$labelForLog = $this->getLabel() . ' (' . $selectedStatus->getLabel() . ')';
			}
		}

		return $labelForLog;
	}
}