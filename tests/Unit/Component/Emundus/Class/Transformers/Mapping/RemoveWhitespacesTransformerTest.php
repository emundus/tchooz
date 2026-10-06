<?php

namespace Unit\Component\Emundus\Class\Transformers\Mapping;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Transformers\Mapping\RemoveWhitespacesTransformer;

class RemoveWhitespacesTransformerTest extends UnitTestCase
{
	private RemoveWhitespacesTransformer $transformer;

	protected function setUp(): void
	{
		parent::setUp();
		$this->transformer = new RemoveWhitespacesTransformer();
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\RemoveWhitespacesTransformer::transform
	 * @return void
	 */
	public function testWhenValueContainsInnerSpacesThenTheyAreRemoved(): void
	{
		$this->assertEquals('1234567', $this->transformer->transform('1 234 567'), 'Inner spaces should be removed, not replaced');
		$this->assertEquals('FR7630006000011234567890189', $this->transformer->transform('FR76 3000 6000 0112 3456 7890 189'), 'An IBAN should be compacted');
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\RemoveWhitespacesTransformer::transform
	 * @return void
	 */
	public function testWhenValueContainsSurroundingWhitespacesThenTheyAreRemoved(): void
	{
		$this->assertEquals('value', $this->transformer->transform("\t value \r\n"), 'Leading and trailing whitespaces should be removed');
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\RemoveWhitespacesTransformer::transform
	 * @return void
	 */
	public function testWhenValueContainsUnicodeWhitespacesThenTheyAreRemoved(): void
	{
		$this->assertEquals('12345', $this->transformer->transform("12\u{00A0}345"), 'Non-breaking spaces should be removed');
		$this->assertEquals('12345', $this->transformer->transform("12\u{202F}345"), 'Narrow no-break spaces should be removed');
		$this->assertEquals('value', $this->transformer->transform("\u{FEFF}value"), 'A BOM should be removed');
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\RemoveWhitespacesTransformer::transform
	 * @return void
	 */
	public function testWhenValueIsAnArrayThenEachItemIsTransformed(): void
	{
		$result = $this->transformer->transform(['a b', ['c d', 'e f'], 12]);

		$this->assertEquals(['ab', ['cd', 'ef'], 12], $result, 'Nested arrays should be transformed recursively and keys preserved');
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\RemoveWhitespacesTransformer::transform
	 * @return void
	 */
	public function testWhenValueIsNotAStringThenItIsReturnedUnchanged(): void
	{
		$object = new \stdClass();

		$this->assertNull($this->transformer->transform(null), 'A null value should stay null');
		$this->assertFalse($this->transformer->transform(false), 'A boolean should not be casted to a string');
		$this->assertSame(12.5, $this->transformer->transform(12.5), 'A float should be returned unchanged');
		$this->assertSame($object, $this->transformer->transform($object), 'An object should be returned unchanged instead of raising a cast error');
	}
}
