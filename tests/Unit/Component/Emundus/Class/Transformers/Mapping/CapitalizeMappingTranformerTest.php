<?php

namespace Unit\Component\Emundus\Class\Transformers\Mapping;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Transformers\Mapping\CapitalizeMappingTranformer;

class CapitalizeMappingTranformerTest extends UnitTestCase
{
	private CapitalizeMappingTranformer $transformer;

	protected function setUp(): void
	{
		parent::setUp();
		$this->transformer = new CapitalizeMappingTranformer();
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\CapitalizeMappingTranformer::transform
	 * @return void
	 */
	public function testTransform(): void
	{
		$input = "john doe";
		$expected = "John Doe";

		$result = $this->transformer->transform($input);
		$this->assertEquals($expected, $result, "Transforming 'john doe' should yield 'John Doe'");
	}
}