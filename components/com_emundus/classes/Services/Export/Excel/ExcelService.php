<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Excel;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\Upload\UploadFormatEnum;
use Tchooz\Services\Export\OptionsSchema\ExcelOptionsSchema;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Label\LabelRepository;
use Tchooz\Services\Export\Export;
use Tchooz\Services\Export\ExportInterface;
use Tchooz\Services\Export\ExportResult;
use Tchooz\Services\UploadService;

class ExcelService extends Export implements ExportInterface
{
	private array $fnums;

	private ?User $user;

	private ?ExcelOptions $options;

	private ?array $oldOptions;

	private ?ExportEntity $exportEntity;

	private ExportRepository $exportRepository;

	private ApplicationFileRepository $applicationFileRepository;

	const TIME_LIMIT = 30; //seconds

	const BATCH_SIZE = 200;

	public function __construct(array $fnums = [], User $user = null, array|object $options = null, ExportEntity $exportEntity = null)
	{
		Log::addLogger(['text_file' => 'com_emundus.service.export.php'], Log::ALL, ['com_emundus.service.export']);

		$this->fnums = $fnums;
		$this->user  = $user;

		if (empty($options))
		{
			$options = ['export_version' => 'next'];
		}

		if ($options['export_version'] === 'next')
		{
			if (is_array($options))
			{
				$options = (object) $options;
			}
			$this->options = !empty($options) ? ExcelOptions::fromObject($options) : new ExcelOptions();
			$this->oldOptions = null;
		}
		else
		{
			$this->oldOptions = $options;
			$this->options    = null;
		}

		$this->exportEntity = $exportEntity;
	}

