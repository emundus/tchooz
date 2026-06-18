<?php

namespace Unit\Component\Emundus\Class\Entities\Automation;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Automation\AutomationExecutionContext;

/**
 * Proves the generic infinite-loop protection for the "2% discount" scenario:
 *
 *   Automation A : IF payment = CB        OR (SEPA AND installments = 1)  -> ADD discount
 *   Automation B : IF payment != CB       OR (SEPA AND installments != 1) -> REMOVE discount
 *
 * With SEPA + 1 installment, both conditions match, so each action saves the cart and the cart
 * event is re-fired. The loop is broken at the runAutomations() level: while a chain is already
 * being processed, a (re-)trigger that did not carry an explicit execution context reuses the SAME
 * request-scoped chain context. Therefore hasRun() persists across re-fires and a given automation
 * runs at most once per top-level chain — even when the re-fire path dropped the execution context
 * (transaction update, legacy status change, ...).
 *
 * This test models runAutomations()'s context resolution:
 *   $executionContext = (explicit context forwarded) ?: AutomationExecutionContext::current();
 * wrapped in beginProcessing()/endProcessing().
 *
 * @package     Unit\Component\Emundus\Class\Entities\Automation
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Automation\AutomationExecutionContext
 */
class AutomationExecutionContextTest extends TestCase
{
	/** Automation ids used in the scenario (A = add discount, B = remove discount). */
	private const AUTOMATION_A = 101;
	private const AUTOMATION_B = 102;

	/** Iteration identities (a file fnum, or a user id) the guard is keyed on. */
	private const FILE_1 = '2026011213522100000080001382';
	private const FILE_2 = '2026011213522100000080009999';

	protected function setUp(): void
	{
		parent::setUp();
		// The guard is static / request-scoped; isolate each test.
		AutomationExecutionContext::resetProcessing();
	}

	protected function tearDown(): void
	{
		AutomationExecutionContext::resetProcessing();
		parent::tearDown();
	}

	/**
	 * Mirrors runAutomations()'s context resolution: an explicit forwarded context wins, otherwise
	 * the request-scoped chain context is (re)used.
	 */
	private function resolveExecutionContext(?AutomationExecutionContext $forwarded): AutomationExecutionContext
	{
		return ($forwarded instanceof AutomationExecutionContext)
			? $forwarded
			: AutomationExecutionContext::current();
	}

	/**
	 * The exposed ping-pong case: a context-less re-fire that happens WHILE the chain is being
	 * processed reuses the same context, so A and B (already run) are skipped — the loop stops.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::current
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::beginProcessing
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::endProcessing
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::hasRun
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::markRun
	 * @return void
	 */
	public function testReentrantTriggerReusesContextAndStopsLoop(): void
	{
		// 1. Top-level trigger (applicant picks SEPA): no execution context forwarded yet.
		$context = $this->resolveExecutionContext(null);
		AutomationExecutionContext::beginProcessing();

		// 2. Automation A (add discount) and B (remove discount) both match and run once on FILE_1.
		$this->assertFalse($context->hasRun(self::AUTOMATION_A, self::FILE_1));
		$context->markRun(self::AUTOMATION_A, self::FILE_1);
		$this->assertFalse($context->hasRun(self::AUTOMATION_B, self::FILE_1));
		$context->markRun(self::AUTOMATION_B, self::FILE_1);

		// 3. One of the cart saves re-fires the event WITHOUT forwarding the context (worst case).
		//    runAutomations resolves the context again: it must be the SAME chain context.
		$reFireContext = $this->resolveExecutionContext(null);
		$this->assertSame($context, $reFireContext, 'A context-less re-fire reuses the chain context.');
		$this->assertTrue($reFireContext->hasRun(self::AUTOMATION_A, self::FILE_1), 'A is skipped on re-fire -> no re-add.');
		$this->assertTrue($reFireContext->hasRun(self::AUTOMATION_B, self::FILE_1), 'B is skipped on re-fire -> no re-remove.');

		AutomationExecutionContext::endProcessing();

		// 4. A genuinely new top-level trigger starts from a clean slate, so the automations can run
		//    again (we did not permanently disable them).
		$this->assertFalse(AutomationExecutionContext::isProcessing());
		$newChainContext = $this->resolveExecutionContext(null);
		$this->assertNotSame($context, $newChainContext, 'A new top-level trigger gets a fresh chain context.');
		$this->assertFalse($newChainContext->hasRun(self::AUTOMATION_A, self::FILE_1), 'Fresh chain -> A may run again.');
	}

	/**
	 * The guard is per (automation, target): the SAME automation acting on a DIFFERENT file/user in
	 * the same chain is legitimate fan-out and must NOT be skipped — only a re-run on the same target
	 * is treated as a loop.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::hasRun
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::markRun
	 * @return void
	 */
	public function testGuardIsPerAutomationAndTarget(): void
	{
		$context = new AutomationExecutionContext();

		$context->markRun(self::AUTOMATION_A, self::FILE_1, 'A');

		// Same automation, same file => already run (loop).
		$this->assertTrue($context->hasRun(self::AUTOMATION_A, self::FILE_1));
		// Same automation, DIFFERENT file => must still be allowed to run.
		$this->assertFalse($context->hasRun(self::AUTOMATION_A, self::FILE_2), 'Same automation on another file is legitimate fan-out.');
		// Different automation, same file => independent.
		$this->assertFalse($context->hasRun(self::AUTOMATION_B, self::FILE_1));

		// A user-targeted automation (no fnum) is keyed by user id; distinct users stay independent.
		$context->markRun(self::AUTOMATION_B, '55', 'B');
		$this->assertTrue($context->hasRun(self::AUTOMATION_B, '55'));
		$this->assertFalse($context->hasRun(self::AUTOMATION_B, '77'), 'Same automation on another user is legitimate fan-out.');

		// Despite running on multiple targets, each automation is listed once for the loop alert.
		$this->assertSame([self::AUTOMATION_A, self::AUTOMATION_B], $context->getExecutedIds());
		$this->assertSame(['A', 'B'], $context->getExecutedLabels());
	}

