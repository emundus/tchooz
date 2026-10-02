<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use EmundusModelFiles;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

class ApplicationFileActionDelete extends ApplicationFileAction
{

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::DELETE;
	}

	/**
	 * Only the applicant who owns the file can delete it, not collaborators.
	 */
	public function isAvailableForFile(ApplicationFileEntity $applicationFileEntity, ?User $currentUser = null): bool
	{
		return $this->isFileOwner($applicationFileEntity, $currentUser);
	}

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		$deleted = false;

		if (!empty($applicationFileEntity->getFnum()))
		{
			if (!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}

			try {
				$filesModel = new EmundusModelFiles();
				$deleted = $filesModel->deleteFile($applicationFileEntity->getFnum(), $currentUser?->id);
			} catch (\Exception $e) {
				Log::add('Failed to delete file ' . $applicationFileEntity->getFnum() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.application_file_actions');
				$deleted = false;
			}
		}

		return $deleted;
	}

	public function getRedirectUrl(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): ?string
	{
		return '/';
	}

	public function confirmBeforeExecute(): bool
	{
		return true;
	}
}