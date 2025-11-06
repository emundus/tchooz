<?php

namespace Unit\Component\Emundus\Entities\Automation\Comparators;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Comparators\DateComparator;
use Tchooz\Enums\Automation\ConditionOperatorEnum;

class DateComparatorTest extends UnitTestCase
{
	private DateComparator $comparator;

	protected function setUp(): void
	{
		parent::setUp();
		$this->comparator = new DateComparator();
	}

	/**
	 * @covers DateComparator::supports
	 * @return void
	 */
	public function testSupportsWithValidDates(): void
	{
		$this->assertTrue($this->comparator->supports("01-10-2025", "02-10-2025"));
	}

	/**
	 * @covers DateComparator::supports
	 * @return void
	 */
	public function testSupportsWithInvalidValues(): void
	{
		$this->assertFalse($this->comparator->supports("foo", "bar"));
	}

	/**
	 * @covers DateComparator::supports
	 * @return void
	 */
	public function testEqualsDates(): void
	{
		$this->assertTrue(
			$this->comparator->compare("01-10-2025", "01-10-2025", ConditionOperatorEnum::EQUALS)
		);
	}

	/**
	 * @covers DateComparator::supports
	 * @return void
	 */
	public function testNotEqualsDates(): void
	{
		$this->assertTrue(
			$this->comparator->compare("02-10-2025", "01-10-2025", ConditionOperatorEnum::NOT_EQUALS)
		);
	}

	/**
	 * @covers DateComparator::supports
	 * @return void
	 */
	public function testGreaterThanDate(): void
	{
		$this->assertTrue(
			$this->comparator->compare("02-10-2025", "01-10-2025", ConditionOperatorEnum::GREATER_THAN)
		);
	}

	/**
	 * @covers DateComparator::supports
	 * @return void
	 */
	public function testLessThanDate(): void
	{
		$this->assertTrue(
			$this->comparator->compare("01-10-2025", "02-10-2025", ConditionOperatorEnum::LESS_THAN)
		);
	}
}