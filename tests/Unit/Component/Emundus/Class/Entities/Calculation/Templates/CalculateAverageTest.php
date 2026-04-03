<?php

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Calculation\CalculationEntity;
use Tchooz\Entities\Calculation\Templates\CalculateAverage;
use Tchooz\Enums\Calculation\CalculationTypeEnum;
use Tchooz\Services\Calculation\CalculationContext;
use Tchooz\Services\Calculation\CalculationEngine;

class CalculateAverageTest extends UnitTestCase
{
	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateAverage::buildExpression
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateAverage::getExpressionFunction
	 * @covers Tchooz\Services\Calculation\CalculationEngine::execute
	 */
	public function testCalculateAverage(): void
	{
		$calculation = new CalculationEntity(1, CalculationTypeEnum::TEMPLATE, CalculateAverage::getCode());
		$context = new CalculationContext([
			'result_out_of' => 100,
			'elements' => [
				[
					'element_id' => 'element1',
					'element_ponderation' => 1,
					'element_out_of' => 20,
					'element_value' => 15,
				],
				[
					'element_id' => 'element2',
					'element_ponderation' => 2,
					'element_out_of' => 10,
					'element_value' => 5,
				],
				[
					'element_id' => 'element3',
					'element_ponderation' => 1,
					'element_out_of' => 5,
					'element_value' => 5,
				],
			],
		]);

		$engine = new CalculationEngine();
		$result = $engine->execute($calculation, $context);

		// Calculation steps:
		// Element 1: (15/20) * 1 = 0.75
		// Element 2: (5/10) * 2 = 1
		// Element 3: (5/5) * 1 = 1
		// Total = 0.75 + 1 + 1 = 2.75
		// Average = Total / Total Ponderation = 2.75 / 4 = 0.6875
		// Result = Average * result_out_of = 0.6875 * 100 = 68.75
		$this->assertEquals(68.75, $result);
	}
}