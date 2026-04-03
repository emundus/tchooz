<?php

namespace Unit\Component\Emundus\Class\Services\Calculation;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Calculation\CalculationEntity;
use Tchooz\Entities\Calculation\Templates\CalculateDatesDiff;
use Tchooz\Enums\Calculation\CalculationTypeEnum;
use Tchooz\Enums\Time\TimeUnitEnum;
use Tchooz\Services\Calculation\CalculationContext;
use Tchooz\Services\Calculation\CalculationEngine;

class CalculationEngineTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::__construct
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::resolveExpression
	 * @return void
	 * @throws \Throwable
	 */
	public function testCalculationEngineExecute(): void
	{
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a + b',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 5,
			'field_b' => 10,
		]);

		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);
		$this->assertEquals(15, $result);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::resolveExpression
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::buildCustomExpression
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::validateValue
	 * Complex operation like a combination of multiple operations and parentheses to test operator precedence and correct evaluation.
	 * Will make sure system can handle at least calculation of average with ponderation
	 */
	public function testCalculationEngineExecuteComplexExpression(): void
	{
		/**
		 * Test:
		 * 1 Note out of 20 with a ponderation of 30% (10/20)
		 * 2 Note out of 10 with a ponderation of 70% (5/10))
		 * Everything is equal 1/2, so the result should ne 10/20 if the engine correctly handle operator precedence and parentheses
		 * This test will ensure that the engine correctly handles operator precedence, parentheses, and multiple operations in a single expression, as well as the correct substitution of variables from the context.
		 */
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => '((a / b) * c + (d / e) * f) * g',
			'fields' => [
				'a' => 'note1',
				'b' => 'note1_max',
				'c' => 'note1_ponderation',
				'd' => 'note2',
				'e' => 'note2_max',
				'f' => 'note2_ponderation',
				'g' => 'total',
			],
		]);
		$context = new CalculationContext([
			'note1' => 10,
			'note1_max' => 20,
			'note1_ponderation' => 0.3,
			'note2' => 5,
			'note2_max' => 10,
			'note2_ponderation' => 0.7,
			'total' => 20,
		]);

		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);
		$this->assertEquals(10, $result, 'The calculation engine should correctly evaluate complex expressions with operator precedence and parentheses.');
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::resolveExpression
	 * Test Template calculation execution with a simple template and context.
	 */
	public function testTemplateCalculationExecution(): void
	{
		$calculation = new CalculationEntity(0, CalculationTypeEnum::TEMPLATE, CalculateDatesDiff::getCode());
		$context = new CalculationContext([
			'unit' => TimeUnitEnum::DAYS,
			'start_date_element' => '2024-01-01',
			'end_date_element' => '2024-01-10',
		]);

		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);
		$this->assertEquals(9, $result);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Teste qu'une exception est levée si la formule contient un opérateur non valide.
	 */
	public function testExecuteWithInvalidOperator(): void
	{
		$this->expectException(\Exception::class);

		$invalidOperator = '[';

		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "a $invalidOperator b",
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
			'field_b' => 2,
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Teste qu'une exception est levée si une valeur du contexte n'est pas numérique ou date.
	 */
	public function testExecuteWithInvalidContextValue(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a + b',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
			'field_b' => ['not' => 'numeric'], // valeur non valide
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Teste qu'une tentative d'injection de code dans la formule ne s'exécute pas.
	 */
	public function testExecuteWithCodeInjectionAttempt(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a + (phpinfo())', // tentative d'appel de fonction
			'fields' => [
				'a' => 'field_a',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	// ─── SECURITY TESTS ──────────────────────────────────────

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * constant() is a native Symfony ExpressionLanguage function that can leak PHP constants like DB_PASSWORD.
	 * ArithmeticExpressionLanguage must NOT register it, and assertExpressionIsSafe must block it.
	 */
	public function testConstantFunctionIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "constant('PHP_VERSION')",
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Verify that constant() is blocked even with extra spacing.
	 */
	public function testConstantFunctionWithSpacesIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "constant  ('PHP_VERSION')",
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Verify that constant() is blocked case-insensitively.
	 */
	public function testConstantFunctionCaseInsensitiveIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "CONSTANT('PHP_VERSION')",
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * enum() is a native Symfony ExpressionLanguage function that wraps constant() for UnitEnum.
	 * Must be blocked for the same security reasons.
	 */
	public function testEnumFunctionIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "enum('Tchooz\\Enums\\Calculation\\CalculationTypeEnum::CUSTOM')",
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * A variable name like "my_constant_field" should NOT trigger a false positive on the "constant" check.
	 */
	public function testVariableNameContainingConstantIsNotBlocked(): void
	{
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a + b',
			'fields' => [
				'a' => 'my_constant_field',
				'b' => 'other_field',
			],
		]);
		$context = new CalculationContext([
			'my_constant_field' => 5,
			'other_field' => 3,
		]);
		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);
		$this->assertEquals(8, $result);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Arbitrary PHP functions like system(), exec(), shell_exec() must not be callable.
	 */
	public function testSystemFunctionCallIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "system('whoami')",
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * exec() must not be callable.
	 */
	public function testExecFunctionCallIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "exec('ls')",
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * file_get_contents() must not be callable.
	 */
	public function testFileGetContentsIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "file_get_contents('/etc/passwd')",
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::validateValue
	 * Even if ExpressionLanguage supports the ~ (concat) operator, a malicious string value
	 * injected through context variables must be rejected by validateValue() before it can be used.
	 */
	public function testStringConcatenationViaContextIsBlocked(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a ~ b',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 'hello',
			'field_b' => 'world',
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Injecting an object as context value must be rejected by validateValue().
	 */
	public function testObjectInjectionInContextIsBlocked(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a + b',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
			'field_b' => new \stdClass(),
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Boolean values must be rejected by validateValue().
	 */
	public function testBooleanValueInContextIsBlocked(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a + b',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
			'field_b' => true,
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Non-date string values must be rejected by validateValue().
	 */
	public function testArbitraryStringValueInContextIsBlocked(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a + b',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
			'field_b' => 'malicious_string',
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Division by zero via a variable must throw an exception.
	 */
	public function testDivisionByZeroVariable(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a / b',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 10,
			'field_b' => 0,
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Division by literal zero must throw an exception, either caught early by the engine
	 * or as a DivisionByZeroError from Symfony ExpressionLanguage at evaluation time.
	 */
	public function testDivisionByLiteralZero(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a / 0',
			'fields' => [
				'a' => 'field_a',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 10,
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * min() should be allowed since ArithmeticExpressionLanguage registers it.
	 */
	public function testMinFunctionIsAllowed(): void
	{
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'min(a, b)',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 3,
			'field_b' => 7,
		]);
		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);
		$this->assertEquals(3, $result);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * max() should be allowed since ArithmeticExpressionLanguage registers it.
	 */
	public function testMaxFunctionIsAllowed(): void
	{
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'max(a, b)',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 3,
			'field_b' => 7,
		]);
		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);
		$this->assertEquals(7, $result);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Attempt to access object property via dot notation must fail.
	 */
	public function testPropertyAccessInExpressionIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a.class',
			'fields' => [
				'a' => 'field_a',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Attempt to read a sensitive constant like DB_PASSWORD via constant() must be blocked.
	 */
	public function testConstantDbPasswordIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "constant('DB_PASSWORD')",
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Embedded constant() inside a valid arithmetic expression must still be blocked.
	 */
	public function testConstantEmbeddedInArithmeticIsBlocked(): void
	{
		$this->expectException(\Exception::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => "a + constant('PHP_INT_MAX')",
			'fields' => [
				'a' => 'field_a',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Empty operation must throw an exception.
	 */
	public function testEmptyOperationIsBlocked(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => '',
			'fields' => [],
		]);
		$context = new CalculationContext([]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}

	/**
	 * @covers \Tchooz\Services\Calculation\CalculationEngine::execute
	 * Null value in context must be rejected.
	 */
	public function testNullValueInContextIsBlocked(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$calculation = new CalculationEntity(0, CalculationTypeEnum::CUSTOM, null, [
			'operation' => 'a + b',
			'fields' => [
				'a' => 'field_a',
				'b' => 'field_b',
			],
		]);
		$context = new CalculationContext([
			'field_a' => 1,
			'field_b' => null,
		]);
		$engine = new CalculationEngine();
		$engine->execute($calculation, $context);
	}
}