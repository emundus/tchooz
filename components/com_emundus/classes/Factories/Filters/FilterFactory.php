<?php
/**
 * @package     Tchooz\Factories\Filters
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Filters;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Finder\Administrator\Service\HTML\Filter;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Filters\FilterEntity;
use Tchooz\Enums\Filters\FilterModeEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Factories\EmundusFactory;

class FilterFactory implements DBFactory
{
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		return self::buildEntity($dbObject, $withRelations);
	}

	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entities[] = self::buildEntity($dbObject, $withRelations);
		}

		return $entities;
	}

	public static function buildEntity(object $dbObject, bool|array $withRelations = true): FilterEntity
	{
		return new FilterEntity(
			name: $dbObject->name,
			constraints: json_decode($dbObject->constraints, true) ?? [],
			user: $withRelations ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->user) : null,
			mode: FilterModeEnum::tryFrom($dbObject->mode) ?? FilterModeEnum::SEARCH,
			itemId: $dbObject->item_id,
			id: $dbObject->id ?? null,
			timeDate: $dbObject->time_date ? new \DateTime($dbObject->time_date) : null
		);
	}
}