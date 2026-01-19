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

	public function toSchema(): array
	{
		$schema = $this->defaultSchema();
		$schema['min'] = $this->getMin();
		$schema['max'] = $this->getMax();

		return $schema;
	}
}