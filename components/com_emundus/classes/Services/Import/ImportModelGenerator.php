<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
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
 * The XLSX variant builds:
 *   - "Data"          : header row + Excel data validation dropdowns for closed-list
 *                       columns (ENUM, BOOLEAN and REFERENTIAL).
 *   - "Documentation" : one row per field describing required / type / format /
 *                       allowed values / examples, so the integrator does not have
 *                       to call /getEntityImportInformation to know the schema.
 *   - one secondary sheet per REFERENTIAL field (and per oversized ENUM list)
 *                       holding the value / label / "label [value]" entries the
 *                       Data sheet dropdown references by range.
 *
 * The service owns where models are stored: a directory covered by the
 * .htaccess rewrite that routes every tmp/ request through the getfile PHP
 * gateway, so a model is never served as a static file and its access control
 * is enforced by EmundusControllerEmundus::getfile().
 */
final class ImportModelGenerator
{
	/**
	 * Root-relative directory holding the generated models. Must stay inside a
	 * path protected by the getfile gateway (see .htaccess tmp/ rewrite rule).
	 */
	public const MODEL_DIRECTORY = 'tmp/import_models/';

	public const MAX_DATA_VALIDATION_ROWS = 1000;
	private const MAX_INLINE_FORMULA_LENGTH = 255;

	private const REFERENTIAL_VALUE_COLUMN   = 'A';
	private const REFERENTIAL_LABEL_COLUMN   = 'B';
	private const REFERENTIAL_DISPLAY_COLUMN = 'C';

	/** @var array<string, string>  referential key => sheet title, so each referential gets a single shared sheet */
	private array $referentialSheetTitles = [];

	/**
	 * High-level entry point with built-in caching.
	 *
	 * Writes the model only when no file matches the current ($type, $format,
	 * code version) tuple, and purges older variants for the same ($type,
	 * $format) so the directory does not accumulate one file per commit.
	 * Returns the download URL going through the getfile gateway.
	 *
	 * @param string                            $type     Entity type ("contact", "organization", ...).
	 * @param string                            $format   "csv" or "xlsx".
	 * @param array<int, array<string, mixed>>  $columns  describe() output.
	 */
	public function build(string $type, string $format, array $columns): string
	{
		$directory = $this->resolveDirectory();
		$filename  = $this->modelFilename($type, $format, $this->cacheKey());
		$filepath  = $directory . $filename;

		// Cache hit: same code version → reuse the existing file.
		if (!is_file($filepath))
		{
			$this->purgeStaleModels($directory, $type, $format, $filename);

			if ($format === 'xlsx')
			{
				$this->writeXlsx($filepath, $columns);
			}
			else
			{
				$this->writeCsv($filepath, $columns);
			}
		}

		return $this->downloadUrl($filename);
	}

