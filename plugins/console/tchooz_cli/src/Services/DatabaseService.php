<?php
namespace Emundus\Plugin\Console\Tchooz\Services;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\ParameterType;
use Symfony\Component\Console\Output\OutputInterface;
use Joomla\Database\DatabaseInterface;

class DatabaseService
{
	private DatabaseInterface $db;

	private string $db_name;

	public function __construct(
		string $configuration_path
	)
	{
		if (is_file($configuration_path)) {
			$copied = copy($configuration_path, JPATH_ROOT . '/configuration_old.php');
			if ($copied)
			{
				$source_config = $this->getConfigFromFile(JPATH_ROOT . '/configuration_old.php', 'PHP', 'Old');

				if (!empty($source_config) && !empty($source_config->db))
				{
					$this->db_name = $source_config->db;
					$options             = array();
					$options['driver']   = isset($source_config->dbtype) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $source_config->dbtype) : 'mysqli';
					$options['database'] = $source_config->db;
					$options['user']     = $source_config->user;
					$options['password'] = $source_config->password;
					$options['select']   = true;
					$options['monitor']  = null;
					$options['host']     = $source_config->host;

					$db_factory = new DatabaseFactory();
					$this->db = $db_factory->getDriver('mysqli', $options);
				}
			} else {
				throw new \RuntimeException('Could not copy the configuration file!');
			}
		} else {
			throw new \RuntimeException('The configuration file is missing!');
		}
	}

	private function getConfigFromFile($file, $type = 'PHP', $namespace = '')
	{
		$file_content = file_get_contents($file);
		$file_content = str_replace('JConfig', 'JConfigOld', $file_content);
		file_put_contents($file, $file_content);
		if (is_file($file)) {
			include_once $file;
		}

		// Sanitize the namespace.
		$namespace = ucfirst((string) preg_replace('/[^A-Z_]/i', '', $namespace));

		// Build the config name.
		$name = 'JConfig' . $namespace;

		$config = null;
		// Handle the PHP configuration type.
		if ($type === 'PHP' && class_exists($name)) {
			// Create the JConfig object
			$config = new $name();
		}

		return $config;
	}

	public function getDatabase(): DatabaseInterface
	{
		return $this->db;
	}

	public function getDbName(): string
	{
		return $this->db_name;
	}

	public function getSchemaVersion(): string
	{
		$query = $this->db->getQuery(true);

		$query->select('extension_id')
			->from($this->db->quoteName('jos_extensions'))
			->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_emundus'));
		$this->db->setQuery($query);
		$extension_id = $this->db->loadResult();

		$query->clear()
			->select('version_id')
			->from($this->db->quoteName('jos_schemas'))
			->where($this->db->quoteName('extension_id') . ' = :extensionId')
			->bind(':extensionId', $extension_id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		return $this->db->loadResult();
	}

	public function getDatabaseEngine()
	{
		$query = $this->db->getQuery(true);

		$query->select('ENGINE')
			->from('information_schema.ENGINES')
			->where('SUPPORT' . ' = ' . $this->db->quote('DEFAULT'));
		$this->db->setQuery($query);
		return $this->db->loadResult();
	}

	public function getDefaultCharsetCollation()
	{
		$query = $this->db->getQuery(true);

		$query->select('DEFAULT_CHARACTER_SET_NAME as charset, DEFAULT_COLLATION_NAME as collation')
			->from('information_schema.SCHEMATA')
			->where('SCHEMA_NAME' . ' = ' . $this->db->quote($this->db_name));
		$this->db->setQuery($query);
		return $this->db->loadObject();
	}
}