	/**
	 * @param   string           $exportPath
	 * @param   TaskEntity|null  $task
	 * @param   string|null      $langCode
	 *
	 * @return ExportResult
	 * @throws \Exception
	 */
	public function export(string $exportPath, ?TaskEntity $task, ?string $langCode = 'fr-FR'): ExportResult
	{
		try
		{
			// Need to initialize parent only here because of langCode
			parent::__construct($langCode);

			$db            = Factory::getContainer()->get('DatabaseDriver');
			$query         = $db->getQuery(true);
			$exportVersion = !empty($this->options) ? $this->options->getExportVersion() : $this->oldOptions['export_version'];

			// Legacy Excel export is isolated in LegacyExcelService (cf. ARCHITECTURE.md §4, Phase 1).
			if ($exportVersion === 'default')
			{
				$legacyService = new LegacyExcelService($this->fnums, $this->user, $this->oldOptions, $this->exportEntity);

				return $legacyService->export($exportPath, $task, $langCode);
			}

			$this->registerClasses();

			$result = new ExportResult(false);
			if (empty($this->fnums) || empty($this->user) || empty($this->exportEntity))
			{
				return $result;
			}

			if (empty($task) && $this->exportRepository->isCancelled($this->exportEntity->getId()))
			{
				throw new \Exception('Export has been cancelled.');
			}

			if (!str_starts_with($exportPath, 'tmp/') && !str_starts_with($exportPath, 'images/emundus/'))
			{
				throw new \Exception('Forbidden export path.');
			}

			$metadata = [];
			if (!empty($task))
			{
				$metadata = $task->getMetadata();
			}

			// Next version export logic here
			{
				$json         = [];
				$jsonFileName = 'export_' . $this->exportEntity->getId() . '.json';
				$jsonFilePath = JPATH_SITE . '/' . $exportPath . $jsonFileName;

				// ----------------------------
				// 1) Load existing JSON if any
				// ----------------------------
				if (!empty($this->exportEntity->getFilename()) && str_ends_with($this->exportEntity->getFilename(), '.json'))
				{
					$jsonFilePath = JPATH_SITE . '/' . $this->exportEntity->getFilename();
					if (file_exists($jsonFilePath))
					{
						$jsonContent = file_get_contents($jsonFilePath);
						if ($jsonContent !== false)
						{
							$json = json_decode($jsonContent, true) ?? [];
						}
					}
				}

				// ----------------------------
				// 2) Init JSON if empty
				// ----------------------------
				if (empty($json))
				{
					$json = [
						'headers' => [],
						'files'   => [],
						'meta'    => [
							'fnums'      => [],
							'processed'  => 0,
							'batch_size' => self::BATCH_SIZE,
							'phase' 	=> 'synthesis',
							'synthesis_index' => 0,
							'element_index' => 0,
						],
					];

					// Save json file in export path
					$jsonContent = json_encode($json);
					file_put_contents($jsonFilePath, $jsonContent);

					$this->exportEntity->setFilename($exportPath . $jsonFileName);
					$this->exportRepository->flush($this->exportEntity);

					// ----------------------------
					// 3) Check access to files
					// ----------------------------
					$validFnums = [];
					$format     = ExportFormatEnum::XLSX;

					if (!class_exists('EmundusHelperAccess'))
					{
						require_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
					}

					foreach ($this->fnums as $fnum)
					{
						if (
							is_string($fnum) &&
							\EmundusHelperAccess::asAccessAction(
								$format->getAccessName(),
								CrudEnum::CREATE->value,
								$this->user->id,
								$fnum
							)
						)
						{
							$validFnums[] = $fnum;
						}
					}

					if (empty($validFnums))
					{
						Log::add('No valid files to export for export ID ' . $this->exportEntity->getId(), Log::WARNING, 'com_emundus.service.export');
						throw new \Exception('No valid files to export.');
					}

					if (Factory::getApplication()->isClient('site'))
					{
						Factory::getApplication()->setUserState('com_emundus.files.export.fnums', $validFnums);
					}

					$this->fnums = array_values($validFnums);

					// Store fnums list in json meta (so we can resume safely)
					$json['meta']['fnums']      = $this->fnums;
					$json['meta']['processed']  = 0;
					$json['meta']['batch_size'] = self::BATCH_SIZE;
				}
				else
				{
					// Ensure meta exists (backward compatible)
					if (empty($json['meta']))
					{
						$json['meta'] = [
							'fnums'      => $this->fnums,
							'processed'  => 0,
							'batch_size' => self::BATCH_SIZE,
						];
					}

					// If fnums are missing in json meta, restore from current request
					if (empty($json['meta']['fnums']))
					{
						$json['meta']['fnums'] = $this->fnums;
					}

					// Ensure processed exists
					if (!isset($json['meta']['processed']))
					{
						$json['meta']['processed'] = 0;
					}

					// Ensure batch size exists
					if (empty($json['meta']['batch_size']))
					{
						$json['meta']['batch_size'] = self::BATCH_SIZE;
					}

					// Ensure headers/files keys exist
					$json['headers'] = $json['headers'] ?? [];
					$json['files']   = $json['files'] ?? [];
				}

				// For safety, always use meta fnums as the source of truth
				$allFnums  = array_values($json['meta']['fnums']);
				$batchSize = (int) $json['meta']['batch_size'];
				$processed = (int) $json['meta']['processed'];

				$processStartTime = microtime(true);
				$atLeastOneProcessed = false;

				// Cancel check
				if (empty($task) && $this->exportRepository->isCancelled($this->exportEntity->getId()))
				{
					throw new \Exception('Export has been cancelled.');
				}

				// ----------------------------
				// 4) Build headers ONCE
				// ----------------------------
				if (empty($json['headers']))
				{
					// We'll build headers based on options only (labels)
					// But your getData() currently needs $files to compute data.
					// So we load a small sample (1 file) just for label resolution.
					$sampleFnums = array_slice($allFnums, 0, 1);
					$sampleFiles = $this->applicationFileRepository->getAll(['fnum' => $sampleFnums]);

					// 4.a) Synthesis headers
					$synthesisIds = $this->options->getSynthesis() ?? [];
					foreach ($synthesisIds as $synthesis)
					{
						$key = 'header_' . $synthesis;

						$customHeaderData      = $this->getData($synthesis, $sampleFiles);
						$json['headers'][$key] = $customHeaderData['label'] ?? ('header_' . $synthesis);
					}

					// 4.b) Elements headers
					$displayEvaluatorName = (bool) $this->options->getSetting(ExcelOptionsSchema::DISPLAY_EVALUATOR_NAME, true);
					$elementIds = $this->options->getElements() ?? [];
					foreach ($elementIds as $elementId)
					{
						$data = $this->getData($elementId, $sampleFiles);

						// evaluator column for evaluation tables
						if ($displayEvaluatorName && !empty($data['is_evaluation']) && !empty($data['db_table_name']))
						{
							$evaluatorElementId = 'evaluator_' . $data['db_table_name'];
							if (!array_key_exists($evaluatorElementId, $json['headers']))
							{
								$json['headers'][$evaluatorElementId] = Text::_('COM_EMUNDUS_EVALUATION_EVALUATOR');
							}
						}

						$json['headers'][$elementId] = $data['label'] ?? ('element_' . $elementId);
					}

					// Save immediately after headers creation
					file_put_contents($jsonFilePath, json_encode($json));
				}

				// ----------------------------
				// 5) Process fnums by chunks of 100
				// ----------------------------
				$totalFnums = count($allFnums);

				$synthesisIds         = $this->options->getSynthesis() ?? [];
				$elementIds           = $this->options->getElements() ?? [];
				$displayEvaluatorName = (bool) $this->options->getSetting(ExcelOptionsSchema::DISPLAY_EVALUATOR_NAME, true);

				while ($processed < $totalFnums)
				{
					if (
						(empty($task) && $this->exportRepository->isCancelled($this->exportEntity->getId())) ||
						($atLeastOneProcessed && (microtime(true) - $processStartTime) >= self::TIME_LIMIT)
					)
					{
						// Save state EXACTLY where we are
						$json['meta']['processed'] = $processed;
						$json['meta']['phase'] = $json['meta']['phase'] ?? 'synthesis';
						$json['meta']['synthesis_index'] = $json['meta']['synthesis_index'] ?? 0;
						$json['meta']['element_index'] = $json['meta']['element_index'] ?? 0;

						file_put_contents($jsonFilePath, json_encode($json));

						$result->setStatus(true);
						$result->setFilePath($exportPath . $jsonFileName);

						$progress = $this->computeProgress($json);

						// If progress is inferior due to calculation error or scope re-determination, keep old progress to avoid regressions in UI
						if ($progress > $result->getProgress())
						{
							$result->setProgress($progress);
						}

						return $result;
					}

					$chunkFnums = array_slice($allFnums, $processed, $batchSize);
					if (empty($chunkFnums))
					{
						break;
					}

					$files = $this->applicationFileRepository->getAll(['fnum' => $chunkFnums]);

					foreach ($chunkFnums as $fnum)
					{
						if (!isset($json['files'][$fnum]))
						{
							$json['files'][$fnum] = [];
						}
					}

					// ----------------------------
					// PHASE 1 : SYNTHESIS
					// ----------------------------
					if ($json['meta']['phase'] === 'synthesis')
					{
						for ($i = (int) $json['meta']['synthesis_index']; $i < count($synthesisIds); $i++)
						{
							if (
								(empty($task) && $this->exportRepository->isCancelled($this->exportEntity->getId())) ||
								($atLeastOneProcessed && (microtime(true) - $processStartTime) >= self::TIME_LIMIT)
							)
							{
								// Save state EXACTLY where we are
								$json['meta']['processed'] = $processed;
								$json['meta']['phase'] = 'synthesis';
								$json['meta']['synthesis_index'] = $i;

								file_put_contents($jsonFilePath, json_encode($json));

								$result->setStatus(true);
								$result->setFilePath($exportPath . $jsonFileName);

								$progress = $this->computeProgress($json);
								if ($progress > $result->getProgress())
								{
									$result->setProgress($progress);
								}

								return $result;
							}

							$synthesis = $synthesisIds[$i];
							$key = 'header_' . $synthesis;

							$customHeaderData = $this->getData($synthesis, $files);

							foreach ($files as $file)
							{
								$fnum = $file->getFnum();
								$json['files'][$fnum][$key] = $customHeaderData['data'][$fnum] ?? '';
							}

							// Move pointer forward
							$json['meta']['synthesis_index'] = $i + 1;
							$atLeastOneProcessed = true;
						}

						// Synthesis done for this chunk → switch to elements
						$json['meta']['phase'] = 'elements';
						$json['meta']['element_index'] = 0;
						$json['meta']['synthesis_index'] = 0;

						file_put_contents($jsonFilePath, json_encode($json));
					}

					// ----------------------------
					// PHASE 2 : ELEMENTS
					// ----------------------------
					if ($json['meta']['phase'] === 'elements')
					{
						for ($i = (int) $json['meta']['element_index']; $i < count($elementIds); $i++)
						{
							if (
								(empty($task) && $this->exportRepository->isCancelled($this->exportEntity->getId())) ||
								($atLeastOneProcessed && (microtime(true) - $processStartTime) >= self::TIME_LIMIT)
							)
							{
								// Save state EXACTLY where we are
								$json['meta']['processed'] = $processed;
								$json['meta']['phase'] = 'elements';
								$json['meta']['element_index'] = $i;

								file_put_contents($jsonFilePath, json_encode($json));

								$result->setStatus(true);
								$result->setFilePath($exportPath . $jsonFileName);

								$progress = $this->computeProgress($json);
								if ($progress > $result->getProgress())
								{
									$result->setProgress($progress);
								}

								return $result;
							}

							$elementId = $elementIds[$i];
							$data = $this->getData($elementId, $files);

							// evaluator column
							if ($displayEvaluatorName && !empty($data['is_evaluation']) && !empty($data['db_table_name']))
							{
								$evaluatorElementId = 'evaluator_' . $data['db_table_name'];

								foreach ($files as $file)
								{
									$db = Factory::getContainer()->get('DatabaseDriver');
									$query = $db->getQuery(true);

									$query->clear()
										->select('CASE WHEN u.name IS NULL THEN "' . Text::_("COM_EMUNDUS_UNKNOWN_EVALUATOR") . '" ELSE u.name END AS name')
										->from($db->quoteName($data['db_table_name'], 'd'))
										->leftJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('d.evaluator') . ' = ' . $db->quoteName('u.id'))
										->where($db->quoteName('d.fnum') . ' = ' . $db->quote($file->getFnum()))
										->order('d.evaluator ASC');

									$db->setQuery($query);
									$evaluatorsName = $db->loadColumn();

									$json['files'][$file->getFnum()][$evaluatorElementId] =
										!empty($evaluatorsName) ? implode(', ', $evaluatorsName) : '';
								}
							}

							foreach ($files as $file)
							{
								$fnum = $file->getFnum();
								$json['files'][$fnum][$elementId] = $data['data'][$fnum] ?? '';
							}

							// Move pointer forward
							$json['meta']['element_index'] = $i + 1;
							$atLeastOneProcessed = true;
						}

						// Elements done for this chunk → chunk finished
						$json['meta']['phase'] = 'synthesis';
						$json['meta']['synthesis_index'] = 0;
						$json['meta']['element_index'] = 0;

						// Move to next chunk
						$processed += count($chunkFnums);
						$json['meta']['processed'] = $processed;

						// Save after each chunk
						file_put_contents($jsonFilePath, json_encode($json));

						$progress = $this->computeProgress($json);
						if ($progress > $result->getProgress())
						{
							$result->setProgress($progress);
						}
					}
				}

				// ----------------------------
				// 6) Pivot processing — user picks a scope (form/group/element/evaluation)
				// and a target id within it; the processor expands each row into N rows
				// and groups them by fnum. Both must be set to apply pivot.
				// ----------------------------
				$pivotScope    = $this->options->getPivotScope();
				$pivotTargetId = $this->options->getPivotTargetId();
				if ($pivotScope !== null && $pivotTargetId !== null)
				{
					// Fresh FabrikRepository — the service's own instance carries stale
					// `$elementFilters` from earlier getData() calls and would narrow the
					// pivot lookups (see ExcelPivotProcessor doc).
					$pivotProcessor = new ExcelPivotProcessor();
					$json['files']  = $pivotProcessor->process($json['files'], $json['headers'], $pivotScope, $pivotTargetId);
				}

				// ----------------------------
				// 7) Finalization when all fnums processed
				// ----------------------------
				$csvFile = $this->fillCsv($exportPath, $json);

				$excelFilename = 'export';

				if (!$exportPath = self::convertToXlsx($csvFile, $excelFilename, $exportPath, count($json['files']), count($json['headers']), $this->exportEntity->getId()))
				{
					throw new \Exception('Excel conversion failed.');
				}

				// Delete temporary csv and json file
				unlink(JPATH_SITE . '/' . $csvFile);

				if (!empty($this->exportEntity->getFilename()) && str_ends_with($this->exportEntity->getFilename(), '.json'))
				{
					$jsonFilePath = JPATH_SITE . '/' . $this->exportEntity->getFilename();
					if (file_exists($jsonFilePath))
					{
						unlink($jsonFilePath);
					}
				}

				$result->setFilePath($exportPath);
				$result->setStatus(true);
				$result->setProgress(100);
			}
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		return $result;
	}

