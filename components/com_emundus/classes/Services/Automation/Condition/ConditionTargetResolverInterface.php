<?php

namespace Tchooz\Services\Automation\Condition;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TableJoin;
use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\ValueFormatEnum;

interface ConditionTargetResolverInterface
{
	/**
	 * Retourne le type de target géré par ce resolver (ex: USERDATA, PAYMENTDATA, etc.)
	 */
	public static function getTargetType(): string;

	/**
	 * Indique quels types de cibles (TargetTypeEnum) peuvent être utilisés avec ce resolver
	 * Ex: FORMDATA, CAMPAIGNDATA, etc. peuvent être utilisés avec TargetTypeEnum::FILE
	 * @return array<TargetTypeEnum>
	 */
	public static function getAllowedActionTargetTypes(): array;

	/**
	 * Retourne la liste des champs disponibles et les types associés
	 *
	 * @param   array  $contextFilters  peut contenir des infos pour filtrer les champs (ex: formId pour FormData)
	 * Il est pertinent d'utiliser la clé 'storedValues' pour passer la liste des champs déjà stockés dans les conditions, afin d'inclure ces champs dans la liste même s'ils ne sont pas disponible par défaut (ex: Nombre de possibilités de réponses pour un champ de type choice trop élevé, limite de champs retournés, etc.)
	 * Il est aussi pertinent d'utiliser la clé 'search' pour filtrer les champs par nom
	 *
	 * @return array<Field>
	 */
	public function getAvailableFields(array $contextFilters): array;

	/**
	 * Extrait la valeur réelle du champ depuis le contexte pour la comparer
	 *
	 * @param   ActionTargetEntity  $context
	 * @param   string              $fieldName
	 * @param   ValueFormatEnum     $format Useful when the value stored in the database is different from the one displayed to users (ex: datebasejoins, choice fields, etc.), default is ValueFormatEnum::RAW
	 *
	 * @return mixed
	 */
	public function resolveValue(ActionTargetEntity $context, string $fieldName, ValueFormatEnum $format = ValueFormatEnum::RAW): mixed;

	public function getColumnsForField(string $field): array;

	/**
	 * Retourne les jointures SQL nécessaires pour accéder aux champs de ce target
	 * @return array<TableJoin>
	 */
	public function getJoins(string $field): array;

	/**
	 * @param   TargetTypeEnum  $targetType
	 *
	 * @return array<TableJoin>
	 */
	public function getJoinsToTable(TargetTypeEnum $targetType): array;

	public function searchable(): bool;
}