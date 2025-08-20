<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

defined('_JEXEC') or die;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Emundus\Plugin\Console\Tchooz\Jobs\Checklist\CheckCustomEventsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Checklist\CheckEmundusTagsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Definition\JobDefinition;
use Emundus\Plugin\Console\Tchooz\Jobs\Checklist\MigrateEvaluationsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Checklist\MigrateWorkflowsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Checklist\CheckJumiJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Checklist\CheckFabrikListsJob;
use Emundus\Plugin\Console\Tchooz\Jobs\Checklist\CheckFabrikFieldsJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Joomla\Database\DatabaseAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Emundus\Plugin\Console\Tchooz\Jobs\Enum\JobStatus;

#[AsCommand(name: 'tchooz:migrate:checklist', description: 'Post migration checklist')]
class TchoozMigrateChecklistCommand extends TchoozCommand
{
	use DatabaseAwareTrait;

	protected static $defaultName = 'tchooz:migrate:checklist';

	private ProgressBar $progressBar;

	private string $projectToMigrate;

	private DatabaseService $databaseServiceSource;

	private DatabaseService $databaseService;

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will provide a checklist for post migration tasks.\n";

		$this->setDescription('Post migration checklist');
		$this->setHelp($help);
	}

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$this->ioStyle->title('Post migration checklist');

		try
		{
			$this->initializeDatabaseConnection();
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		$jobs = [
			MigrateWorkflowsJob::getJobName() => (new JobDefinition(
				MigrateWorkflowsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			MigrateEvaluationsJob::getJobName() => (new JobDefinition(
				MigrateEvaluationsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			CheckCustomEventsJob::getJobName() => (new JobDefinition(
				CheckCustomEventsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			CheckJumiJob::getJobName() => (new JobDefinition(
				CheckJumiJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			CheckFabrikListsJob::getJobName() => (new JobDefinition(
				CheckFabrikListsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			CheckFabrikFieldsJob::getJobName() => (new JobDefinition(
				CheckFabrikFieldsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),

			CheckEmundusTagsJob::getJobName() => (new JobDefinition(
				CheckEmundusTagsJob::class,
				[$this->databaseServiceSource, $this->databaseService]
			)),
		];
		$this->setJobs($jobs);
		$selectedJobs = $this->askJobsToExecute(array_keys($jobs));
		if (in_array('All', $selectedJobs))
		{
			$selectedJobs = array_keys($jobs);
		}

		$report = $this->getReport(self::$defaultName);
		$report['last_execution'] = date('Y-m-d H:i:s TZ');
		foreach ($selectedJobs as $job)
		{
			try
			{
				if ($report['jobs'][$job]['status'] === JobStatus::SUCCESS)
				{
					$this->ioStyle->warning("Job $job has already been completed.");

					$helper = new QuestionHelper();
					$question = new ConfirmationQuestion('Do you still want to run Job ' . $job . '? [y/n] (n)', false);
					if (!$helper->ask($input, $output, $question)) {
						$this->ioStyle->warning("Skipping Job $job.");
						continue;
					} else if (!empty($report['jobs'][$job]['note'])) {
						$this->ioStyle->note("Job $job has a note: " . $report['jobs'][$job]['note']);
					}
				} else if (!empty($report['jobs'][$job]['note'])) {
					$this->ioStyle->note("Job $job has a note: " . $report['jobs'][$job]['note']);

					// Ask if the user wants to run the job again after reading last note
					$helper = new QuestionHelper();
					$question = new ConfirmationQuestion('Do you still want to run Job ' . $job . ' again? [y/n] (n)', false);
					if (!$helper->ask($input, $output, $question)) {
						$this->ioStyle->warning("Skipping Job $job.");
						continue;
					}
				}

				$this->executeJob($job, $input, $output);
				$report['jobs'][$job]['last_execution'] = date('Y-m-d H:i:s TZ');

				$helper = new QuestionHelper();
				$question = new ConfirmationQuestion('Mark Job ' . $job . ' as completed? [y/n] (n)', false);
				if ($helper->ask($input, $output, $question)) {
					$report['jobs'][$job]['status'] = JobStatus::SUCCESS;
				}

				$this->addOption('note', null, InputOption::VALUE_OPTIONAL, 'Add a note for this job (optional)');
				$note = $this->getStringFromOption('note', 'Add a note for this job (optional): ', false, true);
				if (!empty($note)) {
					$report['jobs'][$job]['note'] = $note;
				}

				$this->updateReport($report);
			}
			catch (\Exception $e)
			{
				$report['jobs'][$job]['last_information'] = $e->getMessage();
				$report['jobs'][$job]['status'] = JobStatus::FAILED;
				$this->updateReport($report);

				throw $e;
			}
		}

		$this->ioStyle->success("Migration checklist completed successfully!");

		return Command::SUCCESS;
	}

	private function initializeDatabaseConnection(): void
	{
		try
		{
			$this->databaseService       = new DatabaseService(null, $this->db, true);
			$this->databaseServiceSource = $this->databaseService;

			$this->databaseService->fixExtensionVersion('2.0.0');
		}
		catch (\Exception $e)
		{
			throw new \Exception('Could not connect to the source database!');
		}
	}
}