	public function computeProgress(array $json): float
	{
		$meta = $json['meta'] ?? [];

		$allFnums = $meta['fnums'] ?? [];
		$totalFnums = count($allFnums);
		$batchSize = (int) ($meta['batch_size'] ?? 100);

		if ($totalFnums === 0)
		{
			return 0.0;
		}

		$processed = (int) ($meta['processed'] ?? 0);

		// Nombre total de chunks à traiter
		$totalChunks = ceil($totalFnums / $batchSize);

		// Chunks entièrement terminés
		// ceil() car processed est incrémenté de la taille réelle du chunk (qui peut être < batchSize pour le dernier)
		$doneChunks = $batchSize > 0 ? min(ceil($processed / $batchSize), $totalChunks) : 0;

		// Fraction d'avancement dans le chunk courant basée sur les colonnes traitées
		$totalColumns = isset($json['headers']) ? count($json['headers']) : 0;
		$chunkFraction = 0.0;

		if ($totalColumns > 0 && $doneChunks < $totalChunks)
		{
			$phase = $meta['phase'] ?? 'synthesis';
			$synthesisIndex = (int) ($meta['synthesis_index'] ?? 0);
			$elementIndex = (int) ($meta['element_index'] ?? 0);
			$synthesisTotal = count($this->options->getSynthesis() ?? []);

			if ($phase === 'synthesis')
			{
				$doneColumnsInChunk = $synthesisIndex;
			}
			else
			{
				$doneColumnsInChunk = $synthesisTotal + $elementIndex;
			}

			$chunkFraction = min($doneColumnsInChunk / $totalColumns, 1.0);
		}

		$progress = (($doneChunks + $chunkFraction) / $totalChunks) * 100;

		return round(min($progress, 100.0), 2, PHP_ROUND_HALF_UP);
	}


