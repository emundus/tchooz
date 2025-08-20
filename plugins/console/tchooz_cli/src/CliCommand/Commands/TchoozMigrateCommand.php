<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

defined('_JEXEC') or die;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Emundus\Plugin\Console\Tchooz\Jobs\Checker\CheckHealthJob;
use Emundus\Plugin\Console\Tchooz\Jobs\FixAssetsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Definition\JobDefinition;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateEmundusJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateEvaluationsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateExtensionsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateFabrikJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateFilesJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateJoomlaJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateOtherTablesJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateSQLViewsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateTemplatesJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Migration\MigrateWorkflowsJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Services\StorageService;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(name: 'tchooz:migrate', description: 'Migrate your Core site to Tchooz v2')]
class TchoozMigrateCommand extends TchoozCommand
{
	use DatabaseAwareTrait;

	protected static $defaultName = 'tchooz:migrate';

	private ProgressBar $progressBar;

	private string $projectToMigrate;

	private \JConfigOld $sourceConfiguration;

	private DatabaseService $databaseServiceSource;

	private DatabaseService $databaseService;

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will migrate your Core site to Tchooz v2";

		$this->addOption('project_to_migrate', null, InputOption::VALUE_REQUIRED, 'Path to the project to migrate');

		$this->setDescription('Migrate your Core site to Tchooz v2');
		$this->setHelp($help);
	}

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$this->ioStyle->title('Migrate to Joomla 5!');

		$pathV1 = $this->getApplication()->get('path_v1', '');

		$this->validateProjectPath($this->getStringFromOption('project_to_migrate', 'Absolute path to the v1 project', true, true, $pathV1));
		$this->sourceConfiguration = $this->getSourceConfiguration($this->projectToMigrate . '/configuration.php', 'Old');
		try
		{
			$this->initializeDatabaseConnection();
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		$jobs = [
			MigrateEmundusJob::getJobName() => (new JobDefinition(
				MigrateEmundusJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			MigrateFabrikJob::getJobName() => (new JobDefinition(
				MigrateFabrikJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			MigrateJoomlaJob::getJobName() => (new JobDefinition(
				MigrateJoomlaJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			MigrateExtensionsJob::getJobName() => (new JobDefinition(
				MigrateExtensionsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			MigrateOtherTablesJob::getJobName() => (new JobDefinition(
				MigrateOtherTablesJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			MigrateSQLViewsJob::getJobName() => (new JobDefinition(
				MigrateSQLViewsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			MigrateTemplatesJob::getJobName() => (new JobDefinition(
				MigrateTemplatesJob::class,
				[$this->databaseServiceSource, $this->databaseService, new StorageService($this->databaseService)],
				['projectToMigratePath' => $this->projectToMigrate]
			)),

			MigrateFilesJob::getJobName() => (new JobDefinition(
				MigrateFilesJob::class,
				[new StorageService($this->databaseService)],
				['projectToMigratePath' => $this->projectToMigrate]
			)),

			FixAssetsJob::getJobName() => (new JobDefinition(
				FixAssetsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			CheckHealthJob::getJobName() => (new JobDefinition(
				CheckHealthJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			))
		];
		$this->setJobs($jobs);

		$selectedJobs = $this->askJobsToExecute(array_keys($jobs));
		if (in_array('All', $selectedJobs))
		{
			$selectedJobs = array_keys($jobs);
		}

		if (!empty($selectedJobs))
		{
			$toggled = $this->toggleMaintenanceMode(true, $this->projectToMigrate . '/configuration.php');
			if (!$toggled)
			{
				$this->ioStyle->error("Could not enable maintenance mode!");

				return Command::FAILURE;
			}
		}

		foreach ($selectedJobs as $job)
		{
			try
			{
				$this->executeJob($job, $input, $output);
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}

		$this->ioStyle->success("Migration completed successfully!");

		// Ask if we have to run update command
		$choice           = new ConfirmationQuestion(
			'Do you want to run the update command? (yes/no)'
		);
		$runUpdateCommand = $this->ioStyle->askQuestion($choice);
		if ($runUpdateCommand)
		{
			$updateCommand = $this->getApplication()->getCommand(TchoozUpdateCommand::getDefaultName());
			$updateCommandInput = new ArrayInput(['--component' =>'com_emundus']);
			$updateCommandInput->setInteractive(false);

			$returnCode = $updateCommand->execute(
				$updateCommandInput,
				$output
			);

			if ($returnCode !== Command::SUCCESS)
			{
				$this->ioStyle->warning("Update command failed!");
			}
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
		try
		{
			$this->databaseServiceSource = new DatabaseService($this->sourceConfiguration, null, true);
			$this->databaseService       = new DatabaseService(null, $this->db, true);

			$this->databaseService->fixExtensionVersion('2.0.0');
		}
		catch (\Exception $e)
		{
			throw new \Exception('Could not connect to the source database!');
		}
	}

	private function getSourceConfiguration(string $configuration_path, string $namespace = ''): ?object
	{
		$config = null;

		if (!empty($configuration_path))
		{
			if (is_file($configuration_path))
			{
				$copied = copy($configuration_path, JPATH_ROOT . '/configuration_old.php');
				if ($copied)
				{
					$file         = JPATH_ROOT . '/configuration_old.php';
					$file_content = file_get_contents($file);

					// WARNING: We need to replace JConfig with JConfigOld because we cannot declare JConfig twice
					$file_content = preg_replace('/\bJConfig\b/', 'JConfigOld', $file_content);
					file_put_contents($file, $file_content);
					if (is_file($file))
					{
						include_once $file;
					}

					$namespace = ucfirst((string) preg_replace('/[^A-Z_]/i', '', $namespace));
					$name      = 'JConfig' . $namespace;

					$config = null;
					if (class_exists($name))
					{
						$config = new $name();
					}
				}
			}
		}

		return $config;
	}

	// WARNING: We have to use preg_replace because of we cannot declare JConfig twice
	private function toggleMaintenanceMode(bool $enable, string $path): bool
	{
		$pattern = $enable ? '/public \$offline = \'0\';/' : '/public \$offline = \'1\';/';

		$configContent = file_get_contents($path);
		$configContent = preg_replace($pattern, 'public $offline = ' . ($enable ? '\'1\'' : '\'0\'') . ';', $configContent);

		return file_put_contents($path, $configContent) !== false;
	}
}