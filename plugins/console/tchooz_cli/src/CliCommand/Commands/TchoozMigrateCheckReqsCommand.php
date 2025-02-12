<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Emundus\Plugin\Console\Tchooz\Jobs\CheckExtensionsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\CheckMiddlewareJob;
use Emundus\Plugin\Console\Tchooz\Jobs\CheckStorageJob;
use Emundus\Plugin\Console\Tchooz\Jobs\CheckVersionJob;
use Emundus\Plugin\Console\Tchooz\Jobs\JobDefinition;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Services\StorageService;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(name: 'tchooz:migrate:check-reqs', description: 'Check some prerequisites for the migration to Tchooz v2')]
class TchoozMigrateCheckReqsCommand extends TchoozCommand
{
	use DatabaseAwareTrait;

	protected static $defaultName = 'tchooz:migrate:check-reqs';

	private DatabaseInterface $dbSource;

	private string $dbName;

	private string $projectToMigrate;

	private DatabaseService $databaseService;

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will check certain prerequisites to ensure that migration to Tchooz v2 can be carried out without any problems.";

		$this->addOption('project_to_migrate', null, InputOption::VALUE_REQUIRED, 'Absolute path to the v1 project');

		$this->setDescription('Check some prerequisites for the migration to Tchooz v2');
		$this->setHelp($help);
	}

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$this->ioStyle->title('Checking prerequisites for migration to Tchooz v2');

		$this->validateProjectPath($this->getStringFromOption('project_to_migrate', 'Absolute path to the v1 project: ', true));
		try
		{
			$this->initializeDatabaseConnection();
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		$jobs = [
			CheckMiddlewareJob::getJobName() => (new JobDefinition(
				CheckMiddlewareJob::class,
				[$this->databaseService]
			)),

			CheckStorageJob::getJobName() => (new JobDefinition(
				CheckStorageJob::class,
				[new StorageService($this->databaseService)]
			)),

			CheckVersionJob::getJobName() => (new JobDefinition(
				CheckVersionJob::class,
				[$this->databaseService],
				['projectToMigrate' => $this->projectToMigrate]
			)),

			CheckExtensionsJob::getJobName() => (new JobDefinition(
				CheckExtensionsJob::class,
				[$this->databaseService]
			)),
		];
		$this->setJobs($jobs);

		$selectedJobs = $this->askJobsToExecute(array_keys($jobs), true);
		if (in_array('All', $selectedJobs))
		{
			$selectedJobs = array_keys($jobs);
		}

		foreach ($selectedJobs as $job)
		{
			$this->executeJob($job);
		}

		return Command::SUCCESS;
	}

	private function validateProjectPath(string $projectPath): void
	{
		if (!is_dir($projectPath))
		{
			throw new InvalidOptionException('The specified project directory does not exist!');
		}
		if (!file_exists($projectPath . '/configuration.php'))
		{
			throw new InvalidOptionException('configuration.php file is missing in the project path!');
		}
		$this->projectToMigrate = $projectPath;
	}

	private function initializeDatabaseConnection(): void
	{
		$configuration_file = $this->projectToMigrate . '/configuration.php';

		try
		{
			$this->databaseService = new DatabaseService($configuration_file);
			$this->dbSource        = $this->databaseService->getDatabase();
			$this->dbName          = $this->databaseService->getDbName();
		}
		catch (\Exception $e)
		{
			throw new \Exception('Could not connect to the source database!');
		}
	}
}