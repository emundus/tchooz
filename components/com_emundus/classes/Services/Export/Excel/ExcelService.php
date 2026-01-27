<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Excel;


use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Upload\UploadFormatEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Factories\TransformerFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Label\LabelRepository;
use Tchooz\Repositories\Task\TaskRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Services\Export\Export;
use Tchooz\Services\Export\ExportInterface;
use Tchooz\Services\Export\ExportResult;
use Tchooz\Services\Export\HeadersEnum;
use Tchooz\Services\UploadService;

class ExcelService extends Export implements ExportInterface
{
	private array $fnums;

	private ?User $user;

	private ?ExcelOptions $options;

	private ?array $oldOptions;

	private ?ExportEntity $exportEntity;

	private \EmundusModelFiles $m_files;

	private \EmundusModelApplication $m_application;

	private \EmundusModelEvaluation $m_evaluation;

	private \EmundusModelWorkflow $m_workflow;

	private \EmundusModelUsers $m_users;

	private \EmundusModelRanking $m_ranking;

	private TaskRepository $taskRepository;

	private ExportRepository $exportRepository;

	private ApplicationFileRepository $applicationFileRepository;

	private EmundusUserRepository $emundusUserRepository;

	private int $filesProcessed = 0;

	private int $totalExecutionTime = 0;

	const TIME_LIMIT = 20; //seconds

