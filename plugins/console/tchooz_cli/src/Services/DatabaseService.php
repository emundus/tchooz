<?php

namespace Emundus\Plugin\Console\Tchooz\Services;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

class DatabaseService
{
	private DatabaseInterface $db;

	private string $dbName;

	private QueryInterface $query;

	private $host;

	public function __construct(
		\JConfig|\JConfigOld $configuration = null,
		?DatabaseInterface   $database = null,
		?bool                $disableConstraints = false
	)
	{
		if (!empty($configuration) && !empty($configuration->db))
		{
			$this->host = $configuration->host;
			$this->dbName        = $configuration->db;
			$options             = array();
			$options['driver']   = isset($configuration->dbtype) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $configuration->dbtype) : 'mysqli';
			$options['database'] = $configuration->db;
			$options['user']     = $configuration->user;
			$options['password'] = $configuration->password;
			$options['select']   = true;
			$options['monitor']  = null;
			$options['host']     = $configuration->host;

			$db_factory = new DatabaseFactory();
			$this->db   = $db_factory->getDriver('mysqli', $options);
		}
		else
		{
			if (!empty($database))
			{
				$this->db     = $database;
				$this->dbName = Factory::getApplication()->get('db');
			}
			else
			{
				throw new \RuntimeException('The configuration file is missing!');
			}
		}

		if ($disableConstraints)
		{
			// Do not check foreign keys and unique keys during migration
			$this->db->setQuery('SET sql_mode = ""')->execute();
			$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();
			$this->db->setQuery('SET UNIQUE_CHECKS = 0')->execute();
		}

