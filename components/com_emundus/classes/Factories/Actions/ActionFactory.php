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
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class ActionFactory implements DBFactory
{

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null, array $elements = []): ActionEntity
	{
		if(is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		return new ActionEntity(
			id: (int) $dbObject['id'],
			name: $dbObject['name'],
			label: $dbObject['label'],
			crud: new CrudEntity($dbObject['multi'], $dbObject['c'], $dbObject['r'], $dbObject['u'], $dbObject['d']),
			ordering: (int) $dbObject['ordering'],
			status: (bool) $dbObject['status'],
			description: $dbObject['description'] ?? null
		);
	}
}