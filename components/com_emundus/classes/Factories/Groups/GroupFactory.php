<?php
/**
 * @package     Tchooz\Factories\Groups
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Groups;

use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Factories\Cache\RelationCache;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

class GroupFactory extends AbstractFactory
{
	public const RELATION_PROGRAM = ProgramRepository::NAME;
	public const RELATION_STATUS = StatusRepository::NAME;

	protected const RELATIONS = [
		self::RELATION_PROGRAM,
		self::RELATION_STATUS,
	];

	private ?ProgramRepository $programRepository = null;
	private ?StatusRepository $statusRepository = null;


	public function buildEntity(object $dbObject, array $relations): GroupEntity
	{
		$visibleGroups = [];
		if (!empty($dbObject->visible_groups))
		{
			$visibleGroups = explode(',', $dbObject->visible_groups);
			$visibleGroups = array_map('intval', $visibleGroups);
			$visibleGroups = array_filter($visibleGroups);
			$visibleGroups = array_values($visibleGroups);
		}

		$visibleAttachments = [];
		if (!empty($dbObject->visible_attachments))
		{
			$visibleAttachments = explode(',', $dbObject->visible_attachments);
			$visibleAttachments = array_map('intval', $visibleAttachments);
			$visibleAttachments = array_filter($visibleAttachments);
			$visibleAttachments = array_values($visibleAttachments);
		}
		
		if(isset($relations[self::RELATION_PROGRAM]) && !is_array($relations[self::RELATION_PROGRAM] ?? null))
		{
			$relations[self::RELATION_PROGRAM] = [$relations[self::RELATION_PROGRAM]];
		}
		if(isset($relations[self::RELATION_STATUS]) && !is_array($relations[self::RELATION_STATUS] ?? null))
		{
			$relations[self::RELATION_STATUS] = [$relations[self::RELATION_STATUS]];
		}

		return new GroupEntity(
			id: $dbObject->id,
			label: $dbObject->label,
			description: $dbObject->description ?? '',
			published: (bool) $dbObject->published,
			programs: $relations[self::RELATION_PROGRAM] ?? [],
			anonymize: (bool) $dbObject->anonymize,
			filterStatus: (bool) $dbObject->filter_status,
			statuses: $relations[self::RELATION_STATUS] ?? [],
			visibleGroups: $visibleGroups,
			visibleAttachments: $visibleAttachments,
			class: $dbObject->class ?? 'label-blue-2',
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		switch ($relation)
		{
			case self::RELATION_PROGRAM:
				if (empty($dbObject->programs))
				{
					return [];
				}

				$programs = explode(',', $dbObject->programs);
				if (empty($programs))
				{
					return [];
				}

				$programsEntities = [];
				foreach ($programs as $programCode)
				{
					if (RelationCache::has(ProgramRepository::NAME, $programCode))
					{
						$programsEntities[] = RelationCache::get(ProgramRepository::NAME, $programCode);
					}
					else
					{
						$programsEntities[] = $this->getProgramRepository()->getByCode($programCode);
					}
				}

				return array_values(array_filter($programsEntities));
			case self::RELATION_STATUS:
				if (empty($dbObject->statuses))
				{
					return [];
				}

				$statuses = explode(',', $dbObject->statuses);
				if (empty($statuses))
				{
					return [];
				}

				$statusesEntities = [];
				foreach ($statuses as $statusStep)
				{
					if (RelationCache::has(StatusRepository::NAME, $statusStep))
					{
						$statusesEntities[] = RelationCache::get(StatusRepository::NAME, $statusStep);
					}
					else
					{
						$statusesEntities[] = $this->getStatusRepository()->getByStep((int)$statusStep);
					}
				}

				return array_values(array_filter($statusesEntities));
		}

		return null;
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		// These relations are to-many: the outer cache stores an ARRAY of entities, keyed by the
		// CSV list (e.g. "2,3"). The 'program'/'status' namespaces are shared with factories that
		// cache a SINGLE entity keyed by its int id, so we prefix the key to avoid a collision
		// (PHP normalises the string key "2" to int 2, which would clash with a single-entity entry).
		return match ($relation)
		{
			self::RELATION_PROGRAM => 'group:' . ($dbObject->programs ?? ''),
			self::RELATION_STATUS => 'group:' . ($dbObject->statuses ?? ''),
			default => spl_object_id($dbObject),
		};
	}

	protected function preloadRelations(array $dbObjects, array $relationsToLoad): void
	{
		// Preload programs par training code
		if (in_array(self::RELATION_PROGRAM, $relationsToLoad))
		{
			$trainingCodes = [];
			foreach ($dbObjects as $obj)
			{
				if (!empty($obj->programs))
				{
					$codes         = explode(',', $obj->programs);
					$trainingCodes = array_merge($trainingCodes, $codes);
				}
			}
			$trainingCodes = array_unique($trainingCodes);
			$cacheNs       = self::RELATION_PROGRAM;

			foreach ($trainingCodes as $code)
			{
				if (!RelationCache::has($cacheNs, $code))
				{
					RelationCache::set($cacheNs, $code, $this->getProgramRepository()->getByCode($code));
				}
			}
		}

		// Preload statuses
		if (in_array(self::RELATION_STATUS, $relationsToLoad))
		{
			$statuses = [];
			foreach ($dbObjects as $obj)
			{
				if (!empty($obj->statuses))
				{
					$steps    = explode(',', $obj->statuses);
					$statuses = array_merge($statuses, $steps);
				}
			}
			$statuses = array_unique($statuses);
			$cacheNs  = self::RELATION_STATUS;

			foreach ($statuses as $step)
			{
				if (!RelationCache::has($cacheNs, $step))
				{
					RelationCache::set($cacheNs, $step, $this->getStatusRepository()->getByStep($step));
				}
			}
		}
	}

	private function getProgramRepository(): ProgramRepository
	{
		if ($this->programRepository === null)
		{
			$this->programRepository = new ProgramRepository();
		}

		return $this->programRepository;
	}

	private function getStatusRepository(): StatusRepository
	{
		if ($this->statusRepository === null)
		{
			$this->statusRepository = new StatusRepository();
		}

		return $this->statusRepository;
	}

}