<?php
namespace Emundus\Plugin\Console\Tchooz\Services;

readonly class StorageService
{
	public function __construct(
		private readonly DatabaseService $databaseService
	)
	{}

	public function getDirectorySize(string $directory): int
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

	public function getSqlPartitionPath(): string
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

	public function copyFolderContents($source, $destination): void
	{
		$dir = opendir($source);

		while (($file = readdir($dir)) !== false)
		{
			if ($file !== '.' && $file !== '..')
			{
				$sourcePath      = $source . '/' . $file;
				$destinationPath = $destination . '/' . $file;

				if (is_dir($sourcePath))
				{
					if (!is_dir($destinationPath))
					{
						mkdir($destinationPath, 0755, true);
					}

					$this->copyFolderContents($sourcePath, $destinationPath);
				}
				else
				{
					copy($sourcePath, $destinationPath);
				}
			}
		}

		closedir($dir);
	}

	public function isLocalStorage(): bool
	{
		// Check if the database host is 'localhost' or 127.0.0.1
		return str_contains($this->databaseService->getHost(), 'localhost') || str_contains($this->databaseService->getHost(), '127.0.0.1');
	}
}