	public function __construct(array $fnums = [], User $user = null, array|object $options = null, ExportEntity $exportEntity = null)
	{
		$this->fnums = $fnums;
		$this->user  = $user;

		// TODO: Manage old version options
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

	public function export(string $exportPath, ?TaskEntity $task, ?string $langCode = 'fr-FR'): ExportResult
	{
		try
		{
			// Need to initialize parent only here because of langCode
			parent::__construct($langCode);

			$db            = Factory::getContainer()->get('DatabaseDriver');
			$query         = $db->getQuery(true);
			$exportVersion = !empty($this->options) ? $this->options->getExportVersion() : $this->oldOptions['export_version'];

			$this->registerClasses($exportVersion);

			$result = new ExportResult(false);
			if (empty($this->fnums) || empty($this->user) || empty($this->exportEntity))
			{
				return $result;
			}

			if (empty($task) && $this->exportRepository->isCancelled($this->exportEntity->getId()))
			{
				throw new \Exception('Export has been cancelled.');
			}

			$metadata = [];
			if (!empty($task))
			{
				$metadata = $task->getMetadata();
			}

			if ($exportVersion === 'default')
			{
				return $this->exportOld($exportPath, $metadata);
			}
			// Next verion export logic here
			else
			{
				$json = [];
				if (!empty($this->exportEntity->getFilename()) && str_ends_with($this->exportEntity->getFilename(), '.json'))
				{
					$jsonFilePath = JPATH_SITE . '/' . $this->exportEntity->getFilename();
					if (file_exists($jsonFilePath))
					{
						$jsonContent = file_get_contents($jsonFilePath);
						if ($jsonContent !== false)
						{
							$json = json_decode($jsonContent, true);
						}
					}
				}

				$files = $this->applicationFileRepository->getAll(['fnum' => $this->fnums]);

				if (empty($json))
				{
					$json['headers'] = [];
					foreach ($this->options->getSynthesis() as $synthesis)
					{
						$key = 'header_'.$synthesis;
						$customHeaderData = $this->getData($synthesis, $files);
							
						$json['headers'][$key] = $customHeaderData['label'];
						foreach ($files as $file)
						{
							$json['files'][$file->getFnum()][$key] = $customHeaderData['data'][$file->getFnum()];
						}
					}
					
					if (!empty($this->exportEntity))
					{
						// Store json file in export path
						$jsonFileName = 'export_' . $this->exportEntity->getId() . '.json';
						$jsonFilePath = JPATH_SITE . '/' . $exportPath . $jsonFileName;
						$jsonContent  = json_encode($json);
						file_put_contents($jsonFilePath, $jsonContent);
						$this->exportEntity->setFilename($exportPath . $jsonFileName);

						$this->exportRepository->flush($this->exportEntity);
					}
				}

				// Fill elements data
				$elementIds    = $this->options->getElements() ?? [];
				$totalElements = count($elementIds);

				$last_element_key = 0;
				$last_header      = $json['headers'] ? array_key_last($json['headers']) : null;
				if ($last_header)
				{
					$last_element_id = $last_header;
					if (str_starts_with($last_header, 'element_'))
					{
						$last_element_id = (int) str_replace('element_', '', $last_header);
					}

					$last_element_key = array_search($last_element_id, $elementIds);
					if ($last_element_key !== false)
					{
						$elementIds = array_slice($elementIds, $last_element_key + 1);
					}
				}

				$campaignMoreData = [];

				$processStartTime = microtime(true);
				foreach ($elementIds as $key => $elementId)
				{
					if (
						(microtime(true) - $processStartTime) >= self::TIME_LIMIT ||
						(empty($task) && $this->exportRepository->isCancelled($this->exportEntity->getId()))
					)
					{
						// Store json file in export path
						$jsonFileName = 'export_' . $this->exportEntity->getId() . '.json';
						$jsonFilePath = JPATH_SITE . '/' . $exportPath . $jsonFileName;
						$jsonContent  = json_encode($json);
						file_put_contents($jsonFilePath, $jsonContent);

						$result->setStatus(true);
						$result->setFilePath($exportPath . $jsonFileName);

						return $result;
					}

					$data = $this->getData($elementId, $files);

					// If data is from evaluation check if we have a evaluator_db_table_name
					if($data['is_evaluation'] && !array_key_exists(('evaluator_'.$data['db_table_name']), $json['headers']))
					{
						$evaluatorElementId = 'evaluator_'.$data['db_table_name'];

						$json['headers'][$evaluatorElementId] = Text::_('COM_EMUNDUS_EVALUATION_EVALUATOR');
						foreach ($files as $file)
						{
							// Get evaluators name
							$query->clear()
								->select('u.name')
								->from($db->quoteName('#__users', 'u'))
								->leftJoin($db->quoteName($data['db_table_name'], 'd') . ' ON ' . $db->quoteName('d.evaluator') . ' = ' . $db->quoteName('u.id'))
								->where($db->quoteName('d.fnum') . ' = ' . $db->quote($file->getFnum()))
								->order('d.evaluator ASC');
							$db->setQuery($query);
							$evaluatorsName = $db->loadColumn();

							$json['files'][$file->getFnum()][$evaluatorElementId] = !empty($evaluatorsName) ? implode(', ', $evaluatorsName) : '';
						}
					}

					$json['headers'][$elementId] = $data['label'];
					foreach ($files as $file)
					{
						$json['files'][$file->getFnum()][$elementId] = $data['data'][$file->getFnum()];
					}

					$progress = round((($key + 1 + $last_element_key + 1) / $totalElements) * 100, 2);
					$result->setProgress($progress);
				}

				$csvFile = $this->fillCsv($exportPath, $json);

				$excelFilename = 'export';

				if (!$exportPath = $this->convertToXlsx($csvFile, $excelFilename, $exportPath, count($json['files']), count($json['headers'])))
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

	private function convertToXlsx(string $csvPath, string $excelFilename, string $destinationPath, int $nbrow, int $nbcol): bool|string
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
			$excel_filename = $excelFilename . '_' . $nbrow . 'rows_' . $timestamp . '.xlsx';
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
			Log::add('Excel conversion failed: ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	private function registerClasses(string $exportVersion = 'default'): void
	{
		if ($exportVersion == 'default')
		{
			if (!class_exists('EmundusHelperAccess'))
			{
				require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
			}
			if (!class_exists('EmundusHelperFiles'))
			{
				require_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');
			}
			if (!class_exists('EmundusHelperFabrik'))
			{
				require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');
			}
			if (!class_exists('EmundusModelFiles'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/files.php';
			}
			if (!class_exists('EmundusModelApplication'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/application.php';
			}
			if (!class_exists('EmundusModelEvaluation'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/evaluation.php';
			}
			if (!class_exists('EmundusModelWorkflow'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/workflow.php';
			}
			if (!class_exists('EmundusModelUsers'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/users.php';
			}
			if (!class_exists('EmundusModelRanking'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/ranking.php';
			}

			$this->m_files       = new \EmundusModelFiles();
			$this->m_application = new \EmundusModelApplication();
			$this->m_evaluation  = new \EmundusModelEvaluation();
			$this->m_workflow    = new \EmundusModelWorkflow();
			$this->m_users       = new \EmundusModelUsers();
			$this->m_ranking     = new \EmundusModelRanking();
		}

		$this->taskRepository            = new TaskRepository();
		$this->exportRepository          = new ExportRepository();
		$this->applicationFileRepository = new ApplicationFileRepository();
		$this->emundusUserRepository     = new EmundusUserRepository();

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

	/**
	 * @param   string  $exportPath
	 * @param   array   $metadata
	 *
	 * @return ExportResult
	 *
	 * @throws \Exception
	 * @depecated use exportVersion "next" instead
	 */
	public function exportOld(string $exportPath, array $metadata): ExportResult
	{
		$processStartTime = microtime(true);

		$result = new ExportResult(false);

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$emConfig       = ComponentHelper::getParams('com_emundus');
		$evalCanSeeEval = $emConfig->get('evaluators_can_see_other_eval', 0);

		$anonymize_data = \EmundusHelperAccess::isDataAnonymized($this->user->id);

		$tmpFile = $this->oldOptions['tmp_file'] ?? '';
		if (empty($tmpFile))
		{
			return $result;
		}

		$totalfile = $this->oldOptions['totalfile'] ?? 0;
		$start     = $this->oldOptions['start'] ?? 0;
		$limit     = $this->oldOptions['limit'] ?? 0;
		$nbcol     = $this->oldOptions['nbcol'] ?? 0;
		$campaign  = $this->oldOptions['campaign'] ?? 0;

		$method = $this->oldOptions['method'] ?? 0;

		$step_elts = $this->oldOptions['step_elts'] ?? [];
		$col       = $this->getcolumn($this->oldOptions['elts'] ?? '{}');
		$colsup    = $this->getcolumn($this->oldOptions['objs'] ?? '{}');
		$colOpt    = array();

		$opts = $this->getcolumn($this->oldOptions['opts'] ?? '{}');
		// TODO: upper-case is mishandled, remove temporarily until fixed
		$opts = array_diff($opts, ['upper-case']);

		if (!$csv = fopen(JPATH_SITE . '/tmp/' . $tmpFile, 'a'))
		{
			throw new \Exception('Could not open temporary file for writing.');
		}

		$excel_filename = $this->oldOptions['excel_filename'] ?? 'export';

		$h_files  = new \EmundusHelperFiles();
		$elements = $h_files->getElementsName(implode(',', $col));

		// re-order elements
		$ordered_elements = array();
		foreach ($col as $c)
		{
			$ordered_elements[$c] = $elements[$c];
		}

		// Order elements to have jos_emundus_setup_campaigns_more and jos_emundus_setup_campaigns first
		$orders = [
			'jos_emundus_campaign_candidature' => 1,
			'jos_emundus_setup_campaigns'      => 2,
			'jos_emundus_setup_campaigns_more' => 3,
			'jos_emundus_setup_programmes'     => 4,
			'jos_users'                        => 5,
		];
		usort($ordered_elements, function ($a, $b) use ($orders) {
			$orderA = $orders[$a->tab_name] ?? PHP_INT_MAX; // Default to high value if not found
			$orderB = $orders[$b->tab_name] ?? PHP_INT_MAX;

			return $orderA <=> $orderB;
		});
		//

		$failed_with_old_method = false;
		if ($method == 2)
		{
			$fnumsArray = $this->m_files->getFnumArray($this->fnums, $ordered_elements, $method, $start, $limit, 0);
			if ($fnumsArray === false)
			{
				$failed_with_old_method = true;
			}
		}

		$not_already_handled_fnums = [];
		if ($method != 2 || $failed_with_old_method)
		{
			$not_already_handled_fnums = $this->fnums;
			$fnumsArray                = $this->m_files->getFnumArray2([$not_already_handled_fnums[0]], $ordered_elements, 0, $limit, $method, $this->user->id, $this->translations);
		}

		// Fill csv
		if (!empty($fnumsArray))
		{
			if (!empty($step_elts))
			{
				$evaluations_by_fnum_by_step = $this->m_files->getEvaluationsArray($this->fnums, $step_elts, ExportModeEnum::getFromId((int) $method));

				$step_element_ids = [];
				foreach ($step_elts as $step_id => $step_elements)
				{
					$step_element_ids = array_merge($step_element_ids, array_values($step_elements));
				}
				$step_elements_name = $h_files->getElementsName(implode(',', $step_element_ids));

				$ordered_elements[] = 'step_id';

				foreach ($step_elements_name as $element_id => $step_element_name)
				{
					$ordered_elements[$element_id] = $step_element_name;
				}

				$fnumsArray = $this->m_files->mergeEvaluations($fnumsArray, $evaluations_by_fnum_by_step, $step_elements_name, ExportModeEnum::getFromId((int) $method));
			}

			$fnums = array();
			foreach ($fnumsArray as $fnum)
			{
				$fnums[] = $fnum['fnum'];
			}

			$not_already_handled_fnums = array_diff($not_already_handled_fnums, $fnums);
			$this->fnums               = array_values($not_already_handled_fnums);

			if (!empty($task))
			{
				$targetToRemoves = [];
				foreach ($metadata['actionTargetEntities'] as $key => $target)
				{
					if (in_array($target['file'], $fnums))
					{
						$targetToRemoves[] = $key;
					}
				}

				foreach ($targetToRemoves as $key)
				{
					unset($metadata['actionTargetEntities'][$key]);
				}

				$metadata['actionTargetEntities'] = array_values($metadata['actionTargetEntities']);

				// Remove fnum
				//$metadata['actionTargetEntity']['parameters']['fnums'] = array_values($not_already_handled_fnums);
			}

			foreach ($colsup as $colsupkey => $col)
			{
				$col = explode('.', $col);

				switch ($col[0])
				{
					case "photo":
						if (!$anonymize_data)
						{
							$allowed_attachments = \EmundusHelperAccess::getUserAllowedAttachmentIDs($this->user->id);
							if ($allowed_attachments === true || in_array('10', $allowed_attachments))
							{
								$photos = $this->m_files->getPhotos($fnums);
								if (count($photos) > 0)
								{
									$pictures = array();
									foreach ($photos as $photo)
									{
										$folder                   = Uri::base() . EMUNDUS_PATH_REL . $photo['user_id'];
										$link                     = '=HYPERLINK("' . $folder . '/tn_' . $photo['filename'] . '","' . $photo['filename'] . '")';
										$pictures[$photo['fnum']] = $link;
									}
									$colOpt['PHOTO'] = $pictures;
								}
								else
								{
									$colOpt['PHOTO'] = array();
								}
							}
						}
						break;
					case "forms":
						foreach ($fnums as $fnum)
						{
							$formsProgress[$fnum] = $this->m_application->getFormsProgress($fnum);
						}
						if (!empty($formsProgress))
						{
							$colOpt['forms'] = $formsProgress;
						}
						break;
					case "attachment":
						foreach ($fnums as $fnum)
						{
							$attachmentProgress[$fnum] = $this->m_application->getAttachmentsProgress($fnum);
						}
						if (!empty($attachmentProgress))
						{
							$colOpt['attachment'] = $attachmentProgress;
						}
						break;
					case "assessment":
						$colOpt['assessment'] = $h_files->getEvaluation('text', $fnums);
						break;
					case "comment":
						$colOpt['comment'] = $this->m_files->getCommentsByFnum($fnums);
						break;
					case 'evaluators':
						$colOpt['evaluators'] = $h_files->createEvaluatorList($col[1], $this->m_files);
						break;
					case 'tags':
						$colOpt['tags'] = $this->m_files->getTagsByFnum($fnums);
						break;
					case 'group-assoc':
						$colOpt['group-assoc'] = $this->m_files->getAssocByFnums($fnums, true, false);
						break;
					case 'user-assoc':
						$colOpt['user-asoc'] = $this->m_files->getAssocByFnums($fnums, false, true);
						break;
					case 'overall':
						$evaluations_average_by_step = $this->m_evaluation->getEvaluationAverageBySteps($fnums, $this->user->id);

						foreach ($evaluations_average_by_step as $step_id => $average_by_fnum)
						{
							$step_data                     = $this->m_workflow->getStepData($step_id);
							$colsup['overall_' . $step_id] = Text::_('COM_EMUNDUS_EVALUATIONS_OVERALL') . ' ' . $step_data->label;

							foreach ($average_by_fnum as $fnum => $average)
							{
								$colOpt['overall_' . $step_id][$fnum] = (float) $average;
							}
						}

						unset($colsup[$colsupkey]);
						break;
				}
			}
			$status      = $this->m_files->getStatusByFnums($fnums);
			$line        = "";
			$element_csv = array();
			$i           = $start;

			// Here we filter elements which are already present but under a different name or ID, by looking at tablename___element_name.
			$elts_present        = [];
			$elements_by_aliases = [];
			foreach ($ordered_elements as $elt_id => $o_elt)
			{
				$element = !empty($o_elt->table_join) ? $o_elt->table_join . '___' . $o_elt->element_name : $o_elt->tab_name . '___' . $o_elt->element_name;
				if (in_array($element, $elts_present))
				{
					unset($ordered_elements[$elt_id]);
				}
				else
				{
					$elts_present[] = $element;

					$params = json_decode($o_elt->element_attribs);

					$elements_by_aliases[$element] = [];
					if (!empty($params->alias))
					{
						$elements_by_aliases[$element] = \EmundusHelperFabrik::getElementsByAlias($params->alias);
					}
				}
			}

			// On traite les en-têtes
			if ($start == 0)
			{

				if ($anonymize_data)
				{
					$line = Text::_('COM_EMUNDUS_FILE_F_NUM') . "\t" . Text::_('COM_EMUNDUS_STATUS') . "\t" . Text::_('COM_EMUNDUS_PROGRAMME') . "\t";
				}
				else
				{
					$line = Text::_('COM_EMUNDUS_FILE_F_NUM') . "\t" . Text::_('COM_EMUNDUS_STATUS') . "\t" . Text::_('COM_EMUNDUS_FORM_LAST_NAME') . "\t" . Text::_('COM_EMUNDUS_FORM_FIRST_NAME') . "\t" . Text::_('COM_EMUNDUS_EMAIL') . "\t" . Text::_('COM_EMUNDUS_PROGRAMME') . "\t";
				}

				$nbcol                = 6;
				$date_elements        = [];
				$birthday_elements    = [];
				$textarea_elements    = [];
				$iban_elements        = [];
				$calc_elements        = [];
				$currency_elements    = [];
				$masked_elements      = [];
				$phonenumber_elements = [];
				foreach ($ordered_elements as $fLine)
				{
					if ($fLine === 'step_id')
					{
						$line  .= Text::_('COM_EMUNDUS_EVALUATION_EVAL_STEP') . "\t";
						$line  .= Text::_('COM_EMUNDUS_EVALUATION_ID') . "\t";
						$line  .= Text::_('COM_EMUNDUS_EVALUATION_EVALUATOR') . "\t";
						$nbcol += 3;
						continue;
					}

					if ($fLine->element_name != 'fnum' && $fLine->element_name != 'code' && $fLine->element_label != 'Programme' && $fLine->element_name != 'campaign_id')
					{
						if (!(count($opts) == 1 && in_array("form-csv-only", $opts)) && count($opts) > 0 && $fLine->element_name != "date_time" && $fLine->element_name != "date_submitted")
						{
							if (in_array("form-title", $opts) && in_array("form-group", $opts))
							{
								$line .= $this->getTranslation($fLine->form_label) . " > " . $this->getTranslation($fLine->group_label) . " > " . preg_replace('#<[^>]+>|\t#', ' ', $this->getTranslation($fLine->element_label)) . "\t";
								$nbcol++;
							}
							elseif (count($opts) == 1)
							{
								if (in_array("form-title", $opts))
								{
									$line .= $this->getTranslation($fLine->form_label) . " > " . preg_replace('#<[^>]+>|\t#', ' ', $this->getTranslation($fLine->element_label)) . "\t";
									$nbcol++;
								}
								elseif (in_array("form-group", $opts))
								{
									$line .= $this->getTranslation($fLine->group_label) . " > " . preg_replace('#<[^>]+>|\t#', ' ', $this->getTranslation($fLine->element_label)) . "\t";
									$nbcol++;
								}
							}
						}
						else
						{
							$params   = json_decode($fLine->element_attribs);
							$elt_name = $fLine->tab_name . '___' . $fLine->element_name;
							if (!empty($fLine->table_join) && $fLine->table_join_key == 'parent_id')
							{
								$elt_name = $fLine->table_join . '___' . $fLine->element_name;
							}

							if (in_array($fLine->element_plugin, ['date', 'jdate']))
							{
								if ($fLine->element_plugin === 'jdate')
								{
									$date_elements[$elt_name] = $params->jdate_form_format;
								}
								else
								{
									$date_elements[$elt_name] = $params->date_form_format;
								}
							}

							if ($fLine->element_plugin === 'birthday')
							{
								$birthday_elements[] = $elt_name;
							}

							if ($fLine->element_plugin === 'textarea')
							{
								$textarea_elements[$elt_name] = $params->use_wysiwyg;
							}

							if ($fLine->element_plugin === 'iban')
							{
								$iban_elements[$elt_name] = $params->encrypt_datas;
							}
							if ($fLine->element_plugin === 'calc')
							{
								$calc_elements[] = $elt_name;
							}

							if ($fLine->element_plugin === 'currency')
							{
								$currency_elements[] = $elt_name;
							}

							if ($fLine->element_plugin === 'field' && !empty($params->text_input_mask))
							{
								$masked_elements[] = $elt_name;
							}

							if ($fLine->element_plugin === 'emundus_phonenumber')
							{
								$phonenumber_elements[] = $elt_name;
							}

							$line .= preg_replace('#<[^>]+>|\t#', ' ', $this->getTranslation($fLine->element_label)) . "\t";
							$nbcol++;
						}
					}
				}

				foreach ($colsup as $kOpt => $vOpt)
				{
					if ($vOpt == "forms" || $vOpt == "attachment")
					{
						$line .= Text::_('COM_EMUNDUS_' . strtoupper($vOpt)) . " (%)\t";
					}
					elseif ($vOpt == "overall")
					{
						$line .= Text::_('COM_EMUNDUS_EVALUATIONS_OVERALL') . "\t";
					}
					else
					{
						switch ($vOpt)
						{
							case 'comment':
								$line .= Text::_('COM_EMUNDUS_COMMENT') . "\t";
								break;
							case 'tags':
								$line .= Text::_('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_TAGS') . "\t";
								break;
							case 'group-assoc':
								$line .= Text::_('COM_EMUNDUS_ASSOCIATED_GROUPS') . "\t";
								break;
							case 'user-assoc':
								$line .= Text::_('COM_EMUNDUS_ASSOCIATED_USERS') . "\t";
								break;
							case 'ranking':
								// do nothing, handled later
								break;
							default:
								$line .= '"' . preg_replace("/\r|\n|\t/", "", $vOpt) . '"' . "\t";
								break;
						}
					}
					$nbcol++;
				}

				// On met les en-têtes dans le CSV
				$element_csv[] = $line;
				$line          = "";
			}
			else
			{
				// On définit les bons formats
				$date_elements        = [];
				$birthday_elements    = [];
				$textarea_elements    = [];
				$iban_elements        = [];
				$calc_elements        = [];
				$currency_elements    = [];
				$masked_elements      = [];
				$phonenumber_elements = [];
				foreach ($ordered_elements as $fLine)
				{
					$params   = json_decode($fLine->element_attribs);
					$elt_name = $fLine->tab_name . '___' . $fLine->element_name;
					if (!empty($fLine->table_join) && $fLine->table_join_key == 'parent_id')
					{
						$elt_name = $fLine->table_join . '___' . $fLine->element_name;
					}

					if (in_array($fLine->element_plugin, ['date', 'jdate']))
					{
						if ($fLine->element_plugin == 'jdate')
						{
							$date_elements[$elt_name] = $params->jdate_form_format;
						}
						else
						{
							$date_elements[$elt_name] = $params->date_form_format;
						}
					}

					if ($fLine->element_plugin === 'birthday')
					{
						$birthday_elements[] = $elt_name;
					}

					if ($fLine->element_plugin == 'textarea')
					{
						$textarea_elements[$elt_name] = $params->use_wysiwyg;
					}

					if ($fLine->element_plugin == 'iban')
					{
						$iban_elements[$elt_name] = $params->encrypt_datas;
					}
					if ($fLine->element_plugin == 'calc')
					{
						$calc_elements[] = $elt_name;
					}

					if ($fLine->element_plugin === 'currency')
					{
						$currency_elements[] = $elt_name;
					}

					if ($fLine->element_plugin === 'field' && !empty($params->text_input_mask))
					{
						$masked_elements[] = $elt_name;
					}

					if ($fLine->element_plugin === 'emundus_phonenumber')
					{
						$phonenumber_elements[] = $elt_name;
					}
				}
			}

			//check if evaluator can see others evaluators evaluations
			if (\EmundusHelperAccess::isEvaluator($this->user->id) && !\EmundusHelperAccess::isCoordinator($this->user->id))
			{
				$user      = $this->m_users->getUserById($this->user->id);
				$evaluator = $user[0]->lastname . " " . $user[0]->firstname;
				if ($evalCanSeeEval == 0 && !empty($objclass) && in_array("emundusitem_evaluation otherForm", $objclass))
				{
					foreach ($fnumsArray as $idx => $d)
					{
						foreach ($d as $k => $v)
						{
							if ($k === 'jos_emundus_evaluations___user' && strcasecmp($v, $evaluator) != 0)
							{
								foreach ($fnumsArray[$idx] as $key => $value)
								{
									if (substr($key, 0, 26) === "jos_emundus_evaluations___")
									{
										$fnumsArray[$idx][$key] = Text::_('COM_EMUNDUS_ACCESS_NO_RIGHT');
									}
								}
							}
						}
					}
				}
			}

			if (in_array('ranking', $colsup))
			{
				if ($this->m_ranking->isActivated())
				{
					$hierarchies = $this->m_ranking->getHierarchies();

					foreach ($hierarchies as $hierarchy)
					{
						$files_rankings = $this->m_ranking->getAllRankingsSuperAdmin($hierarchy['id'], 0, 0, [], [], [], '', '', 'ecc.fnum', 'ASC', $fnums);
						// add a header column for each hierarchy and another for the ranker
						$element_csv[0] .= Text::_('COM_EMUNDUS_RANKING_EXPORT_RANKING') . ' ' . $hierarchy['label'] . "\t";
						$element_csv[0] .= Text::_('COM_EMUNDUS_RANKING_EXPORT_RANKER') . ' ' . $hierarchy['label'] . "\t";

						foreach ($files_rankings as $ranking_row)
						{
							$fnumsArray[$ranking_row['fnum']]['ranking_' . $hierarchy['id']] = $ranking_row['rank'] !== -1 && !empty($ranking_row['rank']) ? $ranking_row['rank'] : Text::_('COM_EMUNDUS_RANKING_NOT_RANKED');
							$fnumsArray[$ranking_row['fnum']]['ranker_' . $hierarchy['id']]  = $ranking_row['ranker_name'];
						}

						if (!empty($hierarchy['form_id']))
						{
							$hierarchy_form_elements = $this->m_ranking->getHierarchyFormElements($hierarchy['form_id'], 'array');

							foreach ($hierarchy_form_elements as $form_element)
							{
								$element_csv[0] .= strip_tags($this->getTranslation($form_element['label'])) . "\t";

								foreach ($files_rankings as $ranking_row)
								{
									$element_id = $form_element['db_table_name'] . '___' . $form_element['element_name'];
									$value      = $this->m_files->getFabrikElementValue($form_element, $ranking_row['fnum']);

									if (isset($value[$form_element['id']][$ranking_row['fnum']]['val']))
									{
										$fnumsArray[$ranking_row['fnum']][$element_id] = $value[$form_element['id']][$ranking_row['fnum']]['val'];
									}
									else
									{
										$fnumsArray[$ranking_row['fnum']][$element_id] = '';
									}
								}
							}
						}
					}
				}
			}

			if (!empty($fnumsArray))
			{
				$encrypted_tables = $h_files->getEncryptedTables();
				if (!empty($encrypted_tables))
				{
					$cipher         = 'aes-128-cbc';
					$encryption_key = Factory::getConfig()->get('secret');
				}

				$emParams             = ComponentHelper::getParams('com_emundus');
				$excel_elts_to_escape = $emParams->get('export_elements_to_escape', '');
				if (!empty($excel_elts_to_escape) && is_array($excel_elts_to_escape))
				{
					$query->clear()
						->select('name')
						->from($db->quoteName('#__fabrik_elements'))
						->where($db->quoteName('id') . ' IN (' . implode(',', $excel_elts_to_escape) . ')');
					$db->setQuery($query);
					$excel_elts_to_escape = $db->loadColumn();
				}
				else
				{
					$excel_elts_to_escape = [];
				}

				// On parcours les fnums
				foreach ($fnumsArray as $fnum)
				{
					// On traite les données du fnum
					foreach ($fnum as $k => $v)
					{
						if ($k != 'code' && strpos($k, 'campaign_id') === false)
						{

							if ($k === 'fnum')
							{
								$line .= "'" . $v . "\t";
								$line .= $status[$v]['value'] . "\t";
								$uid  = intval(substr($v, 21, 7));
								if (!$anonymize_data)
								{
									$userProfil = $this->m_users->getUserById($uid)[0];

									if ($userProfil->is_anonym != 1)
									{
										$line .= $userProfil->lastname . "\t";
										$line .= $userProfil->firstname . "\t";
									}
									else
									{
										$line .= Text::_('COM_EMUNDUS_ANONYM_ACCOUNT') . "\t";
										$line .= Text::_('COM_EMUNDUS_ANONYM_ACCOUNT') . "\t";
									}
								}
							}
							else
							{
								// If file is linked to an other campaign than the one selected for export, we try to find value with aliases
								if (!empty($campaign) && empty($v) && $fnum['campaign_id'] != $campaign && !empty($elements_by_aliases[$k]))
								{
									// Maybe we can find a value with one of the alias
									foreach ($elements_by_aliases[$k] as $other_elt)
									{
										// Be sure that fnum column exists in this table
										$fnum_query = 'SHOW COLUMNS FROM ' . $db->quoteName($other_elt->db_table_name) . ' LIKE ' . $db->quote('fnum');
										$db->setQuery($fnum_query);
										$fnum_column = $db->loadResult();

										if (empty($fnum_column))
										{
											continue;
										}

										$query->clear()
											->select($db->quoteName($other_elt->name))
											->from($db->quoteName($other_elt->db_table_name))
											->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum['fnum']));
										$db->setQuery($query);
										$v = $db->loadResult();
										if (!empty($v))
										{
											break;
										}
									}
								}
								list($key_table, $key_element) = explode('___', $k);

								if ($v == "")
								{
									$line .= " " . "\t";
								}
								else
								{
									if (!empty($encrypted_tables))
									{
										if (!empty($key_table) && in_array($key_table, $encrypted_tables))
										{
											$decoded_value = json_decode($v, true);

											if (!empty($decoded_value))
											{
												$all_decrypted_data = [];
												foreach ($decoded_value as $decoded_sub_value)
												{
													$all_decrypted_data[] = \EmundusHelperFabrik::decryptDatas($decoded_sub_value);
												}

												$v = '[' . implode(',', $all_decrypted_data) . ']';
											}
											else
											{
												$v = \EmundusHelperFabrik::decryptDatas($v);
											}
										}
									}

									if ($v[0] == "=" || $v[0] == "-")
									{
										if (count($opts) > 0 && in_array("upper-case", $opts))
										{
											$line .= " " . mb_strtoupper($v) . "\t";
										}
										else
										{
											$line .= " " . preg_replace("/\t/", "", $v) . "\t";
										}
									}
									else
									{
										if (!empty($date_elements[$k]))
										{
											$v = str_replace("\\", '', $v); // if date contains \, remove it

											if (strpos($k, 'repeat'))
											{
												$v = explode(',', $v);

												$repeat_values = [];
												foreach ($v as $repeat_value)
												{
													if ($repeat_value === '0000-00-00 00:00:00')
													{
														$repeat_value = '';
													}
													else
													{
														// Trim and remove double quotes if any
														$repeat_value = trim($repeat_value, '" ');
														$repeat_value = date($date_elements[$k], strtotime($repeat_value));
													}
													$repeat_values[] = $repeat_value;
												}

												$v = implode(',', $repeat_values);
											}
											else
											{
												if ($v === '0000-00-00 00:00:00')
												{
													$v = '';
												}
												else
												{
													$v = date($date_elements[$k], strtotime($v));
												}
											}
											$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
										}
										elseif (!empty($birthday_elements) && in_array($k, $birthday_elements))
										{
											if (strpos($k, 'repeat'))
											{
												$v = explode(',', $v);

												$repeat_values = [];
												foreach ($v as $repeat_value)
												{
													// Trim and remove double quotes if any
													$repeat_value = trim($repeat_value, '" ');

													if ($repeat_value === '0000-00-00')
													{
														$repeat_value = '';
													}
													else
													{
														$repeat_value = date('d/m/Y', strtotime($repeat_value));
													}
													$repeat_values[] = $repeat_value;
												}

												$v = implode(',', $repeat_values);
											}
											else
											{
												// Trim and remove double quotes if any
												$v = trim($v, '" ');

												if ($v === '0000-00-00')
												{
													$v = '';
												}
												else
												{
													$v = date('d/m/Y', strtotime($v));
												}
											}
											$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
										}
										elseif (!empty($textarea_elements) && array_key_exists($k, $textarea_elements))
										{
											if ($textarea_elements[$k] == 1)
											{
												$v = strip_tags($v);
											}
											$line .= preg_replace("/\t/", "", $v) . "\t"; // limit preg_replace to keep linebreaks
										}
										elseif (!empty($iban_elements[$k]))
										{
											if ($iban_elements[$k] == 1)
											{
												if (strpos($k, 'repeat'))
												{
													$v = explode(',', $v);

													$repeat_values_decrypted = [];
													foreach ($v as $repeat_value)
													{
														$repeat_values_decrypted[] = \EmundusHelperFabrik::decryptDatas($repeat_value);
													}

													$v = implode(',', $repeat_values_decrypted);
												}
												else
												{
													$v = \EmundusHelperFabrik::decryptDatas($v);
												}
											}
											$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
										}
										elseif (in_array($k, $calc_elements))
										{
											$v    = strip_tags($v);
											$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
										}
										else
										{
											if (!empty($currency_elements) && in_array($k, $currency_elements))
											{
												$v    = \EmundusHelperFabrik::extractNumericValue($v);
												$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
											}
											else
											{
												if (!empty($masked_elements) && in_array($k, $masked_elements))
												{
													$v    = str_replace('_', '', $v);
													$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
												}
												else
												{
													if (!empty($phonenumber_elements) && in_array($k, $phonenumber_elements))
													{
														$v    = "'" . preg_replace('/^[a-zA-Z]{2}/', '', $v);
														$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
													}
													elseif (count($opts) > 0 && in_array("upper-case", $opts))
													{
														$line .= $this->getTranslation(preg_replace("/\r|\n|\t/", "", mb_strtoupper($v))) . "\t";
													}
													else
													{
														if (!empty($key_element) && in_array($key_element, $excel_elts_to_escape))
														{
															$line .= "'" . $this->getTranslation(preg_replace("/\r|\n|\t/", "", $v)) . "\t";
														}
														else
														{
															$line .= $this->getTranslation(preg_replace("/\r|\n|\t/", "", $v)) . "\t";
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
					// On ajoute les données supplémentaires
					foreach ($colOpt as $kOpt => $vOpt)
					{
						switch ($kOpt)
						{
							case "PHOTO":
							case "forms":
							case "attachment":
							case 'evaluators':
								if (array_key_exists($fnum['fnum'], $vOpt))
								{
									$line .= $vOpt[$fnum['fnum']] . "\t";
								}
								else
								{
									$line .= "\t";
								}
								break;

							case "assessment":
								$eval = '';
								if (array_key_exists($fnum['fnum'], $vOpt))
								{
									$evaluations = $vOpt[$fnum['fnum']];
									foreach ($evaluations as $evaluation)
									{
										$eval .= $evaluation;
										$eval .= chr(10) . '______' . chr(10);
									}
									$line .= $eval . "\t";
								}
								else
								{
									$line .= "\t";
								}
								break;

							case "comment":
								$comments = "";
								if (!empty($vOpt))
								{
									foreach ($colOpt['comment'] as $comment)
									{
										if ($comment['fnum'] == $fnum['fnum'])
										{
											$comments .= $comment['reason'] . " | " . $comment['comment_body'] . "\rn";
										}
									}
									$line .= $comments . "\t";
								}
								else
								{
									$line .= "\t";
								}
								break;

							case "tags":
								$tags = '';

								foreach ($colOpt['tags'] as $tag)
								{
									if ($tag['fnum'] == $fnum['fnum'] && (\EmundusHelperAccess::asAccessAction(14, 'r', $this->user->id, $fnum['fnum']) || (\EmundusHelperAccess::asAccessAction(14, 'c', $this->user->id, $fnum['fnum']) && $tag['user_id'] === $this->user->id)))
									{
										if (!empty($tags))
										{
											$tags .= ", ";
										}

										$tags .= $tag['label'];
									}
								}
								$line .= $tags . "\t";
								break;

							default:
								$line .= $vOpt[$fnum['fnum']] . "\t";
								break;
						}
					}
					// On met les données du fnum dans le CSV
					$element_csv[] = $line;
					$line          = "";
					$i++;
					$this->filesProcessed++;
				}
			}

			foreach ($element_csv as $data)
			{
				$res = fputcsv($csv, explode("\t", $data), "\t");
				if (!$res)
				{
					throw new \Exception(Text::_('COM_EMUNDUS_EXPORTS_ERROR_CANNOT_WRITE_IN_CSV_FILE'));
				}
			}
			if (!fclose($csv))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_EXPORTS_ERROR_CANNOT_CLOSE_CSV_FILE'));
			}

			$start                     = $i;
			$this->oldOptions['start'] = $start;

			if (!empty($task))
			{
				$metadata['actionEntity']['parameter_values']['start'] = $start;
			}

			$result->setStatus(true);
			$result->setFilePath('tmp/' . $tmpFile);

			$progress = ($start / $totalfile) * 100;
			if ($progress > 100)
			{
				$progress = 100;
			}
			$result->setProgress($progress);
		}

		if ($result->getProgress() === 100.00)
		{
			$csvPath = JPATH_SITE . '/tmp/' . $tmpFile;

			if (!$exportPath = $this->convertToXlsx($csvPath, $excel_filename, $exportPath, $totalfile, $nbcol))
			{
				$result->setStatus(false);

				return $result;
			}

			if (!unlink(JPATH_SITE . '/tmp/' . $tmpFile))
			{
				$result->setStatus(false);
			}

			$result->setFilePath($exportPath);
		}
		elseif (empty($task))
		{
			// Recall the export to finish it in one go
			$result = $this->export($exportPath, null);
		}
		else
		{
			$processEndTime           = microtime(true);
			$executionTime            = $processEndTime - $processStartTime;
			$this->totalExecutionTime += $executionTime;

			$filesCanBeProcessed = $this->oldOptions['files_can_be_processed'] ?? 0;
			if (empty($filesCanBeProcessed))
			{
				$filesCanBeProcessed                                                    = floor(self::TIME_LIMIT / $executionTime);
				$this->oldOptions['files_can_be_processed']                             = $filesCanBeProcessed;
				$metadata['actionEntity']['parameter_values']['files_can_be_processed'] = $filesCanBeProcessed;
			}

			// Estimate total time based on current progress
			$estimatedTotalTime = ($executionTime / $result->getProgress()) * 100;
			// multiply estimated time by number of chunks
			$estimatedTotalTime                                            *= ceil($totalfile / ($filesCanBeProcessed > 0 ? $filesCanBeProcessed : 1));
			$estimatedTotalTime                                            = (int) round($estimatedTotalTime);
			$this->oldOptions['time_estimate']                             = $estimatedTotalTime;
			$metadata['actionEntity']['parameter_values']['time_estimate'] = $estimatedTotalTime;

			if ($this->filesProcessed < $filesCanBeProcessed && $this->totalExecutionTime < self::TIME_LIMIT)
			{
				$result = $this->export($exportPath, $task);
			}

			if (!empty($metadata))
			{
				$task->setMetadata($metadata);
				$this->taskRepository->saveTask($task);
			}
		}

		/*$csvFormat = UploadFormatEnum::CSV;

		$uploaderService = new UploadService($exportPath, 10, $csvFormat->getMimeTypes());

		$csvFile = $uploaderService->createTemporaryFile('export_', $csvFormat);

		// Add line for each fnum
		$handle = fopen(JPATH_SITE . '/' . $csvFile, 'w');
		if ($handle === false)
		{
			throw new \Exception('Failed to open export file for writing.');
		}

		$applicationFileRepository = new ApplicationFileRepository();
		foreach ($this->fnums as $fnum)
		{
			$applicationFile = $applicationFileRepository->getByFnum($fnum);
			if(!empty($applicationFile))
			{
				$row = [$fnum];
				$row[] = $applicationFile->getUser()->name;
				$row[] = $applicationFile->getUser()->email;

				$inserted = fputcsv($handle, $row);
				if ($inserted === false)
				{
					throw new \Exception('Failed to insert row into export file.');
				}
			}
		}

		fclose($handle);*/

		return $result;
	}

	private function getcolumn($elts): array
	{
		return (array) json_decode(stripcslashes($elts));
	}
}