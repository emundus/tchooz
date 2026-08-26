<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Automation\EventsDefinitions\onAfterProgramCreateDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Repositories\Groups\GroupRepository;

class ActionAssociateProgramToGroups extends ActionEntity
{
	public const PARAMETER_GROUPS = 'groups';

	public static function getIcon(): ?string
	{
		return 'group_add';
	}

	/**
	 * @inheritDoc
	 */
	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::PROGRAM;
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
		return 'associate_program_to_groups';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_AUTOMATION_ACTION_ASSOCIATE_PROGRAM_TO_GROUPS_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('COM_EMUNDUS_AUTOMATION_ACTION_ASSOCIATE_PROGRAM_TO_GROUPS_DESC');
	}

	/**
	 * The program comes from the event context, the groups from the action parameters: no target to resolve.
	 * @inheritDoc
	 */
	public static function supportTargetTypes(): array
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$this->verifyRequiredParameters();
		$this->verifyParameterValueIsValid(self::PARAMETER_GROUPS);

		if (!is_array($context))
		{
			$context = [$context];
		}

		$groups = $this->getParameterValue(self::PARAMETER_GROUPS);
		if (!is_array($groups))
		{
			$groups = [$groups];
		}
		$groups = array_filter($groups);

		if (empty($groups) || empty($context))
		{
			return ActionExecutionStatusEnum::FAILED;
		}

		$groupRepository = new GroupRepository();

		foreach ($context as $target)
		{
			$programCode = $target->getParameters()[onAfterProgramCreateDefinition::PROGRAM_CODE_KEY] ?? null;

			if (empty($programCode))
			{
				$this->addExecutionMessage(new ActionExecutionMessage(Text::_('COM_EMUNDUS_AUTOMATION_ACTION_ASSOCIATE_PROGRAM_TO_GROUPS_MISSING_PROGRAM'), ActionMessageTypeEnum::WARNING));

				return ActionExecutionStatusEnum::FAILED;
			}

			foreach ($groups as $groupId)
			{
				// The link table has no unique constraint on (parent_id, course): without this check a
				// re-run of the automation would duplicate the row.
				if (!$groupRepository->checkGroupAssociated((int) $groupId, $programCode))
				{
					$groupRepository->addProgram((int) $groupId, $programCode);
				}
			}
		}

		return ActionExecutionStatusEnum::COMPLETED;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField(self::PARAMETER_GROUPS, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_ASSOCIATE_PROGRAM_TO_GROUPS_FIELD_GROUPS_LABEL'), $this->getGroupsOptions(), true, true),
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

		$groupRepository = new GroupRepository();
		$groups = $groupRepository->get();

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				assert($group instanceof GroupEntity);
				$options[] = new ChoiceFieldValue($group->getId(), $group->getLabel());
			}

			$emundusCmptConfig = ComponentHelper::getParams('com_emundus');
			$allRightsGrp      = $emundusCmptConfig->get('all_rights_group', 1);

			// The all-rights group is already linked to every new program by onAfterProgramCreate,
			// offering it here would only duplicate that behaviour.
			$options = array_filter($options, function($option) use ($allRightsGrp) {
				return $option->getValue() != $allRightsGrp;
			});
		}

		return $options;
	}

	public function getLabelForLog(): string
	{
		$labelForLog = $this->getLabel();

		$selectedGroups = $this->getParameterValue(self::PARAMETER_GROUPS);
		if (!empty($selectedGroups))
		{
			if (!is_array($selectedGroups))
			{
				$selectedGroups = [$selectedGroups];
			}

			$labels = array_map(
				fn($choice) => $choice->getLabel(),
				array_filter($this->getGroupsOptions(), fn($choice) => in_array($choice->getValue(), $selectedGroups))
			);

			if (!empty($labels))
			{
				$labelForLog .= ' - ' . implode(', ', $labels);
			}
		}

		return $labelForLog;
	}
}
