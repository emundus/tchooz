<?php
/**
 * @package     Tchooz\Services\Export\OptionsSchema
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\OptionsSchema;

use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Enums\Export\ExportTabEnum;

class ExcelOptionsSchema extends AbstractOptionsSchema
{
	public const DISPLAY_EVALUATOR_NAME = 'display_evaluator_name';

	public function getFields(): array
	{
		$group = ExportTabEnum::OPTIONS->toFieldGroup();

		return [
			new BooleanField(
				name: self::DISPLAY_EVALUATOR_NAME,
				label: 'COM_EMUNDUS_EXPORTS_DISPLAY_EVALUATOR_NAME',
				required: false,
				group: $group,
			),
		];
	}

	public function getDefaults(): array
	{
		return [
			self::DISPLAY_EVALUATOR_NAME => true,
		];
	}
}
