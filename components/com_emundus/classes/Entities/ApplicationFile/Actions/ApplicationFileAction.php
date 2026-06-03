<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

abstract class ApplicationFileAction
{
	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.application_file_actions.php'], Log::ALL, 'com_emundus.application_file_actions');
	}

	abstract public function getActionType(): ApplicationFileActionsEnum;

	public function confirmBeforeExecute(): bool
	{
		return false;
	}

	abstract public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool;

	public function __serialize(): array
	{
		return [
			'name' => $this->getActionType()->value,
			'label' => $this->getActionType()->getLabel(),
			'order' => $this->getActionType()->getOrdering(),
			'parameters' => array_map(function ($param) {
				return $param->toSchema();
			}, $this->getActionType()->getParameters()),
			'confirmBeforeExecute' => $this->confirmBeforeExecute(),
		];
	}
}