	public function fillCsv(string $exportPath, array $json): string
	{
		// Fill csv file if all fnums are processed
		$csvFormat = UploadFormatEnum::CSV;

		$uploaderService = new UploadService($exportPath, 10, $csvFormat->getMimeTypes());

		$csvFile = $uploaderService->createTemporaryFile('export_', $csvFormat);

		// Add line for each fnum
		$handle = fopen(JPATH_SITE . '/' . $csvFile, 'w');
		if ($handle === false)
		{
			throw new \Exception('Failed to open export file for writing.');
		}

		// Fill header
		$header   = array_values($json['headers']);
		$inserted = fputcsv($handle, $header, '	');
		if ($inserted === false)
		{
			throw new \Exception('Failed to insert header into export file.');
		}

		// Fill rows
		foreach ($json['files'] as $file)
		{
			$row = [];
			foreach ($json['headers'] as $key => $label)
			{
				$row[] = $file[$key] ?? '';
			}

			$inserted = fputcsv($handle, $row, '	');
			if ($inserted === false)
			{
				throw new \Exception('Failed to insert row into export file.');
			}
		}

		fclose($handle);

		return $csvFile;
	}

	public static function convertToXlsx(string $csvPath, string $excelFilename, string $destinationPath, int $nbrow, int $nbcol, int $id = 0): bool|string
	{
		$converted = false;

		try
		{
			// Convert csv to xlsx
			require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

			$objReader = IOFactory::createReader("Csv");
			$objReader->setDelimiter("\t");
			$objPHPExcel = new Spreadsheet();

			// Excel colonne
			$colonne_by_id = array();
			for ($i = ord("A"); $i <= ord("Z"); $i++)
			{
				$colonne_by_id[] = chr($i);
			}

			for ($i = ord("A"); $i <= ord("Z"); $i++)
			{
				for ($j = ord("A"); $j <= ord("Z"); $j++)
				{
					$colonne_by_id[] = chr($i) . chr($j);
					if (count($colonne_by_id) == $nbrow) break;
				}
			}

			// Set properties
			$objPHPExcel->getProperties()->setCreator("eMundus SAS : https://www.emundus.fr/");
			$objPHPExcel->getProperties()->setLastModifiedBy("eMundus SAS");
			$objPHPExcel->getProperties()->setTitle("eMmundus Report");
			$objPHPExcel->getProperties()->setSubject("eMmundus Report");
			$objPHPExcel->getProperties()->setDescription("Report from open source eMundus plateform : https://www.emundus.fr/");
			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()->setTitle('Extraction');
			$objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

			$objPHPExcel->getActiveSheet()->freezePane('A2');

			$objReader->loadIntoExisting($csvPath, $objPHPExcel);

			$objConditional1 = new Conditional();
			$objConditional1->setConditionType(Conditional::CONDITION_CELLIS)
				->setOperatorType(Conditional::OPERATOR_EQUAL)
				->addCondition('0');
			$objConditional1->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');

			$objConditional2 = new Conditional();
			$objConditional2->setConditionType(Conditional::CONDITION_CELLIS)
				->setOperatorType(Conditional::OPERATOR_EQUAL)
				->addCondition('100');
			$objConditional2->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF00FF00');

			$objConditional3 = new Conditional();
			$objConditional3->setConditionType(Conditional::CONDITION_CELLIS)
				->setOperatorType(Conditional::OPERATOR_EQUAL)
				->addCondition('50');
			$objConditional3->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');

			$i = 0;
			//FNUM
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
			$objPHPExcel->getActiveSheet()->getStyle('A2:A' . ($nbrow + 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$i++;
			//STATUS
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
			$i++;
			//LASTNAME
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
			$i++;
			//FIRSTNAME
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
			$i++;
			//EMAIL
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
			//$objPHPExcel->getActiveSheet()->getStyle('E2:E'.($nbrow+ 1))->getNumberFormat()->setFormatCode( PHPExcel_Style_Font::UNDERLINE_SINGLE );
			$i++;
			//CAMPAIGN
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
			$i++;

			for ($i; $i < $nbcol; $i++)
			{
				$value = $objPHPExcel->getActiveSheet()->getCell(Coordinate::stringFromColumnIndex($i) . '1')->getValue();

				if (strpos($value, '(%)'))
				{
					$conditionalStyles = $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$i] . '1')->getConditionalStyles();
					array_push($conditionalStyles, $objConditional1);
					array_push($conditionalStyles, $objConditional2);
					array_push($conditionalStyles, $objConditional3);
					$objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$i] . '1')->setConditionalStyles($conditionalStyles);
					$objPHPExcel->getActiveSheet()->duplicateConditionalStyle($conditionalStyles, $colonne_by_id[$i] . '1:' . $colonne_by_id[$i] . ($nbrow + 1));
				}
				$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
			}

