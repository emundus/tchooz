<?php
/**
 * @package     Tchooz\Enums\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Export;

use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\Field;

/**
 * Single source of truth for runtime export settings.
 *
 * Each case binds a key (the POST name) to a {@see Field} entity (which carries
 * the label, type and tab/group) plus its default value. The front renders the
 * toggles via the `transformIntoParameterField` mixin so adding a new setting
 * means: add a case here + one gate in the consumer service.
 */
enum ExportSettingEnum: string
{
	case DISPLAY_EVALUATOR_NAME = 'display_evaluator_name';

	public function getTab(): ExportTabEnum
	{
		return match ($this)
		{
			self::DISPLAY_EVALUATOR_NAME => ExportTabEnum::MANAGEMENT,
		};
	}

	public function toField(): Field
	{
		return match ($this)
		{
			self::DISPLAY_EVALUATOR_NAME => new BooleanField(
				name: $this->value,
				label: 'COM_EMUNDUS_EXPORTS_DISPLAY_EVALUATOR_NAME',
				required: false,
				group: $this->getTab()->toFieldGroup(),
			),
		};
	}

	public function getDefault(): mixed
	{
		return match ($this)
		{
			self::DISPLAY_EVALUATOR_NAME => true,
		};
	}

	public function cast(mixed $raw): mixed
	{
		return match ($this->toField()::getType())
		{
			'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
			'numeric' => (int) $raw,
			default   => $raw,
		};
	}
}
