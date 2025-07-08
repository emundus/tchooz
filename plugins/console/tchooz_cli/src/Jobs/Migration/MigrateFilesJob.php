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
use Emundus\Plugin\Console\Tchooz\Services\StorageService;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateFilesJob extends TchoozJob
{
	public function __construct(
		private readonly object         $logger,
		private readonly StorageService $storageService,
		private readonly string         $projectToMigratePath
	)
	{
		parent::__construct($logger);
	}

	public function execute(OutputInterface $output): void
	{
		if (!$this->migrateFiles())
		{
			Log::add('Error while migrating files', Log::ERROR, self::getJobName());

			throw new \RuntimeException('Error while migrating files');
		}

		Log::add('Files migrated', Log::INFO, self::getJobName());
	}

	private function migrateFiles(): bool
	{
		$uploaded = false;

		// Merge files from source to destination (images/custom)
		$source_files_path      = $this->projectToMigratePath . '/images/custom/';
		$destination_files_path = JPATH_SITE . '/images/custom/';

		// Check if the source directory exists
		if (is_dir($source_files_path))
		{
			// Check if the destination directory exists
			if (!is_dir($destination_files_path))
			{
				mkdir($destination_files_path, 0755, true);
			}

			// Call the function to copy files and folders recursively
			$this->storageService->copyFolderContents($source_files_path, $destination_files_path);

			$uploaded = true;
		}

		return $uploaded;
	}


	public static function getJobName(): string
	{
		return 'Files';
	}

	public static function getJobDescription(): ?string
	{
		return 'Migrate files';
	}
}