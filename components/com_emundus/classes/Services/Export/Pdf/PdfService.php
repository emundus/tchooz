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
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Services\Export\Export;
use Tchooz\Services\Export\ExportInterface;
use Tchooz\Services\Export\ExportResult;
use Tchooz\Services\Export\HeadersEnum;

class PdfService extends Export implements ExportInterface
{
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

	public function __construct(array $fnums = [], User $user = null, array|object $options = null, ExportEntity $exportEntity = null)
	{
		$this->fnums = $fnums;
		$this->user  = $user;

		if (is_array($options))
		{
			$options = (object) $options;
		}
		$this->options = !empty($options) ? PdfOptions::fromObject($options) : new PdfOptions();

		$this->parser       = new PdfParser();
		$this->exportEntity = $exportEntity;
	}

	public function export(string $exportPath, ?TaskEntity $task, ?string $langCode = 'fr-FR'): ExportResult
	{
		try
		{
			// Need to initialize parent only here because of langCode
			parent::__construct($langCode);

			$this->registerClasses();

			$result = new ExportResult(false);
			if (empty($this->fnums) || empty($this->user))
			{
				return $result;
			}

			$files = [];

			$anonymize_data      = \EmundusHelperAccess::isDataAnonymized($this->user->id);
			$allowed_attachments = \EmundusHelperAccess::getUserAllowedAttachmentIDs($this->user->id);

			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);
			$query->select('id')
				->from('#__emundus_setup_step_types')
				->where('published = 1 OR published IS NULL');
			$db->setQuery($query);
			$stepTypes = $db->loadColumn();

			foreach ($this->fnums as $fnum)
			{
				$html = '';

				$applicationFile = $this->applicationFileRepository->getByFnum($fnum);
				$emundusUser     = $this->emundusUserRepository->getByUserId($applicationFile->getUser()->id);

				$anonymize_data = $anonymize_data || $emundusUser->isAnonym();

				$title = $anonymize_data ? 'Anonymized User' : (strtoupper($emundusUser->getLastname()) . ' ' . $emundusUser->getFirstname());

				$html .= $this->parser::HTML_TAG;
				$html .= $this->parser->buildHtmlHead($title);
				$html .= $this->parser::BODY_TAG;

				$html .= $this->buildHeader($applicationFile, $emundusUser, $allowed_attachments, $anonymize_data);
				$html .= $this->parser::STYLE_TAG . $this->getStylesheet() . $this->parser::STYLE_CLOSE_TAG;
				$html .= $this->buildData($applicationFile, $stepTypes);

				if ($this->options->isDisplayPageNumbers())
				{
					$html .= $this->parser->createPageNumbering();
				}

				$html .= $this->parser::BODY_CLOSE_TAG . $this->parser::HTML_CLOSE_TAG;

				$base_path = EMUNDUS_PATH_ABS . $applicationFile->getUser()->id . '/';
				if (empty($this->options->getFilename()))
				{
					$filename = $base_path . $applicationFile->getFnum() . '_' . $this->generatePdfName() . '.pdf';
				}
				else
				{
					$filename = $this->options->getFilename();

					// Build filename from tags, we are using helper functions found in the email model, not sending emails ;)
					$post     = array('FNUM' => $applicationFile->getFnum(), 'CAMPAIGN_YEAR' => $applicationFile->getCampaign()->getYear(), 'PROGRAMME_CODE' => $applicationFile->getCampaign()->getProgram()->getCode());
					$tags     = $this->mEmails->setTags($applicationFile->getUser()->id, $post, $fnum, '', $filename);
					$filename = preg_replace($tags['patterns'], $tags['replacements'], $filename);
					$filename = $this->mEmails->setTagsFabrik($filename, array($fnum));

					// Format filename
					$filename = $this->mEmails->stripAccents($filename);
					$filename = preg_replace('/[^A-Za-z0-9 _.-]/', '', $filename);
					$filename = preg_replace('/\s/', '', $filename);
					$filename = strtolower($filename);

					// Check if extension is present, if yes remove it
					if (str_ends_with($filename, '.pdf'))
					{
						$filename = substr($filename, 0, -4);
					}

					if (empty($filename))
					{
						$filename = $base_path . $applicationFile->getFnum() . '_' . $this->generatePdfName() . '.pdf';
					}
					else
					{
						$filename = $base_path . $filename . '.pdf';
					}
				}

				// Check if directory exists
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
					$tmpArray = array();
					$uploads = $this->m_application->getAttachmentsByFnum($fnum, null, $attachments);
					\EmundusHelperExport::getAttachmentPDF($files, $tmpArray, $uploads, $applicationFile->getUser()->id);
				}
			}

