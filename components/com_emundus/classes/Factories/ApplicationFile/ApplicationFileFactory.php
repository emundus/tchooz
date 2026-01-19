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
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class ApplicationFileFactory implements DBFactory
{
	private CampaignRepository $campaignRepository;

	private StatusRepository $statusRepository;

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null, array $elements = []): ApplicationFileEntity
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		if ($withRelations)
		{
			$this->prepareRelations();
		}

		return $this->buildEntity($dbObject, $withRelations);
	}

	public function fromDbObjects(array $dbObjects, $withRelations = true): array
	{
		$entities = [];

		if ($withRelations)
		{
			$this->prepareRelations();
		}

		foreach ($dbObjects as $dbObject)
		{
			$entities[] = $this->buildEntity($dbObject, $withRelations);
		}

		return $entities;
	}

	public function buildEntity(
		object $dbObject,
		$withRelations = true
	): ApplicationFileEntity
	{
		$applicant = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->applicant_id);

		return new ApplicationFileEntity(
			user: $applicant,
			fnum: $dbObject->fnum,
			status: $withRelations ? $this->statusRepository->getByStep((int) $dbObject->status) : null,
			campaign_id: $dbObject->campaign_id,
			published: $dbObject->published,
			data: [],
			id: (int) $dbObject->id,
			campaign: $withRelations ? $this->campaignRepository->getById((int) $dbObject->campaign_id) : null,
			date_submitted: !empty($dbObject->date_submitted) ? new \DateTime($dbObject->date_submitted) : null,
			formProgress: (int) $dbObject->form_progress,
			attachmentProgress: (int) $dbObject->attachment_progress,
			updated_at: !empty($dbObject->updated_at) ? new \DateTime($dbObject->updated_at) : null,
			updated_by: !empty($dbObject->updated_by) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject->updated_by) : null,
		);
	}

	public function prepareRelations(): void
	{
		if(empty($this->campaignRepository)) {
			$this->setCampaignRepository(new CampaignRepository());
		}

		if(empty($this->statusRepository)) {
			$this->setStatusRepository(new StatusRepository());
		}
	}

	public function setStatusRepository(StatusRepository $statusRepository): void
	{
		$this->statusRepository = $statusRepository;
	}

	public function setCampaignRepository(CampaignRepository $campaignRepository): void
	{
		$this->campaignRepository = $campaignRepository;
	}
}