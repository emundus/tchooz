<?php
/**
 * @package     Tchooz\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

use EmundusHelperFiles;
use EmundusModelFormbuilder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportFactory
{
	private DatabaseInterface $db;

	private string $csv_filename;

	private array $valid_column_names = [
		'status',
		'group',
		'profile',
		'email',
		'username',
		'lastname',
		'firstname',
		'fnum'
	];

	private string $delimiter = ',';

	private string $encoding = 'UTF-8';

	private array $columns = [];

	private array $orig_columns = [];

	public function __construct(?array $file, ?DatabaseInterface $db = null)
	{
		if (!empty($db))
		{
			$this->db = $db;
		}

		if (!empty($file))
		{
			$tmp_directory = JPATH_ROOT . '/tmp/';

			// Only if file is not csv
			if (pathinfo($file['name'], PATHINFO_EXTENSION) != 'csv')
			{
				$spreadsheet = IOFactory::load($file['tmp_name']);

				$sheet              = $spreadsheet->getActiveSheet();
				$this->csv_filename = $tmp_directory . preg_replace('/[\/\\\\:*?"<>|]/', '_', $sheet->getTitle()) . '.csv';
				$this->saveSheetAsCsv($sheet, $this->csv_filename);
			}
			else
			{
				// copy the file to the tmp directory
				$this->csv_filename = $tmp_directory . preg_replace('/[\/\\\\:*?"<>|]/', '_', $file['name']);
				if (!copy($file['tmp_name'], $this->csv_filename))
				{
					throw new \Exception(Text::_('COM_EMUNDUS_ERROR_COPY_FILE'));
				}
			}

			$this->setEncoding();
		}
	}

	private function setEncoding(): void
	{
		$content = file_get_contents($this->csv_filename);
		$this->encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
	}

	public function getCsvFilename(): string
	{
		return $this->csv_filename;
	}

	public function setCsvFilename(string $csv_filename): void
	{
		$this->csv_filename = $csv_filename;
	}

	public function getDelimiter(): string
	{
		return $this->delimiter;
	}

	public function setCsvDelimiter($checkLines = 3): string
	{
		$delimiters = [",", ";", "\t"];

		$default = ",";

		$fileObject = new \SplFileObject($this->csv_filename);
		$results    = [];
		$counter    = 0;
		while ($fileObject->valid() && $counter <= $checkLines)
		{
			$line = $fileObject->fgets();
			foreach ($delimiters as $delimiter)
			{
				$fields      = explode($delimiter, $line);
				$totalFields = count($fields);
				if ($totalFields > 1)
				{
					if (!empty($results[$delimiter]))
					{
						$results[$delimiter] += $totalFields;
					}
					else
					{
						$results[$delimiter] = $totalFields;
					}
				}
			}
			$counter++;
		}
		if (!empty($results))
		{
			$results = array_keys($results, max($results));

			$this->delimiter = $results[0];
		}
		else
		{
			$this->delimiter = $default;
		}

		return $this->delimiter;
	}

	public function setColumns(): array
	{
		$csv  = fopen($this->csv_filename, 'r');
		$data = fgetcsv($csv, 0, $this->delimiter);

		if ($data !== false)
		{
			// Keep only string between [ and ]
			foreach ($data as $column_number => $column)
			{
				$orig_column_name = $column;
				if (preg_match('/\[(.*?)\]/', $column, $matches))
				{
					$column_name = $matches[1];
				}
				else
				{
					$column_name = $column;
				}

				// We have to check if the column name is valid and not already used
				if (in_array($column_name, $this->valid_column_names) && !in_array($column_name, $this->columns))
				{
					$this->columns[] = $column_name;
				}
				else
				{
					// We check if we have a table name after explode ___
					$column_name = explode('___', $column_name);
					if (count($column_name) > 1 && !in_array($column_name, $this->columns))
					{
						$this->columns[] = implode('___', $column_name);
					}
				}

				$this->orig_columns[] = $orig_column_name;
			}
		}

		return $this->columns;
	}

	public function getRowsToImport(): array
	{
		$rows = [];
		$csv  = fopen($this->csv_filename, 'r');
		fgetcsv($csv, 0, $this->delimiter);

		while (($data = fgetcsv($csv, 0, $this->delimiter)) !== false)
		{
			if (count($data) > 0)
			{
				// Check if not all values are empty
				$empty = true;
				foreach ($data as $value)
				{
					if (!empty($value))
					{
						$empty = false;
						break;
					}
				}

				if($empty) {
					continue;
				}

				$row = [];
				
				foreach ($this->orig_columns as $column_number => $column)
				{
					$row['orig_datas'][$column] = $data[$column_number];
				}

				foreach ($this->columns as $column_number => $column)
				{
					// Group rows by table name if we have one
					$column_name = explode('___', $column);

					if (count($column_name) > 1)
					{
						$table_name  = $column_name[0];
						$column_name = $column_name[1];
						if (!isset($row[$table_name]))
						{
							$row[$table_name] = [];
						}
						if (isset($data[$column_number]))
						{
							$row[$table_name][$column_name] = $data[$column_number];
						}
					}
					elseif (isset($data[$column_number]))
					{
						$row[$column] = $data[$column_number];
					}
				}

				$rows[] = $row;
			}
		}

		fclose($csv);

		return $rows;
	}

	public function formatDatas($datas): array
	{
		foreach ($datas as $table => &$elements)
		{
			foreach ($elements as &$element)
			{
				//If we find | explode element and create an array
				if (strpos($element, '|') !== false)
				{
					$element = explode('|', $element);

					foreach ($element as &$value)
					{
						// If we find [ in the value, we have to keep only the value between [ and ]
						if (preg_match('/\[(.*?)\]/', $value, $matches))
						{
							$value = $matches[1];
						}
						else
						{
							$value = trim($value);
						}
					}
				}
				else
				{
					if (preg_match('/\[(.*?)\]/', $element, $matches))
					{
						$element = (int) $matches[1];
					}
				}
			}
		}

		$datas = $this->sanitizeData($datas);

		return $datas;
	}

	private function sanitizeData(array $data): array
	{
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$data[$key] = $this->sanitizeData($value);
			} elseif (is_string($value)) {
				$data[$key] = mb_convert_encoding($value, 'UTF-8', $this->encoding);
			}
		}

		return $data;
	}

	private function saveSheetAsCsv(Worksheet $sheet, string $filename): bool
	{
		$file = fopen($filename, 'w');

		foreach ($sheet->getRowIterator() as $row)
		{
			$rowData = [];
			foreach ($row->getCellIterator() as $cell)
			{
				$rowData[] = $cell->getFormattedValue();
			}
			fputcsv($file, $rowData, ';');
		}

		return fclose($file);
	}

	public function generateXlsxModel(array $campaign, array $model_options): string
	{
		$xlsx_file = '';
		$query = $this->db->createQuery();
		
		try
		{
			if(!class_exists('EmundusModelFormbuilder'))
			{
				require_once(JPATH_BASE . '/components/com_emundus/models/formbuilder.php');
			}
			$m_formbuilder  = new EmundusModelFormbuilder();

			$campaign_label = $m_formbuilder->replaceAccents($campaign['label']);
			$campaign_label = preg_replace('/\s+|-/', '_', $campaign_label);

			$spreadsheet = new Spreadsheet();
			$dataSheet   = $spreadsheet->getActiveSheet();
			$dataSheet->setTitle(substr($campaign_label, 0, 31));

			$csv_directory = JPATH_ROOT . '/tmp/' . $campaign_label . '/';
			if (!is_dir($csv_directory))
			{
				mkdir($csv_directory, 0777, true);
			}

			if(!class_exists('EmundusHelperFiles'))
			{
				require_once(JPATH_BASE . '/components/com_emundus/helpers/files.php');
			}
			$elements = EmundusHelperFiles::getElements(array($campaign['training']), array($campaign['id']), [], null, $model_options['evaluations']);

			$cell = 'A';
			if ($model_options['status'])
			{
				$dataSheet->setCellValue($cell . '1', Text::_('COM_EMUNDUS_ACCESS_STATUS') . ' [status]');
				$query->clear()
					->select([$this->db->quoteName('step', 'value'), $this->db->quoteName('value', 'label')])
					->from($this->db->quoteName('#__emundus_setup_status'));
				$this->db->setQuery($query);
				$status = $this->db->loadObjectList();
				$this->createDataSheet($spreadsheet, Text::_('COM_EMUNDUS_STATUS'), $status, $cell, $model_options['validators']);

				$cell++;
			}

			$dataSheet->setCellValue($cell . '1', Text::_('COM_EMUNDUS_IMPORT_EMAIL_ADDRESS') . ' [email]');
			$cell++;
			$dataSheet->setCellValue($cell . '1', Text::_('COM_EMUNDUS_IMPORT_FIRSTNAME') . ' [firstname]');
			$cell++;
			$dataSheet->setCellValue($cell . '1', Text::_('COM_EMUNDUS_IMPORT_LASTNAME') . ' [lastname]');
			$cell++;

			foreach ($elements as $element)
			{
				$spreadsheet->setActiveSheetIndex(0);
				$dataSheet = $spreadsheet->getActiveSheet();

				if (!empty($element->table_join))
				{
					$dataSheet->setCellValue($cell . '1', $this->normalizeApostrophes($this->removeInvisibleCharacters($element->element_label)) . ' [' . $element->table_join . '___' . $element->element_name . ']');
				}
				else
				{
					$dataSheet->setCellValue($cell . '1', $this->normalizeApostrophes($this->removeInvisibleCharacters($element->element_label)) . ' [' . $element->fabrik_element . ']');
				}

				// If element plugin is radiobutton, dropdown, checkboxes or databasejoin
				switch ($element->element_plugin)
				{
					case 'radiobutton':
					case 'dropdown':
					case 'checkbox':
						$params = json_decode($element->element_attribs, true);

						if (!empty($params['sub_options']))
						{
							$options = [];
							foreach ($params['sub_options']['sub_values'] as $key => $value)
							{
								$option        = new \stdClass();
								$option->value = $value;
								$option->label = Text::_($params['sub_options']['sub_labels'][$key]);

								$options[] = $option;
							}

							$this->createDataSheet($spreadsheet, $element->element_label, $options, $cell, $model_options['validators']);
						}
						break;
					case 'databasejoin':
						$params = json_decode($element->element_attribs, true);

						$query->clear()
							->select([$this->db->quoteName($params['join_key_column'], 'value'), $this->db->quoteName($params['join_val_column'], 'label')])
							->from($this->db->quoteName($params['join_db_name']));
						$this->db->setQuery($query);
						$options = $this->db->loadObjectList();

						if (!empty($options))
						{
							$this->createDataSheet($spreadsheet, $element->element_label, $options, $cell, $model_options['validators']);
						}
						break;
				}

				$cell++;
			}

			$xlsx_file = $csv_directory . $campaign_label . '.xlsx';

			$writer = new Xlsx($spreadsheet);

			$writer->save($xlsx_file);
		}
		catch (\Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $xlsx_file;
	}

	private function createDataSheet(Spreadsheet $spreadsheet, string $label, array $options, string $cell, bool $validators = true): void
	{
		try
		{
			require_once(JPATH_BASE . '/components/com_emundus/models/formbuilder.php');
			$m_formbuilder = new EmundusModelFormbuilder();

			$dataSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet);

			$sheet_name = $m_formbuilder->replaceAccents($label);
			$newSheet      = $spreadsheet->addSheet($dataSheet);
			$newSheetIndex = $newSheet->getParent()->getIndex($newSheet);
			$spreadsheet->setActiveSheetIndex($newSheetIndex);

			$sheet_name = $newSheetIndex . '_' . $sheet_name;
			$sheet_name = preg_replace('/\s+|-/', '_', $sheet_name);
			$sheet_name = substr($sheet_name, 0, 31);
			$dataSheet->setTitle($sheet_name);

			$dataSheet->setCellValue('A1', Text::_('COM_EMUNDUS_ONBOARD_VALUES'));
			$dataSheet->setCellValue('B1', Text::_('COM_EMUNDUS_ONBOARD_LABEL'));
			$dataSheet->setCellValue('C1', Text::_('COM_EMUNDUS_ONBOARD_LABEL_VALUE'));

			$column = 2;
			foreach ($options as $option)
			{
				$dataSheet->setCellValue('A' . $column, $option->value);
				$dataSheet->setCellValue('B' . $column, $this->normalizeApostrophes($this->removeInvisibleCharacters($option->label)));
				$dataSheet->setCellValue('C' . $column, $this->normalizeApostrophes($this->removeInvisibleCharacters($option->label)) . ' [' . $option->value . ']');
				$column++;
			}

			$spreadsheet->setActiveSheetIndex(0);
			$dataSheet = $spreadsheet->getActiveSheet();

			if($validators)
			{
				$validation = $dataSheet->getCell($cell . '2')->getDataValidation();
				$validation->setType(DataValidation::TYPE_LIST);
				$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
				$validation->setAllowBlank(true);
				$validation->setShowInputMessage(true);
				$validation->setShowErrorMessage(false);
				$validation->setShowDropDown(true);
				$validation->setErrorTitle('Erreur de saisie');
				$validation->setError('La valeur n\'est pas dans la liste');
				$validation->setPromptTitle('Choisir dans la liste');
				$validation->setPrompt('Veuillez sélectionner une option dans la liste');
				$validation->setFormula1($sheet_name . '!$C$2:$C$' . (count($options) + 1));
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}
	}

	private function removeInvisibleCharacters(string $str): string
	{
		return preg_replace('/[\x00-\x1F\x7F\xA0\x{200B}\x{202F}]/u', '', $str);
	}

	private function normalizeApostrophes(string $str): string {
		return str_replace(
			["’", "‘", "‛", "`", "´"],
			"'",
			$str
		);
	}


	//TODO: Manage XML format
}