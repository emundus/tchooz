<?php

namespace Tchooz\Entities\Calculation\Templates;

use Tchooz\Entities\Fields\Field;

/**
 * Base class for calculation templates. Each template defines a specific type of calculation with its own parameters and expression structure.
 * Concrete templates must implement the abstract methods to provide their unique code, label, parameters, and expression building logic.
 * If your template requires custom expression functions, you can also define a method to return those functions, which can be registered in the CalculationEngine. The method must be called getExpressionFunction and return an instance of ExpressionFunction.
 */
abstract class CalculationTemplate
{
	abstract public static function getCode(): string;

	abstract public function getLabel(): string;

	/**
	 * @return array<Field>
	 */
	abstract public function getParameters(): array;

	/**
	 * @return array{
	 *     expression: string,
	 *     variables: array<string, mixed>
	 * }
	 */
	abstract public function buildExpression(array $context): array;

	public function isAvailable(): bool
	{
		return false;
	}
}