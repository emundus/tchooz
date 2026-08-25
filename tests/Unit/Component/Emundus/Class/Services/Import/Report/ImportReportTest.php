<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Report
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Report;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Import\ImportErrorCodeEnum;
use Tchooz\Enums\Import\RowStatusEnum;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\Report\ImportReport;
use Tchooz\Services\Import\Report\RowError;

/**
 * @covers \Tchooz\Services\Import\Report\ImportReport
 * @covers \Tchooz\Services\Import\Report\RowResult
 * @covers \Tchooz\Services\Import\Report\RowError
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
		$report->add($this->ctx->withRow(5), RowStatusEnum::FAILED, [$this->err('boom')]);

		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::SKIPPED));
		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
	}

	public function testRowsCarrySourceNameRowNumberStatusAndReasons(): void
	{
		$report = new ImportReport();
		$report->add(new ImportContext('Sheet A', 7), RowStatusEnum::FAILED, [$this->err('e1'), $this->err('e2')]);

		$rows = $report->getRows();
		$this->assertCount(1, $rows);
		$this->assertSame('Sheet A', $rows[0]->sourceName);
		$this->assertSame(7,         $rows[0]->rowNumber);
		$this->assertSame(RowStatusEnum::FAILED, $rows[0]->status);
		$this->assertSame(['e1', 'e2'], array_map(static fn (RowError $e) => $e->params[0], $rows[0]->reasons));
	}

	public function testGetRowsByStatusFiltersAccurately(): void
	{
		$report = new ImportReport();
		$report->add($this->ctx,                  RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(3),      RowStatusEnum::FAILED, [$this->err('x')]);
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
		$a->add($this->ctx->withRow(3), RowStatusEnum::FAILED, [$this->err('boom-a')]);

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
		$b->add(new ImportContext('B', 2), RowStatusEnum::FAILED, [$this->err('boom')]);

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

	public function testToArrayProducesStableShapeWithStructuredErrors(): void
	{
		$report = new ImportReport();
		$report->add($this->ctx,             RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(3), RowStatusEnum::FAILED, [$this->err('bad@', ImportErrorCodeEnum::INVALID_EMAIL)]);

		$out = $report->toArray();

		$this->assertSame(2, $out['summary']['total']);
		$this->assertSame(1, $out['summary']['created']);
		$this->assertSame(1, $out['summary']['failed']);
		$this->assertSame(0, $out['summary']['skipped']);

		$this->assertCount(2, $out['rows']);
		$this->assertSame('TestSource', $out['rows'][0]['source']);
		$this->assertSame(2,            $out['rows'][0]['row']);
		$this->assertSame('created',    $out['rows'][0]['status']);
		$this->assertSame([],           $out['rows'][0]['errors']);

		$this->assertSame('failed', $out['rows'][1]['status']);
		$this->assertSame('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_EMAIL', $out['rows'][1]['errors'][0]['code']);
		$this->assertSame(['bad@'], $out['rows'][1]['errors'][0]['params']);
	}

	// --------------------------------------------------------------------
	// Bounded persistence shape (toStorableArray / mergeStorable)
	// --------------------------------------------------------------------

	public function testToStorableArrayKeepsStructuredPerRowDetail(): void
	{
		$report = new ImportReport();
		$report->add($this->ctx,             RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(3), RowStatusEnum::FAILED, [$this->err('Email invalide', ImportErrorCodeEnum::INVALID_EMAIL)], ['email' => 'a@']);
		$report->add($this->ctx->withRow(4), RowStatusEnum::FAILED, [$this->err('Email invalide', ImportErrorCodeEnum::INVALID_EMAIL)], ['email' => 'b@']);
		$report->add($this->ctx->withRow(5), RowStatusEnum::FAILED, [$this->err('Champ requis manquant : lastname', ImportErrorCodeEnum::MISSING_REQUIRED_FIELDS)]);

		$storable = $report->toStorableArray();

		$this->assertSame(1, $storable['summary']['created']);
		$this->assertSame(3, $storable['summary']['failed']);
		$this->assertSame(3, $storable['failed_total']);
		$this->assertFalse($storable['failed_truncated']);

		// One structured entry per failed row, carrying its number and error code.
		$this->assertCount(3, $storable['failed_rows']);
		$this->assertSame(3, $storable['failed_rows'][0]['row']);
		$this->assertArrayNotHasKey('data', $storable['failed_rows'][0]);
		$this->assertSame('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_EMAIL', $storable['failed_rows'][0]['errors'][0]['code']);
		$this->assertSame('COM_EMUNDUS_IMPORT_MISSING_REQUIRED_FIELDS', $storable['failed_rows'][2]['errors'][0]['code']);
	}

	public function testToStorableArrayStoresOnlyFailedRowsNotEveryRow(): void
	{
		$report = new ImportReport();
		$report->add($this->ctx, RowStatusEnum::CREATED);

		$storable = $report->toStorableArray();
		$this->assertArrayNotHasKey('rows', $storable);
		$this->assertSame([], $storable['failed_rows']);
	}

	public function testToStorableArrayCapsFailedRowsAndFlagsTruncation(): void
	{
		$report = new ImportReport();

		$total = ImportReport::MAX_FAILED_ROWS + 10;
		for ($i = 0; $i < $total; $i++)
		{
			$report->add($this->ctx->withRow($i + 2), RowStatusEnum::FAILED, [$this->err('Cause commune', ImportErrorCodeEnum::INVALID_EMAIL)]);
		}

		$storable = $report->toStorableArray();

		$this->assertCount(ImportReport::MAX_FAILED_ROWS, $storable['failed_rows']);
		$this->assertTrue($storable['failed_truncated']);
		$this->assertSame($total, $storable['failed_total']);
	}

	public function testMergeStorableSumsCountsAndAccumulatesFailedRows(): void
	{
		$first = $this->storable(function (ImportReport $r) {
			$r->add(new ImportContext('s', 2), RowStatusEnum::CREATED);
			$r->add(new ImportContext('s', 3), RowStatusEnum::FAILED, [$this->err('Email invalide', ImportErrorCodeEnum::INVALID_EMAIL)]);
		});

		$second = $this->storable(function (ImportReport $r) {
			$r->add(new ImportContext('s', 4), RowStatusEnum::CREATED);
			$r->add(new ImportContext('s', 5), RowStatusEnum::FAILED, [$this->err('Email invalide', ImportErrorCodeEnum::INVALID_EMAIL)]);
			$r->add(new ImportContext('s', 6), RowStatusEnum::FAILED, [$this->err('Date invalide', ImportErrorCodeEnum::INVALID_DATE)]);
		});

		$merged = ImportReport::mergeStorable($first, $second);

		$this->assertSame(2, $merged['summary']['created']);
		$this->assertSame(3, $merged['summary']['failed']);
		$this->assertSame(3, $merged['failed_total']);

		$this->assertCount(3, $merged['failed_rows']);
		$this->assertSame('COM_EMUNDUS_IMPORT_VALIDATION_INVALID_DATE', $merged['failed_rows'][2]['errors'][0]['code']);
	}

	public function testMergeStorableWithEmptyCumulativeReturnsSlice(): void
	{
		$slice = $this->storable(static function (ImportReport $r) {
			$r->add(new ImportContext('s', 2), RowStatusEnum::CREATED);
		});

		$this->assertSame($slice, ImportReport::mergeStorable([], $slice));
	}

	public function testMergeStorableUnionsUnknownHeadersAndGlobalErrors(): void
	{
		$first = $this->storable(static function (ImportReport $r) {
			$r->setUnknownHeaders(['Extra1']);
			$r->addGlobalError('err A');
			$r->add(new ImportContext('s', 2), RowStatusEnum::CREATED);
		});

		$second = $this->storable(static function (ImportReport $r) {
			$r->setUnknownHeaders(['Extra1', 'Extra2']);
			$r->addGlobalError('err B');
			$r->add(new ImportContext('s', 3), RowStatusEnum::CREATED);
		});

		$merged = ImportReport::mergeStorable($first, $second);

		$this->assertEqualsCanonicalizing(['Extra1', 'Extra2'], $merged['summary']['unknown_headers']);
		$this->assertSame(['err A', 'err B'], $merged['summary']['global_errors']);
	}

	private function err(string $message, ImportErrorCodeEnum $code = ImportErrorCodeEnum::RUNTIME): RowError
	{
		return new RowError($code, null, [$message]);
	}

	private function storable(callable $build): array
	{
		$report = new ImportReport();
		$build($report);

		return $report->toStorableArray();
	}

	// --------------------------------------------------------------------
	// Non-blocking warnings carried from the context
	// --------------------------------------------------------------------

	public function testContextWarningsArePropagatedToTheRow(): void
	{
		$context = new ImportContext('Sheet A', 5);
		$context->addWarning('label mismatch');

		$report = new ImportReport();
		$report->add($context, RowStatusEnum::CREATED);

		$this->assertSame(['label mismatch'], $report->getRows()[0]->warnings, 'Row should carry the context warnings');
		$this->assertSame(['label mismatch'], $report->toArray()['rows'][0]['warnings'], 'toArray should expose the warnings');
	}

	public function testSummaryCountsRowsThatCarryWarnings(): void
	{
		$withWarning = new ImportContext('Sheet A', 2);
		$withWarning->addWarning('check this');

		$report = new ImportReport();
		$report->add($withWarning, RowStatusEnum::CREATED);
		$report->add($this->ctx->withRow(3), RowStatusEnum::CREATED);   // no warning

		$this->assertSame(1, $report->toArray()['summary']['warnings'], 'Summary should count only rows with warnings');
	}

	public function testRowWithoutWarningsExposesEmptyArray(): void
	{
		$report = new ImportReport();
		$report->add($this->ctx, RowStatusEnum::CREATED);

		$this->assertSame([], $report->toArray()['rows'][0]['warnings'], 'A row without warnings must expose an empty list');
	}
}
