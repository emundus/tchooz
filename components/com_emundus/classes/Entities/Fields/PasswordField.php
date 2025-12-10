<?php

namespace Tchooz\Entities\Fields;

class PasswordField extends Field
{
	public function __construct(string $name, string $label, bool $required = false, ?FieldGroup $group = null)
	{
		parent::__construct($name, $label, $required, $group);
	}

	public static function getType(): string
	{
		return 'password';
	}

	public function toSchema(): array
	{
		return [
			'type' => self::getType(),
			'name' => $this->getName(),
			'label' => $this->getLabel(),
			'required' => $this->isRequired(),
			'group' => $this->getGroup()?->toSchema(),
			'displayRules' => array_map(fn($rule) => $rule->toSchema(), $this->getDisplayRules())
		];
	}
}