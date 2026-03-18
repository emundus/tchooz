<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Comparators;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Comparators\ArrayComparator;
use Tchooz\Enums\Automation\ConditionMatchModeEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;

class ArrayComparatorTest extends UnitTestCase
{
	private ArrayComparator $comparator;

	protected function setUp(): void
	{
		parent::setUp();
		$this->comparator = new ArrayComparator();
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testSupportsArrayValues(): void
	{
		$this->assertTrue($this->comparator->supports([1,2], [2,3]));
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testAnyMode(): void
	{
		$this->assertTrue(
			$this->comparator->compare([1,2,3], [2], ConditionOperatorEnum::EQUALS, ConditionMatchModeEnum::ANY)
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testAllMode(): void
	{
		$this->assertTrue(
			$this->comparator->compare([1,2,3], [1,2], ConditionOperatorEnum::EQUALS, ConditionMatchModeEnum::ALL)
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testExactMode(): void
	{
		$this->assertTrue(
			$this->comparator->compare([1,2], [2,1], ConditionOperatorEnum::EQUALS, ConditionMatchModeEnum::EXACT)
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testNotEqualsMode(): void
	{
		$this->assertTrue(
			$this->comparator->compare([1,2], [3,4], ConditionOperatorEnum::NOT_EQUALS, ConditionMatchModeEnum::ANY)
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testNotEqualsModeFails(): void
	{
		$this->assertFalse(
			$this->comparator->compare([1,2], [2,3], ConditionOperatorEnum::NOT_EQUALS, ConditionMatchModeEnum::ANY)
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testNotEqualsAllModeFails(): void
	{
		$this->assertFalse(
			$this->comparator->compare([1,2,3], [2,3], ConditionOperatorEnum::NOT_EQUALS, ConditionMatchModeEnum::ALL)
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testNotEqualsAllModeSucceeds(): void
	{
		$this->assertTrue(
			$this->comparator->compare([1,2,3], [4,5], ConditionOperatorEnum::NOT_EQUALS, ConditionMatchModeEnum::ALL)
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testNotEqualsExactModeFails(): void
	{
		$this->assertFalse(
			$this->comparator->compare([1,2], [2,1], ConditionOperatorEnum::NOT_EQUALS, ConditionMatchModeEnum::EXACT)
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Comparators\ArrayComparator::supports
	 * @return void
	 */
	public function testNotEqualsExactModeSucceeds(): void
	{
		$this->assertTrue(
			$this->comparator->compare([1,2], [1,2,3], ConditionOperatorEnum::NOT_EQUALS, ConditionMatchModeEnum::EXACT)
		);
	}
}
