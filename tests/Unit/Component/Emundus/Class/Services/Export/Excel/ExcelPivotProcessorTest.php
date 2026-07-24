<?php

namespace Unit\Component\Emundus\Class\Services\Export\Excel;

use PHPUnit\Framework\TestCase;
use stdClass;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Export\PivotScopeEnum;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Services\Export\Excel\ExcelPivotProcessor;

/**
 * Pure unit tests for the pivot processor. The `FabrikRepository` is mocked; the
 * `PivotScopeEnum::EVALUATION` branch is intentionally out of scope here — it
 * reaches directly for `Factory::getContainer()->get('DatabaseDriver')` and
 * needs an integration test with real Fabrik tables.
 *
 * @package     Unit\Component\Emundus\Class\Services\Export\Excel
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Export\Excel\ExcelPivotProcessor
 */
class ExcelPivotProcessorTest extends TestCase
{
	private FabrikRepository $fabrikRepository;

	private ExcelPivotProcessor $processor;

	protected function setUp(): void
	{
		parent::setUp();
		$this->fabrikRepository = $this->createMock(FabrikRepository::class);
		$this->processor        = new ExcelPivotProcessor($this->fabrikRepository);
	}

	// -------------------------------------------------------------------------
	// process — no-op guards
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testProcessReturnsUnchangedFilesWhenTargetIdIsZero(): void
	{
		$files   = ['abc' => ['header_fnum' => 'abc', 42 => 'v1,v2']];
		$headers = ['header_fnum' => 'Fnum', 42 => 'Column'];

		$out = $this->processor->process($files, $headers, PivotScopeEnum::ELEMENT, 0);

		$this->assertSame($files, $out, 'Un targetId <= 0 doit court-circuiter le pivot');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testProcessReturnsEmptyWhenNoFiles(): void
	{
		$out = $this->processor->process([], ['header_fnum' => 'Fnum'], PivotScopeEnum::ELEMENT, 42);

		$this->assertSame([], $out, 'Un tableau vide de files doit être renvoyé tel quel');
	}

	// -------------------------------------------------------------------------
	// process — scope=element
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testElementScopeSplitsCommaSeparatedValueIntoRows(): void
	{
		$pivotElement = $this->mockElement(42, 0, [], null);
		$this->fabrikRepository->method('getElementById')->with(42)->willReturn($pivotElement);

		$files = [
			'abc' => ['header_fnum' => 'abc', 42 => 'v1,v2,v3'],
		];
		$headers = ['header_fnum' => 'Fnum', 42 => 'Values'];

		$out = $this->processor->process($files, $headers, PivotScopeEnum::ELEMENT, 42);

		$this->assertCount(3, $out, 'Trois valeurs virgules doivent produire trois lignes');
		$this->assertSame('v1', $out['abc'][42], 'La ligne de base doit contenir la 1re valeur');
		$this->assertSame('v2', $out['abc_1'][42], 'abc_1 doit contenir la 2e valeur');
		$this->assertSame('v3', $out['abc_2'][42], 'abc_2 doit contenir la 3e valeur');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testElementScopeInRepeatGroupExpandsSiblingsToo(): void
	{
		// Pivot element 42 lives in a repeat group with sibling 43 (in headers) and 44 (not exported)
		$pivot = $this->mockElement(42, 10, ['repeat_group_button' => 1], null);
		$sibling = $this->mockElement(43, 10, ['repeat_group_button' => 1], null);
		$absent  = $this->mockElement(44, 10, ['repeat_group_button' => 1], null);

		$this->fabrikRepository->method('getElementById')->willReturn($pivot);
		$this->fabrikRepository->method('getElementsByGroupId')->with(10)->willReturn([$pivot, $sibling, $absent]);

		$files = [
			'abc' => ['header_fnum' => 'abc', 42 => 'a,b', 43 => 'x,y'],
		];
		$headers = ['header_fnum' => 'Fnum', 42 => 'Pivot', 43 => 'Sibling'];

		$out = $this->processor->process($files, $headers, PivotScopeEnum::ELEMENT, 42);

		$this->assertSame('a', $out['abc'][42], 'La ligne de base doit prendre la 1re valeur du pivot');
		$this->assertSame('x', $out['abc'][43], 'La ligne de base doit prendre la 1re valeur du sibling');
		$this->assertSame('b', $out['abc_1'][42], 'La 2e ligne doit prendre la 2e valeur du pivot');
		$this->assertSame('y', $out['abc_1'][43], 'La 2e ligne doit prendre la 2e valeur du sibling');
	}

	/**
	 * Two consecutive repo calls inside expandByElement would silently narrow the
	 * second query if filters carried over. The processor resets filters between
	 * calls — this test locks that reset in.
	 *
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testElementScopeResetsRepositoryFiltersBetweenCalls(): void
	{
		$pivot = $this->mockElement(42, 10, ['repeat_group_button' => 1], null);

		$this->fabrikRepository->method('getElementById')->willReturn($pivot);
		$this->fabrikRepository->method('getElementsByGroupId')->willReturn([$pivot]);

		// Two resets: one before getElementById, one before getElementsByGroupId
		$this->fabrikRepository->expects($this->atLeast(2))->method('setElementFilters')->with($this->identicalTo([]));

		$this->processor->process(
			['abc' => ['header_fnum' => 'abc', 42 => 'a']],
			['header_fnum' => 'Fnum', 42 => 'Pivot'],
			PivotScopeEnum::ELEMENT,
			42
		);
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testElementScopeUnknownIdIsNoOp(): void
	{
		$this->fabrikRepository->method('getElementById')->willReturn(null);

		$files = ['abc' => ['header_fnum' => 'abc']];
		$out = $this->processor->process($files, ['header_fnum' => 'Fnum'], PivotScopeEnum::ELEMENT, 999);

		$this->assertSame($files, $out, 'Un elementId inconnu doit laisser les files inchangés');
	}

	// -------------------------------------------------------------------------
	// process — scope=group
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testGroupScopeExplodesEveryExportedChild(): void
	{
		// Group 10 has three elements; only 42 and 43 are in headers
		$el1 = $this->mockElement(42, 10, null, null);
		$el2 = $this->mockElement(43, 10, null, null);
		$el3 = $this->mockElement(44, 10, null, null);

		$this->fabrikRepository->method('getElementsByGroupId')->with(10)->willReturn([$el1, $el2, $el3]);

		$files = [
			'abc' => ['header_fnum' => 'abc', 42 => 'a,b', 43 => 'x,y', 44 => 'ignored'],
		];
		$headers = ['header_fnum' => 'Fnum', 42 => 'A', 43 => 'B'];

		$out = $this->processor->process($files, $headers, PivotScopeEnum::GROUP, 10);

		$this->assertSame('a', $out['abc'][42], 'Ligne de base doit prendre la 1re valeur de 42');
		$this->assertSame('x', $out['abc'][43], 'Ligne de base doit prendre la 1re valeur de 43');
		$this->assertSame('b', $out['abc_1'][42], '2e ligne doit prendre la 2e valeur de 42');
		$this->assertSame('y', $out['abc_1'][43], '2e ligne doit prendre la 2e valeur de 43');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testGroupScopeIsNoOpWhenGroupHasNoElements(): void
	{
		$this->fabrikRepository->method('getElementsByGroupId')->willReturn([]);

		$files = ['abc' => ['header_fnum' => 'abc']];
		$out = $this->processor->process($files, ['header_fnum' => 'Fnum'], PivotScopeEnum::GROUP, 99);

		$this->assertSame($files, $out, 'Un groupe vide doit laisser les files inchangés');
	}

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testGroupScopeIsNoOpWhenNoGroupChildInHeaders(): void
	{
		$el = $this->mockElement(42, 10, null, null);
		$this->fabrikRepository->method('getElementsByGroupId')->willReturn([$el]);

		// Headers only include synthesis — pivot element 42 not exported
		$files = ['abc' => ['header_fnum' => 'abc', 42 => 'a,b']];
		$headers = ['header_fnum' => 'Fnum'];

		$out = $this->processor->process($files, $headers, PivotScopeEnum::GROUP, 10);

		$this->assertSame($files, $out, 'Aucune colonne du groupe dans les headers → pas d\'expansion');
	}

	// -------------------------------------------------------------------------
	// process — sort by fnum (private, exercised through process)
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::process
	 * @return void
	 */
	public function testProcessGroupsExpandedRowsByBaseFnum(): void
	{
		$el = $this->mockElement(42, 10, null, null);
		$this->fabrikRepository->method('getElementById')->willReturn($el);

		// Two fnums, each with two pivot values
		$files = [
			'abc' => ['header_fnum' => 'abc', 42 => 'a,b'],
			'def' => ['header_fnum' => 'def', 42 => 'c,d'],
		];
		$headers = ['header_fnum' => 'Fnum', 42 => 'Pivot'];

		$out = $this->processor->process($files, $headers, PivotScopeEnum::ELEMENT, 42);

		$keys = array_keys($out);
		$this->assertSame(['abc', 'abc_1', 'def', 'def_1'], $keys, 'Les lignes issues d\'un même fnum doivent être contigües');
	}

	// -------------------------------------------------------------------------
	// Constructor with default (fresh) repository
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Export\Excel\ExcelPivotProcessor::__construct
	 * @return void
	 */
	public function testConstructorAcceptsNullRepositoryAndBootstrapsItsOwn(): void
	{
		$processor = new ExcelPivotProcessor();

		$this->assertInstanceOf(ExcelPivotProcessor::class, $processor, 'Le constructeur doit accepter null et instancier son propre FabrikRepository');
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * @param   int                       $id
	 * @param   int                       $groupId
	 * @param   array<string,mixed>|null  $groupParams   Populates the stdClass returned by getGroupParams()
	 * @param   ElementPluginEnum|null    $plugin        Defaults to FIELD (non-pivotable)
	 * @param   object|null               $elementParams Overrides the stdClass returned by getParams()
	 */
	private function mockElement(
		int $id,
		int $groupId,
		?array $groupParams,
		?ElementPluginEnum $plugin,
		?object $elementParams = null
	) {
		$element = $this->createMock(FabrikElementEntity::class);
		$element->method('getId')->willReturn($id);
		$element->method('getGroupId')->willReturn($groupId);

		$groupParamsObject = new stdClass();
		if (!empty($groupParams))
		{
			foreach ($groupParams as $k => $v)
			{
				$groupParamsObject->$k = $v;
			}
		}
		$element->method('getGroupParams')->willReturn($groupParamsObject);
		$element->method('getPlugin')->willReturn($plugin ?? ElementPluginEnum::FIELD);
		$element->method('getParams')->willReturn($elementParams ?? new stdClass());

		return $element;
	}
}
