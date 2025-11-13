<?php

namespace Tchooz\Entities\Fields;

class YesnoField extends ChoiceField
{
	public function __construct(string $name, string $label, bool $required = false, ?FieldGroup $group = null)
	{
		parent::__construct(
			$name,
			$label,
			[
				new ChoiceFieldValue('1', 'JYES'),
				new ChoiceFieldValue('0', 'JNO'),
			],
			$required,
			false,
			$group,
			false
		);
	}
}