<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Enums\Import\RowStatusEnum;
use Tchooz\Services\Import\EntityImporterInterface;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\ImportOptions;
use Tchooz\Services\Import\ImportPipeline;
use Tchooz\Services\Import\Mapping\AliasColumnMap;
use Tchooz\Services\Import\Mapping\ColumnMap;
use Tchooz\Services\Import\Report\ImportReport;
use Tchooz\Services\Import\Source\ArraySource;
use Tchooz\Services\Import\UpdatableEntityImporter;

/**
 * Unit-tests the orchestration logic of ImportPipeline against a fake
 * importer, isolating it from any concrete entity (Organization, Contact, ...).
 *
 * @covers \Tchooz\Services\Import\ImportPipeline
 */
class ImportPipelineTest extends TestCase
{
	private DatabaseInterface $db;

	protected function setUp(): void
	{
		$this->db = $this->createMock(DatabaseInterface::class);
	}

	// --------------------------------------------------------------------
	// Happy path
	// --------------------------------------------------------------------

	public function testEachValidRowIsCreatedAndCommitted(): void
	{
		$importer = $this->fakeImporter();

		$this->db->expects($this->exactly(3))->method('transactionStart');
		$this->db->expects($this->exactly(3))->method('transactionCommit');
		$this->db->expects($this->never())->method('transactionRollback');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
				['id' => 'c', 'value' => 'C'],
			]),
			$importer
		);

		$this->assertSame(3, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(['a', 'b', 'c'], array_column($importer->persisted, 'id'));
	}

	public function testContextCarriesSourceNameAndRowNumberToImporter(): void
	{
		$importer = $this->fakeImporter();
		$source   = new ArraySource([
			['id' => 'a', 'value' => 'A'],
			['id' => 'b', 'value' => 'B'],
		], headers: null, name: 'CustomSheet');

		(new ImportPipeline($this->db))->run($source, $importer);

		$this->assertCount(2, $importer->contexts);
		$this->assertSame('CustomSheet', $importer->contexts[0]->sourceName);
		$this->assertSame('CustomSheet', $importer->contexts[1]->sourceName);
		// ArraySource yields data starting at row 2 (row 1 is the header line).
		$this->assertSame(2, $importer->contexts[0]->rowNumber);
		$this->assertSame(3, $importer->contexts[1]->rowNumber);
	}

	// --------------------------------------------------------------------
	// Guard rails: no transaction is opened when a row is rejected upstream
	// --------------------------------------------------------------------

	public function testRowMissingRequiredFieldFailsWithoutOpeningTransaction(): void
	{
		$importer = $this->fakeImporter(required: ['id']);

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => '',   'value' => 'no id'],
				['id' => 'b',  'value' => 'B'],
			]),
			$importer
		);

		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
		$this->assertSame(1, $report->count(RowStatusEnum::CREATED));
		$this->assertCount(1, $importer->persisted);
	}

	public function testValidationErrorsFailRowWithoutOpeningTransaction(): void
	{
		$importer = $this->fakeImporter();
		$importer->validateReturns = ['must be uppercase'];

		$this->db->expects($this->never())->method('transactionStart');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([['id' => 'a', 'value' => 'A']]),
			$importer
		);

		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
		$this->assertSame(['must be uppercase'], $report->getRowsByStatus(RowStatusEnum::FAILED)[0]->reasons);
	}

	public function testExistingRowIsSkippedAndPersistNotCalled(): void
	{
		$importer = $this->fakeImporter(existingIds: ['b']);

		$this->db->expects($this->once())->method('transactionStart');
		$this->db->expects($this->once())->method('transactionCommit');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
			]),
			$importer
		);

		$this->assertSame(1, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::SKIPPED));
		$this->assertCount(1, $importer->persisted);
		$this->assertSame('a', $importer->persisted[0]['id']);
	}

	public function testExistsThrowingIsReportedAsFailedRow(): void
	{
		$importer = $this->fakeImporter();
		$importer->existsThrows = new \RuntimeException('lookup failed');

		$this->db->expects($this->never())->method('transactionStart');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([['id' => 'a', 'value' => 'A']]),
			$importer
		);

		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
		$this->assertSame(['lookup failed'], $report->getRowsByStatus(RowStatusEnum::FAILED)[0]->reasons);
	}

	public function testEmptyRowsAreIgnoredSilently(): void
	{
		$importer = $this->fakeImporter();

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => '',  'value' => null],
				['id' => 'c', 'value' => 'C'],
			]),
			$importer
		);

		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(0, $report->count(RowStatusEnum::FAILED));
		$this->assertSame(0, $report->count(RowStatusEnum::SKIPPED));
	}

	// --------------------------------------------------------------------
	// Pre-flight header check (wrong-entity guard)
	// --------------------------------------------------------------------

	public function testAllUnknownHeadersAbortWithGlobalError(): void
	{
		$importer = $this->fakeImporter();

		// No row is touched and no DB call should happen — the file is rejected upfront.
		$this->db->expects($this->never())->method('transactionStart');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['foreign' => 'x', 'columns' => 'y'],
			]),
			$importer
		);

		$this->assertTrue($report->hasGlobalErrors());
		$this->assertSame(['foreign', 'columns'], $report->getUnknownHeaders());
		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(0, $report->count(RowStatusEnum::FAILED));
		$this->assertCount(0, $importer->persisted);
	}

	public function testUnknownHeaderRatioAboveThresholdAborts(): void
	{
		$importer = $this->fakeImporter();
		$this->db->expects($this->never())->method('transactionStart');

		// 1 known (id) + 3 unknown → 75% unknown, default threshold 50%.
		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'foreign1' => 'x', 'foreign2' => 'y', 'foreign3' => 'z'],
			]),
			$importer
		);

		$this->assertTrue($report->hasGlobalErrors());
		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
	}

	public function testUnknownHeaderRatioUnderThresholdProcessesAndWarns(): void
	{
		$importer = $this->fakeImporter();

		// 2 known (id, value) + 1 unknown (notes) → 33% unknown, below default 50%.
		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A', 'notes' => 'side info'],
				['id' => 'b', 'value' => 'B', 'notes' => 'more side info'],
			]),
			$importer
		);

		$this->assertFalse($report->hasGlobalErrors());
		$this->assertSame(['notes'], $report->getUnknownHeaders());
		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
	}

	public function testCustomMaxUnknownRatioCanRelaxOrTighten(): void
	{
		$importer = $this->fakeImporter();

		// Same data as the previous test (33% unknown) but with a strict threshold of 0:
		// any unknown header aborts the run.
		$strict = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A', 'notes' => 'side info'],
			]),
			$importer,
			new ImportOptions(maxUnknownHeaderRatio: 0.0)
		);
		$this->assertTrue($strict->hasGlobalErrors());

		// Re-do with full tolerance: even an 80% unknown ratio passes through.
		$lenient = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'foreign1' => 'x', 'foreign2' => 'y', 'foreign3' => 'z', 'foreign4' => 'w'],
			]),
			$importer,
			new ImportOptions(maxUnknownHeaderRatio: 1.0)
		);
		$this->assertFalse($lenient->hasGlobalErrors());
		$this->assertSame(1, $lenient->count(RowStatusEnum::CREATED));
	}

	public function testAllKnownHeadersProduceNoWarning(): void
	{
		$importer = $this->fakeImporter();

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
			]),
			$importer
		);

		$this->assertSame([], $report->getUnknownHeaders());
		$this->assertFalse($report->hasGlobalErrors());
	}

	// --------------------------------------------------------------------
	// Persistence: rollback on throw
	// --------------------------------------------------------------------

	public function testPersistThrowingRollsBackAndReportsFailure(): void
	{
		$importer = $this->fakeImporter();
		$importer->persistThrowsOnId = 'b';

		$this->db->expects($this->exactly(3))->method('transactionStart');
		$this->db->expects($this->exactly(2))->method('transactionCommit');
		$this->db->expects($this->exactly(1))->method('transactionRollback');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
				['id' => 'c', 'value' => 'C'],
			]),
			$importer
		);

		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
		$this->assertSame(['B fails'], $report->getRowsByStatus(RowStatusEnum::FAILED)[0]->reasons);
	}

	// --------------------------------------------------------------------
	// Conflict mode: SKIP (default) / UPDATE / CREATE_NEW
	// --------------------------------------------------------------------

	public function testUpdateModeRoutesExistingRowsToUpdateOnUpdatableImporter(): void
	{
		$importer = $this->fakeUpdatableImporter(existingIds: ['b']);

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
			]),
			$importer,
			new ImportOptions(conflictMode: ImportConflictModeEnum::UPDATE)
		);

		$this->assertSame(1, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::UPDATED));
		$this->assertSame(0, $report->count(RowStatusEnum::SKIPPED));
		$this->assertSame(['b'], array_column($importer->updated, 'id'));
		$this->assertSame(['a'], array_column($importer->persisted, 'id'));
	}

	public function testUpdateModeRollsBackOnUpdateException(): void
	{
		$importer = $this->fakeUpdatableImporter(existingIds: ['b']);
		$importer->updateThrowsOnId = 'b';

		$this->db->expects($this->once())->method('transactionRollback');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'b', 'value' => 'B'],
			]),
			$importer,
			new ImportOptions(conflictMode: ImportConflictModeEnum::UPDATE)
		);

		$this->assertSame(0, $report->count(RowStatusEnum::UPDATED));
		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
	}

	public function testUpdateModeOnNonUpdatableImporterFailsImmediatelyWithGlobalError(): void
	{
		// The plain fakeImporter does NOT implement UpdatableEntityImporter,
		// so requesting UPDATE must short-circuit before any row is touched.
		$importer = $this->fakeImporter(existingIds: ['a']);

		$this->db->expects($this->never())->method('transactionStart');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
			]),
			$importer,
			new ImportOptions(conflictMode: ImportConflictModeEnum::UPDATE)
		);

		$this->assertTrue($report->hasGlobalErrors());
		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(0, $report->count(RowStatusEnum::UPDATED));
		$this->assertSame(0, $report->count(RowStatusEnum::FAILED));
	}

	public function testCreateNewModeBypassesExistsAndAlwaysPersists(): void
	{
		$importer = $this->fakeImporter(existingIds: ['a', 'b']);

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
			]),
			$importer,
			new ImportOptions(conflictMode: ImportConflictModeEnum::CREATE_NEW)
		);

		// exists() must have been bypassed entirely — both rows go to persist().
		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(0, $report->count(RowStatusEnum::SKIPPED));
		$this->assertSame(['a', 'b'], array_column($importer->persisted, 'id'));
	}

	public function testCreateNewModeIgnoresExistsThrowing(): void
	{
		// Even if the importer's exists() throws, CREATE_NEW never asks it.
		$importer = $this->fakeImporter();
		$importer->existsThrows = new \RuntimeException('would have failed');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([['id' => 'a', 'value' => 'A']]),
			$importer,
			new ImportOptions(conflictMode: ImportConflictModeEnum::CREATE_NEW)
		);

		$this->assertSame(1, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(0, $report->count(RowStatusEnum::FAILED));
	}

	// --------------------------------------------------------------------
	// stopOnError
	// --------------------------------------------------------------------

	public function testStopOnErrorBreaksAtFirstFailure(): void
	{
		$importer = $this->fakeImporter();
		$importer->persistThrowsOnId = 'a';

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
				['id' => 'c', 'value' => 'C'],
			]),
			$importer,
			new ImportOptions(stopOnError: true)
		);

		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
		$this->assertCount(0, $importer->persisted);
	}

	public function testStopOnErrorAlsoBreaksOnValidationFailure(): void
	{
		$importer = $this->fakeImporter();
		$importer->validateReturns = ['nope'];

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
			]),
			$importer,
			new ImportOptions(stopOnError: true)
		);

		$this->assertSame(1, $report->count(RowStatusEnum::FAILED));
		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
	}

	// --------------------------------------------------------------------
	// Dry-run
	// --------------------------------------------------------------------

	public function testDryRunCallsPersistButRollsBackEveryRow(): void
	{
		$importer = $this->fakeImporter();

		$this->db->expects($this->exactly(2))->method('transactionStart');
		$this->db->expects($this->never())->method('transactionCommit');
		$this->db->expects($this->exactly(2))->method('transactionRollback');

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
			]),
			$importer,
			new ImportOptions(dryRun: true)
		);

		// CREATED is reported even though the transaction was rolled back —
		// the report describes "what would happen" in dry-run mode.
		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
		$this->assertCount(2, $importer->persisted);
	}

	public function testDryRunFlagIsPropagatedToImportContext(): void
	{
		$importer = $this->fakeImporter();

		(new ImportPipeline($this->db))->run(
			new ArraySource([['id' => 'a', 'value' => 'A']]),
			$importer,
			new ImportOptions(dryRun: true)
		);

		$this->assertTrue($importer->contexts[0]->dryRun);
	}

	// --------------------------------------------------------------------
	// Async hooks (dormant)
	// --------------------------------------------------------------------

	public function testSkipUntilRowIgnoresEarlierRows(): void
	{
		$importer = $this->fakeImporter();

		// ArraySource yields starting at row 2. Setting skipUntilRow = 3 should
		// skip rows 2 and 3 and only process rows 4+.
		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],   // row 2 → skipped
				['id' => 'b', 'value' => 'B'],   // row 3 → skipped
				['id' => 'c', 'value' => 'C'],   // row 4
				['id' => 'd', 'value' => 'D'],   // row 5
			]),
			$importer,
			new ImportOptions(skipUntilRow: 3)
		);

		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
		$this->assertSame(['c', 'd'], array_column($importer->persisted, 'id'));
	}

	public function testTimeBudgetZeroFiresCheckpointOnceAndStops(): void
	{
		$importer       = $this->fakeImporter();
		$invocations    = 0;
		$capturedRow    = -1;
		$capturedReport = null;

		// timeBudget=0 means the deadline is already past as soon as we enter
		// the loop, so the first iteration checkpoints and breaks before any
		// row is processed.
		(new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
			]),
			$importer,
			new ImportOptions(
				timeBudgetSeconds: 0,
				onCheckpoint: function (int $lastRow, ImportReport $report) use (&$invocations, &$capturedRow, &$capturedReport) {
					$invocations++;
					$capturedRow    = $lastRow;
					$capturedReport = $report;
				}
			)
		);

		$this->assertSame(1, $invocations, 'Checkpoint must be called exactly once per slice.');
		$this->assertSame(0, $capturedRow, 'Last processed row should be 0 — no row was attempted.');
		$this->assertCount(0, $importer->persisted);
		$this->assertInstanceOf(ImportReport::class, $capturedReport);
	}

	public function testCheckpointCallableIsOptional(): void
	{
		$importer = $this->fakeImporter();

		// Sanity: pipeline must not blow up when timeBudget fires without a
		// checkpoint callback configured.
		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([['id' => 'a', 'value' => 'A']]),
			$importer,
			new ImportOptions(timeBudgetSeconds: 0)
		);

		$this->assertSame(0, $report->count(RowStatusEnum::CREATED));
	}

	public function testWithoutTimeBudgetEveryRowIsProcessed(): void
	{
		$importer = $this->fakeImporter();
		$called   = false;

		$report = (new ImportPipeline($this->db))->run(
			new ArraySource([
				['id' => 'a', 'value' => 'A'],
				['id' => 'b', 'value' => 'B'],
			]),
			$importer,
			new ImportOptions(onCheckpoint: function () use (&$called) {
				$called = true;
			})
		);

		$this->assertFalse($called, 'Checkpoint must not fire when no time budget is set.');
		$this->assertSame(2, $report->count(RowStatusEnum::CREATED));
	}

	// --------------------------------------------------------------------
	// Helpers
	// --------------------------------------------------------------------

	/**
	 * @param string[] $required
	 * @param string[] $existingIds  ids that exists() should return true for
	 */
	private function fakeImporter(array $required = [], array $existingIds = []): EntityImporterInterface
	{
		return new class($required, $existingIds) implements EntityImporterInterface {
			public array $persisted = [];
			/** @var ImportContext[] */
			public array $contexts = [];
			public array $validateReturns = [];
			public ?\Throwable $existsThrows = null;
			public ?string $persistThrowsOnId = null;

			private ColumnMap $columnMap;

			public function __construct(array $required, private array $existingIds)
			{
				$builder = AliasColumnMap::create()->field('id');
				if (in_array('id', $required, true))
				{
					$builder = AliasColumnMap::create()->field('id', required: true);
				}
				$this->columnMap = $builder->field('value')->build();
			}

			public static function create(): EntityImporterInterface
			{
				throw new \LogicException('Not used in tests.');
			}

			public function getType(): string
			{
				return 'fake';
			}

			public function getColumnMap(): ColumnMap
			{
				return $this->columnMap;
			}

			public function getSupportedModes(): array
			{
				// Anything except UPDATE — that case requires UpdatableEntityImporter
				// and is exercised separately via fakeUpdatableImporter().
				return [ImportConflictModeEnum::SKIP, ImportConflictModeEnum::CREATE_NEW];
			}

			public function validate(array $row, ImportContext $context): array
			{
				return $this->validateReturns;
			}

			public function exists(array $row, ImportContext $context): bool
			{
				if ($this->existsThrows !== null)
				{
					throw $this->existsThrows;
				}
				return in_array($row['id'] ?? null, $this->existingIds, true);
			}

			public function persist(array $row, ImportContext $context): void
			{
				if ($this->persistThrowsOnId !== null && ($row['id'] ?? null) === $this->persistThrowsOnId)
				{
					throw new \RuntimeException(strtoupper((string) $row['id']) . ' fails');
				}
				$this->persisted[] = $row;
				$this->contexts[]  = $context;
			}
		};
	}

	/**
	 * Variant of fakeImporter() that also implements UpdatableEntityImporter
	 * so the UPDATE conflict mode can route to update() in tests.
	 *
	 * @param string[] $existingIds  ids that exists() should return true for
	 */
	private function fakeUpdatableImporter(array $existingIds = []): UpdatableEntityImporter
	{
		return new class($existingIds) implements UpdatableEntityImporter {
			public array $persisted = [];
			public array $updated   = [];
			/** @var ImportContext[] */
			public array $contexts = [];
			public ?string $updateThrowsOnId = null;

			private ColumnMap $columnMap;

			public function __construct(private array $existingIds)
			{
				$this->columnMap = AliasColumnMap::create()->field('id')->field('value')->build();
			}

			public static function create(): EntityImporterInterface
			{
				throw new \LogicException('Not used in tests.');
			}
			public function getType(): string { return 'fake_updatable'; }
			public function getColumnMap(): ColumnMap { return $this->columnMap; }
			public function getSupportedModes(): array
			{
				return [
					ImportConflictModeEnum::SKIP,
					ImportConflictModeEnum::UPDATE,
					ImportConflictModeEnum::CREATE_NEW,
				];
			}
			public function validate(array $row, ImportContext $context): array { return []; }
			public function exists(array $row, ImportContext $context): bool
			{
				return in_array($row['id'] ?? null, $this->existingIds, true);
			}

			public function persist(array $row, ImportContext $context): void
			{
				$this->persisted[] = $row;
				$this->contexts[]  = $context;
			}

			public function update(array $row, ImportContext $context): void
			{
				if ($this->updateThrowsOnId !== null && ($row['id'] ?? null) === $this->updateThrowsOnId)
				{
					throw new \RuntimeException(strtoupper((string) $row['id']) . ' update failed');
				}
				$this->updated[]  = $row;
				$this->contexts[] = $context;
			}
		};
	}
}
