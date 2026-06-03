<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\Plugin\Emundus\Anonymization\Extension\Anonymization;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

class ApplicationFileActionUnanonymize extends ApplicationFileAction
{
	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::UNANONYMIZE;
	}

	public function confirmBeforeExecute(): bool
	{
		return true;
	}

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		if (empty($currentUser) || $currentUser->id !== $applicationFileEntity->getUser()->id)
		{
			Log::add('User ' . $currentUser->id . ' tried to un-anonymize application files.', Log::ERROR, 'com_emundus.application_file_actions');
			throw new \Exception('ATTEMPT_TO_UNANONYMIZE_SOMEONE_ELSE_FILE');
		}

		if (!$applicationFileEntity->isAnonymous())
		{
			return false;
		}

		if (Anonymization::getCampaignAnonymizationPolicy($applicationFileEntity->getCampaign()) === AnonymizationPolicyEnum::FORCED)
		{
			// platform does not allow to unanonymize files
			return false;
		}

		$applicationFileEntity->setIsAnonymous(false);
		$applicationFileRepository = new ApplicationFileRepository();

		return $applicationFileRepository->flush($applicationFileEntity);
	}
}