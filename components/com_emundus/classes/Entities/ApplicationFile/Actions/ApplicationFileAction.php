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

	/**
	 * Declarative per-file availability. Replaces the special cases that used
	 * to live inside the registry (delete status gating, public copy ban, ...).
	 */
	public function isAvailableForFile(ApplicationFileEntity $applicationFileEntity, ?User $currentUser = null): bool
	{
		return true;
	}

	/**
	 * Whether the current user owns the application file (the applicant), as opposed
	 * to a collaborator the file was shared with. Collaborators must not be able to
	 * copy or delete a file they do not own.
	 */
	protected function isFileOwner(ApplicationFileEntity $applicationFileEntity, ?User $currentUser = null): bool
	{
		return !empty($currentUser) && (int) $applicationFileEntity->getUser()->id === (int) $currentUser->id;
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