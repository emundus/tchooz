<?php

namespace Unit\Component\Emundus\Class\Transformers\Mapping;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Transformers\Mapping\ExtractValueAtIndexTransformer;

class ExtractValueAtIndexTransformerTest extends UnitTestCase
{
	private ExtractValueAtIndexTransformer $transformer;

	protected function setUp(): void
	{
		parent::setUp();
		$this->transformer = new ExtractValueAtIndexTransformer();
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\ExtractValueAtIndexTransformer::transform
	 * @return void
	 */
	public function testExtractValueAtIndexTransformerTransform(): void
	{
		// INDEX MEANS POSITION, SO INDEX 1 MEANS THE FIRST ELEMENT OF THE ARRAY OR THE FIRST CHARACTER OF THE STRING
		$this->transformer->setParametersValues([ExtractValueAtIndexTransformer::PARAMETER_INDEX => 1]);

		$input = ['first', 'second', 'third'];
		$expected = 'first';

		$result = $this->transformer->transform($input);
		$this->assertEquals($expected, $result, "Transforming array with index 1 should yield 'first'");

		$inputString = "hello";
		$expectedChar = 'h';

		$resultString = $this->transformer->transform($inputString);
		$this->assertEquals($expectedChar, $resultString, "Transforming string 'hello' with index 1 should yield 'h'");
	}
}