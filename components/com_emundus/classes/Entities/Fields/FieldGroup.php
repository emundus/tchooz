<?php

namespace Tchooz\Entities\Fields;

class FieldGroup
{
	private string $name;
	private string $label;

	public function __construct(string $name, string $label)
	{
		$this->name = $name;
		$this->label = $label;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function toSchema(): array
	{
		return [
			'name' => $this->name,
			'label' => $this->label
		];
	}

}