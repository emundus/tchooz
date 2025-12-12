<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Joomla\CMS\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'tchooz:fix:collations', description: 'Fix database collations and add missing foreign keys')]
class TchoozFixCollations extends TchoozCommand
{
	use DatabaseAwareTrait;

	protected static $defaultName = 'tchooz:fix:collations';

	private string $db_name;

	private array $db_tables = [];

	private string $collation = 'utf8mb4_0900_ai_ci';

	private string $sql_engine = 'MySQL';

	private array $foreign_keys = [];

	private $backup_file = JPATH_ROOT . '/logs/foreign_keys_backup.sql';

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
		$this->output = $output;


		try
		{
			$this->output->writeln("Fix collation of database tables");
			$this->db_name = Factory::getApplication()->getConfig()->get('db');

			$this->db_tables = $this->db->getTableList();
			$views           = $this->getViews($this->db, $this->db_name);
			$this->db_tables = array_diff($this->db_tables, $views);
			if (empty($this->db_tables))
			{
				$this->output->writeln("No tables found in the database\n");

				return Command::FAILURE;
			}

			$sql_engine = $this->db->setQuery("SHOW VARIABLES LIKE 'version_comment'")->loadAssoc();
			if (empty($sql_engine))
			{
				$this->output->writeln($this->colorLog("Failed to get sql engine version\n", 'e'));

				return Command::FAILURE;
			}
			$sql_engine = $sql_engine['Value'];
			if (!str_contains($sql_engine, 'MySQL'))
			{
				$this->sql_engine = 'MariaDB';
				$this->collation  = 'utf8mb4_unicode_ci';
			}

			if(!$this->fixRowFormat())
			{
				$this->output->writeln($this->colorLog("Failed to fix row format of some tables\n", 'e'));

				return Command::FAILURE;
			}

			$this->db->setQuery('SET sql_mode = ""')->execute();
			$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();

			if ($this->sql_engine == 'MariaDB')
			{
				$this->output->writeln($this->colorLog("Backup foreign keys\n"));

				if (!$this->backupForeignKeys())
				{
					$this->output->writeln($this->colorLog("Failed to backup foreign keys\n", 'e'));

					return Command::FAILURE;
				}
			}

			if(!$this->fixCollations())
			{
				$this->output->writeln($this->colorLog("Failed to fix collation of some tables\n", 'e'));
			}

			if ($this->sql_engine == 'MariaDB')
			{
				$this->output->writeln($this->colorLog("Restoring foreign keys\n"));

				if (!$this->restoreForeignKeys())
				{
					$this->output->writeln($this->colorLog("Failed to restore foreign keys\n", 'e'));

					return Command::FAILURE;
				}
			}

			$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();

			$this->output->writeln($this->colorLog("Collation of database tables fixed\n", 's'));
		}
		catch (\Exception $e)
		{
			$this->output->writeln($this->colorLog($e->getMessage(), 'e'));

			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}


	private function fixRowFormat(): bool
	{
		$fixed = true;

		// ROW FORMAT
		$this->output->writeln($this->colorLog("Fix ROW_FORMAT of old database tables\n"));
		$tables = [
			'jos_emundus_academic',
			'jos_emundus_admission',
			'jos_emundus_cv',
			'jos_emundus_declaration',
			'jos_emundus_evaluations',
			'jos_emundus_final_grade',
			'jos_emundus_languages',
			'jos_emundus_mobility',
			'jos_emundus_personal_detail',
			'jos_emundus_qualifications',
			'jos_emundus_references',
			'jos_emundus_scholarship',
			'jos_emundus_survey',
			'jos_emundus_users'
		];

		foreach ($tables as $table)
		{
			if (!in_array($table, $this->db_tables))
			{
				$this->output->writeln($this->colorLog("Table $table does not exist\n", 'w'));
				continue;
			}

			$this->output->writeln($this->colorLog("Fixing ROW_FORMAT of table $table\n"));

			if ($this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' ROW_FORMAT=DYNAMIC')->execute())
			{
				$this->output->writeln($this->colorLog("ROW_FORMAT of table $table fixed\n", 's'));
			}
			else
			{
				$this->output->writeln($this->colorLog("Failed to fix ROW_FORMAT of table $table\n", 'e'));
				$fixed = false;
			}
		}
		$this->output->writeln($this->colorLog("ROW_FORMAT of old database tables fixed\n", 's'));

		//

