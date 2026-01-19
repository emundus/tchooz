<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\RadioField;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ActionUpdateUserRoles extends ActionEntity
{
	public const PARAMETER_ACTION_TYPE = 'action_type';
	public const PARAMETER_ACTION_TYPE_ADD = 'add';
	public const PARAMETER_ACTION_TYPE_REMOVE = 'remove';
	public const PARAMETER_USER_ROLES = 'user_roles';


	public static function getIcon(): ?string
	{
		return 'badge';
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
		return 'update_user_roles';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_ROLES_LABEL');
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
		$this->verifyParameterValueIsValid(self::PARAMETER_USER_ROLES);

		if (!empty($context->getUserId()))
		{
			try {
				if (!class_exists('EmundusModelUsers'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
				}
				$usersModel = new \EmundusModelUsers();

				$this->addExecutionMessage(new ActionExecutionMessage('Updating roles for user ID ' . $context->getUserId(), ActionMessageTypeEnum::INFO));
				$this->addExecutionMessage(new ActionExecutionMessage('Action type: ' . $this->getParameterValue(self::PARAMETER_ACTION_TYPE), ActionMessageTypeEnum::INFO));

				switch($this->getParameterValue(self::PARAMETER_ACTION_TYPE))
				{
					case self::PARAMETER_ACTION_TYPE_ADD:
						$allAdded = true;
						foreach ($this->getParameterValue(self::PARAMETER_USER_ROLES) as $profile)
						{
							if (!$usersModel->addProfileToUser($context->getUserId(), $profile))
							{
								$this->addExecutionMessage(new ActionExecutionMessage('Failed to add profile ID ' . $profile . ' to user ID ' . $context->getUserId(), ActionMessageTypeEnum::ERROR));
								$allAdded = false;
							}
						}

						$status = $allAdded ? ActionExecutionStatusEnum::COMPLETED : ActionExecutionStatusEnum::FAILED;
						break;
					case self::PARAMETER_ACTION_TYPE_REMOVE:
						$allRemoved = true;
						foreach ($this->getParameterValue(self::PARAMETER_USER_ROLES) as $profile)
						{
							if (!$usersModel->removeProfileToUser($context->getUserId(), $profile))
							{
								$this->addExecutionMessage(new ActionExecutionMessage('Failed to remove profile ID ' . $profile . ' from user ID ' . $context->getUserId(), ActionMessageTypeEnum::ERROR));
								$allRemoved = false;
							}
						}

						$status = $allRemoved ? ActionExecutionStatusEnum::COMPLETED : ActionExecutionStatusEnum::FAILED;
						break;
				}
			}
			catch (\Exception $e) {
				$this->addExecutionMessage(new ActionExecutionMessage('Error updating user roles: ' . $e->getMessage(), ActionMessageTypeEnum::ERROR));
				Log::add('Error updating user roles in ActionUpdateUserRoles: ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
				$status = ActionExecutionStatusEnum::FAILED;
			}
		}
		else
		{
			$this->addExecutionMessage(new ActionExecutionMessage('No user ID found in target entity.', ActionMessageTypeEnum::WARNING));
		}

		return $status;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new RadioField(self::PARAMETER_ACTION_TYPE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_ROLES_FIELD_ACTION_TYPE_LABEL'), [
					new ChoiceFieldValue(self::PARAMETER_ACTION_TYPE_ADD, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_ROLES_FIELD_ACTION_TYPE_OPTION_ADD')),
					new ChoiceFieldValue(self::PARAMETER_ACTION_TYPE_REMOVE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_ROLES_FIELD_ACTION_TYPE_OPTION_REMOVE')),
				], true),
				new ChoiceField(self::PARAMETER_USER_ROLES, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_USER_ROLES_FIELD_USER_ROLES_LABEL'), $this->getRolesOptions(), true, true)
			];
		}

		return $this->parameters;
	}

	public function getLabelForLog(): string
	{
		return '';
	}

	/**
	 * Get user roles options
	 *
	 * @return array<ChoiceFieldValue>
	 */
	public function getRolesOptions(): array
	{
		$options = [];

		if (!class_exists('EmundusModelUsers'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
		}

		$usersModel = new \EmundusModelUsers();
		$applicantProfiles = $usersModel->getApplicantProfiles();
		$options[] = new ChoiceFieldValue($applicantProfiles[0]->id, Text::_('COM_EMUNDUS_APPLICANT_PROFILE_ROLE_LABEL'));

		$nonApplicantProfiles = $usersModel->getNonApplicantProfiles();
		foreach ($nonApplicantProfiles as $role)
		{
			// Skip admin and coordinator menus, they are not automatic assignable roles, only manual ones.
			if (in_array($role->menutype, ['adminmenu', 'coordinatormenu']))
			{
				continue;
			}

			$options[] = new ChoiceFieldValue($role->id, Text::_($role->label));
		}

		return $options;
	}
}