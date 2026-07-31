<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Zip;

use Joomla\Filesystem\Folder;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Attachments\AttachmentType;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Services\Export\Export;
use Tchooz\Services\Export\ExportInterface;
use Tchooz\Services\Export\ExportResult;
use Tchooz\Services\Export\FilenameRenderer;
use Tchooz\Services\Export\HeadersEnum;
use Tchooz\Services\Export\Pdf\PdfMerger;
use Tchooz\Services\Export\Pdf\PdfOptions;
use Tchooz\Services\Export\Pdf\PdfService;
use ZipArchive;

/**
 * Bundles per-fnum application PDFs and their raw attachments into a single .zip archive.
 *
 * Each call to export() processes at most BATCH_SIZE fnums (or runs until TIME_LIMIT seconds when
 * a TaskEntity is provided), persisting incremental state to JSON. The plugins/task cron resumes
 * the export until every fnum has been handled, at which point the .zip is assembled.
 *
 * Composition:
 *   - PdfService builds the application PDF per fnum (forms + optional inline attachments)
 *   - PdfMerger merges that PDF with the optional evaluations PDF (still produced via the legacy
 *     EmundusHelperExport::buildFormPDF helper, as PdfService does not yet support evaluation steps)
 *   - FilenameRenderer resolves the per-fnum folder name shared with PdfService
 *   - UploadRepository / AttachmentTypeRepository surface the raw attachments and their setup metadata
 */
class ZipService extends Export implements ExportInterface
{
	/**
	 * Max number of fnums processed per export() invocation when running asynchronously.
	 */
	private const BATCH_SIZE = 5;

	/**
	 * Max wall time per export() invocation when running asynchronously, in seconds.
	 */
	private const TIME_LIMIT = 30;

	private array $fnums;

	private ?User $user;

	private ZipOptions $options;

	private ?ExportEntity $exportEntity;

	private ApplicationFileRepository $applicationFileRepository;

	private EmundusUserRepository $emundusUserRepository;

	private ExportRepository $exportRepository;

	private UploadRepository $uploadRepository;

	private AttachmentTypeRepository $attachmentTypeRepository;

	private FilenameRenderer $filenameRenderer;

	private PdfMerger $pdfMerger;

	private \EmundusModelApplication $applicationModel;

	public function __construct(array $fnums = [], User $user = null, array|object $options = null, ExportEntity $exportEntity = null)
	{
		$this->fnums = $fnums;
		$this->user  = $user;

		if (is_array($options))
		{
			$options = (object) $options;
		}
		$this->options      = !empty($options) ? ZipOptions::fromObject($options) : new ZipOptions();
		$this->exportEntity = $exportEntity;

		Log::addLogger(['text_file' => 'com_emundus.export.zip.php'], Log::ALL, 'com_emundus.export.zip');
	}

	public function export(string $exportPath, ?TaskEntity $task, ?string $langCode = 'fr-FR'): ExportResult
	{
		parent::__construct($langCode);
		$this->bootstrapDependencies();
		$this->assertExportPreconditions($exportPath, $task);

		$state         = $this->loadOrInitState($exportPath);
		$totalFnums    = count($state['fnums']);
		$pending       = array_slice($state['fnums'], $state['processed']);
		$processStart  = microtime(true);
		$processedNow  = 0;
		$isAsync       = !empty($task);
		$result        = new ExportResult(true);

		foreach ($pending as $fnum)
		{
			if (!$isAsync && $this->isCancelled())
			{
				throw new \Exception('Export has been cancelled.');
			}

			if ($isAsync && $processedNow > 0 && $this->shouldYield($processedNow, $processStart))
			{
				break;
			}

			$entry = $this->buildFnumEntry($fnum, $state['staging_path']);
			if ($entry !== null)
			{
				$state['entries'][$fnum] = $entry;
			}

			$state['processed']++;
			$processedNow++;

			$this->persistState($state);
			$result->setProgress(round(($state['processed'] / max(1, $totalFnums)) * 99, 2));
		}

		if ($state['processed'] >= $totalFnums)
		{
			$zipPath = $this->assembleZip($state, $exportPath);
			$result->setProgress(100.0);
			$result->setFilePath($zipPath);
			$this->cleanupStaging($state);
		}

		return $result;
	}

