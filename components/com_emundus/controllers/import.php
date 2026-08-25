<?php

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Automation\Actions\ActionImport;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Import\ImportEntity;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Entities\List\AdditionalColumnTag;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Enums\Import\ImportStatusEnum;
use Tchooz\Enums\List\ListColumnTypesEnum;
use Tchooz\Enums\List\ListDisplayEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Repositories\Import\ImportRepository;
use Tchooz\Repositories\Task\TaskRepository;
use Tchooz\Services\Import\EntityImporterRegistry;
use Tchooz\Services\Import\ImportModelGenerator;
use Tchooz\Services\Import\ImportOptions;
use Tchooz\Services\Import\ImportPipeline;
use Tchooz\Services\Import\Report\ImportReportExporter;
use Tchooz\Services\Import\Source\ImportSourceFactory;
use Tchooz\Services\UploadService;

defined('_JEXEC') or die;

class EmundusControllerImport extends EmundusController
{
	/** Above this many data rows, the import is queued and processed in background slices. */
	private const ASYNC_ROWS_THRESHOLD = 300;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplicationInterface $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		Log::addLogger(['text_file' => 'com_emundus.import.php'], Log::ALL, array('com_emundus.import'));
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function getEntityImportInformation(): EmundusResponse
	{
		$response = EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);

		$type = $this->input->getString('type', '');

