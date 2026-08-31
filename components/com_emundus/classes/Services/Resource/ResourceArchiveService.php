<?php
/**
 * @package     Tchooz\Services\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Resource;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

/**
 * Owns the zip/filesystem concern of the resources module: create the archive directory,
 * build a zip from a set of source files (skipping missing ones, de-duplicating entry names),
 * and clean up an archive that ended up empty. It knows nothing about resources, access rules
 * or download counters — the caller decides which files go in and what to do afterwards.
 */
class ResourceArchiveService
{
	// Archives live under tmp/ (not the public images/ tree) so they are only reachable through the
	// getfile access gate and can be deleted right after download. The path is namespaced by owner id.
	public const ARCHIVE_DIR = 'tmp/resource-archives';

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.service.resource.php'], Log::ALL, ['com_emundus.service.resource']);
	}

	/**
	 * Build a zip from the given files and return its public URL plus the ids actually archived.
	 * Files whose source path is missing are skipped (and logged); an archive that ends up empty
	 * is removed and a RuntimeException is thrown.
	 *
	 * @param   string  $baseName  Human name used as the archive filename stem.
	 * @param   array<array{id:mixed,path:string,label:string,extension:string}>  $files
	 * @param   int     $ownerId   User the archive is built for; used to namespace and gate access.
	 *
	 * @return  array{url:string,archived_ids:array<int,mixed>}
	 */
	public function create(string $baseName, array $files, int $ownerId): array
	{
		$relativeDir = self::ARCHIVE_DIR . '/' . $ownerId;
		$archiveDir  = JPATH_SITE . '/' . $relativeDir;
		if (!is_dir($archiveDir) && !mkdir($archiveDir, 0755, true) && !is_dir($archiveDir))
		{
			throw new \RuntimeException('Unable to create the archive directory');
		}

		$archiveName = $this->sanitizeName($baseName) . '-' . uniqid() . '.zip';
		$archivePath = $archiveDir . '/' . $archiveName;

		$zip = new \ZipArchive();
		if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
		{
			throw new \RuntimeException('Unable to create the archive ' . $archiveName);
		}

		$usedNames   = [];
		$archivedIds = [];
		foreach ($files as $file)
		{
			if (!is_file($file['path']))
			{
				Log::add('Skipping missing file "' . ($file['label'] ?? '?') . '" while building archive ' . $archiveName, Log::WARNING, 'com_emundus.service.resource');

				continue;
			}

			$zip->addFile($file['path'], $this->buildEntryName((string) $file['label'], (string) $file['extension'], $usedNames));
			$archivedIds[] = $file['id'];
		}

		$zip->close();

		if (empty($archivedIds))
		{
			$this->removeArchive($archivePath);

			throw new \RuntimeException('No downloadable file to archive');
		}

		// Route through getfile: tmp/ is not directly servable, and getfile enforces ownership
		// and deletes the archive once streamed.
		return [
			'url'          => Uri::root() . 'index.php?option=com_emundus&task=getfile&u=' . $relativeDir . '/' . $archiveName,
			'archived_ids' => $archivedIds,
		];
	}

	/**
	 * Zip entry name (label + extension), de-duplicated within a single archive by
	 * inserting an index before the extension: "report.pdf", "report (2).pdf"…
	 */
	private function buildEntryName(string $label, string $extension, array &$usedNames): string
	{
		$base      = $label !== '' ? $label : 'file';
		$extension = $extension !== '' ? '.' . ltrim($extension, '.') : '';
		$name      = $base . $extension;

		$index = 1;
		while (isset($usedNames[$name]))
		{
			$name = $base . ' (' . (++$index) . ')' . $extension;
		}

		$usedNames[$name] = true;

		return $name;
	}

	/**
	 * Filesystem-safe base name for the generated archive.
	 */
	private function sanitizeName(string $name): string
	{
		$safe = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $name);
		$safe = trim((string) $safe, '_');

		if ($safe === '') {
			return 'archive';
		}

		// Garde une marge pour "-xxxxxxxxxxxxx.zip"
		return mb_strcut($safe, 0, 220, 'UTF-8');
	}

	/**
	 * Remove an archive file, logging (rather than swallowing) a failed removal.
	 */
	private function removeArchive(string $path): void
	{
		if (is_file($path) && !@unlink($path))
		{
			Log::add('Failed to remove empty archive ' . $path, Log::WARNING, 'com_emundus.service.resource');
		}
	}
}
