<?php
/**
 * @package     Tchooz\Services\Import\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Source;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Tchooz\Services\Import\ImportModelGenerator;
use Tchooz\Services\Import\ImportPipeline;

/**
 * Single source of truth for "which file formats can be imported" and how to
 * build the matching ImportSourceInterface from an uploaded file.
 */
final class ImportSourceFactory
{
	/**
	 * @var string[] Lower-case file extensions / format identifiers we accept.
	 */
	public const SUPPORTED_FORMATS = ['csv', 'xlsx', 'xls', 'json'];

	public static function supports(string $format): bool
	{
		return in_array(strtolower(trim($format)), self::SUPPORTED_FORMATS, true);
	}

	/**
	 * Builds the right source for the given file + format.
	 *
	 * @param  string       $filePath  Absolute (or readable) path to the file.
	 * @param  string       $format    File extension / format identifier.
	 * @param  string|null  $name      Human-readable source name used in reports
	 *                                 (typically the original upload filename).
	 *
	 * @throws \InvalidArgumentException when the format is not supported.
	 */
	public static function fromFile(string $filePath, string $format, ?string $name = null): ImportSourceInterface
	{
		return match (strtolower(trim($format)))
		{
			'csv'         => new CsvSource($filePath, name: $name),
			'xlsx', 'xls' => XlsxSource::forActiveSheet($filePath),
			'json'        => JsonSource::fromFile($filePath, $name),
			default       => throw new \InvalidArgumentException(
				sprintf(
					'Unsupported import format "%s" (supported: %s).',
					$format,
					implode(', ', self::SUPPORTED_FORMATS)
				)
			),
		};
	}

	/**
	 * Cheapest reliable total row count (header included) for the sync/async
	 * decision, avoiding a full file load whenever the metadata already settles it.
	 *
	 * @param  int  $syncThresholdRows  Data rows at/under which the import stays synchronous.
	 */
	public static function countTotalRows(string $filePath, string $format, int $syncThresholdRows): int
	{
		$format = strtolower(trim($format));

		if ($format !== 'xlsx' && $format !== 'xls')
		{
			return ImportPipeline::countRows(self::fromFile($filePath, $format));
		}

		$reader = IOFactory::createReaderForFile($filePath);
		$reader->setReadDataOnly(true);
		$info = $reader->listWorksheetInfo($filePath);
		$meta = (int) ($info[0]['totalRows'] ?? 0);

		if ($meta - 1 <= $syncThresholdRows || $meta > ImportModelGenerator::MAX_DATA_VALIDATION_ROWS)
		{
			return $meta;
		}

		return ImportPipeline::countRows(XlsxSource::forActiveSheet($filePath));
	}
}