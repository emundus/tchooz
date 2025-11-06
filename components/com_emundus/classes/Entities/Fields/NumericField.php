<?php

namespace Tchooz\Entities\Fields;

class NumericField extends Field
{

	private ?float $max = null;

	private ?float $min = null;

	public static function getType(): string
	{
		return 'numeric';
	}

	public function toSchema(): array
	{
		return [
			'type' => NumericField::getType(),
			'name' => $this->getName(),
			'label' => $this->getLabel(),
			'required' => $this->isRequired(),
			'min' => $this->getMin(),
			'max' => $this->getMax(),
			'group' => $this->getGroup()?->toSchema()
		];
	}

	public function setMax(int|float $max): self
	{
		$this->max = (float)$max;
		return $this;
	}

	public function setMin(int|float $min): self
	{
		$this->min = (float)$min;
		return $this;
	}

	public function getMax(): ?float
	{
		return $this->max;
	}

	public function getMin(): ?float
	{
		return $this->min;
	}
}