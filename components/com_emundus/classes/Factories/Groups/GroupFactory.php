<?php
/**
 * @package     Tchooz\Factories\Groups
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Groups;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Filters\FilterEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Factories\DBFactory;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

class GroupFactory implements DBFactory
{

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): GroupEntity
	{
		$programRepository = null;
		$statusRepository  = null;
		$fabrikRepository  = null;
		if ($withRelations)
		{
			$programRepository = new ProgramRepository();
			$statusRepository  = new StatusRepository();
			$fabrikRepository  = new FabrikRepository();
			$fabrikFactory     = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);
		}

		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return self::buildEntity($dbObject, $programRepository, $statusRepository, $fabrikRepository);
	}

	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true): array
	{
		$programRepository = null;
		$statusRepository  = null;
		$fabrikRepository  = null;
		if ($withRelations)
		{
			$programRepository = new ProgramRepository();
			$statusRepository  = new StatusRepository();
			$fabrikRepository  = new FabrikRepository();
			$fabrikFactory     = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

		}

		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entities[] = self::buildEntity($dbObject, $programRepository, $statusRepository, $fabrikRepository);
		}

		return $entities;
	}

	public static function buildEntity(object $dbObject, ?ProgramRepository $programRepository = null, ?StatusRepository $statusRepository = null, ?FabrikRepository $fabrikRepository = null): GroupEntity
	{
		$programs = [];
		if (!empty($programRepository) && !empty($dbObject->programs))
		{
			$programsAssociated = explode(',', $dbObject->programs);
			$programs           = $programRepository->getItemsByFields(['code' => $programsAssociated], true);
		}

		$statuses = [];
		if (!empty($statusRepository) && !empty($dbObject->statuses))
		{
			$statusRestricted = explode(',', $dbObject->statuses);
			$statuses         = $statusRepository->getItemsByFields(['step' => $statusRestricted], true);
		}

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

		return new GroupEntity(
			id: $dbObject->id,
			label: $dbObject->label,
			description: $dbObject->description ?? '',
			published: (bool) $dbObject->published,
			programs: $programs,
			anonymize: (bool) $dbObject->anonymize,
			filterStatus: (bool) $dbObject->filter_status,
			statuses: $statuses,
			visibleGroups: $visibleGroups,
			visibleAttachments: $visibleAttachments,
			class: $dbObject->class ?? 'label-blue-2',
		);
	}
}