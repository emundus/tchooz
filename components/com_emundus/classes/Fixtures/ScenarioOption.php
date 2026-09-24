<?php

namespace Tchooz\Fixtures;

/**
 * One CLI option a scenario understands. Lets the command declare and read a scenario's options
 * without knowing them ahead of time - the scenario is the single place that lists what it accepts.
 */
final class ScenarioOption
{
	public function __construct(
		public readonly string $name,
		public readonly string $description,
		public readonly string|int|bool|null $default = null,
		public readonly bool $isFlag = false
	) {
	}
}
