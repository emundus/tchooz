<?php

namespace Tchooz\Services\Calculation;

use Joomla\CMS\Log\Log;
use Tchooz\Entities\Calculation\CalculationEntity;
use Tchooz\Enums\Calculation\CalculationTypeEnum;

class CalculationEngine
{
	private ArithmeticExpressionLanguage $expressionLanguage;
	private CalculationTemplateRegistry $templateRegistry;

	public function __construct()
	{
		Log::addLogger(
			['text_file' => 'com_emundus.calculation.php'],
			Log::ALL,
			['com_emundus.calculation']
		);

		$this->templateRegistry   = new CalculationTemplateRegistry();
		$this->expressionLanguage = new ArithmeticExpressionLanguage();
	}

	public function execute(CalculationEntity $calculation, CalculationContext $context): mixed
	{
		$expressionData = $this->resolveExpression($calculation, $context);

		// prevent division by zero errors
		// if there is a division, and var next to it is zero, then throw an exception
		if (str_contains($expressionData['expression'], '/'))
		{
			$trimmedExpression = str_replace(' ', '', $expressionData['expression']);

			foreach ($expressionData['variables'] as $varName => $value)
			{
				if (str_contains($trimmedExpression, "/$varName") && $value == 0)
				{
					throw new \InvalidArgumentException("Division by zero error: variable '$varName' is used in division and has value 0");
				}
			}

			if (str_contains($trimmedExpression, '/0'))
			{
				throw new \InvalidArgumentException("Division by zero error: expression contains division by literal zero");
			}
		}

		$this->assertExpressionIsSafe($expressionData['expression']);

		try
		{
			$result = $this->expressionLanguage->evaluate(
				$expressionData['expression'],
				$expressionData['variables']
			);

			Log::add("Evaluated: {$expressionData['expression']} => $result", Log::DEBUG, 'com_emundus.calculation');

			return $result;

		}
		catch (\Throwable $e)
		{
			Log::add('Calculation error: ' . $e->getMessage(), Log::ERROR, 'com_emundus.calculation');
			throw $e;
		}
	}

	private function resolveExpression(CalculationEntity $calculation, CalculationContext $context): array
	{

		if ($calculation->getType() === CalculationTypeEnum::TEMPLATE)
		{
			$template = $this->templateRegistry->getTemplate(
				$calculation->getTemplateCode()
			);

			if (empty($template))
			{
				throw new \InvalidArgumentException('Template not found: ' . $calculation->getTemplateCode());
			}

			if (method_exists($template, 'getExpressionFunction'))
			{
				$this->expressionLanguage->addFunction($template->getExpressionFunction($context));
			}

			$expression =  $template->buildExpression($context->getVariables());
			if (!isset($expression['expression']) || !isset($expression['variables']))
			{
				throw new \InvalidArgumentException('Invalid expression format from template');
			}
		}
		else
		{
			$expression = $this->buildCustomExpression($calculation, $context);
		}

		return $expression;
	}

	private function buildCustomExpression(CalculationEntity $calculation, CalculationContext $context): array
	{
		$config = $calculation->getConfiguration();

		if (empty($config['operation']))
		{
			throw new \InvalidArgumentException('Missing operation in configuration');
		}

		$expression = $config['operation'];
		foreach ($config['fields'] as $fieldKey => $variableName)
		{
			$expression = preg_replace('/\\b' . preg_quote($fieldKey, '/') . '\\b/', $variableName, $expression);
		}

		$variables = array_map(function ($value) {
			return $this->validateValue($value);
		}, $context->getVariables());

		return [
			'expression' => $expression,
			'variables'  => $variables
		];
	}

	private function validateValue(mixed $value): mixed
	{
		if (is_numeric($value))
		{
			return (float) $value;
		}

		if ($value instanceof \DateTimeInterface)
		{
			return $value;
		}

		if (is_string($value) && strtotime($value) !== false)
		{
			return $value;
		}

		throw new \InvalidArgumentException('Invalid variable type for calculation');
	}

	private function assertExpressionIsSafe(string $expression): void
	{
		$forbiddenPatterns = [
			'/\bconstant\s*\(/i',
			'/\benum\s*\(/i',
		];

		foreach ($forbiddenPatterns as $pattern) {
			if (preg_match($pattern, $expression)) {
				throw new \InvalidArgumentException(
					"Forbidden function detected in expression for security reasons"
				);
			}
		}
	}
}