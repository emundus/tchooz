<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class ApplicationFileActionCopy extends ApplicationFileAction
{

	private ?ApplicationFileEntity $applicationFileCopy = null;

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::COPY;
	}

	/**
	 * @param   ApplicationFileEntity  $applicationFileEntity
	 * @param   array                  $parameters
	 * @param   User|null              $currentUser
	 *
	 * @return bool
	 */
	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		$copied = false;

		if (empty($parameters['campaign_id']))
		{
			return false;
		}

		$campaignRepository = new CampaignRepository(false);
		$campaign = $campaignRepository->getById($parameters['campaign_id']);

		if (!empty($campaign))
		{
			if (!class_exists('EmundusModelApplication'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
			}
			$applicationModel = new \EmundusModelApplication();

			$newApplicationFileEntity = new ApplicationFileEntity($applicationFileEntity->getUser(), '', 0, $campaign->getId());
			$newApplicationFileEntity->setFnum($newApplicationFileEntity->generateFnum($campaign->getId(), $newApplicationFileEntity->getUser()->id));
			$applicationFileRepository = new ApplicationFileRepository();

			if ($applicationFileRepository->flush($newApplicationFileEntity))
			{
				$copied = $applicationModel->copyFile($applicationFileEntity->getFnum(), $newApplicationFileEntity->getFnum());

				if ($copied)
				{
					$this->applicationFileCopy = $newApplicationFileEntity;
				}
			}
		}

		return $copied;
	}

	/**
	 * @param   ApplicationFileEntity  $applicationFileEntity
	 * @param   array                  $parameters
	 * @param   User|null              $currentUser
	 *
	 * @return string|null
	 */
	public function getRedirectUrl(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): ?string
	{
		if (!empty($this->applicationFileCopy))
		{
			return '/index.php?option=com_emundus&task=openfile&fnum=' . $this->applicationFileCopy->getFnum();
		}

		return '';
	}
}