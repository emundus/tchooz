<?php
namespace Emundus\Plugin\Console\Tchooz\CliCommand;

defined('_JEXEC') or die;

use Exception;
use Joomla\Archive\Archive;
use Joomla\Archive\Zip;
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
use Emundus\Plugin\Console\Tchooz\CliCommand\Services\EmundusDatabaseExporter;

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
					$filename = $folderPath . '/' . $view . '.sql';

					$this->ioStyle->text(sprintf('Processing the %s view', $view));

					$dump = $this->exportView($view);
					if(!empty($dump)) {
						if (file_exists($filename)) {
							File::delete($filename);
						}

						File::write($filename, $dump);

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

		if($action == 'export') {
			$symfonyStyle = new SymfonyStyle($input, $output);

			$symfonyStyle->title('Exporting Database');

			$totalTime = microtime(true);

			if (!class_exists(File::class)) {
				$symfonyStyle->error('The "joomla/filesystem" Composer package is not installed, cannot create an export.');

				return 1;
			}

			// Make sure the database supports exports before we get going
			try {
				$exporter = EmundusDatabaseExporter::withStructure();
			} catch (UnsupportedAdapterException $e) {
				$symfonyStyle->error(sprintf('The "%s" database driver does not support exporting data.', 'EmundusDatabaseExporter mysqli'));

				return 1;
			}

			$folderPath = $input->getOption('folder');
			$tableName  = $input->getOption('table');
			$zip        = $input->getOption('zip');

			$zipFile = $folderPath . '/data_exported_' . date("Y-m-d\TH-i-s") . '.zip';
			$tables  = $this->db->getTableList();
			$prefix  = $this->db->getPrefix();

			if ($tableName) {
				if (!\in_array($tableName, $tables)) {
					$symfonyStyle->error(sprintf('The %s table does not exist in the database.', $tableName));

					return 1;
				}

				$tables = [$tableName];
			}

			if ($zip) {
				if (!class_exists(Archive::class)) {
					$symfonyStyle->error('The "joomla/archive" Composer package is not installed, cannot create ZIP files.');

					return 1;
				}

				/** @var Zip $zipArchive */
				$zipArchive = (new Archive())->getAdapter('zip');
			}

			foreach ($tables as $table) {
				// If an empty prefix is in use then we will dump all tables, otherwise the prefix must match
				if (strlen($prefix) === 0 || strpos(substr($table, 0, strlen($prefix)), $prefix) !== false) {
					$taskTime = microtime(true);
					$filename = $folderPath . '/' . $table . '.xml';

					$symfonyStyle->text(sprintf('Processing the %s table', $table));

					$data = (string) $exporter->from($table)->withData(true);

					if (file_exists($filename)) {
						File::delete($filename);
					}

					File::write($filename, $data);

					if ($zip) {
						$zipFilesArray = [['name' => $table . '.xml', 'data' => $data]];
						$zipArchive->create($zipFile, $zipFilesArray);
						File::delete($filename);
					}

					$symfonyStyle->text(sprintf('Exported data for %s in %d seconds', $table, round(microtime(true) - $taskTime, 3)));
				}
			}

			$symfonyStyle->success(sprintf('Export completed in %d seconds', round(microtime(true) - $totalTime, 3)));

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