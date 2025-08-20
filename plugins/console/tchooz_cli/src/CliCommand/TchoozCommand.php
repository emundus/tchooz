<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand;

use Emundus\Plugin\Console\Tchooz\Jobs\Definition\JobDefinition;
use Emundus\Plugin\Console\Tchooz\Jobs\Enum\JobStatus;
use Joomla\CMS\Log\Log;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TchoozCommand extends AbstractCommand
{
	use DatabaseAwareTrait;

	protected SymfonyStyle $ioStyle;

	protected InputInterface $cliInput;

	protected DatabaseInterface $db;

	protected \EmundusHelperCache $hCache;

	protected array $jobs;

	private array $colors = [
		'blue'   => "\e[34m",
		'green'  => "\e[32m",
		'red'    => "\e[31m",
		'yellow' => "\e[33m",
		'reset'  => "\e[0m"
	];

	public function __construct(
		DatabaseInterface $db
	)
	{
		parent::__construct();

		$this->setDatabase($db);
		$this->db = $this->getDatabase();

		require_once(JPATH_BASE . '/components/com_emundus/helpers/cache.php');
		$this->hCache = new \EmundusHelperCache();
	}

	protected function configureIO(InputInterface $input, OutputInterface $output): void
	{
		$this->cliInput = $input;
		$this->ioStyle  = new SymfonyStyle($input, $output);
	}

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		return Command::SUCCESS;
	}

	protected function getStringFromOption(string $option, string $question, bool $required = true, bool $saveValue = false, ?string $defaultValue = null): string
	{
		$answer = (string) $this->cliInput->getOption($option);

		if ($this->cliInput->getOption('no-interaction') === false)
		{
			if(empty($defaultValue))
			{
				$defaultValue = $this->hCache->get($option);
			}

			if ($required)
			{
				while (!$answer)
				{
					$answer = (string) $this->ioStyle->ask($question, $defaultValue);
				}
			}
			else
			{
				if (!$answer)
				{
					$answer = (string) $this->ioStyle->ask($question, $defaultValue);
				}
			}
		}

		if ($saveValue)
		{
			$this->hCache->set($option, $answer);
		}

		return $answer;
	}

	protected function getIoJobs(): array
	{
		$jobs = $this->getJobs();

		$jobsArray = array_map(function ($data) {
			return $data->getDescription();
		}, $jobs);

		return array_merge(['All' => 'All'], $jobsArray);
	}

	protected function askJobsToExecute(?array $defaultJobs = [], ?bool $multiselect = true): array
	{
		$question = new ChoiceQuestion(
			'Please select the jobs you want to perform (separate multiple choices with a comma)',
			$this->getIoJobs()
		);
		$question->setMultiselect($multiselect);

		return $this->ioStyle->askQuestion($question);
	}

	protected function executeJob(string $job, InputInterface $input, OutputInterface $output): void
	{
		$jobDefinition = $this->getJob($job);

		$this->ioStyle->writeln('━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
		$this->ioStyle->writeln('🚀 ' . $this->colors['blue'] . 'Starting job: ' . $job . $this->colors['reset']);
		$this->ioStyle->writeln('━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

		$jobInstance = $jobDefinition->instantiate($this->getLogger(strtolower($job)));

		try
		{
			$jobInstance->execute($input, $output);
		}
		catch (\Exception $e)
		{
			if (!$jobInstance->isAllowFailure())
			{
				throw $e;
			}

			Log::add($e->getMessage(), Log::ERROR, $job);

			$this->ioStyle->warning('Job ' . $job . ' generate some logs available in logs/migration.cli.log');
		}

		$this->ioStyle->success('Job ' . $job . ' executed successfully!');
	}

	protected function getJob(string $jobName): JobDefinition
	{
		return $this->jobs[$jobName];
	}

	protected function getJobs(): array
	{
		return $this->jobs;
	}

	protected function setJobs(array $jobs): void
	{
		$this->jobs = $jobs;
	}

	protected function getLogger($jobName): object
	{
		$logger          = new \stdClass();
		$logger->options = [
			'text_file'         => 'migration.cli.log',
			'text_entry_format' => '{DATETIME} / {PRIORITY} | {CATEGORY} | {MESSAGE}',
			'text_file_no_php'  => true,
		];
		$logger->jobName = $jobName;

		return $logger;
	}

	protected function getReport(string $commandName): array
	{
		$report = [];

		if (!is_dir(JPATH_BASE . '/plugins/console/tchooz_cli/src/Report')) {
			mkdir(JPATH_BASE . '/plugins/console/tchooz_cli/src/Report');
		}

		// get content of Report/$commandName.json
		$reportFile = JPATH_BASE . '/plugins/console/tchooz_cli/src/Report/' . $commandName . '.json';

		if (file_exists($reportFile))
		{
			$reportContent = file_get_contents($reportFile);
			if ($reportContent !== false)
			{
				$report = json_decode($reportContent, true);
				if (json_last_error() !== JSON_ERROR_NONE)
				{
					Log::add('Error decoding JSON report: ' . json_last_error_msg(), Log::ERROR, 'tchooz');
				}
			}
			else
			{
				Log::add('Error reading report file: ' . $reportFile, Log::ERROR, 'tchooz');
			}
		}
		else
		{
			$report = [
				'command' => $commandName,
				'status'  => JobStatus::PENDING,
				'last_execution' => null,
				'jobs' => []
			];

			foreach ($this->jobs as $jobName => $job)
			{
				$report['jobs'][$jobName] = [
					'status' => JobStatus::PENDING,
					'last_execution' => null,
					'last_information' => null,
					'description' => $job->getDescription(),
					'note' => ''
				];
			}

			$report_created = file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			if ($report_created === false)
			{
				$this->ioStyle->warning('Error writing to file: ' . $reportFile);
			}
		}

		return $report;
	}

	protected function updateReport(array $report): void
	{
		$reportFile = JPATH_BASE . '/plugins/console/tchooz_cli/src/Report/' . $report['command'] . '.json';
		file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
}