			if (count($files) > 0)
			{
				$result->setStatus(true);
				if (count($files) === 1)
				{
					// Copy to export path
					$exportFilename = $exportPath . basename($files[0]);
					copy($files[0], $exportFilename);

					$result->setFilePath($exportFilename);
				}
				else
				{
					$exportFilename = $exportPath . $this->generatePdfName() . '.pdf';
					if ($this->mergePdfs($files, $exportFilename))
					{
						$result->setFilePath($exportFilename);
					}
					else
					{
						$result->setStatus(false);
					}
				}
			}
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		$result->setProgress(100.0);

		return $result;
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
		$sub_column     = [];
		$custom_page_headers = $this->options->getPageHeaders();
		foreach ($custom_page_headers as $custom_page_header)
		{
			$customHeaderData = $this->getData($custom_page_header, [$applicationFile], ValueFormatEnum::FORMATTED);

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
				$customHeaderData = $this->getData($custom_header, [$applicationFile], ValueFormatEnum::FORMATTED);

				if($custom_header === 'stickers') {
					$header   .= $this->parser->createTable();

					$header .= $this->parser->addTableRow([$customHeaderData['data'][$applicationFile->getFnum()]]);
					$header .= $this->parser::TABLE_CLOSE_TAG;
					continue;
				}

				$header           .= $this->parser->addTableRow([$this->parser->createContentBlock($customHeaderData['label'] . ' : ') . $customHeaderData['data'][$applicationFile->getFnum()]]);
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
			$forms = $this->m_application->getFormsPDF($applicationFile->getUser()->id, $applicationFile->getFnum(), null, 0, null, $elementIds, true, $stepTypes, $this->user->id);

		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);
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
		$this->m_application             = new \EmundusModelApplication();
		$this->mEmails                   = new \EmundusModelEmails();
	}

	private function renderPdf(string $html, string $filename): bool|int
	{
		$options = new Options();
		$options->set('defaultFont', 'helvetica');
		$options->set('isPhpEnabled', true);

		$dompdf = new Dompdf($options);
		$dompdf->addInfo('Producer', '');
		$dompdf->addInfo('Creator', '');

		try
		{
			$dompdf->loadHtml($html);
			$dompdf->render();

			$output = $dompdf->output();

			return file_put_contents($filename, $output);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR);

			return false;
		}
	}

	private function mergePdfs(array $files, string $exportFilename): bool
	{
		$merged = false;

		$emConfig                   = ComponentHelper::getParams('com_emundus');
		$gotenberg_merge_activation = $emConfig->get('gotenberg_merge_activation', 0);

		/* DEPRECATED: Use Gotenberg service instead of FPDI for merging PDFs */
		if (!$gotenberg_merge_activation)
		{
			require_once(JPATH_LIBRARIES . '/emundus/fpdi.php');

			$pdf = new \ConcatPdf();
			$pdf->setFiles($files);
			$pdf->concat();

			if (isset($tmpArray))
			{
				foreach ($tmpArray as $fn)
				{
					unlink($fn);
				}
			}

			if (!empty($pdf->Output($exportFilename, 'F')))
			{
				$merged = true;
			}

		}
		else
		{
			$gotenberg_url = $emConfig->get('gotenberg_url', 'http://localhost:3000');

			if (!empty($gotenberg_url))
			{
				$got_files = [];
				foreach ($files as $item)
				{
					$got_files[] = Stream::path($item);
				}
				$request  = Gotenberg::pdfEngines($gotenberg_url)
					->merge(...$got_files);
				$response = Gotenberg::send($request);
				$content  = $response->getBody()->getContents();

				$filename = $exportFilename;
				$fp       = fopen($filename, 'w');
				$pieces   = str_split($content, 1024 * 16);
				if ($fp)
				{
					foreach ($pieces as $piece)
					{
						$merged = fwrite($fp, $piece, strlen($piece)) !== false;
					}
				}
			}
		}

		return $merged;
	}
}