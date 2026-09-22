<?php

namespace Unit\Component\Emundus\Class\Entities\Automation;

use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

/**
 * Synchronous action emitting one execution message naming its target, so a test can tell which run
 * produced which message. Stands in for ActionAnonymize / ActionUpdateUserRoles, which do the same
 * without their side effects.
 */
class MessageEmittingAction extends ActionEntity
{
	public int $warnings = 1;

	public int $infos = 0;

	public static function getIcon(): ?string
	{
		return null;
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	public static function getType(): string
	{
		return 'message_emitting_test';
	}

	public static function getLabel(): string
	{
		return 'Message emitting test action';
	}

	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		for ($i = 0; $i < $this->warnings; $i++)
		{
			$this->addExecutionMessage(new ActionExecutionMessage(
				'Executed for ' . $context->getFile() . ($i > 0 ? ' #' . $i : ''),
				ActionMessageTypeEnum::WARNING
			));
		}

		for ($i = 0; $i < $this->infos; $i++)
		{
			$this->addExecutionMessage(new ActionExecutionMessage(
				'Tracing ' . $context->getFile() . ' #' . $i,
				ActionMessageTypeEnum::INFO
			));
		}

		return ActionExecutionStatusEnum::COMPLETED;
	}

	public function getParameters(): array
	{
		return [];
	}

	public function getLabelForLog(): string
	{
		return self::getLabel();
	}
}
