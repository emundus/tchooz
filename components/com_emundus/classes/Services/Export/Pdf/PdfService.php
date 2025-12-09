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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Services\Export\ExportInterface;
use Tchooz\Services\Export\ExportResult;

class PdfService implements ExportInterface
{
	private array $fnums;

	private ?User $user;

	private ?ExportEntity $exportEntity;

	private PdfOptions $options;

	private PdfParser $parser;

	private ApplicationFileRepository $applicationFileRepository;

	private EmundusUserRepository $emundusUserRepository;

	private StatusRepository $statusRepository;

	private \EmundusModelApplication $m_application;

	public function __construct(array $fnums = [], User $user = null, object|array|null $options = null, ExportEntity $exportEntity = null)
	{
		$this->fnums   = $fnums;
		$this->user    = $user;

		if(is_array($options))
		{
			$options = (object) $options;
		}
		$this->options = !empty($options) ? PdfOptions::fromObject($options) : new PdfOptions();

		$this->parser = new PdfParser();
		$this->exportEntity = $exportEntity;
	}

	public function export(string $exportPath, ?TaskEntity $task, ?string $langCode = 'fr-FR'): ExportResult
	{
		$result = new ExportResult(false);
		if (empty($this->fnums) || empty($this->user))
		{
			return $result;
		}

		$this->registerClasses();

		$files = [];

		$anonymize_data      = \EmundusHelperAccess::isDataAnonymized($this->user->id);
		$allowed_attachments = \EmundusHelperAccess::getUserAllowedAttachmentIDs($this->user->id);

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
			$html .= $this->buildData($applicationFile);

			if($this->options->isDisplayPageNumbers())
			{
				$html .= $this->parser->createPageNumbering();
			}

			$html .= $this->parser::BODY_CLOSE_TAG . $this->parser::HTML_CLOSE_TAG;

			$base_path = EMUNDUS_PATH_ABS . $applicationFile->getUser()->id . '/';
			$filename = $base_path . $applicationFile->getFnum() . '_' . $this->generatePdfName() . '.pdf';
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
		}

