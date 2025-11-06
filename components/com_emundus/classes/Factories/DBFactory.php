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

interface DBFactory
{
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed;
}