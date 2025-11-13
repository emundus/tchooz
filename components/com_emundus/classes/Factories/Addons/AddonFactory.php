<?php
/**
 * @package     Tchooz\Factories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Addons;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Addons\AddonValue;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class AddonFactory implements DBFactory
{

	public function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null): AddonEntity
	{
		if(is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		if(!empty($dbObject['value']) && is_string($dbObject['value']))
		{
			$dbObject['value'] = json_decode($dbObject['value'], true);
		}

		return new AddonEntity(
			namekey: $dbObject['namekey'] ?? '',
			value: new AddonValue(
				enabled: !empty($dbObject['value']['enabled']) && (bool) $dbObject['value']['enabled'],
				displayed: !empty($dbObject['value']['displayed']) && (bool) $dbObject['value']['displayed'],
				params: !empty($dbObject['value']['params']) ? $dbObject['value']['params'] : []
			)
		);
	}
}