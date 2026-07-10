<?php
/**
 * @package     Tchooz\Factories
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

use Joomla\Database\DatabaseDriver;

/**
 * Interface pour les factories supportant la conversion batch d'objets DB.
 * Étend DBFactory en ajoutant fromDbObjects() avec support du preloading
 * et du cache statique des relations partagées.
 */
interface BatchDBFactory extends DBFactory
{
	/**
	 * Crée un tableau d'entités à partir d'un tableau d'objets DB.
	 * Les relations partagées sont mises en cache automatiquement.
	 *
	 * @param array                $dbObjects       Tableau d'objets/tableaux DB
	 * @param bool|array           $withRelations   true = toutes les relations par défaut,
	 *                                              false = aucune,
	 *                                              array = liste explicite des relations à charger
	 * @param array                $exceptRelations Relations à exclure
	 * @param DatabaseDriver|null  $db              Driver DB optionnel
	 * @return array Tableau d'entités
	 */
	public function fromDbObjects(
		array $dbObjects,
		bool|array $withRelations = true,
		array $exceptRelations = [],
		?DatabaseDriver $db = null
	): array;
}

