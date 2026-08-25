<?php
/**
 * @package     Tchooz\Factories\Favorite
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Favorite;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Favorite\FavoriteFileEntity;
use Tchooz\Factories\DBFactory;

class FavoriteFileFactory implements DBFactory
{
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return self::buildEntity($dbObject);
	}

	/**
	 * @return array<FavoriteFileEntity>
	 */
	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			if (is_array($dbObject))
			{
				$dbObject = (object) $dbObject;
			}

			$entities[] = self::buildEntity($dbObject);
		}

		return $entities;
	}

	public static function buildEntity(object $dbObject): FavoriteFileEntity
	{
		return new FavoriteFileEntity(
			fnum: $dbObject->fnum ?? '',
			userId: (int) ($dbObject->user_id ?? 0),
			id: (int) ($dbObject->id ?? 0),
			created: !empty($dbObject->created) ? new \DateTimeImmutable($dbObject->created) : null
		);
	}
}
