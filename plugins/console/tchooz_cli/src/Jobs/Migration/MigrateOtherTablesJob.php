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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateOtherTablesJob extends TchoozJob
{
	public function __construct(
		private readonly object $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService,
		private readonly int $limit = 1000
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void {
		$section = $output->section();

		$dbTables       = $this->databaseService->getTables();
		$dbSourceTables = $this->databaseServiceSource->getTables();
		if (empty($dbTables) || empty($dbSourceTables))
		{
			Log::add('No tables found in source or destination database', Log::ERROR, self::getJobName());
			throw new \RuntimeException('No tables found in source or destination database');
		}

		$otherTables = array_filter($dbSourceTables, function ($table) {
			return !str_contains($table, 'jos_');
		});

		$this->databaseService->startTransaction();

		$progressBar = new EmundusProgressBar($section, count($otherTables));
		$progressBar->setMessage('Migrating other tables from source to destination');
		$progressBar->start();

		foreach ($otherTables as $table)
		{
			if (!$this->databaseService->dropTable($table))
			{
				Log::add('Error while dropping table ' . $table, Log::ERROR, self::getJobName());

				throw new \RuntimeException('Error while dropping table ' . $table);
			}

			$tableDump = $this->databaseServiceSource->getTableCreate($table);
			if (empty($tableDump[$table]) || !$this->databaseService->getDatabase()->setQuery($tableDump[$table])->execute())
			{
				Log::add('Error while creating table ' . $table, Log::ERROR, self::getJobName());

				throw new \RuntimeException('Error while creating table ' . $table);
			}

			$count = $this->databaseServiceSource->getDatasCount($table);

			// If datas are too many, we have to split the insertions
			if ($count > $this->limit)
			{
				$limit = $this->limit;
				$offset = 0;
				$datas = $this->databaseServiceSource->getDatas($table, $limit, $offset);
				while (!empty($datas))
				{
					if (!$this->databaseService->insertDatas($datas, $table))
					{
						Log::add('Error while inserting datas in table ' . $table, Log::ERROR, self::getJobName());

						$this->databaseService->rollbackTransaction();

						throw new \RuntimeException('Error while inserting datas in table ' . $table);
					}

					$offset += $limit;
					$datas = $this->databaseServiceSource->getDatas($table, $limit, $offset);
				}
			}
			else
			{
				$datas = $this->databaseServiceSource->getDatas($table);
				if (!$this->databaseService->insertDatas($datas, $table))
				{
					Log::add('Error while inserting datas in table ' . $table, Log::ERROR, self::getJobName());

					$this->databaseService->rollbackTransaction();

					throw new \RuntimeException('Error while inserting datas in table ' . $table);
				}
			}

			$progressBar->advance();
			Log::add('Table ' . $table . ' migrated', Log::INFO, self::getJobName());
		}
		$progressBar->finish('Other tables migrated');

		Log::add('Other tables migrated', Log::INFO, self::getJobName());

		$this->databaseService->commitTransaction();
	}

	public static function getJobName(): string {
		return 'Other tables';
	}

	public static function getJobDescription(): ?string {
		return 'Migrate other tables like datas tables (data_...)';
	}
}