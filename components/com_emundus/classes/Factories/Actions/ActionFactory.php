<?php
/**
 * @package     Tchooz\Factories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Enums\Actions\ActionTypeEnum;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class ActionFactory implements DBFactory
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

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null, array $elements = []): ActionEntity
	{
		if(is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return self::buildEntity($dbObject);
	}

	public static function buildEntity(object $dbObject): ActionEntity
	{
		$crud = new CrudEntity($dbObject->multi, $dbObject->c, $dbObject->r, $dbObject->u, $dbObject->d);

		return new ActionEntity(
			id: (int) $dbObject->id,
			name: $dbObject->name,
			label: $dbObject->label,
			crud: $crud,
			ordering: (int) $dbObject->ordering,
			status: (bool) $dbObject->status,
			description: $dbObject->description ?? null,
			type: ActionTypeEnum::tryFrom($dbObject->type) ?? ActionTypeEnum::FILE
		);
	}
}