			$timestamp      = Factory::getDate()->format('Ymd_His');
			$excel_filename = $excelFilename . '_' . $nbrow . 'rows_' . $timestamp;
			if(!empty($id))
			{
				$excel_filename .= '_'.$id;
			}
			$excel_filename .= '.xlsx';
			// Check if export path directory exists
			if (!is_dir(JPATH_SITE . '/' . $destinationPath))
			{
				mkdir(JPATH_SITE . '/' . $destinationPath, 0755, true);
			}
			$destinationPath = $destinationPath . $excel_filename;

			$objWriter = IOFactory::createWriter($objPHPExcel, "Xlsx");

			$objWriter->save(JPATH_SITE . '/' . $destinationPath);
			$objPHPExcel->disconnectWorksheets();
			unset($objPHPExcel);

			return $destinationPath;
		}
		catch (\Exception $e)
		{
			Log::add('Excel conversion failed: ' . $e->getMessage(), Log::ERROR, 'com_emundus.service.export');

			return false;
		}
	}

	private function registerClasses(): void
	{
		$this->exportRepository          = new ExportRepository();
		$this->applicationFileRepository = new ApplicationFileRepository();

		$this->fabrikRepository = new FabrikRepository();
		$fabrikFactory          = new FabrikFactory($this->fabrikRepository);
		$this->fabrikRepository->setFactory($fabrikFactory);

		$this->campaignRepository = new CampaignRepository();
		$this->labelRepository    = new LabelRepository();

		$this->helperFabrik = new \EmundusHelperFabrik();
	}

	public static function getType(): string
	{
		return 'excel';
	}
}
