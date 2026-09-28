<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tchooz\Fixtures\FixtureRegistry;
use Tchooz\Fixtures\ScenarioInterface;

/**
 * Creates ready-to-use rows so a scenario can be set up without going through the interface that
 * normally creates them. Two tiers, both declared in Tchooz\Fixtures\FixtureRegistry:
 *
 *  - --entity=<name>    one validated row through its repository (low volume, exercises the save path)
 *  - --scenario=<name>  a whole dataset at once (high volume, e.g. sampledata: users, dossiers, ...)
 *
 * The building lives in the component (Tchooz\Fixtures); this command only picks one and reports what
 * it wrote.
 */
#[AsCommand(name: 'tchooz:fixtures', description: 'Create test fixtures or a full sample dataset for Tchooz')]
class TchoozFixturesCommand extends TchoozCommand
{
	/**
	 * @var    string
	 */
	protected static $defaultName = 'tchooz:fixtures';

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$this->ioStyle->title('Tchooz fixtures');

		if ($input->getOption('list'))
		{
			return $this->listAvailable();
		}

		if ($input->getOption('scenario'))
		{
			return $this->runScenario($input);
		}

		return $this->runFixture($input);
	}

	private function listAvailable(): int
	{
		$this->ioStyle->section('Fixtures (--entity)');
		$this->ioStyle->listing(FixtureRegistry::getEntityNames());

		$this->ioStyle->section('Scenarios (--scenario)');
		$this->ioStyle->listing(FixtureRegistry::getScenarioNames());

		return Command::SUCCESS;
	}

	private function runFixture(InputInterface $input): int
	{
		$entity = $this->getStringFromOption('entity', 'Which entity do you want fixtures for? (' . implode(', ', FixtureRegistry::getEntityNames()) . ')');
		$count  = max(1, (int) $input->getOption('count'));

		try
		{
			$fixture = FixtureRegistry::get($entity);
			$created = $fixture->createMany($count);
		}
		catch (\Throwable $e)
		{
			$this->ioStyle->error($e->getMessage());

			return Command::FAILURE;
		}

		foreach ($created as $entityCreated)
		{
			$this->ioStyle->writeln(sprintf(
				'  #%d %s',
				$entityCreated->getId(),
				method_exists($entityCreated, 'getName') ? $entityCreated->getName() : $entityCreated::class
			));
		}

		$this->ioStyle->success(sprintf('%d %s fixture(s) created.', count($created), $entity));

		return Command::SUCCESS;
	}

	private function runScenario(InputInterface $input): int
	{
		$scenarioName = (string) $input->getOption('scenario');

		try
		{
			$scenario = FixtureRegistry::getScenario($scenarioName);
			$options  = $this->collectScenarioOptions($input, $scenario);

			$progress = null;
			$stats    = $scenario->generate(
				$options,
				function (string $phase, int $done, int $total) use (&$progress) {
					if ($progress === null)
					{
						$progress = $this->ioStyle->createProgressBar($total);
						$progress->start();
					}

					$progress->setProgress(min($done, $total));
				}
			);

			if ($progress !== null)
			{
				$progress->finish();
				$this->ioStyle->newLine(2);
			}

			$this->ioStyle->success(sprintf('Scenario "%s" generated.', $scenarioName));
			$this->ioStyle->table(array_keys($stats), [array_values($stats)]);

			return Command::SUCCESS;
		}
		catch (\Throwable $e)
		{
			$this->ioStyle->error($e->getMessage());

			return Command::FAILURE;
		}
	}

	/**
	 * Read the values the chosen scenario declared it accepts.
	 *
	 * @return array<string, mixed>
	 */
	private function collectScenarioOptions(InputInterface $input, ScenarioInterface $scenario): array
	{
		$values = [];

		foreach ($scenario::getOptions() as $option)
		{
			$values[$option->name] = $option->isFlag
				? (bool) $input->getOption($option->name)
				: ($input->getOption($option->name) ?? $option->default);
		}

		return $values;
	}

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> creates test fixtures or a full sample dataset for Tchooz."
			. "\n\nOne validated row: <info>php %command.full_name% --entity=automation_scheduled --count=3</info>"
			. "\nA full dataset:    <info>php %command.full_name% --scenario=sampledata --users=5000 --with-uploads --fast</info>"
			. "\nList everything:   <info>php %command.full_name% --list</info>";

		$this->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The entity to create a fixture for');
		$this->addOption('count', null, InputOption::VALUE_REQUIRED, 'How many rows to create', 1);
		$this->addOption('scenario', null, InputOption::VALUE_REQUIRED, 'The dataset scenario to generate');
		$this->addOption('list', null, InputOption::VALUE_NONE, 'List the fixtures and scenarios available');

		// Options belonging to scenarios, declared by the scenarios themselves.
		foreach (FixtureRegistry::getAllScenarioOptions() as $option)
		{
			if ($option->isFlag)
			{
				$this->addOption($option->name, null, InputOption::VALUE_NONE, $option->description);

				continue;
			}

			$this->addOption($option->name, null, InputOption::VALUE_OPTIONAL, $option->description, $option->default);
		}

		$this->setDescription('Create test fixtures or a full sample dataset for Tchooz');
		$this->setHelp($help);
	}
}
