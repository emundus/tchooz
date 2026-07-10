<?php
/**
 * @package     Tchooz\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 *
 * @deprecated  Utiliser AbstractFactory à la place. Cette classe est conservée pour rétro-compatibilité.
 */

namespace Tchooz\Factories;

/**
 * Couche de compatibilité entre l'ancienne API (tableaux, loadRequestedRelations)
 * et la nouvelle AbstractFactory (objets, cache statique, preloading).
 *
 * Les factories existantes (ContactFactory, OrganizationFactory...) qui étendent
 * cette classe continuent de fonctionner sans changement.
 *
 * Pour les nouvelles factories, étendez AbstractFactory directement.
 *
 * @deprecated Utiliser Tchooz\Factories\AbstractFactory à la place.
 */
abstract class EmundusFactory extends AbstractFactory
{
	/**
	 * Adaptateur de compatibilité : les anciennes factories passent un tableau,
	 * la nouvelle architecture passe un objet.
	 *
	 * @deprecated Implémenter loadRelation(string $relation, object $dbObject) et utiliser fromDbObject() à la place
	 */
	protected function loadRequestedRelations(array $object, bool|array $withRelations, array $exceptRelations): array
	{
		$relationsToLoad = $this->resolveRelations($withRelations, $exceptRelations);

		$results = [];
		foreach ($relationsToLoad as $relation)
		{
			if (!in_array($relation, static::RELATIONS, true))
			{
				continue;
			}
			// Appel via l'ancienne signature (tableau)
			$results[$relation] = $this->loadRelationFromArray($relation, $object);
		}

		return $results;
	}

	/**
	 * Méthode appelée par les anciennes factories avec un tableau.
	 * Les classes enfants qui n'ont pas migré vers AbstractFactory override celle-ci.
	 *
	 * @deprecated Migrer vers loadRelation(string $relation, object $dbObject)
	 */
	protected function loadRelationFromArray(string $relation, array $object): mixed
	{
		return null;
	}

	// ──────────────────────────────────────────────────────────────────
	// Implémentation des méthodes abstraites d'AbstractFactory
	// avec des valeurs par défaut pour la rétro-compatibilité
	// ──────────────────────────────────────────────────────────────────

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		// Pont vers l'ancienne signature (tableau)
		return $this->loadRelationFromArray($relation, (array) $dbObject);
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return $dbObject->id ?? spl_object_id($dbObject);
	}

	public function buildEntity(object $dbObject, array $relations): mixed
	{
		// Les anciennes factories gèrent la construction dans fromDbObject() directement.
		// Ce stub n'est jamais appelé pour les anciennes factories.
		return null;
	}

	// ──────────────────────────────────────────────────────────────────
	// Méthodes dépréciées conservées pour ne pas casser l'existant
	// ──────────────────────────────────────────────────────────────────

	/**
	 * @deprecated Utiliser resolveRelations() d'AbstractFactory
	 */
	protected function buildRelationsToLoad(bool|array $withRelations, array $exceptRelations): array
	{
		return $this->resolveRelations($withRelations, $exceptRelations);
	}

	/**
	 * @deprecated
	 */
	protected function isSupportedRelation(string $relation): bool
	{
		return in_array($relation, static::RELATIONS, true);
	}

}