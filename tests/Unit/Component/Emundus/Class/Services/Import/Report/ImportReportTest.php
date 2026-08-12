<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Report
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Report;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Import\RowStatusEnum;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\Report\ImportReport;

/**
 * @covers \Tchooz\Services\Import\Report\ImportReport
 * @covers \Tchooz\Services\Import\Report\RowResult
 */
class ImportReportTest extends TestCase
{
	private ImportContext $ctx;

	protected function setUp(): void
	{
		$this->ctx = new ImportContext('TestSource', 2);
	}

	public function testEmptyReportHasZeroCountsAndStableSummaryShape(): void
	{
		$report = new ImportReport();

		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(0, $report->count(RowStatusEnum::SKIPPED));
		$this->assertSame(0, $report->count(RowStatusEnum::FAILED));

		$summary = $report->toArray()['summary'];

		// Every enum case must be present even at zero so the JSON shape is stable.
		foreach (RowStatusEnum::cases() as $case)
		{
			$this->assertArrayHasKey($case->value, $summary);
			$this->assertSame(0, $summary[$case->value]);
		}
		$this->assertSame(0, $summary['total']);
	}

	public function testAddIncrementsTheRightCounter(): void
	{
		$report = new ImportReport();
		$report->add($this->ctx, RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(3), RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(4), RowStatusEnum::SKIPPED);
		$report->add($this->ctx->withRow(5), RowStatusEnum::FAILED, ['boom']);

		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::SKIPPED));
		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
	}

	public function testRowsCarrySourceNameRowNumberStatusAndReasons(): void
	{
		$report = new ImportReport();
		$report->add(new ImportContext('Sheet A', 7), RowStatusEnum::FAILED, ['e1', 'e2']);

		$rows = $report->getRows();
		$this->assertCount(1, $rows);
		$this->assertSame('Sheet A', $rows[0]->sourceName);
		$this->assertSame(7,         $rows[0]->rowNumber);
		$this->assertSame(RowStatusEnum::FAILED, $rows[0]->status);
		$this->assertSame(['e1', 'e2'], $rows[0]->reasons);
	}

	public function testGetRowsByStatusFiltersAccurately(): void
	{
		$report = new ImportReport();
		$report->add($this->ctx,                  RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(3),      RowStatusEnum::FAILED, ['x']);
		$report->add($this->ctx->withRow(4),      RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(5),      RowStatusEnum::SKIPPED);

		$failed = $report->getRowsByStatus(RowStatusEnum::FAILED);
		$this->assertCount(1, $failed);
		$this->assertSame(3, $failed[0]->rowNumber);

		$created = $report->getRowsByStatus(RowStatusEnum::CREATED);
		$this->assertCount(2, $created);
	}

	public function testMergeSumsCountsAndConcatenatesRows(): void
	{
		$a = new ImportReport();
		$a->add($this->ctx,             RowStatusEnum::CREATED);
		$a->add($this->ctx->withRow(3), RowStatusEnum::FAILED, ['boom-a']);

		$b = new ImportReport();
		$b->add(new ImportContext('Sheet B', 2), RowStatusEnum::CREATED);
		$b->add(new ImportContext('Sheet B', 3), RowStatusEnum::CREATED);
		$b->add(new ImportContext('Sheet B', 4), RowStatusEnum::SKIPPED);

		$a->merge($b);

		$this->assertSame(3, $a->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $a->count(RowStatusEnum::SKIPPED));
		$this->assertSame(1, $a->count(RowStatusEnum::FAILED));
		$this->assertCount(5, $a->getRows());
	}

	public function testMergeIsAdditiveAndDoesNotMutateOther(): void
	{
		$a = new ImportReport();
		$a->add($this->ctx, RowStatusEnum::CREATED);

		$b = new ImportReport();
		$b->add(new ImportContext('B', 2), RowStatusEnum::FAILED, ['boom']);

		$a->merge($b);

		// The donor report keeps its own state intact.
		$this->assertSame(0, $b->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $b->count(RowStatusEnum::FAILED));
		$this->assertCount(1, $b->getRows());
	}

	// --------------------------------------------------------------------
	// Global errors and unknown headers (pre-flight surface)
	// --------------------------------------------------------------------

	public function testSetUnknownHeadersDeduplicatesAndExposesInSummary(): void
	{
		$report = new ImportReport();
		$report->setUnknownHeaders(['Prénom', 'Email', 'Prénom']);   // duplicate intentionally

		$this->assertSame(['Prénom', 'Email'], $report->getUnknownHeaders());
		$this->assertSame(['Prénom', 'Email'], $report->toArray()['summary']['unknown_headers']);
	}

	public function testAddGlobalErrorAccumulatesAndExposesInSummary(): void
	{
		$report = new ImportReport();
		$report->addGlobalError('wrong entity');
		$report->addGlobalError('another reason');

		$this->assertTrue($report->hasGlobalErrors());
		$this->assertSame(['wrong entity', 'another reason'], $report->getGlobalErrors());
		$this->assertSame(['wrong entity', 'another reason'], $report->toArray()['summary']['global_errors']);
	}

	public function testFreshReportExposesEmptyGlobalErrorsAndUnknownHeaders(): void
	{
		$summary = (new ImportReport())->toArray()['summary'];

		$this->assertFalse((new ImportReport())->hasGlobalErrors());
		$this->assertSame([], $summary['global_errors']);
		$this->assertSame([], $summary['unknown_headers']);
	}

	public function testMergeCombinesUnknownHeadersAndGlobalErrors(): void
	{
		$a = new ImportReport();
		$a->setUnknownHeaders(['Foo', 'Bar']);
		$a->addGlobalError('first');

		$b = new ImportReport();
		$b->setUnknownHeaders(['Bar', 'Baz']);   // overlap intentional
		$b->addGlobalError('second');

		$a->merge($b);

		$this->assertSame(['Foo', 'Bar', 'Baz'], $a->getUnknownHeaders());
		$this->assertSame(['first', 'second'],   $a->getGlobalErrors());
	}

	public function testToArrayProducesStableShape(): void
	{
		$report = new ImportReport();
		$report->add($this->ctx,             RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(3), RowStatusEnum::FAILED, ['oops']);

		$out = $report->toArray();

		$this->assertSame(2, $out['summary']['total']);
		$this->assertSame(1, $out['summary']['created']);
		$this->assertSame(1, $out['summary']['failed']);
		$this->assertSame(0, $out['summary']['skipped']);

		$this->assertCount(2, $out['rows']);
		$this->assertSame('TestSource', $out['rows'][0]['source']);
		$this->assertSame(2,            $out['rows'][0]['row']);
		$this->assertSame('created',    $out['rows'][0]['status']);
		$this->assertSame([],           $out['rows'][0]['reasons']);
		$this->assertSame('failed',     $out['rows'][1]['status']);
		$this->assertSame(['oops'],     $out['rows'][1]['reasons']);
	}
}