		if (!empty($type) && EmundusHelperAccess::asAccessAction($type, CrudEnum::CREATE->value, $this->app->getIdentity()->id))
		{
			$registry = EntityImporterRegistry::default();

			if ($registry->has($type))
			{
				$importer = $registry->get($type);
				$response = EmundusResponse::ok([
					'fields' => $importer->getColumnMap()->describe(),
					'conflictModesSupported' => array_map(fn($mode) => $mode->value, $importer->getSupportedModes()),
					'formatsSupported' => ImportSourceFactory::SUPPORTED_FORMATS
				]);
			}
			else
			{
				$response = EmundusResponse::fail(Text::_('NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
			}
		}

		return $response;
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function dryrun(): EmundusResponse
	{
		$uploadedFile = $this->input->files->get('file');
		if (empty($uploadedFile))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UPLOAD_ERROR'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
		if (!ImportSourceFactory::supports($ext))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UNSUPPORTED_FORMAT'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		try
		{
			$totalRows = ImportSourceFactory::countTotalRows($uploadedFile['tmp_name'], $ext, self::ASYNC_ROWS_THRESHOLD);
		}
		catch (Throwable $e)
		{
			Log::add('Failed to count rows of import file: ' . $e->getMessage(), Log::ERROR, 'com_emundus.import');

			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UPLOAD_ERROR'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		// countTotalRows returns the highest data row number, header (row 1) included.
		$dataRows = max(0, $totalRows - 1);

		// Below the async threshold the exists() lookups are cheap and would run on
		// the (synchronous) real import moments later anyway, so the preview runs
		// them to show the create / update / skip split. Above it the import is
		// queued regardless, and a full exists() pass would be expensive — so the
		// preview stops at validation and only reports the count of valid rows.
		$validateOnly = $dataRows > self::ASYNC_ROWS_THRESHOLD;

		return $this->runPipeline(dryRun: true, validateOnly: $validateOnly);
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function import(): EmundusResponse
	{
		$uploadedFile = $this->input->files->get('file');
		if (empty($uploadedFile))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UPLOAD_ERROR'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
		if (!ImportSourceFactory::supports($ext))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UNSUPPORTED_FORMAT'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		try
		{
			$totalRows = ImportSourceFactory::countTotalRows($uploadedFile['tmp_name'], $ext, self::ASYNC_ROWS_THRESHOLD);
		}
		catch (Throwable $e)
		{
			Log::add('Failed to count rows of import file: ' . $e->getMessage(), Log::ERROR, 'com_emundus.import');

			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UPLOAD_ERROR'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		// countTotalRows returns the highest data row number, header (row 1) included.
		$dataRows = max(0, $totalRows - 1);

		if ($dataRows > self::ASYNC_ROWS_THRESHOLD)
		{
			return $this->queueImport($totalRows);
		}

		return $this->runPipeline(dryRun: false);
	}

	private function runPipeline(bool $dryRun, bool $validateOnly = false): EmundusResponse
	{
		$type = $this->input->getString('type', '');
		if (empty($type) || !EmundusHelperAccess::asAccessAction($type, CrudEnum::CREATE->value, $this->app->getIdentity()->id))
		{
			return EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$mode = $this->input->getString('mode', 'skip');
		$mode = ImportConflictModeEnum::tryFrom(strtolower($mode)) ?? ImportConflictModeEnum::SKIP;


		$registry = EntityImporterRegistry::default();

		if (!$registry->has($type))
		{
			return EmundusResponse::fail(Text::_('NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
		}

		$importer = $registry->get($type);

		if (!in_array($mode, $importer->getSupportedModes(), true))
		{
			return EmundusResponse::fail(
				Text::sprintf('COM_EMUNDUS_IMPORT_MODE_NOT_SUPPORTED', $mode->value, $type),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}

		$uploadedFile = $this->input->files->get('file');

		if (empty($uploadedFile))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UPLOAD_ERROR'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

		if (!ImportSourceFactory::supports($ext))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UNSUPPORTED_FORMAT'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		try
		{
			$source   = ImportSourceFactory::fromFile($uploadedFile['tmp_name'], $ext, $uploadedFile['name']);
			$importer = $registry->get($type);

			if (!in_array($mode, $importer->getSupportedModes(), true)) {
				throw new \Exception(Text::_('COM_EMUNDUS_IMPORT_UNSUPPORTED_CONFLICT_MODE'));
			}

			$options = new ImportOptions(
				dryRun: $dryRun,
				userId: $this->app->getIdentity()->id,
				conflictMode: $mode,
				validateOnly: $validateOnly
			);

			$pipeline = new ImportPipeline();
			$report   = $pipeline->run($source, $importer, $options);

			return EmundusResponse::ok($report->toArray());
		}
		catch (Throwable $e)
		{
			Log::add(
				sprintf(
					'Import failed for type "%s" (mode "%s", dry run: %s): %s %s in %s:%d',
					$type,
					$mode->value,
					$dryRun ? 'yes' : 'no',
					get_class($e),
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				),
				Log::ERROR,
				'com_emundus.import'
			);

			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_IMPORT_UNEXPECTED_ERROR'),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}
	}

	#[AccessAttribute(AccessLevelEnum::COORDINATOR)]
	public function getimportmodel(): EmundusResponse
	{
		$type = $this->input->getString('type', '');

		if (empty($type) || !EmundusHelperAccess::asAccessAction($type, CrudEnum::READ->value, $this->app->getIdentity()->id))
		{
			return EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$registry = EntityImporterRegistry::default();
		if (!$registry->has($type))
		{
			return EmundusResponse::fail(Text::_('NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
		}

		try
		{
			$format  = $this->input->getString('format', 'csv') === 'xlsx' ? 'xlsx' : 'csv';
			$columns = $registry->get($type)->getColumnMap()->describeWithReferentials();

			return EmundusResponse::ok((new ImportModelGenerator())->build($type, $format, $columns));
		}
		catch (Throwable $e)
		{
			Log::add(
				sprintf(
					'Import model generation failed for type "%s": %s in %s:%d',
					$type,
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				),
				Log::ERROR,
				'com_emundus.import'
			);

			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_IMPORT_MODEL_GENERATION_ERROR'),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}
	}

	/**
	 * Persists the uploaded file to a durable location and queues a resumable
	 * ActionImport as a task. Returns the import id + task id; the actual import
	 * runs in the background (ExecuteEmundusActions scheduler plugin).
	 */
	private function queueImport(int $totalRows): EmundusResponse
	{
		$user   = $this->app->getIdentity();
		$userId = $user->id;

		$type = $this->input->getString('type', '');
		if (empty($type) || !EmundusHelperAccess::asAccessAction($type, CrudEnum::CREATE->value, $userId))
		{
			return EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$mode = ImportConflictModeEnum::tryFrom(strtolower($this->input->getString('mode', 'skip'))) ?? ImportConflictModeEnum::SKIP;

		$registry = EntityImporterRegistry::default();
		if (!$registry->has($type))
		{
			return EmundusResponse::fail(Text::_('NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
		}

		$uploadedFile = $this->input->files->get('file');
		if (empty($uploadedFile))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UPLOAD_ERROR'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
		if (!ImportSourceFactory::supports($ext))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UNSUPPORTED_FORMAT'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!in_array($mode, $registry->get($type)->getSupportedModes(), true))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UNSUPPORTED_CONFLICT_MODE'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$importRepository = new ImportRepository();
		$import           = null;

		try
		{
			$import = new ImportEntity(
				id: 0,
				createdAt: new \DateTime(),
				createdBy: $user,
				type: $type,
				filename: '',
				originalFilename: $uploadedFile['name'],
				format: $ext,
				conflictMode: $mode
			);
			if (!$importRepository->flush($import))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_IMPORT_QUEUE_FAILED'));
			}

			$durableName = bin2hex(random_bytes(16)) . '.' . $ext;
			try
			{
				$relativePath = (new UploadService('tmp/imports/'))
					->moveUploadedFileAs($uploadedFile, $durableName);
			}
			catch (\RuntimeException $e)
			{
				throw new \Exception(Text::_('COM_EMUNDUS_IMPORT_UPLOAD_ERROR'));
			}
			$action = new ActionImport();
			$task   = new TaskEntity(0, TaskStatusEnum::PENDING, null, $userId, [
				'actionEntity'       => $action->serialize(),
				'actionTargetEntity' => (new ActionTargetEntity($user, null, $userId))->serialize(),
			]);
			$task->setPriority($action->getPriority());

			$taskRepository = new TaskRepository();
			if (!$taskRepository->saveTask($task))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_IMPORT_QUEUE_FAILED'));
			}

			$import->setFilename($relativePath);
			$import->setTotalRows($totalRows);
			$import->setTask($task);
			if (!$importRepository->flush($import))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_IMPORT_QUEUE_FAILED'));
			}

			return EmundusResponse::ok(
				['import_id' => $import->getId(), 'task_id' => $task->getId()],
				Text::_('COM_EMUNDUS_IMPORT_QUEUED')
			);
		}
		catch (Throwable $e)
		{
			// Best-effort rollback of the half-created job so a failed queueing
			// doesn't leave an orphan row + file behind.
			if (!empty($import) && !empty($import->getId()))
			{
				$importRepository->delete($import->getId());
			}

			Log::add('Failed to queue import: ' . $e->getMessage(), Log::ERROR, 'com_emundus.import');

			return EmundusResponse::fail($e->getMessage(), EmundusResponse::HTTP_BAD_REQUEST);
		}
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function getimports(): EmundusResponse
	{
		try
		{
			$lim    = $this->input->getInt('lim', 0);
			$page   = $this->input->getInt('page', 0);
			$status = ImportStatusEnum::tryFrom($this->input->getString('status', 'all'));

			$sortDir = strtoupper($this->input->getString('sort', 'DESC'));
			if (!in_array($sortDir, ['ASC', 'DESC']))
			{
				$sortDir = 'DESC';
			}

			$repository = new ImportRepository();
			$imports    = $repository->getAll($lim, $page, $sortDir, $status, $this->app->getIdentity()->id);

			$importsSerialized = array_map(function (ImportEntity $importEntity): object {
				$createdAt = EmundusHelperDate::displayDate($importEntity->getCreatedAt()->format('Y-m-d H:i:s'), 'DATE_FORMAT_LC2', 0);

				$label = Text::sprintf('COM_EMUNDUS_IMPORT_LABEL', $createdAt);

				$import                    = new \stdClass();
				$import->id                = $importEntity->getId();
				$import->label             = ['fr' => $label, 'en' => $label];
				$import->original_filename = $importEntity->getOriginalFilename();
				$import->type              = $importEntity->getType();
				$import->progress          = $importEntity->getProgress();

				$statusEnum     = ImportStatusEnum::fromImport($importEntity);
				$import->status = $statusEnum->value;

				[$statusLabel, $statusClass] = match ($statusEnum)
				{
					ImportStatusEnum::PROCESSING => [Text::_('COM_EMUNDUS_IMPORTS_STATUS_PROCESSING') . ' (' . round($importEntity->getProgress()) . '%)', 'tw-bg-blue-500'],
					ImportStatusEnum::COMPLETED  => [Text::_('COM_EMUNDUS_IMPORTS_STATUS_COMPLETED'), 'tw-bg-green-500'],
					ImportStatusEnum::FAILED     => [Text::_('COM_EMUNDUS_IMPORTS_STATUS_FAILED'), 'tw-bg-red-500'],
					ImportStatusEnum::CANCELLED  => [Text::_('COM_EMUNDUS_IMPORTS_STATUS_CANCELLED'), 'tw-bg-neutral-500'],
				};

				$import->additional_columns = [
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_IMPORTS_TYPE'),
						'',
						ListDisplayEnum::ALL,
						'',
						Text::_('COM_EMUNDUS_IMPORT_TYPE_' . strtoupper($importEntity->getType())),
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_IMPORTS_FILE'),
						'',
						ListDisplayEnum::ALL,
						'',
						$importEntity->getOriginalFilename(),
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_IMPORTS_ROWS'),
						'',
						ListDisplayEnum::ALL,
						'',
						(string) max(0, $importEntity->getTotalRows() - 1),
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_IMPORTS_STATUS'),
						'',
						ListDisplayEnum::ALL,
						'',
						'',
						[new AdditionalColumnTag(
							Text::_('COM_EMUNDUS_IMPORTS_STATUS'),
							$statusLabel,
							(string) $importEntity->getProgress(),
							'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-text-white ' . $statusClass
						)],
						ListColumnTypesEnum::TAGS
					),
				];

				return $import;
			}, $imports->getItems());

			$response = EmundusResponse::ok(
				['datas' => $importsSerialized, 'count' => $imports->getTotalItems()],
				Text::_('COM_EMUNDUS_IMPORTS_RETRIEVED_SUCCESSFULLY')
			);
		}
		catch (Throwable $e)
		{
			Log::add('Failed to get imports list: ' . $e->getMessage(), Log::ERROR, 'com_emundus.import');
			$response = EmundusResponse::fail($e->getMessage(), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return $response;
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function getimportprogress(): EmundusResponse
	{
		$repository = new ImportRepository();
		$import     = $this->loadAccessibleImport($this->input->getInt('id', 0), $repository, $error);
		if (empty($import))
		{
			return $error;
		}

		return EmundusResponse::ok([
			'status'             => ImportStatusEnum::fromImport($import)->value,
			'progress'           => $import->getProgress(),
			'total_rows'         => $import->getTotalRows(),
			'last_processed_row' => $import->getLastProcessedRow(),
			'counts'             => $import->getReport()['summary'] ?? null,
		]);
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function getimportreport(): EmundusResponse
	{
		$repository = new ImportRepository();
		$import     = $this->loadAccessibleImport($this->input->getInt('id', 0), $repository, $error);
		if (empty($import))
		{
			return $error;
		}

		return EmundusResponse::ok([
			'status' => ImportStatusEnum::fromImport($import)->value,
			'report' => $import->getReport(),
		]);
	}

	/**
	 * Streams the failed rows of an import as an XLSX file. Generated on demand
	 * in the current request, so error messages come out in the reader's language.
	 */
	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function downloadreport(): EmundusResponse
	{
		$repository = new ImportRepository();
		$import     = $this->loadAccessibleImport($this->input->getInt('id', 0), $repository, $error);
		if (empty($import))
		{
			return $error;
		}

		$report = $import->getReport();
		if (empty($report['failed_rows']))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORTS_NO_REPORT'), EmundusResponse::HTTP_NOT_FOUND);
		}

		$tmpPath = tempnam($this->app->get('tmp_path', sys_get_temp_dir()), 'import_report_');
		(new ImportReportExporter())->toXlsx($report, $tmpPath);

		$filename = 'import_report_' . $import->getId() . '.xlsx';

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . filesize($tmpPath));
		header('Cache-Control: no-store');
		readfile($tmpPath);
		@unlink($tmpPath);

		// Terminate before EmundusController::execute() appends a JSON response.
		$this->app->close();

		return EmundusResponse::ok();
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function cancelimport(): EmundusResponse
	{
		$repository = new ImportRepository();
		$import     = $this->loadAccessibleImport($this->input->getInt('id', 0), $repository, $error);
		if (empty($import))
		{
			return $error;
		}

		// Co-operative cancellation: the running/next slice checks this flag and
		// stops cleanly. Rows already imported are kept.
		$import->setCancelled(true);
		if (!$repository->flush($import))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_CANCEL_FAILED'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_IMPORT_CANCELLED'));
	}

	/**
	 * Loads an import the current user is allowed to act on. On failure, fills
	 * $error with the proper response (404 / 403) and returns null.
	 */
	private function loadAccessibleImport(int $id, ImportRepository $repository, ?EmundusResponse &$error = null): ?ImportEntity
	{
		$error = null;

		if (empty($id))
		{
			$error = EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_INVALID_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
			return null;
		}

		$import = $repository->getById($id);
		if (empty($import))
		{
			$error = EmundusResponse::fail(Text::_('NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
			return null;
		}

		$userId = $this->app->getIdentity()->id;
		if ($import->getCreatedBy()->id !== $userId && !EmundusHelperAccess::asPartnerAccessLevel($userId))
		{
			$error = EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			return null;
		}

		return $import;
	}
}