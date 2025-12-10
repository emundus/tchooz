<?php
/**
 * @package     Tchooz\Factories\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Fabrik;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Fabrik\FabrikFormEntity;
use Tchooz\Enums\Fabrik\FabrikObjectsEnum;

class FabrikFactory
{
	public function fromDbObjects(array $dbObjects, $withRelations = true, FabrikObjectsEnum $object = FabrikObjectsEnum::FORM): array
	{
		$entities = [];

		if($withRelations)
		{}

		foreach ($dbObjects as $dbObject)
		{
			switch ($object)
			{
				case FabrikObjectsEnum::FORM:
					$entities[] = $this->buildFormEntity($dbObject, $withRelations);
					break;
				default:
					// Do nothing
					break;
			}
		}

		return $entities;
	}

	private function buildFormEntity(object $dbObject, $withRelations = true): FabrikFormEntity
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->created_by);
		$created = new \DateTime($dbObject->created);

		return new FabrikFormEntity(
			$dbObject->id,
			$dbObject->label,
			$created,
			$user,
		);
	}
}