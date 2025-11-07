<?php
/**
 * @package     Tchooz\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Analytics\PageAnalyticsEntity;

class PageAnalyticsFactory
{
	public function fromDbObject(object $dbObject): PageAnalyticsEntity
	{
		return new PageAnalyticsEntity(
			(int) $dbObject->id,
			new \DateTime($dbObject->date),
			(int) $dbObject->count,
			(string) $dbObject->link
		);
	}

}