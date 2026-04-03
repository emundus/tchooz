<?php

namespace Tchooz\Services\Calculation;

class CalculationContext
{
	private array $variables = [];

	public function __construct(array $variables = [])
	{
		$this->variables = $variables;
	}

	public function getVariables(): array
	{
		return $this->variables;
	}
}