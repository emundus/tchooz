<?php
/**
 * @package     Tchooz\Services\Import\Report
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Report;

use Joomla\CMS\Language\Text;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Renders a stored import report (toStorableArray output) into a downloadable
 * XLSX listing every failed row: its number and the error messages.
 */
final class ImportReportExporter
{
	/**
	 * @param array  $report  Stored report (must contain 'failed_rows').
	 */
	public function toXlsx(array $report, string $path): void
	{
		$failedRows = $report['failed_rows'] ?? [];

		$headers = [
			Text::_('COM_EMUNDUS_IMPORT_ROW_NUMBER'),
			Text::_('COM_EMUNDUS_IMPORT_REPORT_ERRORS_COLUMN'),
		];

		$rows = [];
		foreach ($failedRows as $failedRow)
		{
			$messages = array_map(
				fn (array $error) => $this->formatError($error),
				$failedRow['errors'] ?? []
			);

			$rows[] = [$failedRow['row'] ?? '', implode(' | ', $messages)];
		}

		$spreadsheet = new Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();
		$sheet->fromArray($headers, null, 'A1');

		// Values originate from the imported file: write them as explicit strings
		// so a cell starting with =, +, -, @ can never be interpreted as a formula
		// by the reader (spreadsheet/formula injection).
		$line = 2;
		foreach ($rows as $row)
		{
			$sheet->setCellValueExplicit('A' . $line, (string) $row[0], DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('B' . $line, (string) $row[1], DataType::TYPE_STRING);
			$line++;
		}

		(new Xlsx($spreadsheet))->save($path);
	}

	/**
	 * Renders one error to text.
	 */
	private function formatError(array $error): string
	{
		$params = array_map(
			static fn ($param) => (is_string($param) && str_starts_with($param, 'COM_EMUNDUS_')) ? Text::_($param) : $param,
			$error['params'] ?? []
		);
		$message = Text::sprintf($error['code'], ...$params);

		if (isset($params[1]) && $params[1] !== '')
		{
			$message .= ' (' . Text::sprintf('COM_EMUNDUS_IMPORT_ERROR_RECEIVED_VALUE', $params[1]) . ')';
		}

		return $message;
	}
}