		$this->query = $this->db->createQuery();
	}

	public function getDatabase(): DatabaseInterface
	{
		return $this->db;
	}

	public function getDbName(): string
	{
		return $this->dbName;
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getSchemaVersion(): string
	{
		$this->query->clear()
			->select('extension_id')
			->from($this->db->quoteName('jos_extensions'))
			->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_emundus'));
		$this->db->setQuery($this->query);
		$extension_id = $this->db->loadResult();

		$this->query->clear()
			->select('version_id')
			->from($this->db->quoteName('jos_schemas'))
			->where($this->db->quoteName('extension_id') . ' = :extensionId')
			->bind(':extensionId', $extension_id, ParameterType::INTEGER);
		$this->db->setQuery($this->query);

		return $this->db->loadResult();
	}

	public function getDatabaseEngine(): string
	{
		$this->query->clear()
			->select('ENGINE')
			->from('information_schema.ENGINES')
			->where('SUPPORT' . ' = ' . $this->db->quote('DEFAULT'));
		$this->db->setQuery($this->query);

		return $this->db->loadResult();
	}

	public function getDefaultCharsetCollation(): object
	{
		$this->query->clear()
			->select('DEFAULT_CHARACTER_SET_NAME as charset, DEFAULT_COLLATION_NAME as collation')
			->from('information_schema.SCHEMATA')
			->where('SCHEMA_NAME' . ' = ' . $this->db->quote($this->dbName));
		$this->db->setQuery($this->query);

		return $this->db->loadObject();
	}

	public function startTransaction(): void
	{
		$this->db->transactionStart();
		$this->db->setQuery('SET AUTOCOMMIT = 0')->execute();
	}

	public function rollbackTransaction(): void
	{
		$this->db->transactionRollback();
		$this->db->setQuery('SET AUTOCOMMIT = 1')->execute();
	}

	public function commitTransaction(): void
	{
		$this->db->transactionCommit();
		$this->db->setQuery('SET AUTOCOMMIT = 1')->execute();
	}

	public function disableForeignKeyChecks(): void
	{
		$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();
	}

	public function enableForeignKeyChecks(): void
	{
		$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
	}

	public function getViews(): array
	{
		$dbName = $this->getDbName();
		$views  = $this->db->setQuery('SHOW FULL TABLES WHERE Table_type = \'VIEW\'')->loadAssocList();

		return array_map(function ($view) use ($dbName) {
			return $view['Tables_in_' . $dbName];
		}, $views);
	}

	public function getViewCreate($view): array
	{
		return $this->db->setQuery('SHOW CREATE VIEW ' . $this->db->quoteName($view))->loadAssoc();
	}

	public function getTables(): array
	{
		return $this->db->getTableList();
	}

	public function getTableCreate($table): array
	{
		return $this->db->getTableCreate($table);
	}

	public function createView(array $viewDump, string $viewName, string $oldDbName = null): bool
	{
		$re       = '/(ALGORITHM.*DEFINER)/m';
		$new_dump = preg_replace($re, '', $viewDump['Create View']);
		$new_dump = str_replace($oldDbName, $this->dbName, $new_dump);

		$this->db->setQuery('DROP VIEW IF EXISTS ' . $this->db->quoteName($viewName))->execute();

		return $this->db->setQuery($new_dump)->execute();
	}

	public function truncateTable($table): bool
	{
		return $this->db->setQuery('TRUNCATE TABLE ' . $this->db->quoteName($table))->execute();
	}

	public function dropTable(string $table): bool
	{
		$this->db->setQuery('DROP TABLE IF EXISTS ' . $this->db->quoteName($table));

		return $this->db->execute();
	}

	public function fixFnumLengthAndCollation(string $table): bool
	{
		$fixed = true;

		$columns = $this->db->setQuery('SHOW COLUMNS FROM ' . $this->db->quoteName($table) . ' WHERE Field LIKE "fnum%"')->loadAssocList();
		if (!empty($columns))
		{
			foreach ($columns as $column)
			{
				try
				{
					$fixed = $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' MODIFY COLUMN ' . $this->db->quoteName($column['Field']) . ' varchar(28)')->execute();
				}
				catch (\Exception $e)
				{
					Log::add('Error while fixing fnum length and collation in table ' . $table . ': ' . $e->getMessage(), Log::ERROR, 'tchooz_cli');
					$fixed = false;
				}
			}
		}

		return $fixed;
	}

	public function convertToUtf8mb4(string $table): bool
	{
		$sql_engine = $this->db->setQuery("SHOW VARIABLES LIKE 'version_comment'")->loadAssoc();
		if (empty($sql_engine))
		{
			$sql_engine = [
				'Value' => 'MySQL'
			];
		}

		$sql_engine = $sql_engine['Value'];
		$collation  = 'utf8mb4_0900_ai_ci';
		if (strpos($sql_engine, 'MySQL') === false)
		{
			$collation = 'utf8mb4_unicode_ci';
		}

		try
		{
			$query = 'ALTER TABLE ' . $this->db->quoteName($table) . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $collation;

			return $this->db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			Log::add('Error while converting table ' . $table . ' to utf8mb4: ' . $e->getMessage(), Log::ERROR, 'tchooz_cli');
			return false;
		}
	}

	public function getPrimaryKey(string $table): string
	{
		$this->query->clear()
			->select('COLUMN_NAME')
			->from('information_schema.KEY_COLUMN_USAGE')
			->where('TABLE_NAME' . ' = ' . $this->db->quote($table))
			->where('CONSTRAINT_NAME' . ' = ' . $this->db->quote('PRIMARY'));
		$this->db->setQuery($this->query);

		$primaryKey = $this->db->loadResult();
		// Some old tables does not have primary key :/
		if(empty($primaryKey)) {
			$primaryKey = 'id';
		}

		return $primaryKey;
	}

	public function getDatasCount(string $table): int
	{
		$primaryKey = $this->getPrimaryKey($table);

		$this->query->clear()
			->select('count('.$this->db->quoteName($primaryKey).')')
			->from($this->db->quoteName($table));
		$this->db->setQuery($this->query);

		return $this->db->loadResult();
	}

	public function getDatas(string $table, ?int $limit = 1000, ?int $offset = 0): array
	{
		$this->query->clear()
			->select('*')
			->from($this->db->quoteName($table))
			->setLimit($limit, $offset);
		$this->db->setQuery($this->query);
		return $this->db->loadAssocList();
	}

	public function convertToInnodb(string $table): bool
	{
		return $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' ENGINE=INNODB')->execute();
	}

	public function insertDatas(array $datas, string $table): bool
	{
		$inserted = true;

		try
		{
			if (!empty($datas))
			{
				$columns = array_keys($datas[0]);
				$this->query->clear()
					->insert($this->db->quoteName($table))
					->columns($this->db->quoteName($columns));

				foreach ($datas as $data)
				{
					foreach ($data as $key => $value)
					{
						if ($value === null)
						{
							$data[$key] = 'NULL';
						}
						else
						{
							$data[$key] = $this->db->quote($value);
						}
					}

					// Convert this to a prepared statement
					$this->query->values(implode(',', $data));
				}

				$this->db->setQuery($this->query);

				if (!$this->db->execute())
				{
					$inserted = false;
				}
			}
		}
		catch (\Exception $e)
		{
			$inserted = false;
		}

		return $inserted;
	}

	public function mergeColumns(array $source_columns, string $table): bool
	{
		$merged = true;

		$columns = $this->db->setQuery('SHOW COLUMNS FROM ' . $this->db->quoteName($table))->loadAssocList('Field');

		$diffs = array_diff_key($source_columns, $columns);
		if (!empty($diffs))
		{
			foreach ($diffs as $diff)
			{
				if ($diff['Null'] == 'NO')
				{
					$diff['Null'] = 'NOT NULL';
				}
				else
				{
					$diff['Null'] = 'NULL';
				}
				$query  = 'ALTER TABLE ' . $this->db->quoteName($table) . ' ADD COLUMN ' . $this->db->quoteName($diff['Field']) . ' ' . $diff['Type'] . ' ' . $diff['Null'];
				$merged = $this->db->setQuery($query)->execute();
			}

		}

		return $merged;
	}

	public function fixExtensionVersion(string $version, string $extension = 'com_emundus'): bool
	{
		$this->query->clear()
			->select('extension_id, manifest_cache')
			->from('jos_extensions')
			->where('element = ' . $this->query->quote('com_emundus'))
			->where('type = ' . $this->query->quote('component'));
		$this->db->setQuery($this->query);
		$emundusExtension = $this->db->loadObject();

		$manifestCache = json_decode($emundusExtension->manifest_cache, true);
		$manifestCache['version'] = '2.0.0';
		$emundusExtension->manifest_cache = json_encode($manifestCache);

		return $this->db->updateObject('jos_extensions', $emundusExtension, 'extension_id');
	}
}