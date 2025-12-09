<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class ActionNotify extends ActionEntity
{

	public static function getIcon(): ?string
	{
		return 'notification_add';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::USER;
	}

	public static function getType(): string
	{
		return 'notify';
	}

	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_NOTIFY_LABEL');
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$this->verifyRequiredParameters();

		$message = $this->getParameterValue('message');
		$type = $this->getParameterValue('type');

		if (empty($context->getTriggeredBy()))
		{
			return ActionExecutionStatusEnum::FAILED;
		}

		$app = Factory::getApplication();

		if ($app->isClient('site'))
		{
			$app->enqueueMessage($message, $type);
			return ActionExecutionStatusEnum::COMPLETED;
		}

		return ActionExecutionStatusEnum::FAILED;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new StringField('message', Text::_('TCHOOZ_AUTOMATION_ACTION_NOTIFY_MESSAGE_LABEL'), true),
				new ChoiceField('type', Text::_('TCHOOZ_AUTOMATION_ACTION_NOTIFY_TYPE_LABEL'), [
					new ChoiceFieldValue('info', Text::_('TCHOOZ_AUTOMATION_ACTION_NOTIFY_TYPE_INFO')),
					new ChoiceFieldValue('warning', Text::_('TCHOOZ_AUTOMATION_ACTION_NOTIFY_TYPE_WARNING')),
					new ChoiceFieldValue('error', Text::_('TCHOOZ_AUTOMATION_ACTION_NOTIFY_TYPE_ERROR')),
					new ChoiceFieldValue('success', Text::_('TCHOOZ_AUTOMATION_ACTION_NOTIFY_TYPE_SUCCESS')),
				], true),
			];
		}

		return $this->parameters;
	}

	public static function supportTargetTypes(): array
	{
		return [];
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}
}