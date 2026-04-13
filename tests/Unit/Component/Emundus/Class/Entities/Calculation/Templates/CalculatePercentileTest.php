<?php

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Calculation\CalculationEntity;
use Tchooz\Entities\Calculation\Templates\CalculatePercentile;
use Tchooz\Enums\Calculation\CalculationTypeEnum;
use Tchooz\Services\Calculation\CalculationContext;
use Tchooz\Services\Calculation\CalculationEngine;

class CalculatePercentileTest extends UnitTestCase
{
	/**
	 * Test the calculation of the 50th percentile (median) for a set of values.
	 * @covers Tchooz\Services\Calculation\CalculationEngine::execute
	 * @covers Tchooz\Entities\Calculation\Templates\CalculatePercentile::getExpressionFunction
	 */
	public function testMedianSalary(): void
	{
		$calculation = new CalculationEntity(1, CalculationTypeEnum::TEMPLATE, CalculatePercentile::getCode());
		$context = new CalculationContext([
			'percentile' => 50,
			'elements' => [
				['element_id' => 's1', 'element_value' => 3200],
				['element_id' => 's2', 'element_value' => 2200],
				['element_id' => 's3', 'element_value' => 4000],
				['element_id' => 's4', 'element_value' => 1800],
				['element_id' => 's5', 'element_value' => 2500],
			],
		]);

		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);

		// sorted values : [1800, 2200, 2500, 3200, 4000]
		// Median = 2500
		$this->assertEquals(2500, $result);
	}

	/**
	 * Test the calculation of the 25th percentile for a set of values.
	 * @covers Tchooz\Services\Calculation\CalculationEngine::execute
	 * @covers Tchooz\Entities\Calculation\Templates\CalculatePercentile::getExpressionFunction
	 */
	public function test25thPercentileSalary(): void
	{
		$calculation = new CalculationEntity(1, CalculationTypeEnum::TEMPLATE, CalculatePercentile::getCode());
		$context = new CalculationContext([
			'percentile' => 25,
			'elements' => [
				['element_id' => 's1', 'element_value' => 3200],
				['element_id' => 's2', 'element_value' => 2200],
				['element_id' => 's3', 'element_value' => 4000],
				['element_id' => 's4', 'element_value' => 1800],
				['element_id' => 's5', 'element_value' => 2500],
			],
		]);

		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);

		// sorted values : [1800, 2200, 2500, 3200, 4000]
		// 25th percentile = 2200
		$this->assertEquals(2200, $result);
	}

	/**
	 * Test the calculation of the 75th percentile for a set of values.
	 * @covers Tchooz\Services\Calculation\CalculationEngine::execute
	 * @covers Tchooz\Entities\Calculation\Templates\CalculatePercentile::getExpressionFunction
	 */
	public function test75thPercentileSalary(): void
	{
		$calculation = new CalculationEntity(1, CalculationTypeEnum::TEMPLATE, CalculatePercentile::getCode());
		$context     = new CalculationContext([
			'percentile' => 75,
			'elements'   => [
				['element_id' => 's1', 'element_value' => 3200],
				['element_id' => 's2', 'element_value' => 2200],
				['element_id' => 's3', 'element_value' => 4000],
				['element_id' => 's4', 'element_value' => 1800],
				['element_id' => 's5', 'element_value' => 2500],
			],
		]);

		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);


		// sorted values : [1800, 2200, 2500, 3200, 4000]
		// 75th percentile = 3200
		$this->assertEquals(3200, $result);
	}
}

