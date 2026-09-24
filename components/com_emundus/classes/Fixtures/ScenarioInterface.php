<?php

namespace Tchooz\Fixtures;

/**
 * A scenario builds a whole dataset at once - many rows across several entities - rather than the
 * single validated row a {@see FixtureInterface} produces. It owns its own volume/batching so it can
 * stay fast at 5000+ rows, and declares the CLI options it accepts through {@see getOptions()}.
 */
interface ScenarioInterface
{
	/**
	 * The name the CLI addresses this scenario by (tchooz:fixtures --scenario=<name>).
	 */
	public static function getScenarioName(): string;

	/**
	 * The options this scenario reads from the command line.
	 *
	 * @return array<ScenarioOption>
	 */
	public static function getOptions(): array;

	/**
	 * Run the scenario and return what it wrote.
	 *
	 * @param   array<string, mixed>  $options     Values keyed by ScenarioOption::$name.
	 * @param   callable|null         $onProgress  fn(string $phase, int $done, int $total).
	 *
	 * @return  array<string, int>  Stats keyed by label (e.g. users, files).
	 */
	public function generate(array $options, ?callable $onProgress = null): array;
}
