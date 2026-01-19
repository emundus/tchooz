<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\RadioField;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ActionUpdateUserGroups extends ActionEntity
{
	public const PARAMETER_ACTION_TYPE = 'action_type';
	public const PARAMETER_ACTION_TYPE_ADD = 'add';
	public const PARAMETER_ACTION_TYPE_REMOVE = 'remove';
	public const PARAMETER_USER_GROUPS = 'user_groups';

	public static function getIcon(): ?string
	{
		return 'group_add';
	}

	/**
	 * @inheritDoc
	 */
	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::USER;
	}

	/**
	 * @inheritDoc
	 */
	public static function isAsynchronous(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'update_user_groups';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_GROUPS_LABEL');
	}

	/**
	 * @inheritDoc
	 */
	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::USER];
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$status = ActionExecutionStatusEnum::FAILED;
		$this->verifyRequiredParameters();
		$this->verifyParameterValueIsValid(self::PARAMETER_USER_GROUPS);

		if (!is_array($context))
		{
			$context = [$context];
		}

		if (!empty($context))
		{
			if (!class_exists('EmundusModelUsers'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
			}
			$usersModel = new \EmundusModelUsers();

			$updated = true;
			switch ($this->getParameterValue(self::PARAMETER_ACTION_TYPE))
			{
				case self::PARAMETER_ACTION_TYPE_ADD:
					$userIds = array_map(function($target) {
						if (empty($target->getUserId()))
						{
							return null;
						}

						return ['user_id' => $target->getUserId()];
					}, $context);
					$userIds = array_filter($userIds);

					if (!$usersModel->affectToGroups($userIds, $this->getParameterValue(self::PARAMETER_USER_GROUPS), $context[0]->getTriggeredBy()))
					{
						$updated = false;
					}
					break;
				case self::PARAMETER_ACTION_TYPE_REMOVE:
					$userIds = array_map(function($target) {
						if (empty($target->getUserId()))
						{
							return null;
						}

						return $target->getUserId();
					}, $context);
					$userIds = array_filter($userIds);

					if (!$usersModel->removeFromGroups($userIds, $this->getParameterValue(self::PARAMETER_USER_GROUPS)))
					{
						$updated = false;
					}
					break;
			}

			$status = $updated ? ActionExecutionStatusEnum::COMPLETED : ActionExecutionStatusEnum::FAILED;
		}

		return $status;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new RadioField(self::PARAMETER_ACTION_TYPE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_GROUPS_FIELD_ACTION_TYPE_LABEL'), [
					new ChoiceFieldValue(self::PARAMETER_ACTION_TYPE_ADD, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_GROUPS_FIELD_ACTION_TYPE_OPTION_ADD')),
					new ChoiceFieldValue(self::PARAMETER_ACTION_TYPE_REMOVE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_GROUPS_FIELD_ACTION_TYPE_OPTION_REMOVE')),
				], true),
				new ChoiceField(self::PARAMETER_USER_GROUPS, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_GROUPS_FIELD_USER_GROUPS_LABEL'), $this->getGroupsOptions(), true, true),
			];
		}

		return $this->parameters;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	public function getGroupsOptions(): array
	{
		$options = [];

		if (!class_exists('EmundusModelGroups'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/groups.php');
		}
		$groupsModel = new \EmundusModelGroups();
		$groups      = $groupsModel->getGroups();

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				$options[] = new ChoiceFieldValue($group->id, $group->label);
			}

			$emundusCmptConfig = ComponentHelper::getParams('com_emundus');
			$allRightsGrp = $emundusCmptConfig->get('all_rights_group', 1);

			// Remove all-rights group from options to avoid automatic elevation
			$options = array_filter($options, function($option) use ($allRightsGrp) {
				return $option->getValue() != $allRightsGrp;
			});
		}

		return $options;
	}

	public function getLabelForLog(): string
	{
		return '';
	}
}