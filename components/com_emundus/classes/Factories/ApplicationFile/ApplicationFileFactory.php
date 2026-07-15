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
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Factories\Cache\RelationCache;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class ApplicationFileFactory extends AbstractFactory
{
	public const RELATION_CAMPAIGN = CampaignRepository::NAME;
	public const RELATION_STATUS = StatusRepository::NAME;
	public const RELATION_USER = 'user';

	protected const RELATIONS = [
		self::RELATION_CAMPAIGN,
		self::RELATION_STATUS,
		self::RELATION_USER,
	];

	private ?CampaignRepository $campaignRepository = null;
	private ?StatusRepository $statusRepository = null;

	public function buildEntity(object $dbObject, array $relations): ApplicationFileEntity
	{
		return new ApplicationFileEntity(
			user: $relations[self::RELATION_USER] ?? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->applicant_id),
			fnum: $dbObject->fnum,
			status: $relations[self::RELATION_STATUS] ?? null,
			campaign_id: $dbObject->campaign_id,
			published: $dbObject->published,
			data: [],
			id: (int) $dbObject->id,
			campaign: $relations[self::RELATION_CAMPAIGN] ?? null,
			date_submitted: !empty($dbObject->date_submitted) && $dbObject->date_submitted !== '0000-00-00 00:00:00' ? new \DateTime($dbObject->date_submitted) : null,
			formProgress: (int) $dbObject->form_progress,
			attachmentProgress: (int) $dbObject->attachment_progress,
			name: $dbObject->name ?? '',
			updated_at: !empty($dbObject->updated_at) ? new \DateTime($dbObject->updated_at) : null,
			updated_by: !empty($dbObject->updated_by) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject->updated_by) : null,
			shortReference: $dbObject->short_reference ?? null,
			isAnonymous: isset($dbObject->anonymous) && $dbObject->anonymous == 1,
			isPublic: isset($dbObject->public) && $dbObject->public == 1
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		return match ($relation)
		{
			self::RELATION_CAMPAIGN => $this->getCampaignRepository()->getById((int) $dbObject->campaign_id),
			self::RELATION_STATUS => $this->getStatusRepository()->getByStep((int) $dbObject->status),
			self::RELATION_USER => Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject->applicant_id),
			default => null,
		};
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return match ($relation)
		{
			self::RELATION_CAMPAIGN => (int) $dbObject->campaign_id,
			self::RELATION_STATUS => (int) $dbObject->status,
			self::RELATION_USER => (int) $dbObject->applicant_id,
			default => spl_object_id($dbObject),
		};
	}

	protected function preloadRelations(array $dbObjects, array $relationsToLoad): void
	{
		// Preload campaigns
		if (in_array(self::RELATION_CAMPAIGN, $relationsToLoad))
		{
			$campaignIds = array_unique(array_map(fn($obj) => (int) $obj->campaign_id, $dbObjects));
			$cacheNs     = self::RELATION_CAMPAIGN;

			foreach ($campaignIds as $campaignId)
			{
				if (!RelationCache::has($cacheNs, $campaignId))
				{
					$campaign = $this->getCampaignRepository()->getById($campaignId);
					RelationCache::set($cacheNs, $campaignId, $campaign);
				}
			}
		}

		// Preload statuses
		if (in_array(self::RELATION_STATUS, $relationsToLoad))
		{
			$statusSteps = array_unique(array_map(fn($obj) => (int) $obj->status, $dbObjects));
			$cacheNs     = self::RELATION_STATUS;

			foreach ($statusSteps as $step)
			{
				if (!RelationCache::has($cacheNs, $step))
				{
					$status = $this->getStatusRepository()->getByStep($step);
					RelationCache::set($cacheNs, $step, $status);
				}
			}
		}

		// Preload users
		if (in_array(self::RELATION_USER, $relationsToLoad))
		{
			$userIds     = array_unique(array_map(fn($obj) => (int) $obj->applicant_id, $dbObjects));
			$cacheNs     = self::RELATION_USER;
			$userFactory = Factory::getContainer()->get(UserFactoryInterface::class);

			foreach ($userIds as $userId)
			{
				if (!RelationCache::has($cacheNs, $userId))
				{
					RelationCache::set($cacheNs, $userId, $userFactory->loadUserById($userId));
				}
			}
		}
	}

	private function getCampaignRepository(): CampaignRepository
	{
		if ($this->campaignRepository === null)
		{
			$this->campaignRepository = new CampaignRepository();
		}

		return $this->campaignRepository;
	}

	private function getStatusRepository(): StatusRepository
	{
		if ($this->statusRepository === null)
		{
			$this->statusRepository = new StatusRepository();
		}

		return $this->statusRepository;
	}


	public function setCampaignRepository(CampaignRepository $campaignRepository): void
	{
		$this->campaignRepository = $campaignRepository;
	}

	public function setStatusRepository(StatusRepository $statusRepository): void
	{
		$this->statusRepository = $statusRepository;
	}
}