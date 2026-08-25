<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Services\Export\OptionsSchema\PdfOptionsSchema;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Services\Export\Export;
use Tchooz\Services\Export\ExportInterface;
use Tchooz\Services\Export\ExportResult;
use Tchooz\Services\Export\FilenameRenderer;
use Tchooz\Services\Export\HeadersEnum;
use Tchooz\Traits\TraitAutomatedTask;

class PdfService extends Export implements ExportInterface
{
	use TraitAutomatedTask;

	/**
	 * Max number of fnums rendered per export() invocation when running asynchronously.
	 */
	private const BATCH_SIZE = 5;

	/**
	 * Max wall time per export() invocation once the export can be resumed, in seconds.
	 */
	private const TIME_LIMIT = 25;

	private array $fnums;

	private ?User $user;

	private ?ExportEntity $exportEntity;

	private PdfOptions $options;

	private PdfParser $parser;

	private ApplicationFileRepository $applicationFileRepository;

	private EmundusUserRepository $emundusUserRepository;

	private \EmundusModelEmails $mEmails;

	private StatusRepository $statusRepository;

	private \EmundusModelApplication $m_application;

	private ExportRepository $exportRepository;

	public function __construct(array $fnums = [], User $user = null, array|object $options = null, ExportEntity $exportEntity = null)
	{
		$this->fnums = $fnums;
		$this->user  = $user;

		if ($options instanceof PdfOptions)
		{
			$this->options = $options;
		}
		else
		{
			if (is_array($options))
			{
				$options = (object) $options;
			}
			$this->options = !empty($options) ? PdfOptions::fromObject($options) : new PdfOptions();
		}

		$this->parser       = new PdfParser();
		$this->exportEntity = $exportEntity;
		Log::addLogger(['text_file' => 'com_emundus.export.pdf.php'], Log::ALL, 'com_emundus.export.pdf');
	}

	public function export(string $exportPath, ?TaskEntity $task, ?string $langCode = 'fr-FR'): ExportResult
	{
		// Need to initialize parent only here because of langCode
		parent::__construct($langCode);

		$this->registerClasses();

		$result = new ExportResult(false);
		if (empty($this->fnums) || empty($this->user))
		{
			return $result;
		}

		if (empty($task) && $this->isCancelled())
		{
			throw new \Exception('Export has been cancelled.');
		}

		$state = $this->loadOrInitState($exportPath);
		$fnums = $state !== null ? $state['fnums'] : $this->filterAccessibleFnums($this->fnums);
		$files = $state !== null ? $state['files'] : [];

		$anonymizeData      = \EmundusHelperAccess::isDataAnonymized($this->user->id);
		$allowedAttachments = \EmundusHelperAccess::getUserAllowedAttachmentIDs($this->user->id);
		$stepTypes          = $this->loadPublishedStepTypes();

		$pending      = $state !== null ? array_slice($fnums, $state['processed']) : $fnums;
		$processStart = microtime(true);
		$processedNow = 0;
		// Stopping halfway is only an option when something can pick the export up again: a state
		// file to resume from, and a task to resume it.
		$canYield = $state !== null && (!empty($task) || $this->isAsyncExportAllowed());

		foreach ($pending as $fnum)
		{
			if (empty($task) && $this->isCancelled())
			{
				throw new \Exception('Export has been cancelled.');
			}

			if ($canYield && $processedNow > 0 && $this->shouldYield($processedNow, $processStart, !empty($task)))
			{
				$result->setStatus(true);
				$result->setFilePath($state['state_path']);
				$result->setProgress($this->computeProgress($state));

				return $result;
			}

			$files = array_merge($files, $this->renderFnumFiles($fnum, $stepTypes, $allowedAttachments, $anonymizeData));
			$processedNow++;

			if ($state !== null)
			{
				$state['processed']++;
				$state['files'] = $files;
				$this->persistState($state);
				$result->setProgress($this->computeProgress($state));
			}
		}

		$this->assemble($files, $fnums, $exportPath, $result);

		if ($state !== null)
		{
			$this->cleanupState($state);
		}

		$result->setProgress(100.0);

		return $result;
	}

