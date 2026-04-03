<?php

namespace Tchooz\Services\Calculation;


use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ArithmeticExpressionLanguage extends ExpressionLanguage
{
	protected function registerFunctions(): void
	{
		// On enregistre UNIQUEMENT min() et max()
		$this->addFunction(ExpressionFunction::fromPhp('min'));
		$this->addFunction(ExpressionFunction::fromPhp('max'));
	}
}