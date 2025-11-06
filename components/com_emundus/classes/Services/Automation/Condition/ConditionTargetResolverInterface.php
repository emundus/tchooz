<?php

namespace Tchooz\Services\Automation\Condition;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TableJoin;
use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Automation\TargetTypeEnum;

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
	 * $contextFilters peut contenir des infos pour filtrer les champs (ex: formId pour FormData)
	 * @return array<Field>
	 */
	public function getAvailableFields(array $contextFilters): array;

	/**
	 * Extrait la valeur réelle du champ depuis le contexte pour la comparer
	 */
	public function resolveValue(ActionTargetEntity $context, string $fieldName): mixed;

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