	/**
	 * Render the application PDF of a single fnum plus, when requested, the PDF version of its
	 * attachments.
	 *
	 * @param   array|bool  $allowedAttachments  attachment ids the user may see, or true for all of them.
	 *
	 * @return string[]  absolute paths of the generated files, in merge order.
	 */
	private function renderFnumFiles(string $fnum, array $stepTypes, array|bool $allowedAttachments, bool $anonymizeData): array
	{
		$files = [];

		$applicationFile = $this->applicationFileRepository->getByFnum($fnum);
		if (empty($applicationFile))
		{
			Log::add('Application file not found for fnum ' . $fnum, Log::WARNING, 'com_emundus.export.pdf');

			return $files;
		}

		$emundusUser = $this->emundusUserRepository->getByUserId($applicationFile->getUser()->id);
		$anonymize   = $anonymizeData || $emundusUser->isAnonym() || $applicationFile->isAnonymous();

		$title = $anonymize ? Text::_('COM_EMUNDUS_ANONYM_ACCOUNT') : (strtoupper($emundusUser->getLastname()) . ' ' . $emundusUser->getFirstname());

		$html = $this->parser::HTML_TAG;
		$html .= $this->parser->buildHtmlHead($title);
		$html .= $this->parser::BODY_TAG;
		$html .= $this->buildHeader($applicationFile, $emundusUser, $allowedAttachments, $anonymize);
		$html .= $this->parser::STYLE_TAG . $this->getStylesheet() . $this->parser::STYLE_CLOSE_TAG;
		$html .= $this->buildData($applicationFile, $stepTypes);

		if ($this->options->isDisplayPageNumbers())
		{
			$html .= $this->parser->createPageNumbering();
		}

		$html .= $this->parser::BODY_CLOSE_TAG . $this->parser::HTML_CLOSE_TAG;

		$filename = $this->buildOutputFilename($applicationFile);

		$dir = dirname($filename);
		if (!is_dir($dir))
		{
			mkdir($dir, 0755, true);
		}

		if ($this->renderPdf($html, $filename) !== false)
		{
			$files[] = $filename;
		}

		$attachments = $this->options->getAttachments();
		if (!empty($attachments))
		{
			$tmpArray = [];
			$uploads  = $this->m_application->getAttachmentsByFnum($fnum, null, $attachments);
			\EmundusHelperExport::getAttachmentPDF($files, $tmpArray, $uploads, $applicationFile->getUser()->id);
		}

		return $files;
	}

	/**
	 * Copy (single file) or merge (several files) the rendered PDFs into the final export file.
	 */
	private function assemble(array $files, array $fnums, string $exportPath, ExportResult $result): void
	{
		$files = array_values(array_filter($files, 'file_exists'));
		if (empty($files))
		{
			return;
		}

		$result->setStatus(true);

		if (count($files) === 1)
		{
			$exportFilename = $exportPath . basename($files[0]);
			copy($files[0], $this->toAbsolutePath($exportFilename));

			$result->setFilePath($exportFilename);

			return;
		}

		// The merged filename is built from the first exported file, which a resumed run no longer
		// holds in memory, so it is read back from the fnum list.
		$firstApplicationFile = !empty($fnums) ? $this->applicationFileRepository->getByFnum(reset($fnums)) : null;
		$exportFilename       = $exportPath . $this->buildMergedFilename($firstApplicationFile) . '.pdf';

		if ((new PdfMerger())->merge($files, $this->toAbsolutePath($exportFilename)))
		{
			$result->setFilePath($exportFilename);
		}
		else
		{
			$result->setStatus(false);
		}
	}

	/**
	 * ZipService renders into an absolute staging directory while an export run works with a
	 * JPATH-relative path, and a relative write would land wherever the process happens to run
	 * from — the site root for a request, anywhere for the task CLI.
	 */
	private function toAbsolutePath(string $path): string
	{
		return str_starts_with($path, '/') ? $path : JPATH_SITE . '/' . $path;
	}

