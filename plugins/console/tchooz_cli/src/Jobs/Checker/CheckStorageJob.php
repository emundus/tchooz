<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checker;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\StorageService;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckStorageJob extends TchoozJob
{
	public function __construct(
		private readonly object $logger,
		private readonly StorageService $storageService
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		// Only if database is in local storage
		if (!$this->storageService->isLocalStorage()) {
			$output->writeln('<info>Skipping storage check, database is not in local storage.</info>');
			Log::add('Skipping storage check, database is not in local storage.', Log::INFO, self::getJobName());
			return;
		}

		$sqlPartition = $this->storageService->getSqlPartitionPath();
		Log::add('SQL partition path: ' . $sqlPartition, Log::INFO, self::getJobName());

		$databaseSize = $this->storageService->getDatabaseSize($sqlPartition);
		Log::add('Size of the database: ' . $this->storageService->formatSize($databaseSize), Log::INFO, self::getJobName());

		$freeSpace = disk_free_space($sqlPartition);

		$totalSize = $databaseSize * 2;
		$freeSpace = $freeSpace - ($freeSpace * 0.20);

		Log::add('Free disk space: ' . $this->storageService->formatSize($freeSpace), Log::INFO, self::getJobName());
		Log::add('Size needed: ' . $this->storageService->formatSize($totalSize), Log::INFO, self::getJobName());

		if($totalSize > $freeSpace)
		{
			throw new \Exception('Insufficient disk space for migration.');
		}
	}

	public static function getJobName(): string {
		return 'Storage';
	}

	public static function getJobDescription(): ?string {
		return 'Check of storage requirements, need at least 20% of free space';
	}
}