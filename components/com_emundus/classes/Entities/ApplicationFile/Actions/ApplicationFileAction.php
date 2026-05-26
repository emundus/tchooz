<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

abstract class ApplicationFileAction
{
	abstract public function getActionType(): ApplicationFileActionsEnum;

	abstract public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool;

	public function __serialize(): array
	{
		return [
			'name' => $this->getActionType()->value,
			'label' => $this->getActionType()->getLabel(),
			'order' => $this->getActionType()->getOrdering(),
			'parameters' => array_map(function ($param) {
				return $param->toSchema();
			}, $this->getActionType()->getParameters())
		];
	}
}