<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Migration;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Style\EmundusProgressBar;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateExtensionsJob extends TchoozJob
{
	public function __construct(
		private readonly object $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService
	)
	{
		parent::__construct($logger);
	}

	public function execute(OutputInterface $output): void {
		$section = $output->section();

		$dbTables       = $this->databaseService->getTables();
		$dbSourceTables = $this->databaseServiceSource->getTables();
		if (empty($dbTables) || empty($dbSourceTables))
		{
			Log::add('No tables found in source or destination database', Log::ERROR, self::getJobName());
			throw new \RuntimeException('No tables found in source or destination database');
		}

		$extension_tables = array_filter($dbTables, function ($table) {
			return str_contains($table, 'hikashop') || str_contains($table, 'dropfiles') || str_contains($table, 'falang');
		});

		$this->databaseService->getDatabase()->transactionStart();
		$this->databaseService->getDatabase()->setQuery('SET AUTOCOMMIT = 0')->execute();

		$progressBar = new EmundusProgressBar($section, count($extension_tables));
		$progressBar->setMessage('Migrating extension tables from source to destination');
		$progressBar->start();

		foreach ($extension_tables as $table)
		{
			if (in_array($table, $dbSourceTables))
			{
				if (!$this->databaseService->truncateTable($table))
				{
					Log::add('Error while truncating table ' . $table, Log::ERROR, self::getJobName());

					$this->databaseService->getDatabase()->transactionRollback();

					throw new \RuntimeException('Error while truncating table ' . $table);
				}

				$source_columns = $this->databaseServiceSource->getDatabase()->setQuery('SHOW COLUMNS FROM ' . $this->databaseServiceSource->getDatabase()->quoteName($table))->loadAssocList('Field');
				if (empty($source_columns) || !$this->databaseService->mergeColumns($source_columns, $table))
				{
					Log::add('Error while merging columns in table ' . $table, Log::ERROR, self::getJobName());

					throw new \RuntimeException('Error while merging columns in table ' . $table);
				}

				$datas = $this->databaseServiceSource->getDatas($table);
				if (!$this->databaseService->insertDatas($datas, $table))
				{
					Log::add('Error while inserting datas in table ' . $table, Log::ERROR, self::getJobName());

					$this->databaseService->getDatabase()->transactionRollback();

					throw new \RuntimeException('Error while inserting datas in table ' . $table);
				}

				Log::add('Table ' . $table . ' migrated', Log::INFO, self::getJobName());
			}

			$progressBar->advance();
		}
		$progressBar->finish('Extension tables migrated');

		Log::add('Extension tables migrated', Log::INFO, self::getJobName());

		$this->databaseService->getDatabase()->transactionCommit();
		$this->databaseService->getDatabase()->setQuery('SET AUTOCOMMIT = 1')->execute();
	}

	public static function getJobName(): string {
		return 'Extensions';
	}

	public static function getJobDescription(): ?string {
		return 'Migrate extensions';
	}
}