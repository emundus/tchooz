<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand;

use Emundus\Plugin\Console\Tchooz\Jobs\JobDefinition;
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

	protected array $jobs;

	public function __construct(
		DatabaseInterface $db
	)
	{
		parent::__construct();

		$this->setDatabase($db);
		$this->db = $this->getDatabase();
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

	protected function getStringFromOption($option, $question, $required = true): string
	{
		$answer = (string) $this->cliInput->getOption($option);

		if ($this->cliInput->getOption('no-interaction') === false)
		{
			if ($required)
			{
				while (!$answer)
				{
					$answer = (string) $this->ioStyle->ask($question);
				}
			}
			else
			{
				if (!$answer)
				{
					$answer = (string) $this->ioStyle->ask($question);
				}
			}
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
			'Please select the jobs you want to perform (separate multiple choices with a comma):',
			$this->getIoJobs(),
			implode(',', $defaultJobs) // Valeurs par défaut
		);
		$question->setMultiselect($multiselect);

		return $this->ioStyle->askQuestion($question);
	}

	protected function executeJob(string $job): void
	{
		$jobDefinition = $this->getJob($job);

		$this->ioStyle->section('Starting job: ' . $job);

		$jobInstance = $jobDefinition->instantiate($this->getLogger(strtolower($job)));

		try
		{
			$jobInstance->execute();
		}
		catch (\Exception $e)
		{
			if (!$jobInstance->isAllowFailure())
			{
				throw $e;
			}

			Log::add($e->getMessage(), Log::ERROR, $job);
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
}