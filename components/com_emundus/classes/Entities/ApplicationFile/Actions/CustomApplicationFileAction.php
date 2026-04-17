<?php

namespace Tchooz\Entities\ApplicationFile\Actions;


use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\Actions\ActionRedirect;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class CustomApplicationFileAction
{
	private $authorizedActionTypes = [];

	/**
	 * @param   string                 $id
	 * @param   string                 $label
	 * @param   string                 $icon
	 * @param   ?ConditionGroupEntity  $conditionGroup
	 * @param   ActionEntity           $action
	 */
	public function __construct(private string $id, private string $label, private string $icon, private ?ConditionGroupEntity $conditionGroup, private ActionEntity $action)
	{
		$this->authorizedActionTypes = [
			ActionUpdateStatus::getType(),
			ActionRedirect::getType(),
		];
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setId(string $id): CustomApplicationFileAction
	{
		$this->id = $id;

		return $this;
	}

	public function getLabel(): string
	{
		return Text::_($this->label);
	}

	public function setLabel(string $label): self
	{
		$this->label = $label;

		return $this;
	}

	public function getIcon(): string
	{
		return $this->icon;
	}

	public function setIcon(string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}

	public function getConditionGroup(): ConditionGroupEntity
	{
		return $this->conditionGroup;
	}

	public function setConditionGroup(ConditionGroupEntity $conditionGroup): self
	{
		$this->conditionGroup = $conditionGroup;

		return $this;
	}

	public function getAction(): ActionEntity
	{
		return $this->action;
	}

	public function setAction(ActionEntity $action): self
	{
		if (!in_array($action, $this->authorizedActionTypes))
		{
			throw new \Exception('FORBIDDEN_ACTION_TYPE');
		}

		$this->action = $action;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'name' => $this->id,
			'label' => $this->label,
			'order' => 9999,
			'parameters' => []
		];
	}

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::CUSTOM;
	}

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		$executed = false;

		$target = new ActionTargetEntity($currentUser, $applicationFileEntity->getFnum(), $applicationFileEntity->getUser()->id);
		if ($this->getConditionGroup()->isSatisfied($target))
		{
			if ($this->getAction() instanceof ActionRedirect)
			{
				$executed = true;
			}
			else if ($this->getAction()->execute($target) === ActionExecutionStatusEnum::COMPLETED)
			{
				$executed = true;
			}
		}

		return $executed;
	}

	public function getRedirectUrl(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): string
	{
		$url = '';

		$target = new ActionTargetEntity($currentUser, $applicationFileEntity->getFnum(), $applicationFileEntity->getUser()->id);
		if ($this->getConditionGroup()->isSatisfied($target))
		{
			if ($this->getAction() instanceof ActionRedirect)
			{
				$url = $this->getAction()->constructLink();
			}
		}

		return $url;
	}
}