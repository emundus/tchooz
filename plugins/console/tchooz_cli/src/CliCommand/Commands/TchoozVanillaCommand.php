<?php
namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;


use Exception;
use Joomla\CMS\Factory;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Exception\UnsupportedAdapterException;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TchoozVanillaCommand extends AbstractCommand
{
    use DatabaseAwareTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'tchooz:vanilla';

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

	/**
	 * Database connector
	 *
	 * @var    DatabaseDriver
	 * @since  2.0.0
	 */
	private $db;

    /**
     * Command constructor.
     *
     * @param   DatabaseInterface  $db  The database
     *
     * @since   4.2.0
     */
    public function __construct(DatabaseInterface $db)
    {
	    $this->db = $db;

        parent::__construct();
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
		
		$action = $input->getOption('action');

		if($action == 'create') {
			$this->ioStyle->title('Dump vanilla');

			$this->ioStyle->warning('This command will clean your database to perform a vanilla dump.');
			$confirm = $this->ioStyle->confirm('Are you sure you want to continue?');

			if ($confirm) {
				$totalTime = microtime(true);

				if ($this->cleanDB()) {
					$this->ioStyle->text(sprintf('Database cleaned in %d seconds', round(microtime(true) - $totalTime, 3)));
				}
				else {
					$this->ioStyle->error('Error while cleaning database');

					return 1;
				}

				if (!class_exists(File::class)) {
					$this->ioStyle->error('The "joomla/filesystem" Composer package is not installed, cannot create an export.');

					return 1;
				}

				// Make sure the database supports exports before we get going
				try {
					$exporter = $this->db->getExporter()
						->withStructure();
				}
				catch (UnsupportedAdapterException $e) {
					$this->ioStyle->error(sprintf('The "%s" database driver does not support exporting data.', $this->db->getName()));

					return 1;
				}

				$folderPath = $input->getOption('folder');

				$tables = $this->db->getTableList();
				$views  = $this->getViews();
				$tables = array_diff($tables, $views);

				foreach ($tables as $table) {
					$taskTime = microtime(true);
					$filename = $folderPath . '/' . $table . '.xml';

					$this->ioStyle->text(sprintf('Processing the %s table', $table));

					$data = (string) $exporter->from($table)->withData(true);

					if (file_exists($filename)) {
						File::delete($filename);
					}

					File::write($filename, $data);

					$this->ioStyle->text(sprintf('Exported data for %s in %d seconds', $table, round(microtime(true) - $taskTime, 3)));
				}

				foreach ($views as $view) {
					$taskTime = microtime(true);
					$xml_filename = $folderPath . '/' . $view . '.xml';
					$filename = $folderPath . '/' . $view . '.sql';

					$this->ioStyle->text(sprintf('Processing the %s view', $view));

					$dump = $this->exportView($view);
					if(!empty($dump)) {
						if (file_exists($filename)) {
							File::delete($filename);
						}

						File::write($filename, $dump);

						if(file_exists($xml_filename)) {
							File::delete($xml_filename);
						}

						$this->ioStyle->text(sprintf('Exported data for %s in %d seconds', $view, round(microtime(true) - $taskTime, 3)));
					}
				}

				$this->ioStyle->success(sprintf('Dump completed in %d seconds', round(microtime(true) - $totalTime, 3)));
			}
		}

		if($action == 'import') {
			$this->ioStyle->title('Importing vanilla views');

			$this->ioStyle->warning('This command will replace your views.');
			$confirm = $this->ioStyle->confirm('Are you sure you want to continue?');

			if($confirm) {
				$totalTime = microtime(true);

				$folderPath = $input->getOption('folder');

				$views = Folder::files($folderPath, '\.sql$');
				foreach ($views as $view) {
					$taskTime = microtime(true);
					$percorso = $folderPath . '/' . $view;

					// Check file
					if (!file_exists($percorso)) {
						$this->ioStyle->error(sprintf('The %s file does not exist.', $view));

						return 1;
					}

					$viewName = str_replace('.xml', '', $view);
					$this->ioStyle->text(sprintf('Importing %1$s from %2$s', $viewName, $view));

					$queries = $this->db->splitSql(file_get_contents($percorso));

					$this->ioStyle->text(sprintf('Processing the %s table', $viewName));

					foreach ($queries as $query){
						if(!empty($query)){
							$this->db->setQuery($query);
							$this->db->execute();
						}
					}

					$this->ioStyle->text(sprintf('Imported data for %s in %d seconds', $view, round(microtime(true) - $taskTime, 3)));
				}

				$this->ioStyle->success(sprintf('Import completed in %d seconds', round(microtime(true) - $totalTime, 3)));
			}
		}

		// php cli/joomla.php tchooz:vanilla --action="export_foreign_keys"
		if($action === 'export_foreign_keys') {
			$this->ioStyle->title('Exporting foreign keys');

			$this->ioStyle->warning('This command will export your foreign keys.');
			$confirm = $this->ioStyle->confirm('Are you sure you want to continue?');

			if($confirm) {
				$totalTime = microtime(true);

				$destinationFile = '.docker/installation/vanilla/foreign_keys/foreign_keys.xml';
				$tables = $this->db->getTableList();

				// erase $destinationFile content
				if (file_exists($destinationFile)) {
					File::delete($destinationFile);
				}

				File::write($destinationFile, '');

				// add xml header
				$dom = new \DOMDocument('1.0', 'utf-8');
				$dom->formatOutput = true;

				$xml = $dom->createElement('tables');
				foreach ($tables as $table) {
					$taskTime = microtime(true);

					$this->ioStyle->text(sprintf('Processing the %s table', $table));

					$query = 'SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = "' . $table . '" AND REFERENCED_TABLE_NAME IS NOT NULL';
					$this->db->setQuery($query);
					$foreign_keys = $this->db->loadAssocList();

					if (!empty($foreign_keys)) {
						$xml_table = $dom->createElement('table');
						$xml_table->setAttribute('name', $table);

						foreach ($foreign_keys as $foreign_key) {
							$query_rules = 'SELECT UPDATE_RULE,DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME = "' . $table . '" AND CONSTRAINT_NAME = "' . $foreign_key['CONSTRAINT_NAME'] . '"';
							$this->db->setQuery($query_rules);
							$foreign_key_rules = $this->db->loadAssoc();

							$xml_row = $dom->createElement('row');
							$xml_row->setAttribute('constraint_name', $foreign_key['CONSTRAINT_NAME']);
							$xml_row->setAttribute('column_name', $foreign_key['COLUMN_NAME']);
							$xml_row->setAttribute('referenced_table_name', $foreign_key['REFERENCED_TABLE_NAME']);
							$xml_row->setAttribute('referenced_column_name', $foreign_key['REFERENCED_COLUMN_NAME']);
							$xml_row->setAttribute('update_rule', $foreign_key_rules['UPDATE_RULE']);
							$xml_row->setAttribute('delete_rule', $foreign_key_rules['DELETE_RULE']);

							$xml_table->appendChild($xml_row);
						}

						$xml->appendChild($xml_table);
					}

					$this->ioStyle->text(sprintf('Exported data for %s in %d seconds', $table, round(microtime(true) - $taskTime, 3)));
				}

				$dom->appendChild($xml);
				$dom->save($destinationFile);

				$this->ioStyle->success(sprintf('Export completed in %d seconds', round(microtime(true) - $totalTime, 3)));
			}
		}

		// php cli/joomla.php tchooz:vanilla --action="import_foreign_keys"
	    if ($action === 'import_foreign_keys') {
			$this->ioStyle->title('Importing foreign keys');

		    $this->ioStyle->warning('This command will replace your foreign keys.');
		    $confirm = $this->ioStyle->confirm('Are you sure you want to continue?');

		    if ($confirm) {
			    $totalTime = microtime(true);

			    $srcFile = '.docker/installation/vanilla/foreign_keys/foreign_keys.xml';

				// Check file
			    if (!file_exists($srcFile)) {
					$this->ioStyle->warning(sprintf('The %s file does not exist.', $srcFile));
				} else {
					$dom = new \DOMDocument('1.0', 'utf-8');
					$dom->load($srcFile);

					$xpath = new \DOMXPath($dom);
					$tables = $xpath->query('//table');

				    $this->db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();

					foreach ($tables as $table) {
						$taskTime = microtime(true);

						$tableName = $table->getAttribute('name');
						$this->ioStyle->text(sprintf('Processing the %s table', $tableName));

						// check if table exists
						$query = 'SELECT * FROM information_schema.TABLES WHERE TABLE_NAME = "' . $tableName . '"';
						$this->db->setQuery($query);
						$tableExists = $this->db->loadAssoc();

						if (empty($tableExists)) {
							$this->ioStyle->warning(sprintf('The %s table does not exist.', $tableName));
							continue;
						}

						$rows = $xpath->query('//table[@name="' . $tableName . '"]/row');

						foreach ($rows as $row) {
							$constraintName = $row->getAttribute('constraint_name');
							$columnName = $row->getAttribute('column_name');
							$referencedTableName = $row->getAttribute('referenced_table_name');
							$referencedColumnName = $row->getAttribute('referenced_column_name');
							$deleteRule = $row->getAttribute('delete_rule');
							$updateRule = $row->getAttribute('update_rule');

							// Check if foreign key exists
							$query = 'SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = "' . $tableName . '" AND CONSTRAINT_NAME = "' . $constraintName . '"';
							$this->db->setQuery($query);
							$foreignKeyExists = $this->db->loadAssoc();

							if(!empty($foreignKeyExists))
							{
								$drop_query = "ALTER TABLE `" . $tableName . "` DROP FOREIGN KEY `" . $constraintName . "`";
								$this->db->setQuery($drop_query);
								$this->db->execute();
							}

							$query = 'ALTER TABLE `' . $tableName . '` ADD CONSTRAINT `' . $constraintName . '` FOREIGN KEY (`' . $columnName . '`) REFERENCES `' . $referencedTableName . '` (`' . $referencedColumnName . '`) ON DELETE ' . $deleteRule . ' ON UPDATE ' . $updateRule;

							try {
								$this->db->setQuery($query);
								$this->db->execute();
							} catch (\Exception $e) {
								$this->ioStyle->error(sprintf('Error while importing foreign key %s for the %s table.', $constraintName, $tableName));
								continue;
							}
						}

						$this->ioStyle->text(sprintf('Imported data for %s in %d seconds', $tableName, round(microtime(true) - $taskTime, 3)));
					}

				    $this->db->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
			    }

			    $this->ioStyle->success(sprintf('Import completed in %d seconds', round(microtime(true) - $totalTime, 3)));
		    }
	    }

	    return Command::SUCCESS;
    }

	private function getViews(): array
	{
		$app = Factory::getApplication();
		$views = [];
		
		try {
			$query = 'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = "' . $app->get('db') . '" AND `TABLE_TYPE` = "VIEW"';
			$this->db->setQuery($query);
			$views = $this->db->loadColumn();
		}
		catch (Exception $e) {
			$this->ioStyle->error(sprintf('Error while getting views: %s', $e->getMessage()));
		}
		
		return $views;
	}

	private function cleanDB(): bool
	{
		$cleaned = true;

		try {
			$queries = $this->db->splitSql(file_get_contents(JPATH_ROOT . '/.docker/installation/cleandb.sql'));

			foreach ($queries as $query){
				if(!empty($query)){
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
		}
		catch (Exception $e) {
			$this->ioStyle->error(sprintf('Error while cleaning database: %s', $e->getMessage()));
			$cleaned = false;

		}

		return $cleaned;
	}
	
	private function exportView($view): string
	{
		$view_dump = '';

		try {
			$query = 'SHOW CREATE VIEW ' . $view;
			$this->db->setQuery($query);
			$dump = $this->db->loadAssoc();

			if(!empty($dump['Create View'])) {
				$re = '/(ALGORITHM.*DEFINER)/m';
				$view_dump = preg_replace($re, '', $dump['Create View']);
			}
		}
		catch (Exception $e) {
			$this->ioStyle->error(sprintf('Error while exporting view %s: %s', $view, $e->getMessage()));
		}

		return $view_dump;
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
	    $this->setDescription('Create a vanilla dump');
	    $this->addOption('folder', null, InputOption::VALUE_OPTIONAL, 'Path to write the export files to', '.docker/installation/vanilla');
	    $this->addOption('action', null, InputOption::VALUE_OPTIONAL, 'Action to perform (create or import)', 'create');
    }
}