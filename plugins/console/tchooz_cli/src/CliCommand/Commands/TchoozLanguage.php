<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Language\Language;
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

			$job = $input->getOption('job');

			if(empty($job))
			{
				$question = new ChoiceQuestion(
					'Please select what you want to fix ?',
					[
						'database'     => 'Fix languages in database',
						'files'        => 'Fix override files',
						'check_health' => 'Check health of languages and fix if needed',
					]
				);

				$job = $this->ioStyle->askQuestion($question);
			}

			if(!in_array($job, ['database', 'files', 'check_health']))
			{
				throw new \Exception("Invalid option selected");
			}

			if($job == 'database')
			{
				$dbLanguage = new DbLanguage();
				if (!$dbLanguage->filesToDatabase())
				{
					throw new \Exception("Error while fixing languages in database");
				}

				$this->output->writeln("Languages fixed\n");
			}
			elseif($job == 'files')
			{
				$dbLanguage = new DbLanguage();
				if (!$dbLanguage->databaseToFiles())
				{
					throw new \Exception("Error while fixing override files");
				}

				$this->output->writeln("Languages fixed\n");
			}
			elseif($job == 'check_health')
			{
				$languageClass = Factory::getApplication()->getLanguage();

				$filesToDebug = [
					JPATH_ROOT . '/components/com_emundus/language/en-GB/en-GB.com_emundus.ini',
					JPATH_ROOT . '/components/com_emundus/language/fr-FR/fr-FR.com_emundus.ini',
				];
				$keys = [];
				foreach ($filesToDebug as $file)
				{
					if (!file_exists($file))
					{
						throw new \Exception("File $file does not exist");
					}
					if (!is_readable($file))
					{
						throw new \Exception("File $file is not readable");
					}

					$debug = $languageClass->debugFile($file);
					if (!empty($debug)) {
						throw new \Exception("File $file has errors in lines : " . implode(', ', $languageClass->getErrorFiles()[$file]));
					}

					$strings = LanguageHelper::parseIniFile($file);
					if (empty($strings))
					{
						throw new \Exception("File $file is empty or has invalid format");
					}
					
					$keys[$file] = array_keys($strings);
				}
				
				// Check if keys are the same in both files
				$keysToCheck = array_shift($keys);
				foreach ($keys as $file => $fileKeys)
				{
					$missingKeys = array_diff($keysToCheck, $fileKeys);
					if (!empty($missingKeys))
					{
						foreach ($missingKeys as $missingKey)
						{
							$this->output->writeln("Missing key : $missingKey\n");
						}
						throw new \Exception("File $file has missing keys that found in other files");
					}
					$extraKeys = array_diff($fileKeys, $keysToCheck);
					if (!empty($extraKeys))
					{
						foreach ($extraKeys as $extraKey)
						{
							$this->output->writeln("Extra key : $extraKey\n");
						}

						throw new \Exception("File $file has extra keys that not found in other files");
					}
				}

				$this->output->writeln("Languages are healthy");
			}
		}
		catch (\Exception $e)
		{
			$this->output->writeln($e->getMessage());

			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will fix languages by updating database or files.
		\nUsage: <info>php %command.full_name%</info>";

		$this->addOption('job', null, InputOption::VALUE_OPTIONAL, 'Job to execute');
		
		$this->setDescription('Fix languages by update database or files');
		$this->setHelp($help);
	}
}