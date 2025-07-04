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

class MigrateEmundusJob extends TchoozJob
{
	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService,
		private readonly int $limit = 1000
	)
	{
		parent::__construct($logger);
	}

	public function execute(OutputInterface $output): void
	{
		$section1 = $output->section();
		$section2 = $output->section();

		$tablesToIgnore = ['jos_emundus_version'];

		$dbTables       = $this->databaseService->getTables();
		$dbSourceTables = $this->databaseServiceSource->getTables();
		if (empty($dbTables) || empty($dbSourceTables))
		{
			Log::add('No tables found in source or destination database', Log::ERROR, self::getJobName());
			throw new \RuntimeException('No tables found in source or destination database');
		}

		$views           = $this->databaseServiceSource->getViews();
		$emundusTables   = array_filter($dbSourceTables, function ($table) {
			return str_contains($table, 'jos_emundus_');
		});
		$tablesToMigrate = $emundusTables;
		$tablesToMigrate = array_diff($tablesToMigrate, $views);
		$tablesToDrop    = array_intersect($dbTables, $tablesToMigrate);
		if(!empty($tablesToDrop))
		{
			$progressBarDropping = new EmundusProgressBar($section1, count($tablesToDrop));

			$progressBarDropping->setMessage('Dropping eMundus tables');
			$progressBarDropping->start();
			foreach ($tablesToDrop as $table)
			{
				if (!$this->databaseService->dropTable($table))
				{
					Log::add('Error while dropping table ' . $table, Log::ERROR, self::getJobName());
					throw new \RuntimeException('Error while dropping table ' . $table);
				}
				$progressBarDropping->advance();
			}
			$progressBarDropping->finish('eMundus tables dropped');
		}

		$this->databaseService->startTransaction();

		$progressBarMigrate = new EmundusProgressBar($section2, count($tablesToMigrate));

		$progressBarMigrate->setMessage('Migrating eMundus tables from source to destination');
		$progressBarMigrate->start();
		$this->databaseServiceSource->disableForeignKeyChecks();
		foreach ($tablesToMigrate as $table)
		{
			if (in_array($table, $tablesToIgnore))
			{
				continue;
			}

			if (!$this->databaseServiceSource->fixFnumLengthAndCollation($table))
			{
				Log::add('Error while fixing fnum length and collation in table ' . $table, Log::ERROR, self::getJobName());
				// Allow the migration to continue even if a table fix fails
				//throw new \RuntimeException('Error while fixing fnum length and collation in table ' . $table);
			}

			if (!$this->databaseServiceSource->convertToUtf8mb4($table))
			{
				Log::add('Error while converting table ' . $table . ' to utf8mb4', Log::ERROR, self::getJobName());
				// Allow the migration to continue even if a table conversion fails
				//throw new \RuntimeException('Error while converting table ' . $table . ' to utf8mb4');
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

			$progressBarMigrate->advance();
		}
		$this->databaseServiceSource->enableForeignKeyChecks();

		$progressBarMigrate->finish('eMundus tables migrated');

		Log::add('Emundus tables migrated', Log::INFO, self::getJobName());

		$this->databaseService->commitTransaction();
	}

	public static function getJobName(): string
	{
		return 'Emundus';
	}

	public static function getJobDescription(): ?string
	{
		return 'Migrate Emundus tables';
	}
}