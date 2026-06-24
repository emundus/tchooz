<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

use Joomla\CMS\Language\Text;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tchooz\Enums\Import\FieldTypeEnum;

/**
 * Generates an import template file (CSV or XLSX) from a column descriptor
 * array as produced by ColumnMap::describe().
 *
 * The XLSX variant builds two sheets:
 *   - "Data"          : header row + Excel data validation dropdowns for ENUM columns.
 *   - "Documentation" : one row per field describing required / type / format /
 *                       allowed values / examples, so the integrator does not have
 *                       to call /getEntityImportInformation to know the schema.
 *
 * The service is stateless. Callers are responsible for choosing the output
 * path and serving the file (the service only writes to disk).
 */
final class ImportModelGenerator
{
	private const MAX_DATA_VALIDATION_ROWS = 1000;
	private const MAX_INLINE_FORMULA_LENGTH = 255;

	/**
	 * High-level entry point with built-in caching.
	 *
	 * Writes the model only when no file matches the current ($type, $format,
	 * $cacheKey) tuple, and purges older variants for the same ($type, $format)
	 * so /tmp does not accumulate one file per commit. Returns the absolute
	 * file path on disk — URL composition stays the caller's concern.
	 *
	 * @param string                            $directory  Absolute target directory.
	 * @param string                            $type       Entity type ("contact", "organization", ...).
	 * @param string                            $format     "csv" or "xlsx".
	 * @param string                            $cacheKey   Filename suffix used for cache invalidation.
	 * @param array<int, array<string, mixed>>  $columns    describe() output.
	 */
	public function build(string $directory, string $type, string $format, string $cacheKey, array $columns): string
	{
		$directory = rtrim($directory, '/\\') . '/';
		$filename  = $this->modelFilename($type, $format, $cacheKey);
		$filepath  = $directory . $filename;

		// Cache hit: same code version → reuse the existing file.
		if (is_file($filepath))
		{
			return $filepath;
		}

		$this->purgeStaleModels($directory, $type, $format, $filename);

		if ($format === 'xlsx')
		{
			$this->writeXlsx($filepath, $columns);
		}
		else
		{
			$this->writeCsv($filepath, $columns);
		}

		return $filepath;
	}

	/**
	 * Canonical filename for a model. Public so callers (controllers, tests)
	 * can refer to a file without writing it.
	 */
	public function modelFilename(string $type, string $format, string $cacheKey): string
	{
		return sprintf('import_model_%s_%s.%s', $type, $cacheKey, $format);
	}

	/**
	 * @param string                            $filepath  Absolute path the CSV will be written to.
	 * @param array<int, array<string, mixed>>  $columns   describe() output.
	 */
	public function writeCsv(string $filepath, array $columns): void
	{
		$handle = fopen($filepath, 'w');
		if ($handle === false)
		{
			throw new \RuntimeException(sprintf('Unable to open "%s" for writing.', $filepath));
		}

		try
		{
			// UTF-8 BOM so Excel opens the file with the right encoding.
			fwrite($handle, "\xEF\xBB\xBF");
			fputcsv($handle, array_map(fn(array $col) => $this->buildHeader($col), $columns));
		}
		finally
		{
			fclose($handle);
		}
	}

	/**
	 * @param string                            $filepath  Absolute path the XLSX will be written to.
	 * @param array<int, array<string, mixed>>  $columns   describe() output.
	 */
	public function writeXlsx(string $filepath, array $columns): void
	{
		$spreadsheet = new Spreadsheet();

		$dataSheet = $spreadsheet->getActiveSheet();
		$dataSheet->setTitle($this->sheetTitle('COM_EMUNDUS_IMPORT_MODEL_DATA_SHEET', 'Data'));
		$this->fillDataSheet($dataSheet, $columns);

		$docSheet = $spreadsheet->createSheet();
		$docSheet->setTitle($this->sheetTitle('COM_EMUNDUS_IMPORT_MODEL_DOC_SHEET', 'Documentation'));
		$this->fillDocumentationSheet($docSheet, $columns);

		// Always land on the data sheet when the user opens the file.
		$spreadsheet->setActiveSheetIndex(0);

		$writer = new Xlsx($spreadsheet);
		$writer->save($filepath);
	}

	/**
	 * Header label used in the data sheet for a given column descriptor.
	 *
	 * Picks the first declared alias when available (more human-readable than the
	 * canonical name) and appends a star suffix for required fields so the
	 * integrator sees at a glance which columns must be filled.
	 *
	 * @param array<string, mixed> $column
	 */
	private function buildHeader(array $column): string
	{
		$label = $column['label'] ?? $column['aliases'][0] ?? $column['canonical'];

		return !empty($column['required']) ? $label . ' *' : $label;
	}

	/**
	 * Header row + data validation dropdowns for ENUM columns.
	 *
	 * @param array<int, array<string, mixed>> $columns
	 */
	private function fillDataSheet(Worksheet $sheet, array $columns): void
	{
		$cell = 'A';
		foreach ($columns as $column)
		{
			$sheet->setCellValue($cell . '1', $this->buildHeader($column));

			// Bold required headers — visual cue beside the trailing star.
			if (!empty($column['required']))
			{
				$sheet->getStyle($cell . '1')->getFont()->setBold(true);
			}

			// ENUM → dropdown over the first MAX_DATA_VALIDATION_ROWS data rows of this column.
			if (($column['type'] ?? null) === FieldTypeEnum::ENUM->value && !empty($column['values']))
			{
				$this->attachEnumValidation($sheet, $cell, $column);
			}

			$sheet->getColumnDimension($cell)->setAutoSize(true);
			$cell++;
		}

		// Freeze the header so it stays visible when scrolling rows.
		$sheet->freezePane('A2');
	}

