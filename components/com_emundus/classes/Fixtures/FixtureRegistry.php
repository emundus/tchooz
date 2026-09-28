<?php

namespace Tchooz\Fixtures;

use Tchooz\Fixtures\SampleData\SampleDataScenario;

/**
 * The fixtures and scenarios the CLI can run. Adding one is a single line here - the name it answers
 * to is declared by the fixture/scenario itself, so there is no second place where the two could
 * disagree.
 *
 * Two tiers:
 *  - FIXTURES  build one validated row through its repository (low volume, exercises the save path).
 *  - SCENARIOS build a whole dataset at once (high volume, own batching).
 */
final class FixtureRegistry
{
	/**
	 * @var array<class-string<FixtureInterface>>
	 */
	private const FIXTURES = [];

	/**
	 * @var array<class-string<ScenarioInterface>>
	 */
	private const SCENARIOS = [
		SampleDataScenario::class,
	];

	/**
	 * @return array<string>
	 */
	public static function getEntityNames(): array
	{
		return array_map(fn(string $fixture) => $fixture::getEntityName(), self::FIXTURES);
	}

	/**
	 * @return array<string>
	 */
	public static function getScenarioNames(): array
	{
		return array_map(fn(string $scenario) => $scenario::getScenarioName(), self::SCENARIOS);
	}

	/**
	 * @throws \InvalidArgumentException  When no fixture answers to that name.
	 */
	public static function get(string $entityName): FixtureInterface
	{
		foreach (self::FIXTURES as $fixture)
		{
			if ($fixture::getEntityName() === $entityName)
			{
				return new $fixture();
			}
		}

		throw new \InvalidArgumentException(
			'Unknown fixture entity "' . $entityName . '". Available: ' . implode(', ', self::getEntityNames()) . '.'
		);
	}

	/**
	 * @throws \InvalidArgumentException  When no scenario answers to that name.
	 */
	public static function getScenario(string $scenarioName): ScenarioInterface
	{
		foreach (self::SCENARIOS as $scenario)
		{
			if ($scenario::getScenarioName() === $scenarioName)
			{
				return new $scenario();
			}
		}

		throw new \InvalidArgumentException(
			'Unknown scenario "' . $scenarioName . '". Available: ' . implode(', ', self::getScenarioNames()) . '.'
		);
	}

	/**
	 * Every CLI option declared by any scenario, de-duplicated by name, so the command can register
	 * them all up front without knowing which scenario the user will pick.
	 *
	 * @return array<ScenarioOption>
	 */
	public static function getAllScenarioOptions(): array
	{
		$options = [];

		foreach (self::SCENARIOS as $scenario)
		{
			foreach ($scenario::getOptions() as $option)
			{
				$options[$option->name] = $option;
			}
		}

		return array_values($options);
	}
}