	/**
	 * An explicit execution context forwarded by a propagating save path always wins over the
	 * shared chain context.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::current
	 * @return void
	 */
	public function testForwardedContextWinsOverSharedContext(): void
	{
		$forwarded = new AutomationExecutionContext();

		$resolved = $this->resolveExecutionContext($forwarded);
		$this->assertSame($forwarded, $resolved, 'A forwarded execution context is used as-is.');
		$this->assertNotSame(AutomationExecutionContext::current(), $forwarded, 'It is independent from the shared context.');
	}

	/**
	 * The shared chain context is dropped only when the OUTERMOST processing pass ends, so nested
	 * re-entrant triggers keep sharing it.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::current
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::beginProcessing
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::endProcessing
	 * @return void
	 */
	public function testSharedContextLivesUntilOutermostPassEnds(): void
	{
		$outer = AutomationExecutionContext::current();
		AutomationExecutionContext::beginProcessing(); // depth 1 (top-level)

		AutomationExecutionContext::beginProcessing(); // depth 2 (nested re-entrant trigger)
		$this->assertSame($outer, AutomationExecutionContext::current(), 'Nested trigger shares the context.');
		AutomationExecutionContext::endProcessing();   // depth 1
		$this->assertSame($outer, AutomationExecutionContext::current(), 'Context kept while outer pass is still running.');

		AutomationExecutionContext::endProcessing();   // depth 0 -> shared dropped
		$this->assertNotSame($outer, AutomationExecutionContext::current(), 'Context reset once the outermost pass ends.');
	}

	/**
	 * The depth circuit breaker trips when processing is nested too deep, allowing runAutomations()
	 * to abort a runaway cascade.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::beginProcessing
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::endProcessing
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::getProcessingDepth
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::hasReachedMaxProcessingDepth
	 * @return void
	 */
	public function testProcessingDepthCircuitBreakerTrips(): void
	{
		$this->assertFalse(AutomationExecutionContext::hasReachedMaxProcessingDepth(), 'Breaker is open at depth 0.');

		try
		{
			for ($i = 0; $i < AutomationExecutionContext::MAX_PROCESSING_DEPTH; $i++)
			{
				AutomationExecutionContext::beginProcessing();
			}

			$this->assertSame(
				AutomationExecutionContext::MAX_PROCESSING_DEPTH,
				AutomationExecutionContext::getProcessingDepth(),
				'Depth tracked exactly.'
			);
			$this->assertTrue(
				AutomationExecutionContext::hasReachedMaxProcessingDepth(),
				'Breaker trips once the maximum nesting depth is reached, so runAutomations() aborts.'
			);
		}
		finally
		{
			for ($i = 0; $i < AutomationExecutionContext::MAX_PROCESSING_DEPTH; $i++)
			{
				AutomationExecutionContext::endProcessing();
			}
		}

		$this->assertSame(0, AutomationExecutionContext::getProcessingDepth(), 'Depth fully unwound.');
		$this->assertFalse(AutomationExecutionContext::hasReachedMaxProcessingDepth());
	}

	/**
	 * The chain context exposes which automations ran (ids + labels) and raises a single
	 * "loop detected" alert per chain, so the manager history is not flooded with one entry per
	 * skipped re-execution.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::markRun
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::getExecutedIds
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::getExecutedLabels
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::loopAlertWasRaised
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::markLoopAlertRaised
	 * @return void
	 */
	public function testTracksInvolvedAutomationsAndRaisesLoopAlertOnce(): void
	{
		$context = new AutomationExecutionContext();

		$context->markRun(self::AUTOMATION_A, self::FILE_1, 'Remise paiement en une fois');
		$context->markRun(self::AUTOMATION_B, self::FILE_1, 'Suppression remise');

		$this->assertSame([self::AUTOMATION_A, self::AUTOMATION_B], $context->getExecutedIds());
		$this->assertSame(['Remise paiement en une fois', 'Suppression remise'], $context->getExecutedLabels());

		// markRun without a label falls back to the id as a string (backward compatible).
		$context->markRun(999, self::FILE_1);
		$this->assertContains('999', $context->getExecutedLabels());

		// The loop alert is raised at most once per chain.
		$this->assertFalse($context->loopAlertWasRaised());
		$context->markLoopAlertRaised();
		$this->assertTrue($context->loopAlertWasRaised());
	}

	/**
	 * endProcessing() must never drive the depth below zero, otherwise an unbalanced call would
	 * corrupt the request-scoped state for the rest of the request.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::endProcessing
	 * @covers \Tchooz\Entities\Automation\AutomationExecutionContext::getProcessingDepth
	 * @return void
	 */
	public function testEndProcessingNeverGoesNegative(): void
	{
		AutomationExecutionContext::endProcessing();
		AutomationExecutionContext::endProcessing();

		$this->assertSame(0, AutomationExecutionContext::getProcessingDepth(), 'Depth is clamped at 0.');
		$this->assertFalse(AutomationExecutionContext::isProcessing());
	}
}
