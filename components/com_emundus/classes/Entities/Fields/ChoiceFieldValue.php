<?php

namespace Tchooz\Entities\Fields;

class ChoiceFieldValue
{
	private ?string $value;
	private string $label;

	private ?FieldGroup $group = null;

	private array $params = [];

	public function __construct(?string $value, string $label, ?FieldGroup $group = null, array $params = [])
	{
		$this->value = $value;
		$this->label = $label;
		$this->group = $group;
		$this->params = $params;
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

	public function getParams(): array
	{
		return $this->params;
	}

	public function toSchema(): array
	{
		return [
			...$this->params,
			'value' => $this->value,
			'label' => $this->label,
			'group' => $this->group?->toSchema(),
		];
	}
}