		if(count($files) > 0)
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
				else {
					$result->setStatus(false);
				}
			}
		}

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

		$header .= $this->parser::HEADER_TAG;
		$header .= $this->parser->createTable();

		$columns   = [];
		$columns[] = $this->parser->createImg($logo_base64, 'auto', 60);

		if ($this->options->isDisplayHeader())
		{
			$sub_column     = [];
			$date_submitted = Text::_('NOT_SENT');
			if (!empty($applicationFile->getDateSubmitted()))
			{
				$date_submitted = $applicationFile->getDateSubmitted()->format('Y-m-d H:i:s');
				$date_submitted = \EmundusHelperDate::displayDate($date_submitted);
			}

			$date_printed = new Date();
			$date_printed = \EmundusHelperDate::displayDate($date_printed, 'DATE_FORMAT_LC2', 0);

			if (!$anonymize_data)
			{
				$sub_column[] = '<p>' . $this->parser->createContentBlock(Text::_('PDF_HEADER_INFO_CANDIDAT') . ' : ') . $emundusUser->getFirstname() . ' ' . strtoupper($emundusUser->getLastname()) . '</p>';
			}

			if (!$anonymize_data && in_array(PdfHeadersEnum::EMAIL, $this->options->getHeaders()))
			{
				$sub_column[] = '<p>' . $this->parser->createContentBlock(Text::_('EMAIL') . ' : ') . $applicationFile->getUser()->email . '</p>';
			}
			if (in_array(PdfHeadersEnum::FNUM, $this->options->getHeaders()))
			{
				$sub_column[] = '<p>' . $this->parser->createContentBlock(Text::_('FNUM') . ' : ') . $applicationFile->getFnum() . '</p>';
			}
			if (!empty($sub_column))
			{
				$columns[] = implode('', $sub_column);
			}

			$header .= $this->parser->addTableRow($columns);
			$header .= $this->parser::TABLE_CLOSE_TAG . $this->parser::HR_TAG . $this->parser::HEADER_CLOSE_TAG;

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

			if (in_array(PdfHeadersEnum::ID, $this->options->getHeaders()))
			{
				$content = $this->parser->createContentBlock(Text::_('ID_CANDIDAT') . ' : ') . $applicationFile->getUser()->id;
				$header  .= $this->parser->addTableRow([$content]);
			}

			$header .= $this->parser->addTableRow([$this->parser->createTitle(Text::_('PDF_HEADER_INFO_DOSSIER'), 3)]);
			$header .= $this->parser->addTableRow([$applicationFile->getCampaign()->getLabel() . ' (' . $applicationFile->getCampaign()->getYear() . ')']);

			if (in_array(PdfHeadersEnum::FNUM, $this->options->getHeaders()))
			{
				$header .= $this->parser->addTableRow([$this->parser->createContentBlock(Text::_('FNUM') . ' : ') . $applicationFile->getFnum()]);
			}

			if (in_array(PdfHeadersEnum::SUBMITTED_DATE, $this->options->getHeaders()))
			{
				$header .= $this->parser->addTableRow([$this->parser->createContentBlock(Text::_('APPLICATION_SENT_ON') . ' : ') . $date_submitted]);
			}

			if (in_array(PdfHeadersEnum::PRINTED_DATE, $this->options->getHeaders()))
			{
				$header .= $this->parser->addTableRow([$this->parser->createContentBlock(Text::_('DOCUMENT_PRINTED_ON') . ' : ') . $date_printed]);
			}

			if (in_array(PdfHeadersEnum::STATUS, $this->options->getHeaders()))
			{
				$status = $this->statusRepository->getByStep($applicationFile->getStatus());
				$header .= $this->parser->addTableRow([$this->parser->createContentBlock(Text::_('COM_EMUNDUS_EXPORTS_PDF_STATUS') . ' : ') . $status->getLabel()]);
			}

			/*if ($attachments)
			{
				$uploads       = $m_application->getUserAttachmentsByFnum($fnum, '');
				$files_updated = count($uploads) > 1 ? Text::_('COM_EMUNDUS_ATTACHMENTS_FILES_UPLOADED') : Text::_('COM_EMUNDUS_ATTACHMENTS_FILE_UPLOADED');
				$htmldata      .= '<tr class="sent"><td><b>' . $files_updated . ' :</b>' . ' ' . count($uploads) . '</a></td></tr>';
			}*/

			$header .= $this->parser::TABLE_CLOSE_TAG;

			if (in_array(PdfHeadersEnum::STICKERS, $this->options->getHeaders()))
			{
				/*$tags     = $m_files->getTagsByFnum(explode(',', $fnum));
				$htmldata .= '<table style="margin-top: 8px" class="tags-table"><tr><td> ';
				foreach ($tags as $tag)
				{
					if (EmundusHelperAccess::asAccessAction(14, 'r', $app->getIdentity()->id, $fnum) || (EmundusHelperAccess::asAccessAction(14, 'c', $app->getIdentity()->id, $fnum) && $tag['user_id'] === $app->getIdentity()->id))
					{
						$class    = str_replace('label-', '', $tag['class']);
						$htmldata .= '<span class="sticker label-' . $class . '">' . $tag['label'] . '</span>&nbsp;';
						//$htmldata .= '<div class="sticker label-' . $class . '"><span class="circle"></span><span class="tw-text-white tw-truncate tw-font-semibold tw-w-[150px] tw-text-sm">' . $tag['label'] . '</span></div>';
					}
				}
				$htmldata .= '</td></tr></table>';*/
			}

			$header .= $this->parser::HR_TAG;
		}

		return $header;
	}

	private function buildData(ApplicationFileEntity $applicationFile): string
	{
		// TODO: Replace this method
		return $this->m_application->getFormsPDF($applicationFile->getUser()->id, $applicationFile->getFnum(), null, 0, null, null, true, [1], $this->user->id);
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

		$this->applicationFileRepository = new ApplicationFileRepository();
		$this->emundusUserRepository     = new EmundusUserRepository();
		$this->statusRepository          = new StatusRepository();
		$this->m_application             = new \EmundusModelApplication();
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
		
		$emConfig = ComponentHelper::getParams('com_emundus');
		$gotenberg_merge_activation = $emConfig->get('gotenberg_merge_activation', 0);

		/* DEPRECATED: Use Gotenberg service instead of FPDI for merging PDFs */
		if(!$gotenberg_merge_activation) {
			require_once(JPATH_LIBRARIES . '/emundus/fpdi.php');

			$pdf = new \ConcatPdf();
			$pdf->setFiles($files);
			$pdf->concat();

			if (isset($tmpArray)) {
				foreach ($tmpArray as $fn) {
					unlink($fn); 
				}
			}
			
			if(!empty($pdf->Output($exportFilename, 'F')))
			{
				$merged = true;
			}
			
		} else {
			$gotenberg_url = $emConfig->get('gotenberg_url', 'http://localhost:3000');

			if (!empty($gotenberg_url)) {
				$got_files = [];
				foreach ($files as $item) {
					$got_files[] = Stream::path($item);
				}
				$request  = Gotenberg::pdfEngines($gotenberg_url)
					->merge(...$got_files);
				$response = Gotenberg::send($request);
				$content = $response->getBody()->getContents();

				$filename = $exportFilename;
				$fp       = fopen($filename, 'w');
				$pieces   = str_split($content, 1024 * 16);
				if ($fp)
				{
					foreach ($pieces as $piece) {
						$merged = fwrite($fp, $piece, strlen($piece)) !== false;
					}
				}
			}
		}
		
		return $merged;
	}

	// TODO: Manage languages via export parameters
}