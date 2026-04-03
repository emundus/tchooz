<?php

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Calculation\CalculationEntity;
use Tchooz\Entities\Calculation\Templates\CalculateDatesDiff;
use Tchooz\Enums\Calculation\CalculationTypeEnum;
use Tchooz\Services\Calculation\CalculationContext;
use Tchooz\Services\Calculation\CalculationEngine;

class CalculateDatesDiffTest extends UnitTestCase
{
	private function executeDiff(string $start, string $end, string $unit): mixed
	{
		$calculation = new CalculationEntity(1, CalculationTypeEnum::TEMPLATE, CalculateDatesDiff::getCode());
		$context = new CalculationContext([
			'start_date_element' => $start,
			'end_date_element' => $end,
			'unit' => $unit,
		]);

		$engine = new CalculationEngine();
		return $engine->execute($calculation, $context);
	}

	// ─── DAYS ────────────────────────────────────────────────

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testDaysSameMonth(): void
	{
		$this->assertEquals(9, $this->executeDiff('2024-01-01', '2024-01-10', 'days'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testDaysAcrossYears(): void
	{
		// 2020-01-10 → 2026-09-20 = 2445 days
		$start = new \DateTime('2020-01-10');
		$end = new \DateTime('2026-09-20');
		$expected = (int) $start->diff($end)->format('%a');

		$this->assertEquals($expected, $this->executeDiff('2020-01-10', '2026-09-20', 'days'));
	}

	// ─── MONTHS ──────────────────────────────────────────────

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testMonthsSameYear(): void
	{
		$this->assertEquals(3, $this->executeDiff('2024-01-01', '2024-04-01', 'months'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testMonthsAcrossYears(): void
	{
		// 2020-01-10 → 2026-09-20 = 6 years * 12 + 8 = 80 months
		$this->assertEquals(80, $this->executeDiff('2020-01-10', '2026-09-20', 'months'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testMonthsExactlyOneYear(): void
	{
		$this->assertEquals(12, $this->executeDiff('2023-03-15', '2024-03-15', 'months'));
	}

	// ─── YEARS ───────────────────────────────────────────────

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testYears(): void
	{
		$this->assertEquals(4, $this->executeDiff('2020-01-01', '2024-01-01', 'years'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testYearsPartialDoesNotRound(): void
	{
		// 2020-01-01 → 2024-06-15 = 4 years (partial year not counted)
		$this->assertEquals(4, $this->executeDiff('2020-01-01', '2024-06-15', 'years'));
	}

	// ─── WEEKS ───────────────────────────────────────────────

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testWeeksSameMonth(): void
	{
		// 14 days = 2 weeks
		$this->assertEquals(2, $this->executeDiff('2024-01-01', '2024-01-15', 'weeks'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testWeeksAcrossYears(): void
	{
		// 2020-01-10 → 2026-09-20 = 2445 days = 349 weeks (2445 / 7 = 349.28…)
		$start = new \DateTime('2020-01-10');
		$end = new \DateTime('2026-09-20');
		$totalDays = (int) $start->diff($end)->format('%a');
		$expected = intdiv($totalDays, 7);

		$this->assertEquals($expected, $this->executeDiff('2020-01-10', '2026-09-20', 'weeks'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testWeeksPartialDoesNotRound(): void
	{
		// 10 days = 1 week (not 2)
		$this->assertEquals(1, $this->executeDiff('2024-01-01', '2024-01-11', 'weeks'));
	}

	// ─── HOURS ───────────────────────────────────────────────

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testHours(): void
	{
		// 2 days = 48 hours
		$this->assertEquals(48, $this->executeDiff('2024-01-01', '2024-01-03', 'hours'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testHoursWithTime(): void
	{
		// 1 day 6 hours = 30 hours
		$this->assertEquals(30, $this->executeDiff('2024-01-01 06:00:00', '2024-01-02 12:00:00', 'hours'));
	}

	// ─── MINUTES ─────────────────────────────────────────────

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testMinutes(): void
	{
		// 1 day = 1440 minutes
		$this->assertEquals(1440, $this->executeDiff('2024-01-01', '2024-01-02', 'minutes'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testMinutesWithTime(): void
	{
		// 1 hour 30 minutes = 90 minutes
		$this->assertEquals(90, $this->executeDiff('2024-01-01 10:00:00', '2024-01-01 11:30:00', 'minutes'));
	}

	// ─── SECONDS ─────────────────────────────────────────────

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testSeconds(): void
	{
		// 1 day = 86400 seconds
		$this->assertEquals(86400, $this->executeDiff('2024-01-01', '2024-01-02', 'seconds'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testSecondsWithTime(): void
	{
		// 2 minutes 30 seconds = 150 seconds
		$this->assertEquals(150, $this->executeDiff('2024-01-01 00:00:00', '2024-01-01 00:02:30', 'seconds'));
	}

	// ─── EDGE CASES ──────────────────────────────────────────

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testSameDateReturnsZero(): void
	{
		$this->assertEquals(0, $this->executeDiff('2024-06-15', '2024-06-15', 'days'));
		$this->assertEquals(0, $this->executeDiff('2024-06-15', '2024-06-15', 'months'));
		$this->assertEquals(0, $this->executeDiff('2024-06-15', '2024-06-15', 'years'));
		$this->assertEquals(0, $this->executeDiff('2024-06-15', '2024-06-15', 'weeks'));
		$this->assertEquals(0, $this->executeDiff('2024-06-15', '2024-06-15', 'hours'));
		$this->assertEquals(0, $this->executeDiff('2024-06-15', '2024-06-15', 'minutes'));
		$this->assertEquals(0, $this->executeDiff('2024-06-15', '2024-06-15', 'seconds'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testReversedDatesReturnNegativeDays(): void
	{
		// end < start → negative result for days
		$this->assertEquals(-9, $this->executeDiff('2024-01-10', '2024-01-01', 'days'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testReversedDatesReturnNegativeWeeks(): void
	{
		// 14 days backwards = -2 weeks
		$this->assertEquals(-2, $this->executeDiff('2024-01-15', '2024-01-01', 'weeks'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testReversedDatesReturnNegativeMonths(): void
	{
		// 3 months backwards = -3
		$this->assertEquals(-3, $this->executeDiff('2024-04-01', '2024-01-01', 'months'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testReversedDatesReturnNegativeYears(): void
	{
		// 4 years backwards = -4
		$this->assertEquals(-4, $this->executeDiff('2024-01-01', '2020-01-01', 'years'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testReversedDatesReturnNegativeHours(): void
	{
		// 2 days backwards = -48 hours
		$this->assertEquals(-48, $this->executeDiff('2024-01-03', '2024-01-01', 'hours'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testReversedDatesReturnNegativeMinutes(): void
	{
		// 1 day backwards = -1440 minutes
		$this->assertEquals(-1440, $this->executeDiff('2024-01-02', '2024-01-01', 'minutes'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testReversedDatesReturnNegativeSeconds(): void
	{
		// 1 day backwards = -86400 seconds
		$this->assertEquals(-86400, $this->executeDiff('2024-01-02', '2024-01-01', 'seconds'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testReversedDatesReturnNegativeMonthsAcrossYears(): void
	{
		// 2026-09-20 → 2020-01-10 = -80 months
		$this->assertEquals(-80, $this->executeDiff('2026-09-20', '2020-01-10', 'months'));
	}

	/**
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testInvalidUnitThrowsException(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->executeDiff('2024-01-01', '2024-01-10', 'invalid_unit');
	}

	/**
	 * months across multiple years was returning only the month component (8) instead of total months (80).
	 *
	 * @covers Tchooz\Entities\Calculation\Templates\CalculateDatesDiff::getExpressionFunction
	 */
	public function testMonthsAcrossMultipleYears(): void
	{
		$result = $this->executeDiff('2020-01-10', '2026-09-20', 'months');

		// Must NOT be 8 (the old buggy value)
		$this->assertNotEquals(8, $result);
		// Must be 80 (6 years * 12 + 8 months)
		$this->assertEquals(80, $result);
	}
}