<?php
/**
 * @package     Tchooz\Factories\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Addons;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Factories\DBFactory;
use Tchooz\Providers\DateProvider;

class AddonFactory implements DBFactory
{
	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];

		foreach ($dbObjects as $dbObject)
		{
			$entities[] = self::buildEntity($dbObject);
		}

		return $entities;
	}

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null): AddonEntity
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return self::buildEntity($dbObject);
	}

	private static function buildEntity(object $dbObject): AddonEntity
	{
		$params = [];
		if(!empty($dbObject->params) && is_string($dbObject->params))
		{
			$params = json_decode($dbObject->params, true);
		}
		$default = [];
		 if(!empty($dbObject->default) && is_string($dbObject->default))
		 {
			 $default = json_decode($dbObject->default, true);
		 }

		 $activatedAt = !empty($dbObject->activated_at) && !DateProvider::isNullableDate($dbObject->activated_at) ? new \DateTimeImmutable($dbObject->activated_at) : null;
		 return new AddonEntity(
			 namekey: $dbObject->namekey ?? '',
			 activated: isset($dbObject->activated) && $dbObject->activated == 1,
			 displayed: isset($dbObject->displayed) && $dbObject->displayed == 1,
			 suggested: isset($dbObject->suggested) && $dbObject->suggested == 1,
			 params: $params,
			 default: $default,
			 activatedAt: $activatedAt
		 );
	}
}