	/**
	 * Adds an Excel data validation list to a column for an ENUM field.
	 *
	 * @param array<string, mixed> $column
	 */
	private function attachEnumValidation(Worksheet $sheet, string $columnLetter, array $column): void
	{
		$values  = array_map(static fn(array $v) => (string) $v['value'], $column['values']);
		$formula = '"' . implode(',', $values) . '"';

		// Excel caps inline list formulas around 255 chars; bail out gracefully on long enums.
		if (strlen($formula) > self::MAX_INLINE_FORMULA_LENGTH)
		{
			return;
		}

		$firstCell = $columnLetter . '2';
		$range     = $firstCell . ':' . $columnLetter . self::MAX_DATA_VALIDATION_ROWS;

		// PhpSpreadsheet's getDataValidation() takes a single coordinate, not a
		// range. We attach the validation to the first data cell and let sqref
		// tell Excel that it actually applies to the whole column area below.
		$validation = $sheet->getCell($firstCell)->getDataValidation();
		$validation->setType(DataValidation::TYPE_LIST);
		$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
		$validation->setAllowBlank(empty($column['required']));
		$validation->setShowDropDown(true);
		$validation->setShowInputMessage(true);
		$validation->setShowErrorMessage(true);
		$validation->setErrorTitle(Text::_('COM_EMUNDUS_IMPORT_MODEL_INVALID_VALUE'));
		$validation->setError(Text::_('COM_EMUNDUS_IMPORT_MODEL_INVALID_VALUE_DESC'));
		$validation->setPromptTitle(Text::_('COM_EMUNDUS_IMPORT_MODEL_CHOOSE_VALUE'));
		$validation->setPrompt($this->buildEnumPromptText($column));
		$validation->setFormula1($formula);
		$validation->setSqref($range);
	}

	/**
	 * Second sheet: one row per canonical field with all the descriptor metadata.
	 *
	 * @param array<int, array<string, mixed>> $columns
	 */
	private function fillDocumentationSheet(Worksheet $sheet, array $columns): void
	{
		$labels = [
			'A' => Text::_('COM_EMUNDUS_IMPORT_MODEL_DOC_FIELD'),
			'B' => Text::_('COM_EMUNDUS_IMPORT_MODEL_DOC_REQUIRED'),
			'C' => Text::_('COM_EMUNDUS_IMPORT_MODEL_DOC_TYPE'),
			'D' => Text::_('COM_EMUNDUS_IMPORT_MODEL_DOC_FORMAT'),
			'E' => Text::_('COM_EMUNDUS_IMPORT_MODEL_DOC_VALUES'),
			'F' => Text::_('COM_EMUNDUS_IMPORT_MODEL_DOC_EXAMPLES'),
		];

		foreach ($labels as $cell => $label)
		{
			$sheet->setCellValue($cell . '1', $label);
			$sheet->getStyle($cell . '1')->getFont()->setBold(true);
			$sheet->getColumnDimension($cell)->setAutoSize(true);
		}

		$row = 2;
		foreach ($columns as $column)
		{
			$sheet->setCellValueExplicit('A' . $row, $this->buildHeader($column), DataType::TYPE_STRING);
			$sheet->setCellValue('B' . $row, !empty($column['required']) ? '✓' : '');
			$sheet->setCellValue('C' . $row, $column['type_label'] ?? ($column['type'] ?? FieldTypeEnum::STRING->value));
			$sheet->setCellValue('D' . $row, $column['format'] ?? '');
			$sheet->setCellValue('E' . $row, $this->renderEntries($column['values'] ?? null));
			$sheet->setCellValue('F' . $row, $this->renderEntries($column['examples'] ?? null));

			$sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
			$sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
			$sheet->getStyle('A' . $row . ':F' . $row)
				->getAlignment()
				->setVertical(Alignment::VERTICAL_TOP);

			$row++;
		}

		$sheet->freezePane('A2');
	}

	/**
	 * @param array<string, mixed> $column
	 */
	private function buildEnumPromptText(array $column): string
	{
		$lines = [];
		foreach ($column['values'] as $entry)
		{
			$value = (string) $entry['value'];
			$label = (string) ($entry['label'] ?? $value);
			$lines[] = $label === $value ? $value : sprintf('%s (%s)', $value, $label);
		}

		return implode("\n", $lines);
	}

	/**
	 * Renders a list of {value, label} entries as a single multi-line cell. Used
	 * for both `values` (ENUM) and `examples` (non-ENUM) in the doc sheet.
	 *
	 * @param array<int, array{value: string, label: string}>|null $entries
	 */
	private function renderEntries(?array $entries): string
	{
		if (empty($entries))
		{
			return '';
		}

		return implode("\n", array_map(
			static fn(array $entry) => $entry['value'] === $entry['label']
				? (string) $entry['value']
				: sprintf('%s — %s', $entry['value'], $entry['label']),
			$entries
		));
	}

	/**
	 * Excel caps sheet titles at 31 characters. Falls back to the provided
	 * default when the translation key is unresolved or too short.
	 */
	private function sheetTitle(string $key, string $default): string
	{
		$resolved = Text::_($key);
		$candidate = ($resolved === $key) ? $default : $resolved;

		return substr($candidate, 0, 31) ?: $default;
	}

	/**
	 * Removes previous model files for the same ($type, $format) tuple so
	 * the cache directory only keeps the latest cache-key variant.
	 */
	private function purgeStaleModels(string $directory, string $type, string $format, string $currentFilename): void
	{
		$pattern = $directory . 'import_model_' . $type . '_*.' . $format;
		foreach (glob($pattern) ?: [] as $file)
		{
			if (basename($file) !== $currentFilename)
			{
				@unlink($file);
			}
		}
	}
}
