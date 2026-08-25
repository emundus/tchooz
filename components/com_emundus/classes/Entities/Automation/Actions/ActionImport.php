<?php
/**
 * @package     Tchooz\Entities\Automation\Actions
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Import\ImportEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Repositories\Import\ImportRepository;
use Tchooz\Services\Import\EntityImporterRegistry;
use Tchooz\Services\Import\ImportOptions;
use Tchooz\Services\Import\ImportPipeline;
use Tchooz\Services\Import\Report\ImportReport;
use Tchooz\Services\Import\Source\ImportSourceFactory;

/**
 * Resumable, asynchronous entity import.
 *
 * Queued as a TaskEntity and run by the ExecuteEmundusActions scheduler plugin.
 */
class ActionImport extends ActionEntity
{
	/**
	 * Per-run time budget handed to the pipeline. Kept below the
	 * ExecuteEmundusActions plugin budget (30s) to leave room for the final
	 * flush + (de)serialization before the cron run is reclaimed.
	 */
	public const SLICE_TIME_BUDGET_SECONDS = 25;

	public static function getType(): string
	{
		return 'import';
	}

	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_AUTOMATION_ACTION_IMPORT');
	}

	public static function getIcon(): ?string
	{
		return 'upload';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	public static function isAsynchronous(): bool
	{
		return true;
	}

	/**
	 * The job is bound to an ImportEntity (via the task), not to a file/user
	 * target — so no target type is advertised.
	 */
	public static function supportTargetTypes(): array
	{
		return [];
	}

	public function getParameters(): array
	{
		return [];
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}

	/**
	 * Queued programmatically by the import controller, not picked from the
	 * automation UI.
	 */
	public function isAvailable(): bool
	{
		return false;
	}

	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		try
		{
			// Scheduler context: com_emundus strings are not loaded. Text::_() reads
			// Factory::getLanguage(), and the .ini lives in the component's own folder
			// — hence this base path.
			Factory::getLanguage()->load('com_emundus', JPATH_SITE . '/components/com_emundus');

			$importRepository = new ImportRepository();

			// The running task identifies which import to resume.
			$task = $this->getWithOfType(TaskEntity::class)[0] ?? null;
			if (empty($task))
			{
				Log::add('ActionImport executed without a task context.', Log::ERROR, 'com_emundus.import');
				return ActionExecutionStatusEnum::FAILED;
			}

			$import = $importRepository->getByTask($task->getId());
			if (empty($import))
			{
				Log::add('No import job linked to task ' . $task->getId() . '.', Log::ERROR, 'com_emundus.import');
				return ActionExecutionStatusEnum::FAILED;
			}

			if ($import->isCancelled())
			{
				return ActionExecutionStatusEnum::COMPLETED;
			}

			$fullPath = JPATH_SITE . '/' . $import->getFilename();
			if (empty($import->getFilename()) || !is_file($fullPath))
			{
				return $this->failWithGlobalError($import, $importRepository, Text::_('COM_EMUNDUS_IMPORT_SOURCE_FILE_MISSING'));
			}

			$registry = EntityImporterRegistry::default();
			if (!$registry->has($import->getType()))
			{
				return $this->failWithGlobalError($import, $importRepository, Text::sprintf('COM_EMUNDUS_IMPORT_NO_IMPORTER', $import->getType()));
			}

			$source   = ImportSourceFactory::fromFile($fullPath, $import->getFormat(), $import->getOriginalFilename());
			$importer = $registry->get($import->getType());

			$reachedEnd = false;
			$lastRow    = $import->getLastProcessedRow();

			$baseReport = $import->getReport();

			$options = new ImportOptions(
				userId: $import->getCreatedBy()->id,
				skipUntilRow: $import->getLastProcessedRow(),
				timeBudgetSeconds: self::SLICE_TIME_BUDGET_SECONDS,
				onCheckpoint: function (int $row, ImportReport $report, bool $completed) use (&$reachedEnd, &$lastRow) {
					$reachedEnd = $completed;
					$lastRow    = $row;
				},
				conflictMode: $import->getConflictMode(),
				onProgress: function (int $row, ImportReport $sliceReport) use ($import, $importRepository, $baseReport) {
					$total = $import->getTotalRows();
					$import->setLastProcessedRow($row);
					$import->setProgress($total > 0 ? min(99.0, round($row / $total * 100, 2)) : 0.0);
					$import->setReport(ImportReport::mergeStorable($baseReport, $sliceReport->toStorableArray()));
					$importRepository->flush($import);
				}
			);

			$sliceReport = (new ImportPipeline())->run($source, $importer, $options);

			$import->setReport(ImportReport::mergeStorable($baseReport, $sliceReport->toStorableArray()));
			$import->setLastProcessedRow($lastRow);

			if ($sliceReport->hasGlobalErrors())
			{
				$import->setFailed(true);
				$importRepository->flush($import);
				return ActionExecutionStatusEnum::FAILED;
			}

			$total = $import->getTotalRows();
			$import->setProgress(
				$reachedEnd
					? 100.0
					: ($total > 0 ? min(99.0, round($lastRow / $total * 100, 2)) : 0.0)
			);
			$importRepository->flush($import);

			return $reachedEnd
				? ActionExecutionStatusEnum::COMPLETED
				: ActionExecutionStatusEnum::PENDING;
		}
		catch (\Throwable $e)
		{
			Log::add('Import action failed: ' . $e->getMessage(), Log::ERROR, 'com_emundus.import');
			return ActionExecutionStatusEnum::FAILED;
		}
	}

	/**
	 * Marks the import as globally failed, records the reason in its cumulative
	 * report and persists it. Centralises the "stop the whole job" path.
	 */
	private function failWithGlobalError(ImportEntity $import, ImportRepository $importRepository, string $message): ActionExecutionStatusEnum
	{
		$errorReport = new ImportReport();
		$errorReport->addGlobalError($message);

		$import->setReport(ImportReport::mergeStorable($import->getReport(), $errorReport->toStorableArray()));
		$import->setFailed(true);
		$importRepository->flush($import);

		Log::add('Import job ' . $import->getId() . ' failed: ' . $message, Log::ERROR, 'com_emundus.import');

		return ActionExecutionStatusEnum::FAILED;
	}
}
