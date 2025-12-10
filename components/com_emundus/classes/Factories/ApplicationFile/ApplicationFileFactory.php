<?php
/**
 * @package     Tchooz\Factories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class ApplicationFileFactory implements DBFactory
{

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null, array $elements = []): ApplicationFileEntity
	{
		if (is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		if ($withRelations)
		{
			$campaignRepository = new CampaignRepository();
		}

		$applicant = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject['applicant_id']);

		return new ApplicationFileEntity(
			user: $applicant,
			fnum: $dbObject['fnum'],
			status: $dbObject['status'],
			campaign_id: $dbObject['campaign_id'],
			published: $dbObject['published'],
			data: [],
			id: (int) $dbObject['id'],
			campaign: $withRelations ? $campaignRepository->getById((int) $dbObject['campaign_id']) : null,
			date_submitted: !empty($dbObject['date_submitted']) ? new \DateTime($dbObject['date_submitted']) : null,
			formProgress: (int) $dbObject['form_progress'],
			attachmentProgress: (int) $dbObject['attachment_progress'],
			updated_at: !empty($dbObject['updated_at']) ? new \DateTime($dbObject['updated_at']) : null,
			updated_by: !empty($dbObject['updated_by']) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject['updated_by']) : null,
		);
	}
}