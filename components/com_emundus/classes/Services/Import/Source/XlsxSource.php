<?php
/**
 * @package     Tchooz\Services\Import\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Source;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reads a single worksheet from an xlsx/xls/ods file.
 *
 * Sheet selection is explicit: callers either name the sheet they want
 * (forSheet) or use the active sheet (forActiveSheet). No automatic
 * detection here — the controller orchestrates which sheet maps to which
 * entity importer.
 */
final class XlsxSource implements ImportSourceInterface
{
	/** @var string[] */
	private array $headers;

	/** @var array<int, array<string, mixed>>  rowNumber => raw row */
	private array $rows;

	private string $name;

	/**
	 * @param string[]                          $headers
	 * @param array<int, array<string, mixed>>  $rows  rowNumber => assoc row
	 */
	private function __construct(string $name, array $headers, array $rows)
	{
		$this->name    = $name;
		$this->headers = $headers;
		$this->rows    = $rows;
	}

	public static function forSheet(string $filePath, string $sheetName): self
	{
		$spreadsheet = self::loadSpreadsheet($filePath);
		$sheet       = $spreadsheet->getSheetByName($sheetName);

		if ($sheet === null)
		{
			throw new \RuntimeException(sprintf('Sheet "%s" not found in "%s".', $sheetName, $filePath));
		}

		return self::fromWorksheet($sheet);
	}

	public static function forActiveSheet(string $filePath): self
	{
		return self::fromWorksheet(self::loadSpreadsheet($filePath)->getActiveSheet());
	}

	public static function fromWorksheet(Worksheet $sheet): self
	{
		$rows = $sheet->toArray(null, true, true, false);

		if ($rows === [])
		{
			return new self($sheet->getTitle(), [], []);
		}

		$headers = array_map(static fn ($value) => trim((string) ($value ?? '')), array_shift($rows));

		$mapped = [];
		foreach ($rows as $index => $row)
		{
			$assoc = [];
			foreach ($headers as $headerIndex => $header)
			{
				if ($header === '')
				{
					continue;
				}
				$assoc[$header] = $row[$headerIndex] ?? null;
			}

			// Row number = data index + 2 (header is row 1).
			$mapped[$index + 2] = $assoc;
		}

		return new self($sheet->getTitle(), $headers, $mapped);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getRawHeaders(): array
	{
		return $this->headers;
	}

	public function getIterator(): \Iterator
	{
		foreach ($this->rows as $rowNumber => $row)
		{
			yield $rowNumber => $row;
		}
	}

	private static function loadSpreadsheet(string $filePath): Spreadsheet
	{
		if (!is_readable($filePath))
		{
			throw new \RuntimeException(sprintf('Spreadsheet file "%s" is not readable.', $filePath));
		}

		$reader = IOFactory::createReaderForFile($filePath);
		$reader->setReadDataOnly(true);

		return $reader->load($filePath);
	}
}