	private function loadPublishedStepTypes(): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__emundus_setup_step_types')
			->where('published = 1 OR published IS NULL');
		$db->setQuery($query);

		return $db->loadColumn() ?: [];
	}

	/**
	 * @return string[] fnums the user is allowed to export.
	 */
	private function filterAccessibleFnums(array $fnums): array
	{
		$valid = [];

		foreach ($fnums as $fnum)
		{
			// Automated tasks (plugins, crons) run as the system user, which owns no group nor
			// fnum association, so the regular ACL check would always drop the fnum.
			if (
				$this->isAutomatedTaskUser((int) $this->user->id)
				|| \EmundusHelperAccess::asAccessAction(ExportFormatEnum::PDF->getAccessName(), CrudEnum::CREATE->value, $this->user->id, $fnum)
			)
			{
				$valid[] = $fnum;
			}
		}

		return $valid;
	}

	private function isCancelled(): bool
	{
		return $this->exportEntity !== null && $this->exportRepository->isCancelled($this->exportEntity->getId());
	}

	/**
	 * A task keeps its tick short by also stopping on a file count; a request has nobody else to hand
	 * the work to before its time is up, so it only stops on time and serves small exports inline.
	 */
	private function shouldYield(int $processedNow, float $processStart, bool $isAsync): bool
	{
		return ($isAsync && $processedNow >= self::BATCH_SIZE) || (microtime(true) - $processStart) >= self::TIME_LIMIT;
	}

	private function computeProgress(array $state): float
	{
		return round(($state['processed'] / max(1, count($state['fnums']))) * 99, 2);
	}

	// -----------------------------------------------------------------------
	// State management — only used when an ExportEntity backs the run, which is the
	// only case where a later invocation can pick the export up again.
	// -----------------------------------------------------------------------

	/**
	 * State shape:
	 *   - fnums:      string[]  ordered list of fnums to render (post-ACL filter)
	 *   - processed:  int       number of fnums already rendered
	 *   - files:      string[]  absolute paths of the PDFs rendered so far, in merge order
	 *   - state_path: string    JPATH-relative path of the JSON state file
	 *
	 * @return array|null  null when the run cannot be resumed and must go through in one shot.
	 */
	private function loadOrInitState(string $exportPath): ?array
	{
		if ($this->exportEntity === null)
		{
			return null;
		}

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
		if (!is_array($decoded) || !isset($decoded['fnums'], $decoded['processed'], $decoded['files']))
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

		$state = [
			'fnums'      => array_values($validFnums),
			'processed'  => 0,
			'files'      => [],
			'state_path' => $exportPath . 'export_' . $this->exportEntity->getId() . '.json',
		];

		$this->exportEntity->setFilename($state['state_path']);
		if (!$this->exportRepository->flush($this->exportEntity))
		{
			throw new \Exception('Failed to store the export state path on export ' . $this->exportEntity->getId());
		}

		$this->persistState($state);

		return $state;
	}

	private function persistState(array $state): void
	{
		if (file_put_contents(JPATH_SITE . '/' . $state['state_path'], json_encode($state)) === false)
		{
			throw new \Exception('Failed to write export state at ' . $state['state_path']);
		}
	}

	private function cleanupState(array $state): void
	{
		$statePath = JPATH_SITE . '/' . $state['state_path'];
		if (file_exists($statePath))
		{
			unlink($statePath);
		}
	}

	public static function getType(): string
	{
		return 'pdf';
	}

	private function getStylesheet(): string
	{
		$css = file_get_contents(JPATH_SITE . '/components/com_emundus/assets/css/pdf-export.css');

		return $css ?: '';
	}

	private function generatePdfName(): string
	{
		$today = date("MdYHis");
		$name  = md5($today . rand(0, 10));

		return $name . '-applications';
	}

	private function buildLogo(ApplicationFileEntity $applicationFile): string
	{
		$logo_base64 = '';

		$logo = \EmundusHelperEmails::getLogo(false, $applicationFile->getCampaign()->getProgram()->getCode());

		$type = pathinfo($logo, PATHINFO_EXTENSION);
		$data = file_get_contents($logo);
		if ($data)
		{
			$logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		}

		return $logo_base64;
	}

	private function buildHeader(
		ApplicationFileEntity $applicationFile,
		EmundusUserEntity     $emundusUser,
		array|bool            $allowed_attachments,
		bool                  $anonymize_data
	): string
	{
		$header = '';

		$logo_base64 = $this->buildLogo($applicationFile);

		$columns   = [];
		$columns[] = $this->parser->createImg($logo_base64, 'auto', 60);

		// Fixed header
		$sub_column          = [];
		$custom_page_headers = $this->options->getPageHeaders();
		foreach ($custom_page_headers as $custom_page_header)
		{
			$customHeaderData = $this->getData($custom_page_header, [$applicationFile], ValueFormatEnum::FORMATTED, $anonymize_data);

			$sub_column[] = '<p>' . $this->parser->createContentBlock($customHeaderData['label'] . ' : ') . $customHeaderData['data'][$applicationFile->getFnum()] . '</p>';
		}

		if (!empty($sub_column))
		{
			$columns[] = implode('', $sub_column);
		}

		$header .= $this->parser->createHeader(sizeof($sub_column));
		$header .= $this->parser->createTable();

		$header .= $this->parser->addTableRow($columns);
		$header .= $this->parser::TABLE_CLOSE_TAG . $this->parser::HR_TAG . $this->parser::HEADER_CLOSE_TAG;
		//

		$custom_headers = $this->options->getHeaders();
		if ($this->options->isDisplayHeader() && !empty($custom_headers))
		{
			$header .= $this->parser->createTable();

			/*
			if (!empty($item->avatar) && is_image_ext($item->avatar) && ($allowed_attachments === true || in_array('10', $allowed_attachments)))
			{
				if (file_exists(EMUNDUS_PATH_ABS . @$item->user_id . '/tn_' . @$item->avatar))
				{
					$avatar = EMUNDUS_PATH_ABS . @$item->user_id . '/tn_' . @$item->avatar;
				}
				elseif (file_exists(EMUNDUS_PATH_ABS . @$item->user_id . '/' . @$item->avatar) && !empty($item->avatar) && is_image_ext($item->avatar))
				{
					$avatar = EMUNDUS_PATH_ABS . @$item->user_id . '/' . @$item->avatar;
				}

				if (!empty($avatar))
				{
					$type          = pathinfo($avatar, PATHINFO_EXTENSION);
					$data          = file_get_contents($avatar);
					$avatar_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

					$htmldata .= '<tr><td><img style="border-radius: 50%" src="' . $avatar_base64 . '" width="auto" height="60" align="right"/></td></tr>';
				}
			}*/

			$header .= $this->parser->addTableRow([$this->parser->createTitle(Text::_('PDF_HEADER_INFO_DOSSIER'), 3)]);

			foreach ($custom_headers as $custom_header)
			{
				$customHeaderData = $this->getData($custom_header, [$applicationFile], ValueFormatEnum::FORMATTED, $anonymize_data);

				if ($custom_header === 'stickers')
				{
					$header .= $this->parser->createTable();

					$header .= $this->parser->addTableRow([$customHeaderData['data'][$applicationFile->getFnum()]]);
					$header .= $this->parser::TABLE_CLOSE_TAG;
					continue;
				}

				$header .= $this->parser->addTableRow([$this->parser->createContentBlock($customHeaderData['label'] . ' : ') . $customHeaderData['data'][$applicationFile->getFnum()]]);
			}

			$header .= $this->parser::TABLE_CLOSE_TAG;
			$header .= $this->parser::HR_TAG;
		}

		return $header;
	}

	private function buildData(ApplicationFileEntity $applicationFile, array $stepTypes = [1]): string
	{
		$forms = '';
		// TODO: Replace this method
		$elementIds = $this->options->getElements();

		// Remove element IDs that are not numeric
		$elementIds = array_filter($elementIds, function ($id) {
			return is_numeric($id);
		});

		try
		{
			$displayEvaluatorName = (bool) $this->options->getSetting(PdfOptionsSchema::DISPLAY_EVALUATOR_NAME, true);
			$forms = $this->m_application->getFormsPDF($applicationFile->getUser()->id, $applicationFile->getFnum(), null, 0, null, $elementIds, true, $stepTypes, $this->user->id, $displayEvaluatorName);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus.export.pdf');
		}

		return $forms;
	}

	private function registerClasses(): void
	{
		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}
		if (!class_exists('EmundusHelperEmails'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/emails.php';
		}
		if (!class_exists('EmundusHelperDate'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/date.php';
		}
		if (!class_exists('EmundusHelperExport'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/export.php';
		}
		if (!class_exists('EmundusModelApplication'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/application.php';
		}
		if (!class_exists('EmundusModelEmails'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
		}

		$this->applicationFileRepository = new ApplicationFileRepository();
		$this->emundusUserRepository     = new EmundusUserRepository();
		$this->statusRepository          = new StatusRepository();
		$this->exportRepository          = new ExportRepository();
		$this->m_application             = new \EmundusModelApplication();
		$this->mEmails                   = new \EmundusModelEmails();
	}

	private function renderPdf(string $html, string $filename): bool|int
	{
		$options = new Options();
		PdfFont::configureOptions($options);
		$options->set('isPhpEnabled', true);

		$dompdf = new Dompdf($options);
		$dompdf->addInfo('Producer', '');
		$dompdf->addInfo('Creator', '');

		try
		{
			$dompdf->loadHtml(PdfFont::injectFontFace($html));
			$dompdf->render();

			$output = $dompdf->output();

			$dir = dirname($filename);
			if (!is_dir($dir))
			{
				mkdir($dir, 0755, true);
			}

			return file_put_contents($filename, $output);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus.export.pdf');

			return false;
		}
	}

	/**
	 * Resolve the absolute output path for the per-fnum PDF, applying the configured filename
	 * template (or a random fallback when the template is empty or sanitizes to nothing).
	 */
	private function buildOutputFilename(ApplicationFileEntity $applicationFile): string
	{
		$basePath = EMUNDUS_PATH_ABS . $applicationFile->getUser()->id . '/';
		$fallback = $basePath . $applicationFile->getFnum() . '_' . $this->generatePdfName() . '.pdf';

		$template = $this->options->getFilename();
		if ($template === '')
		{
			return $fallback;
		}

		$rendered = (new FilenameRenderer($this->mEmails))->render($template, $applicationFile);
		if (str_ends_with($rendered, '.pdf'))
		{
			$rendered = substr($rendered, 0, -4);
		}

		if ($rendered === '')
		{
			return $fallback;
		}

		return $basePath . $rendered . '.pdf';
	}

	/**
	 * Resolve the filename stem (no path, no extension) for the merged multi-fnum PDF, applying the
	 * configured filename template so a custom name is honoured. Any per-fnum tags are rendered
	 * against the first exported file. Falls back to a random name when no file was exported or the
	 * template sanitizes to nothing.
	 */
	private function buildMergedFilename(?ApplicationFileEntity $applicationFile): string
	{
		$template = $this->options->getFilename();

		// Untouched default template: keep a unique auto-generated name so consecutive default
		// exports don't overwrite each other. Only a genuinely custom name overrides it.
		$default = (string) ComponentHelper::getParams('com_emundus')->get('application_form_name', 'application_form_pdf');
		if ($applicationFile === null || $template === '' || $template === $default)
		{
			return $this->generatePdfName();
		}

		$rendered = (new FilenameRenderer($this->mEmails))->render($template, $applicationFile);
		if (str_ends_with($rendered, '.pdf'))
		{
			$rendered = substr($rendered, 0, -4);
		}

		return $rendered !== '' ? $rendered : $this->generatePdfName();
	}
}