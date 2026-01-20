<?php

namespace Tchooz\Entities\Fields;

class RadioField extends ChoiceField
{
	public function __construct(string $name, string $label, array $choices, bool $required = false, ?FieldGroup $group = null, bool $choicesGrouped = false)
	{
		parent::__construct(
			$name,
			$label,
			$choices,
			$required,
			false,
			$group,
			$choicesGrouped,
			false
		);
	}

	public static function getType(): string
	{
		return 'radio';
	}
}