		return $fixed;
	}

	private function backupForeignKeys(): bool
	{
		$results = [];

		file_put_contents($this->backup_file, "-- Foreign key backup for database `$this->db_name`\n\n");

		foreach ($this->db_tables as $table)
		{
			// 1. Fetch foreign key constraints for this table
			$constraints = $this->db->setQuery("
			    SELECT 
		            tc.CONSTRAINT_NAME,
		            kcu.COLUMN_NAME,
			        kcu.REFERENCED_TABLE_NAME,
			        kcu.REFERENCED_COLUMN_NAME,
			        rc.UPDATE_RULE,
		            rc.DELETE_RULE
			    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS tc
			    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS kcu
		        ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
			        AND tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
			    JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS rc
			        ON rc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
		            AND rc.CONSTRAINT_SCHEMA = tc.TABLE_SCHEMA
			    WHERE tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
			      AND tc.TABLE_SCHEMA = DATABASE()
		          AND tc.TABLE_NAME = '$table'
		    ")->loadAssocList();

			if (!empty($constraints))
			{
				$this->foreign_keys[$table] = $constraints;

				// 2. Drop foreign keys before altering table
				foreach ($constraints as $fk)
				{
					$sql = sprintf(
						"ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s ON UPDATE %s;\n",
						$table,
						$fk['CONSTRAINT_NAME'],
						$fk['COLUMN_NAME'],
						$fk['REFERENCED_TABLE_NAME'],
						$fk['REFERENCED_COLUMN_NAME'],
						$fk['DELETE_RULE'],
						$fk['UPDATE_RULE']
					);

					// Append to backup file

					if(file_put_contents($this->backup_file, $sql, FILE_APPEND))
					{
						$results[] = $this->db->setQuery("ALTER TABLE `$table` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`")->execute();
						$this->output->writeln($this->colorLog("Dropped FK {$fk['CONSTRAINT_NAME']} from $table\n"));
					}
					else {
						$results[] = false;
					}
				}
			}
		}

		return !in_array(false, $results, true);
	}

	private function restoreForeignKeys(): bool
	{
		$results = [];

		foreach ($this->foreign_keys as $table => $constraints)
		{
			foreach ($constraints as $fk)
			{
				try
				{
					// Need to ensure the referenced column is indexed
					$indexCheck = $this->db->setQuery("
					    SELECT COUNT(1)
					    FROM INFORMATION_SCHEMA.STATISTICS
					    WHERE TABLE_SCHEMA = DATABASE()
					      AND TABLE_NAME = '{$fk['REFERENCED_TABLE_NAME']}'
					      AND COLUMN_NAME = '{$fk['REFERENCED_COLUMN_NAME']}'
					")->loadResult();

					if ($indexCheck == 0)
					{
						$indexSql = "ALTER TABLE `{$fk['REFERENCED_TABLE_NAME']}` ADD INDEX (`{$fk['REFERENCED_COLUMN_NAME']}`);\n";

						if(file_put_contents($this->backup_file, $indexSql, FILE_APPEND))
						{
							$results[] = $this->db->setQuery($indexSql)->execute();
						}
						else {
							$this->output->writeln($this->colorLog("Failed to write index creation SQL to backup file\n", 'e'));

							$results[] = false;
							continue;
						}
					}
					//

					$results[] = $this->db->setQuery("
		            ALTER TABLE `$table`
		            ADD CONSTRAINT `{$fk['CONSTRAINT_NAME']}`
		            FOREIGN KEY (`{$fk['COLUMN_NAME']}`)
		            REFERENCES `{$fk['REFERENCED_TABLE_NAME']}` (`{$fk['REFERENCED_COLUMN_NAME']}`)
		            ON DELETE {$fk['DELETE_RULE']}
		            ON UPDATE {$fk['UPDATE_RULE']}
		        ")->execute();

					$this->output->writeln($this->colorLog("Recreated FK {$fk['CONSTRAINT_NAME']} on $table\n"));
				}
				catch (\Exception $e)
				{
					$this->output->writeln($this->colorLog("Failed to recreate FK {$fk['CONSTRAINT_NAME']} on $table: " . $e->getMessage() . "\n", 'e'));

					$results[] = false;
				}
			}
		}

		return !in_array(false, $results, true);
	}

	private function fixCollations(): bool
	{
		$results = [];
		$this->output->writeln($this->colorLog("Fixing collation of database tables\n"));

		foreach ($this->db_tables as $table)
		{
			if ($table === 'jos_emundus_version' || $table === 'language_requirements')
			{
				continue;
			}

			// Ignore hikashop and securitycheck tables
			if (strpos($table, 'hikashop_') !== false || strpos($table, 'securitycheck') !== false)
			{
				continue;
			}

			$this->output->writeln($this->colorLog("Converting table $table to " . $this->collation . "\n"));
			if (!$this->convertToUtf8mb4($table))
			{
				$this->output->writeln($this->colorLog("Failed to convert table $table to " . $this->collation . "\n", 'e'));
				$results[] = false;
			}

			$this->output->writeln($this->colorLog("Fixing collation of table $table\n"));

			if (!$this->fixFnumLengthAndCollation($table))
			{
				$this->output->writeln($this->colorLog("Failed to fix fnum length and collation for table $table\n", 'e'));
				$results[] = false;
			}
		}

		return !in_array(false, $results, true);
	}

	private function fixFnumLengthAndCollation($table): bool
	{
		$fixed = true;

		try
		{
			$columns = $this->db->setQuery('SHOW COLUMNS FROM ' . $this->db->quoteName($table) . ' WHERE Field LIKE "fnum%"')->loadAssocList();
			if (!empty($columns))
			{
				foreach ($columns as $column)
				{
					$fixed = $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' MODIFY COLUMN ' . $this->db->quoteName($column['Field']) . ' varchar(28)')->execute();
				}
			}
		}
		catch (Exception $e)
		{
			$this->output->writeln($e->getMessage());
			$fixed = false;
		}

		return $fixed;
	}

	private function convertToUtf8mb4($table): bool
	{
		$converted = false;
		try
		{
			$query     = 'ALTER TABLE ' . $this->db->quoteName($table) . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $this->collation;
			$converted = $this->db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			$this->output->writeln($e->getMessage());
		}

		return $converted;
	}

	private function getViews($db, $db_name): array
	{
		$views = $this->db->setQuery('SHOW FULL TABLES WHERE Table_type = \'VIEW\'')->loadAssocList();

		return array_map(function ($view) use ($db_name) {
			return $view['Tables_in_' . $db_name];
		}, $views);
	}

	private function colorLog($str, $type = 'i')
	{
		$results = $str;
		switch ($type)
		{
			case 'e': //error
				$results = "\033[31m$str \033[0m";
				break;
			case 's': //success
				$results = "\033[32m$str \033[0m";
				break;
			case 'w': //warning
				$results = "\033[33m$str \033[0m";
				break;
			case 'i': //info
				$results = "\033[36m$str \033[0m";
				break;
			default:
				# code...
				break;
		}

		return $results;
	}
}