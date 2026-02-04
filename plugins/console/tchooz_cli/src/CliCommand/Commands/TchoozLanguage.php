<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Joomla\CMS\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tchooz\Services\Language\DbLanguage;

#[AsCommand(name: 'tchooz:language', description: 'Fix languages by update database or files')]
class TchoozLanguage extends TchoozCommand
{
	use DatabaseAwareTrait;

	protected static $defaultName = 'tchooz:language';

	private OutputInterface $output;

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   4.0.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$this->output = $output;

		try
		{
			$this->output->writeln("Fix language");

			$question = new ChoiceQuestion(
				'Please select what you want to fix ?',
				[
					'database' => 'Fix languages in database',
					'files' => 'Fix override files'
				]
			);

			$response = $this->ioStyle->askQuestion($question);

			if(!in_array($response, ['database', 'files']))
			{
				throw new \Exception("Invalid option selected");
			}

			if($response == 'database')
			{
				$dbLanguage = new DbLanguage();
				if (!$dbLanguage->filesToDatabase())
				{
					throw new \Exception("Error while fixing languages in database");
				}
			}
			else
			{
				$dbLanguage = new DbLanguage();
				if (!$dbLanguage->databaseToFiles())
				{
					throw new \Exception("Error while fixing override files");
				}
			}

			$this->output->writeln("Languages fixed\n");
		}
		catch (\Exception $e)
		{
			$this->output->writeln($e->getMessage(), 'e');

			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will fix languages by updating database or files.
		\nUsage: <info>php %command.full_name%</info>";

		$this->setDescription('Fix languages by update database or files');
		$this->setHelp($help);
	}
}