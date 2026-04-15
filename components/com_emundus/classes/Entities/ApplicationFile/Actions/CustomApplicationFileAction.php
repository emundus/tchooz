<?php

namespace Tchooz\Entities\ApplicationFile\Actions;


use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\Actions\ActionRedirect;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\ConditionGroupEntity;

class CustomApplicationFileAction
{
	private $authorizedActionTypes = [];

	/**
	 * @param   string                $label
	 * @param   string                $icon
	 * @param   ConditionGroupEntity  $conditionGroup
	 * @param   ActionEntity          $action
	 */
	public function __construct(private string $label, private string $icon, private ConditionGroupEntity $conditionGroup, private ActionEntity $action)
	{
		$this->authorizedActionTypes = [
			ActionUpdateStatus::getType(),
			ActionRedirect::getType(),
		];
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
}