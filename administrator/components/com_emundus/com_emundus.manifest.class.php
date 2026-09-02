<?php
/**
 * eMundus: Installer Manifest Class
 *
 * @package     Joomla
 * @subpackage  eMundus
 * @author      eMundus
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Component\Emundus\Administrator\Attributes\PostflightAttribute;
use Joomla\Database\DatabaseInterface;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Reference\InternalReferenceEntity;
use Tchooz\Services\Language\DbLanguage;
use Tchooz\Traits\TraitVersion;

class Com_EmundusInstallerScript
{
	use TraitVersion;

	private DatabaseInterface $db;

	protected array|object|null $manifest_cache;

	protected string|int|null $schema_version;

	public function __construct()
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$query    = $this->db->getQuery(true);

		$query->select('extension_id, manifest_cache')
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_emundus'));
		$this->db->setQuery($query);
		$extension            = $this->db->loadObject();
		$this->manifest_cache = json_decode($extension->manifest_cache);

		$query->clear()
			->select('version_id')
			->from($this->db->quoteName('#__schemas'))
			->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($extension->extension_id));
		$this->db->setQuery($query);
		$this->schema_version = $this->db->loadResult();

		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/EmundusTableColumn.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/EmundusColumnTypeEnum.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/EmundusTableForeignKey.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/src/Attributes/PostflightAttribute.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/postflight.php');
	}

	public function preflight(string $type, object $parent): void
	{
		EmundusHelperUpdate::displayMessage('Exécution des tâches pré-installation');

		if (version_compare(PHP_VERSION, '7.4.0', '<'))
		{
			EmundusHelperUpdate::displayMessage('This extension works with PHP 7.4.0 or newer. Please contact your web hosting provider to update your PHP version.', 'error');
			exit;
		}

		$query_str = 'SHOW TABLES LIKE ' . $this->db->quote('jos_emundus_version');
		$this->db->setQuery($query_str);
		$table_exists = $this->db->loadResult();
		if (!$table_exists)
		{
			$columns             = [
				[
					'name' => 'update_date',
					'type' => 'date',
					'null' => 0,
				],
			];
			$primary_key_options = [
				'name'           => 'version',
				'type'           => 'varchar',
				'length'         => 20,
				'auto_increment' => 0,
			];

			EmundusHelperUpdate::createTable('#__emundus_version', $columns, [], '', [], $primary_key_options);
		}

		if (!$this->updateTablesBeforeFilesScripts())
		{
			EmundusHelperUpdate::displayMessage('Échec de mise à jour des tables avant les scripts de fichiers.', 'error');
			exit;
		}

		if (!$this->checkForeignKeys())
		{
			EmundusHelperUpdate::displayMessage('Échec de la vérification de l\'existence des clés étrangères', 'error');
			exit;
		}

		$this->generateAutoloadTables();
	}

	public function install(object $parent): bool
	{
		$parent->getParent()->setRedirectURL('index.php?option=com_emundus');

		return true;
	}

	public function update(object $parent): bool
	{
		$succeed = true;

		$cache_version = $this->manifest_cache->version;

		$firstrun = false;
		$regex    = '/^6\.[0-9]*/m';
		preg_match_all($regex, $cache_version, $matches, PREG_SET_ORDER, 0);
		if (!empty($matches))
		{
			$cache_version = (string) $parent->manifest->version;
			$firstrun      = true;
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/release.php';

		$releases_path = JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/releases/';

		$releases_available = scandir($releases_path);
		natcasesort($releases_available);

		if ($this->manifest_cache)
		{
			foreach ($releases_available as $release)
			{
				if (pathinfo($release, PATHINFO_EXTENSION) === 'php')
				{
					$release_with_underscores = str_replace('.php', '', $release);
					$release_version          = str_replace('_', '.', $release_with_underscores);

					if (version_compare($cache_version, $release_version, '<=') || $firstrun)
					{
						EmundusHelperUpdate::displayMessage('Installing version ' . $release_version . '...');

						require_once $releases_path . $release;
						$class             = '\scripts\Release' . $release_with_underscores . 'Installer';
						$release_installer = new $class();
						$release_installed = $release_installer->install();
						if ($release_installed['status'])
						{
							EmundusHelperUpdate::displayMessage('Version ' . $release_version . ' installed', 'success');

							$date            = Factory::getDate()->toSql();
							$existingVersion = $this->getVersion($this->db, $release_version);
							if ($existingVersion)
							{
								if (!$this->updateVersion($this->db, $release_version, $date))
								{
									EmundusHelperUpdate::displayMessage('Version ' . $release_version . ' update failed', 'error');
									$succeed = false;
								}
							}
							else
							{
								// Run only once for 2.13.0
								if ($release_version === '2.13.0')
								{
									$dbLanguage = new DbLanguage();
									if (!$dbLanguage->filesToDatabase())
									{
										EmundusHelperUpdate::displayMessage('Erreur lors de la mise à jour de la base de données des langue.', 'error');
									}
								}

								if (!$this->createVersion($this->db, $release_version, $date))
								{
									EmundusHelperUpdate::displayMessage('Version ' . $release_version . ' creation failed', 'error');
									$succeed = false;
								}
							}
						}
						else
						{
							EmundusHelperUpdate::displayMessage($release_installed['message'], 'error');
							$succeed = false;
						}
					}
				}
			}
		}

		return $succeed;
	}

	public function uninstall(object $parent): void
	{
	}

	public function postflight(string $type, object $parent): bool
	{
		$postflightTasks = new Com_EmundusPostflightTasks($this->db);

		foreach ($this->getPostflightMethods($postflightTasks) as $method => $name)
		{
			EmundusHelperUpdate::displayMessage('Exécution de la tâche post-installation : ' . $name);

			if (!$postflightTasks->$method())
			{
				EmundusHelperUpdate::displayMessage('Erreur lors de l\'exécution de la tâche post-installation : ' . $name, 'error');
			}
		}

		$cachingMethod = Factory::getApplication()->get('caching');
		if ($cachingMethod === 1)
		{
			// Update to 2
			$options['caching'] = 2;
			EmundusHelperUpdate::updateConfigurationFile($options);
		}

		$dbLanguage = new DbLanguage();
		if (!$dbLanguage->repairOrphans())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la réparation des entrées orphelines de la base de données des langues.', 'error');
		}

		if (!EmundusHelperUpdate::clearJoomlaCache())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la suppression du cache Joomla.', 'error');
		}

		EmundusHelperUpdate::generateCampaignsAlias();

		return true;
	}

	/**
	 * Discover the postflight tasks registered on the given holder via the
	 * #[PostflightAttribute] PHP attribute.
	 *
	 * @param   Com_EmundusPostflightTasks  $postflightTasks
	 *
	 * @return  array<string, string>  Method name => human-readable task label.
	 */
	private function getPostflightMethods(Com_EmundusPostflightTasks $postflightTasks): array
	{
		$reflection = new ReflectionClass($postflightTasks);
		$results    = [];

		foreach ($reflection->getMethods() as $method)
		{
			$attributes = $method->getAttributes(PostflightAttribute::class);

			if (!empty($attributes))
			{
				/**
				 * @var PostflightAttribute $attributeInstance
				 */
				$attributeInstance           = $attributes[0]->newInstance();
				$results[$method->getName()] = $attributeInstance->name;
			}
		}

		return $results;
	}

	/**
	 * Update tables before files scripts to avoid errors in case of missing columns
	 *
	 * E.g. : jos_emundus_setup_actions table is used to create new actions, but since 2.17, a new columns is used in repositories.
	 * So, we add the column before scripts to ensure installation still works
	 *
	 * @return bool
	 */
	private function updateTablesBeforeFilesScripts(): bool
	{
		$updates = [];

		$db = Factory::getContainer()->get('DatabaseDriver');

		$table_existing = $db->setQuery('SHOW TABLE STATUS WHERE Name LIKE ' . $db->quote('jos_emundus_setup_step_types'))->loadResult();
		if (!empty($table_existing))
		{
			$result    = EmundusHelperUpdate::addColumn('#__emundus_setup_step_types', 'code', 'varchar', 50);
			$updates[] = $result['status'];
		}

		// since 2.17.0
		$result    = EmundusHelperUpdate::addColumn('jos_emundus_setup_actions', 'type', 'VARCHAR', 20, 0, 'file');
		$updates[] = $result['status'];
		if (!$result['status'])
		{
			EmundusHelperUpdate::displayMessage($result['message'], 'error');
		}

		$updates[] = \EmundusHelperUpdate::makeFromEntity(AddonEntity::class);

		// since 2.19.0 : the postflight short reference task reads these. Platforms whose manifest cache
		// jumped past 2.19.0 never ran the release, so the column and the table are missing.
		$updates[] = \EmundusHelperUpdate::makeFromEntity(ApplicationFileEntity::class);
		$updates[] = \EmundusHelperUpdate::makeFromEntity(InternalReferenceEntity::class);

		$this->dropLegacyContentXreference();

		return !in_array(false, $updates);
	}

	/**
	 * Joomla 3 leftover dropped by Joomla 4 : on databases where the column survived the migration,
	 * it is NOT NULL without default and no Joomla 5 code fills it, so every article INSERT fails.
	 */
	private function dropLegacyContentXreference(): void
	{
		$this->db->setQuery(
			'SELECT ' . $this->db->quoteName('COLUMN_NAME')
			. ' FROM ' . $this->db->quoteName('information_schema.COLUMNS')
			. ' WHERE ' . $this->db->quoteName('TABLE_SCHEMA') . ' = DATABASE()'
			. ' AND ' . $this->db->quoteName('TABLE_NAME') . ' = ' . $this->db->quote('jos_content')
			. ' AND ' . $this->db->quoteName('COLUMN_NAME') . ' = ' . $this->db->quote('xreference')
		);

		if (empty($this->db->loadResult()))
		{
			return;
		}

		try
		{
			$this->db->setQuery('ALTER TABLE ' . $this->db->quoteName('jos_content') . ' DROP COLUMN ' . $this->db->quoteName('xreference'));
			$this->db->execute();

			EmundusHelperUpdate::displayMessage('Colonne obsolète ' . $table . '.xreference supprimée.', 'success');
		}
		catch (Exception $e)
		{
			EmundusHelperUpdate::displayMessage('Suppression de la colonne obsolète ' . $table . '.xreference impossible : ' . $e->getMessage(), 'warning');
		}
	}

	/**
	 * Ensure that the foreign keys declared in .docker/installation/vanilla/foreign_keys/foreign_keys.xml
	 * exist on the schema. The XML is the source of truth: each <table name="..."> holds <row> entries
	 * with constraint_name, column_name, referenced_table_name, referenced_column_name, update_rule, delete_rule.
	 *
	 * A FK is considered present when a row in information_schema.KEY_COLUMN_USAGE matches the same
	 * (source_table, source_column, referenced_table, referenced_column) tuple — the constraint name is
	 * ignored on purpose so an existing FK under a different name is still detected.
	 *
	 * @return bool
	 */
	private function checkForeignKeys(): bool
	{
		$xml_path = JPATH_ROOT . '/.docker/installation/vanilla/foreign_keys/foreign_keys.xml';

		if (!is_file($xml_path) || !is_readable($xml_path))
		{
			EmundusHelperUpdate::displayMessage('Fichier de description des clés étrangères introuvable : ' . $xml_path, 'error');

			return false;
		}

		$xml = simplexml_load_file($xml_path);

		if ($xml === false)
		{
			EmundusHelperUpdate::displayMessage('Impossible de parser le fichier XML des clés étrangères : ' . $xml_path, 'error');

			return false;
		}

		$success = true;

		$original_sql_mode = (string) $this->db->setQuery('SELECT @@SESSION.sql_mode')->loadResult();

		$this->db->setQuery('SET sql_mode = ""')->execute();
		$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();

		try
		{
			foreach ($xml->table as $table_node)
			{
				$source_table = (string) $table_node['name'];

				if ($source_table === '')
				{
					EmundusHelperUpdate::displayMessage('Nœud <table> sans attribut "name", ignoré.', 'warning');
					continue;
				}

				$source_table_resolved = $this->db->replacePrefix($source_table);

				$table_exists_query = $this->db->getQuery(true)
					->select('COUNT(*)')
					->from($this->db->quoteName('information_schema.TABLES'))
					->where($this->db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
					->where($this->db->quoteName('TABLE_NAME') . ' = ' . $this->db->quote($source_table_resolved));

				$this->db->setQuery($table_exists_query);
				$table_exists = (int) $this->db->loadResult();

				if ($table_exists === 0)
				{
					EmundusHelperUpdate::displayMessage('Table ' . $source_table . ' introuvable, clés étrangères associées ignorées.', 'warning');
					continue;
				}

				foreach ($table_node->row as $row_node)
				{
					$name            = (string) $row_node['constraint_name'];
					$column_name     = (string) $row_node['column_name'];
					$target_table    = (string) $row_node['referenced_table_name'];
					$target_column   = (string) $row_node['referenced_column_name'];
					$update_rule_raw = (string) $row_node['update_rule'];
					$delete_rule_raw = (string) $row_node['delete_rule'];

					if ($name === '' || $column_name === '' || $target_table === '' || $target_column === '')
					{
						EmundusHelperUpdate::displayMessage('Définition de clé étrangère incomplète sur ' . $source_table . ', ignorée.', 'warning');
						continue;
					}

					$on_update = EmundusTableForeignKeyOnEnum::tryFrom($update_rule_raw) ?? EmundusTableForeignKeyOnEnum::NO_ACTION;
					$on_delete = EmundusTableForeignKeyOnEnum::tryFrom($delete_rule_raw) ?? EmundusTableForeignKeyOnEnum::NO_ACTION;

					$foreign_key = new EmundusTableForeignKey(
						$name,
						$column_name,
						$target_table,
						$target_column,
						$on_update,
						$on_delete
					);

					try
					{
						$target_table_resolved = $this->db->replacePrefix($target_table);

						$check_query = $this->db->getQuery(true)
							->select('COUNT(*)')
							->from($this->db->quoteName('information_schema.KEY_COLUMN_USAGE'))
							->where($this->db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
							->where($this->db->quoteName('TABLE_NAME') . ' = ' . $this->db->quote($source_table_resolved))
							->where($this->db->quoteName('COLUMN_NAME') . ' = ' . $this->db->quote($foreign_key->getFromColumn()))
							->where($this->db->quoteName('REFERENCED_TABLE_NAME') . ' = ' . $this->db->quote($target_table_resolved))
							->where($this->db->quoteName('REFERENCED_COLUMN_NAME') . ' = ' . $this->db->quote($foreign_key->getReferencedColumn()));

						$this->db->setQuery($check_query);
						$exists = (int) $this->db->loadResult();

						if ($exists > 0)
						{
							continue;
						}

						$constraint_query = $this->db->getQuery(true)
							->select('COUNT(*)')
							->from($this->db->quoteName('information_schema.TABLE_CONSTRAINTS'))
							->where($this->db->quoteName('CONSTRAINT_SCHEMA') . ' = DATABASE()')
							->where($this->db->quoteName('CONSTRAINT_TYPE') . ' = ' . $this->db->quote('FOREIGN KEY'))
							->where($this->db->quoteName('CONSTRAINT_NAME') . ' = ' . $this->db->quote($foreign_key->getName()));

						$this->db->setQuery($constraint_query);
						$name_taken = (int) $this->db->loadResult() > 0;

						if (!$name_taken)
						{
							$index_query = $this->db->getQuery(true)
								->select('COUNT(*)')
								->from($this->db->quoteName('information_schema.STATISTICS'))
								->where($this->db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
								->where($this->db->quoteName('TABLE_NAME') . ' = ' . $this->db->quote($source_table_resolved))
								->where($this->db->quoteName('INDEX_NAME') . ' = ' . $this->db->quote($foreign_key->getName()));

							$this->db->setQuery($index_query);
							$name_taken = (int) $this->db->loadResult() > 0;
						}

						$alter = 'ALTER TABLE ' . $this->db->quoteName($source_table) . ' ADD ';

						if (!$name_taken)
						{
							$alter .= 'CONSTRAINT ' . $this->db->quoteName($foreign_key->getName()) . ' ';
						}

						$alter .= 'FOREIGN KEY (' . $this->db->quoteName($foreign_key->getFromColumn()) . ')'
							. ' REFERENCES ' . $this->db->quoteName($foreign_key->getReferencedTable())
							. ' (' . $this->db->quoteName($foreign_key->getReferencedColumn()) . ')'
							. ' ON UPDATE ' . $foreign_key->getOnUpdate()->value
							. ' ON DELETE ' . $foreign_key->getOnDelete()->value;

						$this->db->setQuery($alter);
						$this->db->execute();

						if ($name_taken)
						{
							EmundusHelperUpdate::displayMessage('Clé étrangère ajoutée sur ' . $source_table . ' (' . $foreign_key->getFromColumn() . ' vers ' . $target_table . '.' . $foreign_key->getReferencedColumn() . '), nommée par MySQL car ' . $foreign_key->getName() . ' est déjà pris sur cette base.', 'warning');
						}
						else
						{
							EmundusHelperUpdate::displayMessage('Clé étrangère ' . $foreign_key->getName() . ' ajoutée sur ' . $source_table . '.', 'success');
						}
					}
					catch (Exception $e)
					{
						EmundusHelperUpdate::displayMessage('Erreur lors de la vérification de la clé étrangère ' . $foreign_key->getName() . ' : ' . $e->getMessage(), 'error');
						$success = false;
					}
				}
			}
		}
		finally
		{
			$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
			$this->db->setQuery('SET sql_mode = ' . $this->db->quote($original_sql_mode))->execute();
		}

		return $success;
	}

	private function generateAutoloadTables(): void
	{
		// Regenerate autoload_tables file located in JPATH_CACHE. Check only files in components/com_emundus/classes/Repositories directory
		$repositoryPath = JPATH_SITE . '/components/com_emundus/classes/Repositories';
		$outputFile     = JPATH_CACHE . '/autoload_tables.php';

		$files    = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repositoryPath));
		$phpFiles = new RegexIterator($files, '/\.php$/');

		$map = [];

		foreach ($phpFiles as $file)
		{
			$contents = file_get_contents($file->getPathname());

			// Search namespace declaration
			preg_match('/namespace\s+([^;]+);/', $contents, $namespaceMatches);
			$namespace = $namespaceMatches[1] ?? '';

			// Search class declaration
			preg_match('/class\s+([^\s{]+)/', $contents, $classMatches);
			$class = $classMatches[1] ?? '';

			if (!empty($namespace) && !empty($class))
			{
				$fqcn = $namespace . '\\' . $class;
				try
				{
					require_once $file->getPathname();
					if (!class_exists($fqcn, false))
					{
						continue;
					}

					$ref   = new ReflectionClass($fqcn);
					$attrs = $ref->getAttributes('Tchooz\Attributes\TableAttribute');
					if (count($attrs) > 0)
					{
						$instance = $attrs[0]->newInstance();

						$map[$fqcn] = [
							'table'   => $instance->table,
							'alias'   => $instance->alias,
							'columns' => $instance->columns,
						];
					}

				}
				catch (Throwable $e)
				{
					// Ignore classes that fail to load during scanning
				}
			}
		}

		$export = var_export($map, true);
		$export = preg_replace(['/\barray\s*\(/', '/\)(,)/'], ['[', ']$1'], $export);
		$export = preg_replace('/\)(;)?$/', ']$1', $export);
		$php    = "<?php\ndefined('_JEXEC') or die;\nreturn $export;\n";
		$tmp    = 'autoload_tables.php' . '.tmp';
		file_put_contents($tmp, $php);
		rename($tmp, $outputFile);
	}
}
