<?php

namespace Tchooz\Entities\Automation;

class AutomationExecutionContext
{
	private array $executed = [];

	public function hasRun(int $automationId): bool {
		return isset($this->executed[$automationId]);
	}

	public function markRun(int $automationId): void {
		$this->executed[$automationId] = true;
	}
}