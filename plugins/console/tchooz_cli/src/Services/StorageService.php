<?php
namespace Emundus\Plugin\Console\Tchooz\Services;

use Symfony\Component\Console\Output\OutputInterface;
use Joomla\Database\DatabaseInterface;

class StorageService
{
	public function __construct(
		private readonly DatabaseService $databaseService
	)
	{}

	public function getDirectorySize($directory): int
	{
		if (!is_dir($directory)) {
			throw new \RuntimeException(sprintf('Le chemin spécifié "%s" n\'est pas un répertoire valide.', $directory));
		}

		$size = 0;
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$size += $file->getSize();
			}
		}

		return $size;
	}

	public function getSqlPartitionPath()
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);
		$query->select('@@datadir as path');
		$this->databaseService->getDatabase()->setQuery($query);
		return $this->databaseService->getDatabase()->loadResult();
	}

	public function getDatabaseSize(?string $sqlPartition = ''): int
	{
		return $this->getDirectorySize($sqlPartition.'/'.$this->databaseService->getDbName());
	}

	public function formatSize(int $bytes): string
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
		return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
	}
}
