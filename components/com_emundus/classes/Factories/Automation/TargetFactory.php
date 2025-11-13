<?php

namespace Tchooz\Factories\Automation;

use Joomla\CMS\Factory;
use Tchooz\Entities\Automation\TargetEntity;
use \Joomla\Database\DatabaseDriver;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Automation\ConditionRepository;
use Tchooz\Services\Automation\TargetPredefinitionRegistry;

class TargetFactory
{
	/**
	 * @param object[] $dbObjects
	 * @param DatabaseDriver|null $db
	 * @return TargetEntity[]
	 */
	public static function fromDbObjects(array $dbObjects, ?DatabaseDriver $db = null): array
	{
		$targets = [];

		if (!empty($dbObjects)) {
			if ($db === null) {
				$db = Factory::getContainer()->get('DatabaseDriver');
			}

			$registry = new TargetPredefinitionRegistry();
			$conditionRepository = new ConditionRepository($db);

			foreach ($dbObjects as $dbObject)
			{
				$targets[] = new TargetEntity(
					(int) $dbObject->id,
					TargetTypeEnum::from((string) $dbObject->type),
					!empty($dbObject->predefinition) ? $registry->getTargetPredefinitionInstance($dbObject->predefinition) : null,
					$conditionRepository->getConditionsByTargetId((int) $dbObject->id)
				);
			}
		}

		return $targets;
	}
}