<?php

namespace Tchooz\Entities\Fields;

class FieldGroup
{
	private string $name;
	private string $label;
	private bool $isRepeatable = false;

	public function __construct(string $name, string $label, bool $isRepeatable = false)
	{
		$this->name = $name;
		$this->label = $label;
		$this->isRepeatable = $isRepeatable;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function isRepeatable(): bool
	{
		return $this->isRepeatable;
	}

	public function toSchema(): array
	{
		return [
			'name' => $this->name,
			'label' => $this->label,
			'isRepeatable' => $this->isRepeatable,
		];
	}

}