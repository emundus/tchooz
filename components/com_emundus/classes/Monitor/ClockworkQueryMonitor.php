<?php

namespace Tchooz\Monitor;

use Joomla\Database\QueryMonitorInterface;

class ClockworkQueryMonitor implements QueryMonitorInterface
{
	private $start;
	private $sql;
	private $params;

	public function __construct(private $clockwork) {}

	public function startQuery(string $sql, ?array $boundParams = null): void
	{
		$this->sql    = $sql;
		$this->params = $boundParams ?? [];
		$this->start  = microtime(true);
	}

	public function stopQuery(): void
	{
		$duration = (microtime(true) - $this->start) * 1000; // en ms
		$this->clockwork->addDatabaseQuery($this->sql, $this->params, $duration);
	}
}