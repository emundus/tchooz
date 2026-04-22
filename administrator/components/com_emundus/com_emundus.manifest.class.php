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
use Tchooz\Services\Language\DbLanguage;
use Tchooz\Services\Reference\InternalReferenceService;
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
		if(!$table_exists)
		{
			$columns = [
				[
					'name'   => 'update_date',
					'type'   => 'date',
					'null'   => 0,
				],
			];
			$primary_key_options = [
				'name' => 'version',
				'type' => 'varchar',
				'length' => 20,
				'auto_increment' => 0,
			];

			EmundusHelperUpdate::createTable('#__emundus_version', $columns,[], '', [], $primary_key_options);
		}

		if (!$this->updateTablesBeforeFilesScripts())
		{
			EmundusHelperUpdate::displayMessage('Échec de mise à jour des tables avant les scripts de fichiers.', 'error');
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

							$date = Factory::getDate()->toSql();
							$existingVersion = $this->getVersion($this->db, $release_version);
							if($existingVersion)
							{
								if(!$this->updateVersion($this->db, $release_version, $date))
								{
									EmundusHelperUpdate::displayMessage('Version ' . $release_version . ' update failed', 'error');
									$succeed = false;
								}
							}
							else
							{
								// Run only once for 2.13.0
								if($release_version === '2.13.0')
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
	{}

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
		if($cachingMethod === 1)
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
			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_step_types', 'code', 'varchar', 50);
			$updates[] = $result['status'];
		}

		// since 2.17.0
		$result = EmundusHelperUpdate::addColumn('jos_emundus_setup_actions', 'type', 'VARCHAR', 20, 0, 'file');
		$updates[] = $result['status'];
		if (!$result['status'])
		{
			EmundusHelperUpdate::displayMessage($result['message'], 'error');
		}

		$updates[] = \EmundusHelperUpdate::makeFromEntity(AddonEntity::class);

		return !in_array(false, $updates);
	}

	private function generateAutoloadTables(): void
	{
		// Regenerate autoload_tables file located in JPATH_CACHE. Check only files in components/com_emundus/classes/Repositories directory
		$repositoryPath = JPATH_SITE . '/components/com_emundus/classes/Repositories';
		$outputFile = JPATH_CACHE . '/autoload_tables.php';

		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repositoryPath));
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
				try {
					require_once $file->getPathname();
					if (!class_exists($fqcn, false)) {
						continue;
					}

					$ref = new ReflectionClass($fqcn);
					$attrs = $ref->getAttributes('Tchooz\Attributes\TableAttribute');
					if (count($attrs) > 0) {
						$instance = $attrs[0]->newInstance();

						$map[$fqcn] = [
							'table' => $instance->table,
							'alias' => $instance->alias,
							'columns' => $instance->columns,
						];
					}

				} catch (Throwable $e) {
					// Ignore classes that fail to load during scanning
				}
			}
		}

		$export = var_export($map, true);
		$export = preg_replace(['/\barray\s*\(/', '/\)(,)/'], ['[', ']$1'], $export);
		$export = preg_replace('/\)(;)?$/', ']$1', $export);
		$php = "<?php\ndefined('_JEXEC') or die;\nreturn $export;\n";
		$tmp ='autoload_tables.php' . '.tmp';
		file_put_contents($tmp, $php);
		rename($tmp, $outputFile);
	}
}
