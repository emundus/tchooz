<?php
/**
 * @package     Tchooz\Factories\Campaigns
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Campaigns;

use DateTime;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Factories\Cache\RelationCache;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

class CampaignFactory extends AbstractFactory
{
	// TODO: Move to a private property and typed
	public const RELATION_PROGRAM = ProgramRepository::NAME;
	public const RELATION_PARENT  = CampaignRepository::NAME;

	protected const RELATIONS = [
		self::RELATION_PROGRAM,
		self::RELATION_PARENT,
	];

	private ?ProgramRepository $programRepository = null;
	private ?CampaignRepository $campaignRepository = null;

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
	): CampaignEntity
	{
		if (!empty($elements)) {
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
		if (!empty($elements)) {
			$this->elements = $elements;
		}

		return parent::fromDbObjects($dbObjects, $withRelations, $exceptRelations, $db);
	}

	public function buildEntity(object $dbObject, array $relations): CampaignEntity
	{
		$this->buildMoreProperties($dbObject, $this->elements);

		return new CampaignEntity(
			label: $dbObject->label,
			start_date: new DateTime($dbObject->start_date),
			end_date: new DateTime($dbObject->end_date),
			program: $relations[self::RELATION_PROGRAM] ?? null,
			year: $dbObject->year,
			description: $dbObject->description,
			short_description: $dbObject->short_description,
			profile_id: (int) $dbObject->profile_id,
			published: (bool) $dbObject->published,
			pinned: (bool) $dbObject->pinned,
			alias: $dbObject->alias,
			visible: (bool) $dbObject->visible,
			parent: $relations[self::RELATION_PARENT] ?? null,
			id: (int) $dbObject->id,
			moreProperties: $dbObject->more_properties ?? [],
			files_count: isset($dbObject->files_count) ? (int) $dbObject->files_count : 0,
			createdBy: isset($dbObject->user) ? (int) $dbObject->user : 0,
			isPublic: isset($dbObject->public) && $dbObject->public == 1,
			anonymizationPolicy: !empty($dbObject->anonymization_policy) ? AnonymizationPolicyEnum::tryFrom($dbObject->anonymization_policy) : AnonymizationPolicyEnum::FORBIDDEN,
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		// TODO: Pouvoir charger des relations via les colonnes de l'objet chargé par une possible jointure, fallback
		return match ($relation) {
			self::RELATION_PROGRAM => !empty($dbObject->training) ? $this->getProgramRepository()->getByCode($dbObject->training) : null,
			self::RELATION_PARENT  => !empty($dbObject->parent_id) ? $this->getCampaignRepository()->getById((int) $dbObject->parent_id) : null,
			default                => null,
		};
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return match ($relation) {
			self::RELATION_PROGRAM => $dbObject->training ?? '',
			self::RELATION_PARENT  => (int) ($dbObject->parent_id ?? 0),
			default                => spl_object_id($dbObject),
		};
	}

	// TODO: Réfléchir à un trait
	protected function preloadRelations(array $dbObjects, array $relationsToLoad): void
	{
		if (in_array(self::RELATION_PROGRAM, $relationsToLoad))
		{
			$trainingCodes = array_unique(array_filter(array_map(fn($obj) => $obj->training ?? null, $dbObjects)));
			$cacheNs       = self::RELATION_PROGRAM;

			foreach ($trainingCodes as $code)
			{
				if (!RelationCache::has($cacheNs, $code))
				{
					RelationCache::set($cacheNs, $code, $this->getProgramRepository()->getByCode($code));
				}
			}
		}

		if (in_array(self::RELATION_PARENT, $relationsToLoad))
		{
			$parentIds = array_unique(array_filter(array_map(fn($obj) => (int) ($obj->parent_id ?? 0), $dbObjects)));
			$cacheNs   = self::RELATION_PARENT;

			foreach ($parentIds as $parentId)
			{
				if ($parentId > 0 && !RelationCache::has($cacheNs, $parentId))
				{
					RelationCache::set($cacheNs, $parentId, $this->getCampaignRepository()->getById($parentId));
				}
			}
		}
	}

	private function buildMoreProperties(object $dbObject, array $elements): void
	{
		if(!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}

		$dbObject->more_properties = [];
		foreach ($elements as $element)
		{
			if (isset($dbObject->more_data[$element['name']]))
			{
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
					'formatted_value' => $formatted_value
				];
			}
		}
	}

	private function getProgramRepository(): ProgramRepository
	{
		if ($this->programRepository === null) {
			$this->programRepository = new ProgramRepository();
		}

		return $this->programRepository;
	}

	private function getCampaignRepository(): CampaignRepository
	{
		if ($this->campaignRepository === null) {
			$this->campaignRepository = new CampaignRepository();
		}

		return $this->campaignRepository;
	}

	public function setProgramRepository(ProgramRepository $programRepository): void
	{
		$this->programRepository = $programRepository;
	}

	public function setCampaignRepository(CampaignRepository $campaignRepository): void
	{
		$this->campaignRepository = $campaignRepository;
	}
}