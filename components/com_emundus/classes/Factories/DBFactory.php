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
	/**
	 * Crée une entité à partir d'un objet ou tableau DB.
	 *
	 * @param object|array         $dbObject        Les données brutes
	 * @param bool|array           $withRelations   true = toutes les relations par défaut,
	 *                                              false = aucune,
	 *                                              array = liste explicite des relations à charger
	 * @param array                $exceptRelations Relations à exclure
	 * @param DatabaseDriver|null  $db              Driver DB optionnel
	 */
	public function fromDbObject(
		object|array $dbObject,
		bool|array $withRelations = true,
		array $exceptRelations = [],
		?DatabaseDriver $db = null
	): mixed;
}