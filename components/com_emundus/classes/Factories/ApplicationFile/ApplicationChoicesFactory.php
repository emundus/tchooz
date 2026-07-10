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
use Tchooz\Factories\AbstractFactory;
use Tchooz\Factories\Cache\RelationCache;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class ApplicationChoicesFactory extends AbstractFactory
{
	public const RELATION_CAMPAIGN         = CampaignRepository::NAME;
	public const RELATION_APPLICATION_FILE = ApplicationFileRepository::NAME;
	public const RELATION_USER             = 'user';

	protected const RELATIONS = [
		self::RELATION_CAMPAIGN,
		self::RELATION_APPLICATION_FILE,
		self::RELATION_USER,
	];

	protected string $cacheNamespace = 'application_choices';

	/**
	 * Sous-relations par défaut :
	 * - L'ApplicationFile chargé n'ira PAS re-chercher sa campaign
	 *   (puisqu'on la charge déjà au niveau de la campagne du voeu dans l'attribut parent).
	 */
	protected array $subRelations = [
		self::RELATION_APPLICATION_FILE => [
			'withRelations'   => true,
			'exceptRelations' => [CampaignRepository::NAME],
		],
	];

	private ?CampaignRepository $campaignRepository = null;
	private ?ApplicationFileRepository $applicationFileRepository = null;

	private array $elements = [];

	public function setElements(array $elements): self
	{
		$this->elements = $elements;

		return $this;
	}

	public function fromDbObject(
		object|array    $dbObject,
		bool|array      $withRelations = true,
		array           $exceptRelations = [],
		?DatabaseDriver $db = null,
		array           $elements = []
	): ApplicationChoicesEntity
	{
		if (!empty($elements))
		{
			$this->elements = $elements;
		}

		return parent::fromDbObject($dbObject, $withRelations, $exceptRelations, $db);
	}

	public function fromDbObjects(
		array           $dbObjects,
		bool|array      $withRelations = true,
		array           $exceptRelations = [],
		?DatabaseDriver $db = null,
		array           $elements = []
	): array
	{
		if (!empty($elements))
		{
			$this->elements = $elements;
		}

		return parent::fromDbObjects($dbObjects, $withRelations, $exceptRelations, $db);
	}

	public function buildEntity(object $dbObject, array $relations): ApplicationChoicesEntity
	{
		$this->buildMoreProperties($dbObject, $this->elements);

		return new ApplicationChoicesEntity(
			fnum: $dbObject->fnum,
			user: $relations[self::RELATION_USER] ?? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->user_id),
			campaign: $relations[self::RELATION_CAMPAIGN] ?? null,
			campaign_id: $dbObject->campaign_id,
			order: (int) $dbObject->order,
			state: ChoicesStateEnum::tryFrom($dbObject->state),
			id: (int) $dbObject->id,
			moreProperties: $dbObject->more_properties ?? [],
			application_file: $relations[self::RELATION_APPLICATION_FILE] ?? null,
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		return match ($relation)
		{
			self::RELATION_CAMPAIGN         => $this->getCampaignRepository()->getById((int) $dbObject->campaign_id),
			self::RELATION_APPLICATION_FILE => $this->loadApplicationFile($dbObject),
			self::RELATION_USER             => Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject->user_id),
			default                         => null,
		};
	}

	private function loadApplicationFile(object $dbObject): ?ApplicationFileEntity
	{
		$config = $this->getSubRelationConfig(self::RELATION_APPLICATION_FILE);
		$repo   = $this->getApplicationFileRepository($config['withRelations'], $config['exceptRelations']);

		return $repo->getByFnum($dbObject->fnum);
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return match ($relation)
		{
			self::RELATION_CAMPAIGN         => (int) $dbObject->campaign_id,
			self::RELATION_APPLICATION_FILE => $dbObject->fnum,
			self::RELATION_USER             => (int) $dbObject->user_id,
			default                         => spl_object_id($dbObject),
		};
	}

	protected function preloadRelations(array $dbObjects, array $relationsToLoad): void
	{
		if (in_array(self::RELATION_CAMPAIGN, $relationsToLoad))
		{
			$campaignIds = array_unique(array_map(fn($obj) => (int) $obj->campaign_id, $dbObjects));
			$cacheNs     = self::RELATION_CAMPAIGN;

			foreach ($campaignIds as $campaignId)
			{
				if (!RelationCache::has($cacheNs, $campaignId))
				{
					RelationCache::set($cacheNs, $campaignId, $this->getCampaignRepository()->getById($campaignId));
				}
			}
		}

		if (in_array(self::RELATION_APPLICATION_FILE, $relationsToLoad))
		{
			$fnums   = array_unique(array_map(fn($obj) => $obj->fnum, $dbObjects));
			$config  = $this->getSubRelationConfig(self::RELATION_APPLICATION_FILE);
			$subConfigKey = !empty($config['exceptRelations']) || $config['withRelations'] !== true
				? '|' . md5(serialize($config))
				: '';
			$cacheNs = self::RELATION_APPLICATION_FILE;
			$repo    = $this->getApplicationFileRepository($config['withRelations'], $config['exceptRelations']);

			foreach ($fnums as $fnum)
			{
				if (!RelationCache::has($cacheNs, $fnum . $subConfigKey))
				{
					RelationCache::set($cacheNs, $fnum . $subConfigKey, $repo->getByFnum($fnum));
				}
			}
		}

		if (in_array(self::RELATION_USER, $relationsToLoad))
		{
			$userIds     = array_unique(array_map(fn($obj) => (int) $obj->user_id, $dbObjects));
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

	private function buildMoreProperties(object $dbObject, array $elements): void
	{
		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}

		$dbObject->more_properties = [];

		if (empty($elements) || empty($dbObject->more_data))
		{
			return;
		}

		foreach ($elements as $element)
		{
			if (!isset($dbObject->more_data[$element['name']]))
			{
				continue;
			}

			if (is_array($dbObject->more_data[$element['name']]))
			{
				$formatted_value = [];
				foreach ($dbObject->more_data[$element['name']] as $value)
				{
					$formatted_value[] = \EmundusHelperFabrik::formatElementValue($element['name'], $value, $element['group_id']);
				}

				$formatted_value = implode(', ', $formatted_value);
			}
			else
			{
				$formatted_value = \EmundusHelperFabrik::formatElementValue($element['name'], $dbObject->more_data[$element['name']]);
			}

			$dbObject->more_properties[$element['name']] = [
				'id'              => $element['id'],
				'label'           => Text::_($element['label']),
				'value'           => $dbObject->more_data[$element['name']],
				'hidden'          => $element['hidden'] == 1 || $element['plugin'] === 'internalid',
				'formatted_value' => $formatted_value,
			];
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

	private function getApplicationFileRepository(bool|array $withRelations = true, array $exceptRelations = []): ApplicationFileRepository
	{
		if ($this->applicationFileRepository === null
			|| $this->applicationFileRepository->getWithRelations() !== $withRelations
			|| $this->applicationFileRepository->getExceptRelations() !== $exceptRelations)
		{
			$this->applicationFileRepository = new ApplicationFileRepository($withRelations, $exceptRelations);
		}

		return $this->applicationFileRepository;
	}

	public function setCampaignRepository(CampaignRepository $campaignRepository): void
	{
		$this->campaignRepository = $campaignRepository;
	}

	public function setApplicationFileRepository(ApplicationFileRepository $applicationFileRepository): void
	{
		$this->applicationFileRepository = $applicationFileRepository;
	}
}