	/**
	 * Absolute model directory, created on first use.
	 */
	private function resolveDirectory(): string
	{
		$directory = JPATH_ROOT . '/' . self::MODEL_DIRECTORY;

		if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory))
		{
			throw new \RuntimeException(sprintf('Unable to create the import model directory "%s".', $directory));
		}

		return $directory;
	}

	/**
	 * Models are streamed by the getfile gateway rather than linked statically:
	 * the directory is behind an .htaccess rewrite, and getfile is where the
	 * access level is checked.
	 */
	private function downloadUrl(string $filename): string
	{
		return Uri::root() . 'index.php?option=com_emundus&task=getfile&u=' . self::MODEL_DIRECTORY . $filename;
	}

	/**
	 * Short, filename-safe identifier of the current code version, sourced from
	 * EmundusHelperCache::getCurrentGitHash() — the same helper used by every
	 * cache-busting view in the project — so a new commit (dev) or a new
	 * component release (prod) automatically invalidates cached models.
	 */
	private function cacheKey(): string
	{
		static $cached = null;
		if ($cached !== null)
		{
			return $cached;
		}

		if (!class_exists('EmundusHelperCache'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
		}

		$safe = preg_replace('/[^A-Za-z0-9.\-]/', '_', \EmundusHelperCache::getCurrentGitHash());

		return $cached = ($safe !== '' ? substr($safe, 0, 12) : 'v0');
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
		$this->referentialSheetTitles = [];

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
	 * Header row + data validation dropdowns for closed-list columns.
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

			$this->attachClosedListValidation($sheet, $cell, $column);

			$sheet->getColumnDimension($cell)->setAutoSize(true);
			$cell++;
		}

		// Freeze the header so it stays visible when scrolling rows.
		$sheet->freezePane('A2');
	}

	/**
	 * Adds a data validation dropdown to a column backed by a closed list: a
	 * static `values` list (ENUM, BOOLEAN) or a REFERENTIAL (dynamic
	 * `referential.entries`).
	 *
	 * Short static lists stay inline. REFERENTIAL fields and oversized static lists
	 * (beyond Excel's ~255 char inline cap) are rendered on a dedicated secondary
	 * sheet referenced by range. Static-list cells reference the raw value column;
	 * referential cells reference the "label [value]" column (decoded back to the
	 * value at import time).
	 *
	 * @param array<string, mixed> $column
	 */
	private function attachClosedListValidation(Worksheet $sheet, string $columnLetter, array $column): void
	{
		$isReferential = !empty($column['referential']['entries']);
		$isStaticList  = !empty($column['values']);

		if (!$isReferential && !$isStaticList)
		{
			return;
		}

		$entries = $isReferential ? $column['referential']['entries'] : $column['values'];

		if (!$isReferential)
		{
			$inline = '"' . implode(',', array_map(static fn(array $v) => (string) $v['value'], $entries)) . '"';

			if (strlen($inline) <= self::MAX_INLINE_FORMULA_LENGTH)
			{
				$validation = $this->newListValidation($sheet, $columnLetter, $column);
				$validation->setShowInputMessage(true);
				$validation->setPromptTitle(Text::_('COM_EMUNDUS_IMPORT_MODEL_CHOOSE_VALUE'));
				$validation->setPrompt($this->buildListPromptText($entries));
				$validation->setFormula1($inline);

				return;
			}
		}

		$spreadsheet    = $sheet->getParent();
		$listColumn     = $isReferential ? self::REFERENTIAL_DISPLAY_COLUMN : self::REFERENTIAL_VALUE_COLUMN;
		$referentialKey = $isReferential ? ($column['referential']['key'] ?? null) : null;

		if ($referentialKey !== null && isset($this->referentialSheetTitles[$referentialKey]))
		{
			$sheetTitle = $this->referentialSheetTitles[$referentialKey];
		}
		else
		{
			$desiredTitle = !empty($column['referential']['label'])
				? (string) $column['referential']['label']
				: $this->buildHeader($column);

			$sheetTitle = $this->writeReferentialSheet($spreadsheet, $desiredTitle, $entries);

			if ($referentialKey !== null)
			{
				$this->referentialSheetTitles[$referentialKey] = $sheetTitle;
			}
		}

		$formula = sprintf(
			"'%s'!$%s$2:$%s$%d",
			str_replace("'", "''", $sheetTitle),
			$listColumn,
			$listColumn,
			count($entries) + 1
		);

		$validation = $this->newListValidation($sheet, $columnLetter, $column);
		$validation->setFormula1($formula);
	}

	/**
	 * Builds a list-type data validation on the first data cell of a column and
	 * stretches it over the column area below via sqref (PhpSpreadsheet's
	 * getDataValidation() only takes a single coordinate). Caller sets formula1.
	 *
	 * @param array<string, mixed> $column
	 */
	private function newListValidation(Worksheet $sheet, string $columnLetter, array $column): DataValidation
	{
		$firstCell = $columnLetter . '2';
		$range     = $firstCell . ':' . $columnLetter . self::MAX_DATA_VALIDATION_ROWS;

		$validation = $sheet->getCell($firstCell)->getDataValidation();
		$validation->setType(DataValidation::TYPE_LIST);
		$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
		$validation->setAllowBlank(empty($column['required']));
		$validation->setShowDropDown(true);
		$validation->setShowErrorMessage(true);
		$validation->setErrorTitle(Text::_('COM_EMUNDUS_IMPORT_MODEL_INVALID_VALUE'));
		$validation->setError(Text::_('COM_EMUNDUS_IMPORT_MODEL_INVALID_VALUE_DESC'));
		$validation->setSqref($range);

		return $validation;
	}

	/**
	 * Writes a secondary sheet listing a closed list as value / label /
	 * "label [value]" columns and returns its unique, ≤31 char title.
	 *
	 * @param array<int, array{value: string, label?: string}> $entries
	 */
	private function writeReferentialSheet(Spreadsheet $spreadsheet, string $desiredTitle, array $entries): string
	{
		$sheet = new Worksheet($spreadsheet);
		$spreadsheet->addSheet($sheet);

		$title = $this->uniqueSheetTitle($spreadsheet, $desiredTitle);
		$sheet->setTitle($title);

		$sheet->setCellValue(self::REFERENTIAL_VALUE_COLUMN . '1', Text::_('COM_EMUNDUS_IMPORT_MODEL_REF_VALUE'));
		$sheet->setCellValue(self::REFERENTIAL_LABEL_COLUMN . '1', Text::_('COM_EMUNDUS_IMPORT_MODEL_REF_LABEL'));
		$sheet->setCellValue(self::REFERENTIAL_DISPLAY_COLUMN . '1', Text::_('COM_EMUNDUS_IMPORT_MODEL_REF_DISPLAY'));

		$row = 2;
		foreach ($entries as $entry)
		{
			$value   = (string) $entry['value'];
			$label   = (string) ($entry['label'] ?? $value);
			$display = $label === $value ? $value : sprintf('%s [%s]', $label, $value);

			$sheet->setCellValueExplicit(self::REFERENTIAL_VALUE_COLUMN . $row, $value, DataType::TYPE_STRING);
			$sheet->setCellValue(self::REFERENTIAL_LABEL_COLUMN . $row, $label);
			$sheet->setCellValueExplicit(self::REFERENTIAL_DISPLAY_COLUMN . $row, $display, DataType::TYPE_STRING);
			$row++;
		}

		foreach ([self::REFERENTIAL_VALUE_COLUMN, self::REFERENTIAL_LABEL_COLUMN, self::REFERENTIAL_DISPLAY_COLUMN] as $col)
		{
			$sheet->getColumnDimension($col)->setAutoSize(true);
		}

		return $title;
	}

	/**
	 * Sanitizes a desired sheet title (Excel forbids \ / ? * [ ] : and caps at
	 * 31 chars) and disambiguates collisions with a numeric suffix.
	 */
	private function uniqueSheetTitle(Spreadsheet $spreadsheet, string $desired): string
	{
		$clean = trim((string) preg_replace('/[\\\\\/?*\[\]:]/', ' ', $desired));
		$base  = substr($clean !== '' ? $clean : 'Ref', 0, 31);

		$title = $base;
		$index = 1;
		while ($spreadsheet->sheetNameExists($title))
		{
			$suffix = '_' . $index;
			$title  = substr($base, 0, 31 - strlen($suffix)) . $suffix;
			$index++;
		}

		return $title;
	}

	/**
	 * @param array<int, array{value: string, label?: string}> $entries
	 */
	private function buildListPromptText(array $entries): string
	{
		$lines = [];
		foreach ($entries as $entry)
		{
			$value = (string) $entry['value'];
			$label = (string) ($entry['label'] ?? $value);
			$lines[] = $label === $value ? $value : sprintf('%s (%s)', $value, $label);
		}

		return implode("\n", $lines);
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