	public static function getType(): string
	{
		return 'zip';
	}

	private function bootstrapDependencies(): void
	{
		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}
		if (!class_exists('EmundusHelperExport'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/export.php';
		}
		if (!class_exists('EmundusModelApplication'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/application.php';
		}

		$this->applicationModel          = new \EmundusModelApplication();
		$this->applicationFileRepository = new ApplicationFileRepository();
		$this->emundusUserRepository     = new EmundusUserRepository();
		$this->exportRepository          = new ExportRepository();
		$this->uploadRepository          = new UploadRepository();
		$this->attachmentTypeRepository  = new AttachmentTypeRepository();
		$this->filenameRenderer          = new FilenameRenderer();
		$this->pdfMerger                 = new PdfMerger();
	}

	private function assertExportPreconditions(string $exportPath, ?TaskEntity $task): void
	{
		if (empty($this->fnums) || empty($this->user))
		{
			throw new \Exception('Missing fnums or user for ZIP export.');
		}

		if (empty($task) && $this->isCancelled())
		{
			throw new \Exception('Export has been cancelled.');
		}

		if (!str_starts_with($exportPath, 'tmp/') && !str_starts_with($exportPath, 'images/emundus/'))
		{
			throw new \Exception('Forbidden export path.');
		}
	}

	private function isCancelled(): bool
	{
		return $this->exportEntity !== null && $this->exportRepository->isCancelled($this->exportEntity->getId());
	}

	private function shouldYield(int $processedNow, float $processStart): bool
	{
		return $processedNow >= self::BATCH_SIZE || (microtime(true) - $processStart) >= self::TIME_LIMIT;
	}

	// -----------------------------------------------------------------------
	// State management
	// -----------------------------------------------------------------------

	/**
	 * State shape:
	 *   - base_name:    string     filename stem of the final archive (no extension)
	 *   - fnums:        string[]   ordered list of fnums to process (post-ACL filter)
	 *   - processed:    int        number of fnums already handled
	 *   - entries:      array<string, array{folder:string, pdf:?string, attachments:array, missing:string[]}>
	 *   - staging_path: string     absolute filesystem path under which per-fnum artefacts are gathered
	 *   - state_path:   string     JPATH-relative path of the JSON state file
	 */
	private function loadOrInitState(string $exportPath): array
	{
		return $this->tryResumeState() ?? $this->initState($exportPath);
	}

	private function tryResumeState(): ?array
	{
		$existingFilename = $this->exportEntity?->getFilename() ?? '';
		if ($existingFilename === '' || !str_ends_with($existingFilename, '.json'))
		{
			return null;
		}

		$jsonAbs = JPATH_SITE . '/' . $existingFilename;
		if (!file_exists($jsonAbs))
		{
			return null;
		}

		$decoded = json_decode(file_get_contents($jsonAbs), true);
		if (!is_array($decoded) || !isset($decoded['fnums'], $decoded['staging_path']))
		{
			return null;
		}

		$decoded['state_path'] = $existingFilename;

		return $decoded;
	}

	private function initState(string $exportPath): array
	{
		$validFnums = $this->filterAccessibleFnums($this->fnums);
		if (empty($validFnums))
		{
			throw new \Exception('No valid files to export.');
		}

		$stateId     = $this->exportEntity?->getId() ?: ('legacy_' . random_int(100000, 999999));
		$baseName    = date('Y-m-d') . '_' . random_int(1000, 9999) . '_x' . count($validFnums);
		$statePath   = $exportPath . 'export_' . $stateId . '.json';
		$stagingPath = JPATH_SITE . '/' . $exportPath . 'staging_' . $stateId . '/';

		if (!is_dir($stagingPath))
		{
			Folder::create($stagingPath);
		}

		$state = [
			'base_name'    => $baseName,
			'fnums'        => array_values($validFnums),
			'processed'    => 0,
			'entries'      => [],
			'staging_path' => $stagingPath,
			'state_path'   => $statePath,
		];

		if (!empty($this->exportEntity))
		{
			$this->exportEntity->setFilename($statePath);
			$this->exportRepository->flush($this->exportEntity);
		}

		$this->persistState($state);

		return $state;
	}

	private function persistState(array $state): void
	{
		file_put_contents(JPATH_SITE . '/' . $state['state_path'], json_encode($state));
	}

	private function cleanupStaging(array $state): void
	{
		if (!empty($state['staging_path']) && is_dir($state['staging_path']))
		{
			Folder::delete($state['staging_path']);
		}

		$statePath = JPATH_SITE . '/' . $state['state_path'];
		if (file_exists($statePath))
		{
			unlink($statePath);
		}
	}

	/**
	 * @return string[] fnums the user is allowed to export.
	 */
	private function filterAccessibleFnums(array $fnums): array
	{
		$accessName = ExportFormatEnum::ZIP->getAccessName();
		$valid      = [];

		foreach ($fnums as $fnum)
		{
			if (is_string($fnum) && \EmundusHelperAccess::asAccessAction($accessName, CrudEnum::CREATE->value, $this->user->id, $fnum))
			{
				$valid[] = $fnum;
			}
		}

		return $valid;
	}

	// -----------------------------------------------------------------------
	// Per-fnum entry building
	// -----------------------------------------------------------------------

	/**
	 * Compute everything that goes into the ZIP for a single fnum. Returns null when the fnum cannot
	 * be located (logs a warning) — the caller then skips it but the rest of the export proceeds.
	 *
	 * @param   string  $fnum
	 * @param   string  $stagingPath
	 *
	 * @return array|null
	 */
	private function buildFnumEntry(string $fnum, string $stagingPath): ?array
	{
		$applicationFile = $this->applicationFileRepository->getByFnum($fnum);
		if (empty($applicationFile))
		{
			Log::add('Application file not found for fnum ' . $fnum, Log::WARNING, 'com_emundus.export.zip');

			return null;
		}

		$emundusUser    = $this->emundusUserRepository->getByUserId($applicationFile->getUser()->id);
		$folderName     = $this->resolveFolderName($applicationFile, $emundusUser);
		$fnumStagingDir = $stagingPath . $folderName . '/';
		if (!is_dir($fnumStagingDir))
		{
			Folder::create($fnumStagingDir);
		}

		$pdfPath = $this->renderApplicationPdf($applicationFile, $folderName, $fnumStagingDir);

		$concatWithForm = $this->options->isConcatAttachmentsWithForm();

		if ($concatWithForm && $pdfPath === null)
		{
			$pdfPath = $this->renderAttachmentsOnlyPdf($applicationFile, $folderName, $fnumStagingDir);
		}

		[$attachments, $missing] = ($concatWithForm && $pdfPath !== null)
			? [[], []]
			: $this->collectRawAttachments($applicationFile, $folderName);

		return [
			'folder'      => $folderName,
			'pdf'         => $pdfPath,
			'attachments' => $attachments,
			'missing'     => $missing,
		];
	}

	private function resolveFolderName(ApplicationFileEntity $applicationFile, ?EmundusUserEntity $emundusUser): string
	{
		$template = $this->options->getFilename() ?: 'application_form_pdf';
		$rendered = $this->filenameRenderer->render($template, $applicationFile, $emundusUser);

		if ($rendered === '' || $rendered === 'application_form_pdf')
		{
			$applicantName = trim(($emundusUser?->getLastname() ?? '') . '_' . ($emundusUser?->getFirstname() ?? ''), '_');
			$fallback      = $applicantName !== '' ? $applicantName . '_' . $applicationFile->getFnum() : 'file_' . $applicationFile->getFnum();
			$rendered      = $this->filenameRenderer->render($fallback, $applicationFile, $emundusUser);
		}

		return $rendered !== '' ? $rendered : ('file_' . $applicationFile->getFnum());
	}

	// -----------------------------------------------------------------------
	// PDF rendering
	// -----------------------------------------------------------------------

	/**
	 * Render the per-fnum PDF via PdfService and, if eval-steps were requested, merge the evaluations
	 * PDF on top. Returns null when no PDF should be produced for this fnum (no forms and no eval steps
	 * configured) or when generation fails — failure is logged but does not abort the rest of the export.
	 */
	private function renderApplicationPdf(ApplicationFileEntity $applicationFile, string $folderName, string $fnumStagingDir): ?string
	{
		if ($this->options->getFormPost() === 0 && empty($this->options->getFormIds()) && empty($this->options->getEvalSteps()))
		{
			return null;
		}

		try
		{
			$mainPdfPath = $this->renderMainApplicationPdf($applicationFile, $folderName, $fnumStagingDir);
			if ($mainPdfPath === null)
			{
				return null;
			}

			$evalPdfPath = $this->renderEvaluationsPdf($applicationFile, $folderName, $fnumStagingDir);
			if ($evalPdfPath !== null)
			{
				$merged = $fnumStagingDir . $folderName . '_combined.pdf';
				if ($this->pdfMerger->merge([$mainPdfPath, $evalPdfPath], $merged))
				{
					@unlink($mainPdfPath);
					@unlink($evalPdfPath);
					$mainPdfPath = $merged;
				}
			}

			$finalPdf = $fnumStagingDir . $folderName . '.pdf';
			if ($mainPdfPath !== $finalPdf)
			{
				rename($mainPdfPath, $finalPdf);
			}

			return $finalPdf;
		}
		catch (\Throwable $e)
		{
			Log::add('Failed to render PDF for fnum ' . $applicationFile->getFnum() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.export.zip');

			return null;
		}
	}

	private function renderMainApplicationPdf(ApplicationFileEntity $applicationFile, string $folderName, string $fnumStagingDir): ?string
	{
		$pdfOptions = $this->buildPdfOptions($folderName);
		$pdfService = new PdfService([$applicationFile->getFnum()], $this->user, $pdfOptions);
		$pdfResult  = $pdfService->export($fnumStagingDir, null, $this->options->getLang());

		return $pdfResult->isStatus() && !empty($pdfResult->getFilePath()) ? $pdfResult->getFilePath() : null;
	}

	/**
	 * Concatenate the selected documents into a single PDF, without any application form.
	 *
	 * Used when "concat attachments with form" is enabled on a documents-only export: there is no
	 * form to merge into, so each selected attachment is rendered to PDF (converting non-PDF files)
	 * and the results are merged together. Returns null when the applicant uploaded none of the
	 * selected documents, so the caller falls back to staging them as raw files.
	 */
	private function renderAttachmentsOnlyPdf(ApplicationFileEntity $applicationFile, string $folderName, string $fnumStagingDir): ?string
	{
		$attachmentTypeIds = $this->options->getAttachments();
		if (empty($attachmentTypeIds))
		{
			return null;
		}

		try
		{
			$applicantId = $applicationFile->getUser()->id;
			$uploads     = $this->applicationModel->getAttachmentsByFnum($applicationFile->getFnum(), null, $attachmentTypeIds, null, $this->user->id);
			if (empty($uploads))
			{
				return null;
			}

			$files    = [];
			$tmpFiles = [];
			\EmundusHelperExport::getAttachmentPDF($files, $tmpFiles, $uploads, $applicantId, $this->options->isConvertDocxToPdf());

			if (empty($files))
			{
				return null;
			}

			$finalPdf = $fnumStagingDir . $folderName . '.pdf';
			if (count($files) === 1)
			{
				copy($files[0], $finalPdf);
			}
			elseif (!$this->pdfMerger->merge($files, $finalPdf))
			{
				$finalPdf = null;
			}

			foreach ($tmpFiles as $tmpFile)
			{
				@unlink($tmpFile);
			}

			return $finalPdf;
		}
		catch (\Throwable $e)
		{
			Log::add('Failed to render documents-only PDF for fnum ' . $applicationFile->getFnum() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.export.zip');

			return null;
		}
	}

	/**
	 * Cases eligible to appear in PdfOptions::$pageHeaders (the running header repeated on every
	 * page). Every other case ends up in PdfOptions::$headers (the info-dossier block, rendered
	 * on the first page only).
	 */
	private const PAGE_HEADER_CASES = [
		HeadersEnum::FULLNAME,
		HeadersEnum::EMAIL,
		HeadersEnum::FNUM,
	];

	private function buildPdfOptions(string $folderName): PdfOptions
	{
		[$pageHeaders, $firstPageHeaders, $displayHeader] = $this->resolvePdfHeaders();

		$payload = (object) [
			'campaign'           => $this->options->getCampaign(),
			'exportVersion'      => $this->options->getExportVersion(),
			'elements'           => implode(',', $this->options->getElements()),
			'lang'               => $this->options->getLang(),
			'displayHeader'      => $displayHeader,
			'attachments'        => $this->options->isConcatAttachmentsWithForm() ? $this->options->getAttachments() : [],
			'displayPageNumbers' => $this->options->isDisplayPageNumbers(),
		];

		$pdfOptions = PdfOptions::fromObject($payload);
		$pdfOptions->setHeaders($firstPageHeaders);
		$pdfOptions->setPageHeaders($pageHeaders);
		$pdfOptions->setFilename($folderName);

		return $pdfOptions;
	}

	/**
	 * Resolve the page header (every page) + first-page header (info-dossier) + displayHeader
	 * triplet fed to PdfOptions.
	 *
	 * Priority:
	 *   1. When explicit `headers` / `synthesis` are populated on ZipOptions (new endpoint), they
	 *      pass through unchanged — the caller already decided what belongs where.
	 *   2. Otherwise the legacy header-inclusion tokens (`aemail`, `afnum`, …, plus a `'0'`/`'1'`
	 *      toggle in position 0) are translated via HeadersEnum::fromLegacyOptions() and split by
	 *      PAGE_HEADER_CASES so legacy callers get a sensible default layout. The legacy toggle
	 *      also drives displayHeader.
	 *
	 * @return array Tuple [pageHeaders, firstPageHeaders, displayHeader].
	 */
	private function resolvePdfHeaders(): array
	{
		$displayHeader = $this->options->isDisplayHeader();
		$explicit      = $this->options->getHeaders();
		$synthesis     = $this->options->getSynthesis();

		if (!empty($explicit) || !empty($synthesis))
		{
			return [$explicit, $synthesis, $displayHeader];
		}

		$legacyOptions = $this->options->getLegacyHeaderOptions();
		if (empty($legacyOptions))
		{
			return [[], [], $displayHeader];
		}

		[$pageHeaders, $firstPageHeaders] = $this->splitByRendering(HeadersEnum::fromLegacyOptions($legacyOptions));
		$displayHeader                    = $displayHeader && HeadersEnum::isLegacyHeaderEnabled($legacyOptions);

		return [$pageHeaders, $firstPageHeaders, $displayHeader];
	}

	/**
	 * Split a list of HeadersEnum cases between the every-page running header and the first-page
	 * info-dossier block, following PAGE_HEADER_CASES. Used only for the legacy translation path —
	 * explicit headers from the new endpoint pass through resolvePdfHeaders() unchanged.
	 *
	 * @param   array<HeadersEnum>  $cases
	 *
	 * @return array
	 */
	private function splitByRendering(array $cases): array
	{
		$pageHeaders      = [];
		$firstPageHeaders = [];

		foreach ($cases as $case)
		{
			if (in_array($case, self::PAGE_HEADER_CASES, true))
			{
				$pageHeaders[$case->value] = $case->value;
			}
			else
			{
				$firstPageHeaders[$case->value] = $case->value;
			}
		}

		return [array_values($pageHeaders), array_values($firstPageHeaders)];
	}

	/**
	 * Optional evaluations PDF for the configured eval_steps. Still uses the legacy
	 * EmundusHelperExport::buildFormPDF helper because PdfService does not yet support evaluation
	 * step rendering — TODO: lift this into PdfService once it gains an EvalElements descriptor.
	 */
	private function renderEvaluationsPdf(ApplicationFileEntity $applicationFile, string $folderName, string $fnumStagingDir): ?string
	{
		$evalSteps = $this->options->getEvalSteps();
		if (empty($evalSteps) || (empty($evalSteps['tables']) && empty($evalSteps['groups']) && empty($evalSteps['elements'])))
		{
			return null;
		}

		try
		{
			$fnumInfos = $this->buildLegacyFnumInfos($applicationFile);
			$elements  = [[
				'fids' => $evalSteps['tables'] ?? [],
				'gids' => $evalSteps['groups'] ?? [],
				'eids' => $evalSteps['elements'] ?? [],
			]];

			$legacyOptions   = $this->options->getLegacyHeaderOptions();
			$legacyOptions[] = 'eval_steps';

			$evalPath = \EmundusHelperExport::buildFormPDF(
				$fnumInfos,
				$applicationFile->getUser()->id,
				$applicationFile->getFnum(),
				0,
				$evalSteps['tables'] ?? [],
				$legacyOptions,
				null,
				$elements,
				false,
				'_evaluations'
			);

			if (empty($evalPath) || !file_exists($evalPath))
			{
				return null;
			}

			$dest = $fnumStagingDir . $folderName . '_evaluations.pdf';
			copy($evalPath, $dest);

			return $dest;
		}
		catch (\Throwable $e)
		{
			Log::add('Failed to render evaluations PDF for fnum ' . $applicationFile->getFnum() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.export.zip');

			return null;
		}
	}

	/**
	 * Shape an array that mimics EmundusModelFiles::getFnumsInfos() for a single fnum, so we can keep
	 * calling EmundusHelperExport::buildFormPDF until it speaks entities.
	 */
	private function buildLegacyFnumInfos(ApplicationFileEntity $applicationFile): array
	{
		$emundusUser = $this->emundusUserRepository->getByUserId($applicationFile->getUser()->id);

		return [
			'fnum'         => $applicationFile->getFnum(),
			'applicant_id' => $applicationFile->getUser()->id,
			'training'     => $applicationFile->getCampaign()->getProgram()->getCode(),
			'campaign_id'  => $applicationFile->getCampaign()->getId(),
			'year'         => $applicationFile->getCampaign()->getYear(),
			'is_anonym'    => $emundusUser?->isAnonym() ? 1 : 0,
			'name'         => ($emundusUser?->getLastname() ?? '') . '_' . ($emundusUser?->getFirstname() ?? ''),
		];
	}

	// -----------------------------------------------------------------------
	// Raw attachments
	// -----------------------------------------------------------------------

	/**
	 * @return array Tuple of [files-to-zip, missing-placeholders].
	 */
	private function collectRawAttachments(ApplicationFileEntity $applicationFile, string $folderName): array
	{
		$attachmentTypeIds = $this->resolveAttachmentTypeIds($applicationFile);

		if ($this->options->getAttachmentDefault() === 0 && empty($attachmentTypeIds))
		{
			return [[], []];
		}

		$filters = ['fnum' => $applicationFile->getFnum()];
		if (!empty($attachmentTypeIds))
		{
			$filters['attachment_id'] = $attachmentTypeIds;
		}

		$uploads = $this->uploadRepository->get($filters);
		$home    = EMUNDUS_PATH_ABS . $applicationFile->getUser()->id . DIRECTORY_SEPARATOR;

		$entries = [];
		$missing = [];
		$present = [];

		foreach ($uploads as $upload)
		{
			assert($upload instanceof UploadEntity);
			$present[] = $upload->getAttachmentId();

			$source = $home . $upload->getFilename();
			$name   = $folderName . '/' . $upload->getFilename();

			if (file_exists($source))
			{
				$entries[] = ['source' => $source, 'name' => $name];
			}
			else
			{
				$missing[] = $name . '-missing.txt';
			}
		}

		$missing = array_merge($missing, $this->collectMissingTypePlaceholders($attachmentTypeIds, $present, $folderName));

		return [$entries, $missing];
	}

	/**
	 * @return string[] '.txt' placeholders for attachment types that the applicant never uploaded.
	 * TODO: Ask team about the value of these placeholders vs just silently skipping missing attachments
	 */
	private function collectMissingTypePlaceholders(array $attachmentTypeIds, array $presentTypeIds, string $folderName): array
	{
		if (empty($attachmentTypeIds))
		{
			return [];
		}

		$types = $this->attachmentTypeRepository->get(['id' => $attachmentTypeIds]);
		if (empty($types))
		{
			return [];
		}

		$presentFlipped = array_flip($presentTypeIds);
		$placeholders   = [];

		foreach ($types as $type)
		{
			assert($type instanceof AttachmentType);
			if (!isset($presentFlipped[$type->getId()]))
			{
				$placeholders[] = $folderName . '/' . str_replace('_', '', $type->getLbl()) . '-notfound.txt';
			}
		}

		return $placeholders;
	}

	/**
	 * Decode the legacy "attachmentId|trainingCode|campaignId" tokens to the attachment-type IDs
	 * actually applicable to this fnum's training & campaign.
	 *
	 * @return int[]
	 */
	private function resolveAttachmentTypeIds(ApplicationFileEntity $applicationFile): array
	{
		$attachIds = $this->options->getAttachIds();
		if (empty($attachIds))
		{
			return [];
		}

		$training   = $applicationFile->getCampaign()->getProgram()->getCode();
		$campaignId = (string) $applicationFile->getCampaign()->getId();

		$resolved = [];
		foreach ($attachIds as $token)
		{
			$parts = explode('|', (string) $token);
			if (count($parts) < 3)
			{
				continue;
			}

			[$attachmentId, $tokenTraining, $tokenCampaign] = $parts;
			if ($tokenTraining === $training && ($tokenCampaign === $campaignId || $tokenCampaign === '0'))
			{
				$resolved[] = (int) $attachmentId;
			}
		}

		return array_values(array_unique($resolved));
	}

	// -----------------------------------------------------------------------
	// ZIP assembly
	// -----------------------------------------------------------------------

	private function assembleZip(array $state, string $exportPath): string
	{
		$zipFilename = $state['base_name'] . '.zip';
		$zipAbsPath  = JPATH_SITE . '/' . $exportPath . $zipFilename;
		$zip         = new ZipArchive();

		if (file_exists($zipAbsPath))
		{
			unlink($zipAbsPath);
		}

		if ($zip->open($zipAbsPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
		{
			throw new \Exception('Failed to open ZIP archive for writing at ' . $zipAbsPath);
		}

		foreach ($state['entries'] as $entry)
		{
			$this->addEntryToZip($zip, $entry);
		}

		// An archive with zero entries is never written to disk by ZipArchive::close().
		// Returning its path anyway would store a phantom filename whose download serves an
		// HTML 404 page instead of the .zip, so fail loudly instead.
		if ($zip->numFiles === 0)
		{
			$zip->close();
			Log::add('ZIP archive is empty (no PDF/attachment could be added) for ' . $zipAbsPath, Log::ERROR, 'com_emundus.export.zip');

			throw new \Exception('ZIP archive is empty: no document could be generated for the selected files.');
		}

		if ($zip->close() !== true || !file_exists($zipAbsPath))
		{
			Log::add('Failed to write ZIP archive at ' . $zipAbsPath, Log::ERROR, 'com_emundus.export.zip');

			throw new \Exception('Failed to write ZIP archive at ' . $zipAbsPath);
		}

		return $exportPath . $zipFilename;
	}

	private function addEntryToZip(ZipArchive $zip, array $entry): void
	{
		if (!empty($entry['pdf']) && file_exists($entry['pdf']))
		{
			$zip->addFile($entry['pdf'], $entry['folder'] . '/' . basename($entry['pdf']));
		}

		foreach ($entry['attachments'] as $attachment)
		{
			if (file_exists($attachment['source']))
			{
				$zip->addFile($attachment['source'], $attachment['name']);
			}
			else
			{
				$zip->addFromString($attachment['name'] . '-missing.txt', '');
			}
		}

		foreach ($entry['missing'] as $missingName)
		{
			$zip->addFromString($missingName, '');
		}
	}
}
