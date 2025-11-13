<?php

namespace Tchooz\Entities\Fields;

class ChoiceFieldValue
{
	private ?string $value;
	private string $label;

	private ?FieldGroup $group = null;

	public function __construct(?string $value, string $label, ?FieldGroup $group = null)
	{
		$this->value = $value;
		$this->label = $label;
		$this->group = $group;
	}

	public function getValue(): ?string
	{
		return $this->value;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getGroup(): ?FieldGroup
	{
		return $this->group;
	}

	public function toSchema(): array
	{
		return [
			'value' => $this->value,
			'label' => $this->label,
			'group' => $this->group?->toSchema()
		];
	}
}