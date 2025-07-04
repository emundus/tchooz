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
use Emundus\Plugin\Console\Tchooz\Services\StorageService;
use Emundus\Plugin\Console\Tchooz\Style\EmundusProgressBar;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateTemplatesJob extends TchoozJob
{
	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService    $databaseServiceSource,
		private readonly DatabaseService    $databaseService,
		private readonly StorageService    $storageService,
		private readonly string            $projectToMigratePath
	)
	{
		parent::__construct($logger);
	}

	public function execute(OutputInterface $output): void
	{
		$this->databaseService->getDatabase()->transactionStart();
		$this->databaseService->getDatabase()->setQuery('SET AUTOCOMMIT = 0')->execute();

		if (!$this->mergeTemplates($output->section()))
		{
			Log::add('Error while merging templates', Log::ERROR, self::getJobName());

			$this->databaseService->getDatabase()->transactionRollback();

			throw new \RuntimeException('Error while merging templates');
		}

		Log::add('Templates merged', Log::INFO, self::getJobName());

		$this->databaseService->getDatabase()->transactionCommit();
		$this->databaseService->getDatabase()->setQuery('SET AUTOCOMMIT = 1')->execute();
	}

	private function mergeTemplates(OutputInterface $outputSection): bool
	{
		$merged = true;

		$query_source = $this->databaseServiceSource->getDatabase()->getQuery(true);
		$query        = $this->databaseService->getDatabase()->getQuery(true);

		$query_source->clear()
			->select('*')
			->from($this->databaseServiceSource->getDatabase()->quoteName('jos_template_styles'))
			->where($this->databaseServiceSource->getDatabase()->quoteName('template') . ' LIKE ' . $this->databaseServiceSource->getDatabase()->quote('yootheme%'));
		$this->databaseServiceSource->getDatabase()->setQuery($query_source);
		$templates = $this->databaseServiceSource->getDatabase()->loadAssocList();

		if(!empty($templates))
		{
			$progressBar = new EmundusProgressBar($outputSection, count($templates));
			$progressBar->setMessage('Merging templates');
			$progressBar->start();

			foreach ($templates as $template)
			{
				$query->clear()
					->select('id')
					->from($this->databaseService->getDatabase()->quoteName('jos_template_styles'))
					->where($this->databaseService->getDatabase()->quoteName('template') . ' = ' . $this->databaseService->getDatabase()->quote($template['template']));
				$this->databaseService->getDatabase()->setQuery($query);
				$template_id = $this->databaseService->getDatabase()->loadResult();

				if (!empty($template_id))
				{
					$query->clear()
						->update($this->databaseService->getDatabase()->quoteName('jos_template_styles'))
						->set($this->databaseService->getDatabase()->quoteName('params') . ' = ' . $this->databaseService->getDatabase()->quote($template['params']))
						->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($template_id));
					$this->databaseService->getDatabase()->setQuery($query);
					$merged = $this->databaseService->getDatabase()->execute();

					if ($merged)
					{
						$query->clear()
							->update($this->databaseService->getDatabase()->quoteName('jos_menu'))
							->set($this->databaseService->getDatabase()->quoteName('template_style_id') . ' = ' . $this->databaseService->getDatabase()->quote($template_id))
							->where($this->databaseService->getDatabase()->quoteName('template_style_id') . ' = ' . $this->databaseService->getDatabase()->quote($template['id']));
						$this->databaseService->getDatabase()->setQuery($query);
						$merged = $this->databaseService->getDatabase()->execute();
					}
				}
				else
				{
					$query->clear()
						->insert($this->databaseService->getDatabase()->quoteName('jos_template_styles'))
						->columns($this->databaseService->getDatabase()->quoteName(array_keys($template)))
						->values(implode(',', $this->databaseService->getDatabase()->quote($template)));
					$this->databaseService->getDatabase()->setQuery($query);
					$merged = $this->databaseService->getDatabase()->execute();
				}

				$progressBar->advance();
			}
			$progressBar->finish('Templates merged');

			// Check if we found directories with the name yootheme_ in templates folder of old project then copy them to the destination
			$source_template_path      = $this->projectToMigratePath . '/templates/';
			$destination_template_path = JPATH_SITE . '/templates/';
			$source_template_folders   = array_filter(glob($source_template_path . 'yootheme_*'), 'is_dir');
			if (!empty($source_template_folders))
			{
				foreach ($source_template_folders as $source_template_folder)
				{
					$destination_template_folder = $destination_template_path . basename($source_template_folder);

					// Create the destination directory if it doesn't exist
					if (!is_dir($destination_template_folder))
					{
						mkdir($destination_template_folder, 0755, true);
					}

					// Call the function to copy files and folders recursively
					$this->storageService->copyFolderContents($source_template_folder, $destination_template_folder);
				}
			}
		}

		return $merged;
	}

	public static function getJobName(): string
	{
		return 'Templates';
	}

	public static function getJobDescription(): ?string
	{
		return 'Migrate templates';
	}
}