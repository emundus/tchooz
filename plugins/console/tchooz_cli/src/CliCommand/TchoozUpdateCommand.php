<?php
namespace Emundus\Plugin\Console\Tchooz\CliCommand;

defined('_JEXEC') or die;

use Joomla\CMS\Installer\Installer;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TchoozUpdateCommand extends AbstractCommand
{
    use DatabaseAwareTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'tchooz:update';

    /**
     * SymfonyStyle Object
     * @var   object
     * @since 4.0.0
     */
    private $ioStyle;

    /**
     * Stores the Input Object
     * @var   object
     * @since 4.0.0
     */
    private $cliInput;

	private $components;

	private $schema_version;

	private $firstrun;

    /**
     * Command constructor.
     *
     * @param   DatabaseInterface  $db  The database
     *
     * @since   4.2.0
     */
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct();

        $this->setDatabase($db);
    }

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
        $this->ioStyle->title('Update Tchooz');

	    $availableComponents = array('com_emundus', 'com_fabrik', 'com_hikashop', 'com_hikamarket', 'com_falang', 'com_dpcalendar', 'com_dropfiles', 'com_loginguard', 'com_admintools',
		    'com_jumi', 'com_gantry5', 'com_securitycheckpro');
	    $choice = new ChoiceQuestion(
		    'Please select components to update (separate multiple profiles with a comma)',
		    $availableComponents
	    );
	    $choice->setMultiselect(true);

	    $answer = (array) $this->ioStyle->askQuestion($choice);

	    foreach ($answer as $component) {
		    $this->components[] = $component;
	    }

	    $this->components = $this->getComponentsElement('extensions', $this->components);

		$this->updateComponents();

        $this->ioStyle->success("Tchooz updated successfully");

        return Command::SUCCESS;
    }

	private function getComponentsElement($table, $comp)
	{
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select('extension_id, name, package_id, type, element, manifest_cache')
			->where($db->quoteName('element') . " IN (" . implode(',', $db->quote($comp)) . ') AND state != -1 AND extension_id != 10000 AND client_id = 1')
			->from('#__' . $table);
		$db->setQuery($query);

		return $db->loadAssocList('element');
	}

	private function updateComponents()
	{
		$installer = Installer::getInstance();
		$success = true;
		$failure_msg = '';
		$count_exec = 0;

		# Case where element isn't defined in script parameters -> update all
		$elements = empty($elements) ? array_keys($this->components) : $elements;

		if (empty($elements)) {
			$this->ioStyle->warning("Nothing component available for update");

			return false;
		}

		$count_exec += count($elements);

		# Process update for each component listed
		foreach ($elements as $element) {
			# Get component row & load manifest cache
			$elementArr = $this->components[$element];
			$manifest_cache = json_decode($elementArr['manifest_cache'], true);

			# Check xml path
			if ($manifest_cache['filename']) {
				$xml_file = $manifest_cache['filename'] . '.xml';
			} else {
				$xml_file = preg_split("/[_]+/", $elementArr["element"], 2)[1] . '.xml';
			}
			$path = is_dir(JPATH_ADMINISTRATOR . '/components/' . $elementArr['element'] . '/') ? JPATH_ADMINISTRATOR . '/components/' . $elementArr['element'] . '/' : JPATH_ROOT . '/components/' . $elementArr['element'] . '/';

			# Load xml or fail
			$xml_path = $path . $xml_file;
			if ($element == 'com_extplorer' || $element == 'com_dropfiles') {
				if (empty($manifest_cache['version'])) {
					$manifest_cache['version'] = $this->refreshManifestCache($elementArr['extension_id'], $elementArr['element']);
				}
				if (!file_exists($xml_path)) {
					if ($element == 'com_extplorer') {
						$short_element = str_replace('com_', '', $element);
						$file = JPATH_ADMINISTRATOR . '/components/' . $element . '/' . $short_element . '.j30.xml';
					} elseif ($element == 'com_dropfiles') {
						$file = JPATH_ADMINISTRATOR . '/components/' . $element . '/' . $element . '.xml';
						$short_element = str_replace('com_', '', $element);
					}
					if (file_exists($file)) {
						$rename_file = JPATH_ADMINISTRATOR . '/components/' . $element . '/' . $short_element . '.xml';
						rename($file, $rename_file);
						$xml_path = $rename_file;
					} else {
						$xml_path = JPATH_ADMINISTRATOR . '/components/' . $element . '/' . $short_element . '.xml';
					}
				}
			}
			if (file_exists($xml_path)) {
				$this->manifest_xml = simplexml_load_file($xml_path);
				$this->ioStyle->text("*--------------------*\n");

				$regex = '/^6\.[0-9]*/m';
				preg_match_all($regex, $manifest_cache['version'], $matches, PREG_SET_ORDER, 0);

				# Check if this is the first run for emundus component
				if ($elementArr['element'] == "com_emundus" and (!empty($matches) || $manifest_cache['version'] <= "2.0.0")) {
					$this->firstrun = true;
					$this->ioStyle->text("** Script first run **");

					# Set schema version and align manifest cache version
					$this->schema_version = '2.0.0';
					$manifest_cache['version'] = '2.0.0';
					$this->updateSchema($elementArr['extension_id'], null, null, $this->schema_version);
				}

				# Update loop
				if ($this->firstrun or version_compare($manifest_cache['version'], $this->manifest_xml->version, '<=')) {
					$this->ioStyle->text("UPDATE " . $manifest_cache['name'] . ' (' . $manifest_cache['version'] . ' to ' . $this->manifest_xml->version . ')');

					# Require scriptfile
					if ($this->manifest_xml->scriptfile) {
						$scriptfile = JPATH_ADMINISTRATOR . '/components/' . $elementArr['element'] . '/' . $this->manifest_xml->scriptfile;
						try {
							if (file_exists($scriptfile) && is_readable($scriptfile)) {
								require_once $scriptfile;
							} else {
								$this->ioStyle->error($elementArr['element'] . " scriptfile doesn't exists or is not readable.");
							}
						} catch (\Exception $e) {
							$this->ioStyle->error("-> " . $e->getMessage());
							continue;
						}
					} else {
						unset($scriptfile);
					}

					if ($this->firstrun) {
						$this->firstrun = false;
					}

					# Step 2 : Check custom updates
					# Setup adapter
					$installer->setPath('source', $path);

					if (!$adapter = $installer->setupInstall('update', true)) {
						$this->ioStyle->error("-> Couldn't detect manifest file");
						return false;
					}

					$scriptClass = $elementArr['element'] . "InstallerScript";
					if (class_exists($scriptClass)) {
						# Create a new instance
						$script = new $scriptClass();

						try {
							switch ($elementArr["element"]) {
								case 'com_securitycheckpro':
									ob_start();
									try {
										$installer->setPath('source', JPATH_ROOT);

										if (method_exists($scriptClass, 'preflight')) {
											$script->preflight('update', $adapter);
										}
										if (method_exists($scriptClass, 'update')) {
											$script->update($adapter);
										}
										if (method_exists($scriptClass, 'postflight')) {
											$script->postflight('update', $adapter);
										}
									} catch (\RuntimeException $e) {
										// Install failed, roll back changes
										$this->ioStyle->error($e);
										$installer->abort($e->getMessage());
										return false;
									}
									ob_end_clean();
									break;
								case 'com_emundus':
									if (method_exists($scriptClass, 'preflight')) {
										$script->preflight('update', $adapter);
									}
									if (method_exists($scriptClass, 'update')) {
										$update = $script->update($adapter);

										if (!$update) {
											$success = false;
										}
									}
									if (method_exists($scriptClass, 'postflight')) {
										$script->postflight('update', $adapter);
									}

									break;
								default :
									ob_start();
									if (method_exists($scriptClass, 'preflight')) {
										$script->preflight('update', $adapter);
									}
									if (method_exists($scriptClass, 'update')) {
										$script->update($adapter);
									}
									if (method_exists($scriptClass, 'postflight')) {
										$script->postflight('update', $adapter);
									}
									ob_end_clean();
									break;
							}

						} catch (\Throwable $e) {
							$success = false;
						}
					} else {
						$this->ioStyle->error("-> Scriptfile doesn't exists");
					}
				} else {
					$this->ioStyle->text($elementArr['element'] . " component already up-to-date\n", 's');
					$this->updateSchema($elementArr['extension_id'], null, null, $this->manifest_xml->version);
					continue;
				}
			} else {
				$this->ioStyle->error("-> Manifest path doesn't exists");
				$success = false;
			}

			$this->schema_version = $this->getSchemaVersion($elementArr['extension_id']);
		}

		return $success;
	}

	private function refreshManifestCache($ext_id, $element)
	{
		if (is_array($ext_id)) {
			$ext_id = $ext_id[0];
		}
		$installer = Installer::getInstance();
		$installer->extension->load($ext_id);

		# For Joomla update method works, we need to rename manifest file for some extensions (extplorer and dropfiles)
		if ($element == 'com_extplorer' or $element == 'com_dropfiles') {
			$comp = $this->getElementFromId('extensions', array($ext_id));
			$manifest = json_decode($comp[0]['manifest_cache'], true);
			$file = JPATH_ADMINISTRATOR . '/components/' . $comp[0]['element'] . '/' . $comp[0]['element'] . '.xml';
			$short_element = str_replace('com_', '', $comp[0]['element']);
			if (file_exists($file)) {
				$rename_file = JPATH_ADMINISTRATOR . '/components/' . $comp[0]['element'] . '/' . $short_element . '.xml';
				rename($file, $rename_file);
			}
		}
		$result = 0;
		$result |= $installer->refreshManifestCache($ext_id);

		# Case for component with non conventional file naming
		if ($element == 'com_extplorer' or $element == 'com_dropfiles') {
			if (file_exists($rename_file)) {
				rename($rename_file, $file);
				$manifest['version'] = (string)$this->manifest_xml->version;
				$manifest = json_encode($manifest);
				$installer->extension->manifest_cache = $manifest;
				$installer->extension->store();
			}
		}
		if ($result != 1) {
			$this->ioStyle->error("-> Refresh manifest cache Failed");
			exit();
		}

		return $installer->manifest->version;
	}

	private function getElementFromId($table, $ids)
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select('*')
			->from('#__' . $table)
			->where($db->quoteName('extension_id') . " IN (" . implode(',', $ids) . ')');
		$db->setQuery($query);

		return $db->loadAssocList('',);
	}

	private function updateSchema($eid, $files = null, $method = null, $version = null)
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->delete('#__schemas')
			->where('extension_id = ' . $eid);
		$db->setQuery($query);

		if ($db->execute()) {
			if ($method && $files) {
				$query->clear()
					->insert($db->quoteName('#__schemas'))
					->columns(array($db->quoteName('extension_id'), $db->quoteName('version_id')))
					->values($eid . ', ' . $db->quote($method($files)));
			} else {
				$query->clear()
					->insert($db->quoteName('#__schemas'))
					->columns(array($db->quoteName('extension_id'), $db->quoteName('version_id')))
					->values($eid . ', ' . $db->quote($version));
			}
			$db->setQuery($query);
			$db->execute();

		}
	}

	private function getSchemaVersion($eid)
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select('version_id')
			->from('#__schemas')
			->where('extension_id = ' . $eid);
		$db->setQuery($query);

		return $db->loadResult();
	}

    /**
     * Configure the IO.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    private function configureIO(InputInterface $input, OutputInterface $output)
    {
        $this->cliInput = $input;
        $this->ioStyle  = new SymfonyStyle($input, $output);
    }

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function configure(): void
    {
        $help = "<info>%command.name%</info> will update Tchooz product
		\nUsage: <info>php %command.full_name%</info>";

        $this->setDescription('Update Tchooz core product');
        $this->setHelp($help);
    }
}