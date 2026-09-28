<?php
/**
 * @package     Tchooz\Services\Export
 *
 * @copyright   (C) eMundus
 * @license     GNU/GPL
 */

namespace Tchooz\Services\Export;

use Joomla\CMS\Log\Log;
use Joomla\Filesystem\Folder;
use Tchooz\Entities\Automation\Actions\ActionExport;
use Tchooz\Repositories\Export\ExportRepository;

/**
 * Removes what a resumable export leaves on disk while it runs (staging_<id>/ folder, export_<id>.json
 * state) once its export row is gone. Deleting the row only removes the produced files, so a failed
 * or abandoned export would otherwise keep its staging forever. An export still in the table is never
 * touched, whatever its progress.
 *
 * Deleting recursively is only safe on paths it has fully checked itself, so the root is fixed (never
 * given by a caller) and every candidate must be a real entry, not a symbolic link, lying exactly at
 * <exports root>/<numeric user id>/<leftover name>.
 */
class ExportStorageCleaner
{
	private const USER_DIR_PATTERN = '/^\d+$/';

	private const STAGING_PATTERN = '/^staging_(\d+)$/';

	private const STATE_PATTERN = '/^export_(\d+)\.json$/';

	public function __construct(
		private readonly ExportRepository $exportRepository = new ExportRepository()
	)
	{
		Log::addLogger(['text_file' => 'com_emundus.export.cleaner.php'], Log::ALL, ['com_emundus.export.cleaner']);
	}

	/**
	 * @return int number of leftovers removed
	 */
	public function deleteOrphans(): int
	{
		$leftovers = $this->findLeftovers();
		if (empty($leftovers))
		{
			return 0;
		}

		$existingIds = array_flip($this->exportRepository->getExistingIds(array_column($leftovers, 'id')));
		$deleted     = 0;

		foreach ($leftovers as $leftover)
		{
			if (isset($existingIds[$leftover['id']]))
			{
				continue;
			}

			try
			{
				$removed = $leftover['isDir'] ? Folder::delete($leftover['path']) : unlink($leftover['path']);
			}
			catch (\Throwable $e)
			{
				$removed = false;
				Log::add('Could not remove export leftover ' . $leftover['path'] . ': ' . $e->getMessage(), Log::WARNING, 'com_emundus.export.cleaner');
			}

			if ($removed)
			{
				$deleted++;
			}
		}

		return $deleted;
	}

	private function getRoot(): ?string
	{
		$root = realpath(JPATH_SITE . '/' . ActionExport::EXPORT_BASE_PATH);

		return $root !== false && is_dir($root) ? $root : null;
	}

	/**
	 * @return array<array{id: int, path: string, isDir: bool}>
	 */
	private function findLeftovers(): array
	{
		$root = $this->getRoot();
		if ($root === null)
		{
			return [];
		}

		$leftovers = [];

		foreach (scandir($root) ?: [] as $userDirName)
		{
			$userDir = $root . '/' . $userDirName;
			if (!preg_match(self::USER_DIR_PATTERN, $userDirName) || !$this->isRealEntryOf($userDir, $root, true))
			{
				continue;
			}

			foreach (scandir($userDir) ?: [] as $name)
			{
				$path = $userDir . '/' . $name;

				if (preg_match(self::STAGING_PATTERN, $name, $matches) && $this->isRealEntryOf($path, $userDir, true))
				{
					$leftovers[] = ['id' => (int) $matches[1], 'path' => $path, 'isDir' => true];
				}
				elseif (preg_match(self::STATE_PATTERN, $name, $matches) && $this->isRealEntryOf($path, $userDir, false))
				{
					$leftovers[] = ['id' => (int) $matches[1], 'path' => $path, 'isDir' => false];
				}
			}
		}

		return $leftovers;
	}

	/**
	 * A symbolic link is rejected outright: deleting "through" it would empty whatever it points to.
	 * The resolved path must also sit directly in $parent, so nothing outside the exports root can match.
	 */
	private function isRealEntryOf(string $path, string $parent, bool $expectDir): bool
	{
		if (is_link($path))
		{
			return false;
		}

		if ($expectDir ? !is_dir($path) : !is_file($path))
		{
			return false;
		}

		$resolved = realpath($path);

		return $resolved !== false && dirname($resolved) === $parent;
	}
}
