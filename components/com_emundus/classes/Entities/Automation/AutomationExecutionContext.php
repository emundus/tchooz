<?php

namespace Tchooz\Entities\Automation;

class AutomationExecutionContext
{
	/**
	 * Re-entrancy guard keyed by "automationId:iterationIdentity". The guard is per (automation,
	 * target) — not per automation id — so that one automation legitimately acting on several
	 * distinct targets (files, users) within the same chain is not wrongly skipped. The iteration
	 * identity is the file (fnum), or the user id, or the triggering user id, depending on the
	 * automation's target type (see AutomationEntity::process).
	 *
	 * @var array<string, true>
	 */
	private array $executed = [];

	/**
	 * Map of automation id => label, one entry per automation that ran in this chain (deduped across
	 * targets). Used only to name the automations involved when a loop is detected; kept separate
	 * from the per-target $executed guard above.
	 *
	 * @var array<int, string>
	 */
	private array $involvedLabels = [];

	/**
	 * Whether a "possible loop" alert has already been raised for this chain. Prevents flooding the
	 * automation history with one entry per skipped re-execution: a single alert per top-level chain.
	 *
	 * @var bool
	 */
	private bool $loopAlertRaised = false;

	/**
	 * Whether the "loop aborted by circuit breaker" alert has already been raised for this chain.
	 * The breaker can be hit by several sibling events once the depth ceiling is reached; this keeps
	 * a single critical alert per chain.
	 *
	 * @var bool
	 */
	private bool $loopAbortAlertRaised = false;

	/**
	 * Request-scoped chain context.
	 *
	 * While a chain of automations is being processed, every (re-)trigger that did not carry an
	 * explicit execution context must reuse THIS instance instead of creating a fresh one. That
	 * way hasRun() persists across re-fires, even when an event is re-dispatched by a code path
	 * that forgot to forward the execution context (transaction update, legacy status change,
	 * etc.). It is the generic infinite-loop protection: a given automation runs at most once per
	 * top-level chain, so two automations with non mutually-exclusive conditions cannot ping-pong
	 * forever. Reset to null once the outermost chain finishes (see endProcessing()).
	 *
	 * @var self|null
	 */
	private static ?self $shared = null;

	/**
	 * Current automation processing nesting depth for this request. Used to detect the boundary of
	 * the outermost chain (to reset the shared context) and to back the depth circuit breaker.
	 *
	 * @var int
	 */
	private static int $processingDepth = 0;

	/**
	 * Maximum nesting depth allowed for automation processing within a single request. Last-resort
	 * circuit breaker: reaching it aborts further processing and is logged as a warning.
	 */
	public const MAX_PROCESSING_DEPTH = 25;

	private function guardKey(int $automationId, string $iterationIdentity): string {
		return $automationId . ':' . $iterationIdentity;
	}

	public function hasRun(int $automationId, string $iterationIdentity): bool {
		return isset($this->executed[$this->guardKey($automationId, $iterationIdentity)]);
	}

	public function markRun(int $automationId, string $iterationIdentity, ?string $label = null): void {
		$this->executed[$this->guardKey($automationId, $iterationIdentity)] = true;
		$this->involvedLabels[$automationId] = $label ?? (string) $automationId;
	}

	/**
	 * @return array<int> Ids of the automations that ran in this chain (deduped across targets).
	 */
	public function getExecutedIds(): array {
		return array_map('intval', array_keys($this->involvedLabels));
	}

	/**
	 * @return array<string> Labels of the automations that ran in this chain (deduped across targets).
	 */
	public function getExecutedLabels(): array {
		return array_values($this->involvedLabels);
	}

	public function loopAlertWasRaised(): bool {
		return $this->loopAlertRaised;
	}

	public function markLoopAlertRaised(): void {
		$this->loopAlertRaised = true;
	}

	public function loopAbortAlertWasRaised(): bool {
		return $this->loopAbortAlertRaised;
	}

	public function markLoopAbortAlertRaised(): void {
		$this->loopAbortAlertRaised = true;
	}

	/**
	 * Return the request-scoped chain context shared across (re-)triggers that did not forward an
	 * explicit execution context. Created lazily; cleared when the outermost chain finishes.
	 */
	public static function current(): self {
		if (self::$shared === null) {
			self::$shared = new self();
		}

		return self::$shared;
	}

	/**
	 * Mark the beginning of an automation processing pass for the current request.
	 * Must be paired with endProcessing() in a finally block.
	 */
	public static function beginProcessing(): void {
		self::$processingDepth++;
	}

	/**
	 * Mark the end of an automation processing pass. When the outermost pass ends, the shared chain
	 * context is dropped so the next independent top-level trigger starts from a clean slate (this
	 * matters for CLI workers / queues that process many items within a single PHP process).
	 */
	public static function endProcessing(): void {
		if (self::$processingDepth > 0) {
			self::$processingDepth--;
		}

		if (self::$processingDepth === 0) {
			self::$shared = null;
		}
	}

	/**
	 * @return bool True if an automation chain is currently being processed in this request.
	 */
	public static function isProcessing(): bool {
		return self::$processingDepth > 0;
	}

	/**
	 * @return int Current automation processing nesting depth for this request.
	 */
	public static function getProcessingDepth(): int {
		return self::$processingDepth;
	}

	/**
	 * @return bool True if the processing depth circuit breaker has been reached.
	 */
	public static function hasReachedMaxProcessingDepth(): bool {
		return self::$processingDepth >= self::MAX_PROCESSING_DEPTH;
	}

	/**
	 * Reset the request-scoped state. Mainly useful for tests and CLI workers that reuse the same
	 * PHP process across several independent requests.
	 */
	public static function resetProcessing(): void {
		self::$processingDepth = 0;
		self::$shared = null;
	}
}
