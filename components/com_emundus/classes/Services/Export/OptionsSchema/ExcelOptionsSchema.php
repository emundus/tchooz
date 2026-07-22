<?php
/**
 * @package     Tchooz\Services\Export\OptionsSchema
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\OptionsSchema;

use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Export\ExportTabEnum;
use Tchooz\Enums\Export\PivotScopeEnum;

class ExcelOptionsSchema extends AbstractOptionsSchema
{
	public const DISPLAY_EVALUATOR_NAME = 'display_evaluator_name';

	public const PIVOT_SCOPE  = 'pivot_scope';

	public const PIVOT_TARGET = 'pivot_target';

	protected function getFormatFields(): array
	{
		$group = ExportTabEnum::OPTIONS->toFieldGroup();

		return [
			new ChoiceField(
				name: self::PIVOT_SCOPE,
				label: 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_LABEL',
				choices: array_map(
					fn (PivotScopeEnum $scope) => new ChoiceFieldValue($scope->value, $scope->getLabel()),
					PivotScopeEnum::cases()
				),
				required: false,
				multiple: false,
				group: $group,
				addSelectOption: true,
				selectOptionLabel: 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_EMPTY',
			),
			new ChoiceField(
				name: self::PIVOT_TARGET,
				label: 'COM_EMUNDUS_EXPORT_PIVOT_DATA_LABEL',
				choices: [],
				required: false,
				multiple: false,
				group: $group,
				addSelectOption: false,
			),
			new BooleanField(
				name: self::DISPLAY_EVALUATOR_NAME,
				label: 'COM_EMUNDUS_EXPORTS_DISPLAY_EVALUATOR_NAME',
				required: false,
				group: $group,
			),
		];
	}

	protected function getFormatDefaults(): array
	{
		return [
			self::DISPLAY_EVALUATOR_NAME => true,
			self::PIVOT_SCOPE            => null,
			self::PIVOT_TARGET           => null,
		];
	}
}
