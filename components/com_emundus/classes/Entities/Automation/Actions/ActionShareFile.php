<?php

namespace Tchooz\Entities\Automation\Actions;

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

class ActionShareFile extends ActionEntity
{
	public const ADD_OR_REMOVE_PARAMETER = 'add_or_remove';
	public const ADD = 'add';
	public const REMOVE = 'remove';

	public static function getIcon(): ?string
	{
		return 'share';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'share_file';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_SHARE_FILE_LABEL');
	}

	/**
	 * @inheritDoc
	 */
	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::USER, TargetTypeEnum::GROUP];
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$this->verifyRequiredParameters();
		$executed = ActionExecutionStatusEnum::FAILED;

		if (!empty($context->getOriginalContext()->getFile()))
		{
			switch($this->getParameterValue(self::ADD_OR_REMOVE_PARAMETER))
			{
				case self::ADD:
					$actions = [['id' => 1, 'c' => 0, 'r' => 1, 'u' => 0, 'd' => 0]];
					if (!empty($context->getUserId()))
					{
						if (!class_exists('EmundusModelFiles'))
						{
							require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
						}
						$m_files = new \EmundusModelFiles();
						$shared  = $m_files->shareUsers([$context->getUserId()], $actions, [$context->getOriginalContext()->getFile()], $context->getTriggeredBy());

						if ($shared)
						{
							$executed = ActionExecutionStatusEnum::COMPLETED;
						}
					}
					else
					{
						if (!empty($context->getParameters()[TargetTypeEnum::GROUP->value]))
						{
							if (!class_exists('EmundusModelFiles'))
							{
								require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
							}
							$m_files = new \EmundusModelFiles();
							$groups  = is_array($context->getParameters()[TargetTypeEnum::GROUP->value]) ? $context->getParameters()[TargetTypeEnum::GROUP->value] : [$context->getParameters()[TargetTypeEnum::GROUP->value]];

							$shared = $m_files->shareGroups($groups, $actions, [$context->getOriginalContext()->getFile()]);

							if ($shared)
							{
								$executed = ActionExecutionStatusEnum::COMPLETED;
							}
						}
					}

					break;
				case self::REMOVE:
					if (!empty($context->getUserId()))
					{
						if (!class_exists('EmundusModelFiles'))
						{
							require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
						}
						$m_files = new \EmundusModelFiles();
						if ($m_files->unshareUsers([$context->getUserId()], [$context->getOriginalContext()->getFile()], $context->getTriggeredBy()))
						{
							$executed = ActionExecutionStatusEnum::COMPLETED;
						}
					}
					else
					{
						if (!empty($context->getParameters()[TargetTypeEnum::GROUP->value]))
						{
							if (!class_exists('EmundusModelApplication'))
							{
								require_once JPATH_ROOT . '/components/com_emundus/models/application.php';
							}
							$m_application = new \EmundusModelApplication();
							$groups  = is_array($context->getParameters()[TargetTypeEnum::GROUP->value]) ? $context->getParameters()[TargetTypeEnum::GROUP->value] : [$context->getParameters()[TargetTypeEnum::GROUP->value]];

							$deletes = [];
							foreach ($groups as $group)
							{
								$deletes[] = $m_application->deleteGroupAccess($context->getOriginalContext()->getFile(), $group, $context->getTriggeredBy()->id);
							}

							if (!in_array(false, $deletes, true))
							{
								$executed = ActionExecutionStatusEnum::COMPLETED;
							}
						}
					}
					break;
			}
		}

		return $executed;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField(self::ADD_OR_REMOVE_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_FILE_PARAMETER_ADD_OR_REMOVE_LABEL'), [
					new ChoiceFieldValue(self::ADD, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_FILE_PARAMETER_ADD_OR_REMOVE_OPTION_ADD_LABEL')),
					new ChoiceFieldValue(self::REMOVE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_SHARE_FILE_PARAMETER_ADD_OR_REMOVE_OPTION_REMOVE_LABEL')),
				], true),
			];
		}

		return $this->parameters;
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}
}