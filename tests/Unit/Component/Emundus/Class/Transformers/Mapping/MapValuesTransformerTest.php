<?php

namespace Unit\Component\Emundus\Class\Transformers\Mapping;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Transformers\Mapping\MapValuesTransformer;

class MapValuesTransformerTest extends UnitTestCase
{
	private MapValuesTransformer $transformer;

	public function setUp(): void
	{
		parent::setUp();
		$this->transformer = new MapValuesTransformer();
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\MapValuesTransformer::transform
	 * @return void
	 */
	public function testTransform(): void
	{
		// Set up parameter values for mapping
		$mappingParameter = [
			['map_from' => 'A', 'map_to' => 'Alpha'],
			['map_from' => 'B', 'map_to' => 'Beta'],
			['map_from' => 'C', 'map_to' => 'Gamma'],
		];
		$this->transformer->setParametersValues(['mapping' => $mappingParameter]);

		// Test cases
		$testCases = [
			'A' => 'Alpha',
			'B' => 'Beta',
			'C' => 'Gamma',
			'D' => 'D', // No mapping, should return original value
			''  => '',  // Empty string, should return original value
		];

		foreach ($testCases as $input => $expected) {
			$result = $this->transformer->transform($input);
			$this->assertEquals($expected, $result, "Transforming '$input' should yield '$expected'");
		}
	}
}