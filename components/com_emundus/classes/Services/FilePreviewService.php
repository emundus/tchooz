<?php
/**
 * @package     Tchooz\Services
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

/**
 * Build an in-app HTML preview of a stored file (PDF, image, text, Word, spreadsheet).
 *
 * Returns the same envelope shape consumed by the front-end previewers
 * (AttachmentPreview / ResourcePreview): a `style` hint tells the client how to
 * render the `content` (shadow DOM for office documents, plain HTML otherwise).
 */
class FilePreviewService
{
	private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
	private const WORD_EXTENSIONS  = ['doc', 'docx', 'odt', 'rtf'];
	private const SHEET_EXTENSIONS = ['xls', 'xlsx', 'ods', 'csv'];

	/**
	 * @param   string  $absolutePath  Absolute path to the stored file.
	 * @param   string  $publicUrl     Public URL used for direct rendering (PDF/image).
	 *
	 * @return  array{status: bool, content: string, style: string, overflowX: bool, overflowY: bool, msg: string, error: string}
	 */
	public function build(string $absolutePath, string $publicUrl): array
	{
		$preview = $this->baseEnvelope();

		if (!is_file($absolutePath))
		{
			$preview['status'] = false;
			$preview['error']  = 'file_not_found';

			return $preview;
		}

		$extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

		if ($extension === 'pdf')
		{
			$preview['content'] = '<iframe src="' . htmlspecialchars($publicUrl, ENT_QUOTES) . '" style="width:100%;height:100%;" border="0"></iframe>';

			return $preview;
		}

		if (in_array($extension, self::IMAGE_EXTENSIONS, true))
		{
			$preview['content'] = '<div class="wrapper" style="height:100%;display:flex;justify-content:center;align-items:center;"><img src="' . htmlspecialchars($publicUrl, ENT_QUOTES) . '" style="display:block;max-width:100%;max-height:100%;width:auto;height:auto;" /></div>';

			return $preview;
		}

		if ($extension === 'txt')
		{
			$content              = file_get_contents($absolutePath);
			$preview['overflowY'] = true;
			$preview['content']   = '<div class="wrapper" style="max-width:100%;margin:5px;padding:20px;background-color:white;"><pre style="white-space:break-spaces;">' . htmlspecialchars($content) . '</pre></div>';

			return $preview;
		}

		if (in_array($extension, self::WORD_EXTENSIONS, true))
		{
			return $this->buildWordPreview($absolutePath, $extension);
		}

		if (in_array($extension, self::SHEET_EXTENSIONS, true))
		{
			return $this->buildSheetPreview($absolutePath);
		}

		$preview['status'] = false;
		$preview['error']  = 'unsupported';

		return $preview;
	}

	/**
	 * Build the HTML preview envelope for a Word-family document (doc, docx, odt, rtf).
	 * Public so legacy consumers (e.g. models/application.php) share this single implementation.
	 *
	 * @param   string  $absolutePath  Absolute path to the stored file.
	 * @param   string  $extension     Lower-cased file extension, used to pick the PhpWord reader.
	 *
	 * @return  array{status: bool, content: string, style: string, overflowX: bool, overflowY: bool, msg: string, error: string}
	 */
	public function buildWordPreview(string $absolutePath, string $extension): array
	{
		$preview = $this->baseEnvelope();
		$this->ensureAutoload();

		// Escape the document's own text/attributes at the source: any markup a user typed into
		// the .docx (e.g. "<img src=x onerror=...>") is emitted as inert text, not live HTML.
		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

		$class = match ($extension) {
			'odt'   => 'ODText',
			'rtf'   => 'RTF',
			default => 'Word2007',
		};

		$phpWord    = \PhpOffice\PhpWord\IOFactory::load($absolutePath, $class);
		$htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
		$content    = $htmlWriter->getContent();

		$contentWithoutSpaces = preg_replace('/\s+/', '', $content);
		if (strpos($contentWithoutSpaces, '<body></') !== false)
		{
			$preview['status']  = false;
			$preview['error']   = 'unavailable';
			$preview['content'] = '<div style="width:100%;height:100%;display:flex;justify-content:center;align-items:center;"><p style="margin:0;text-align:center;">' . Text::_('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_PREVIEW_UNAVAILABLE') . '</p></div>';

			return $preview;
		}

		// Defence in depth: strip any residual <script>/<iframe>/on*=/javascript: the writer may
		// still emit (e.g. from hyperlinks) before it reaches shadowRoot.innerHTML on the client.
		$preview['content']   = '<div class="wrapper">' . $this->sanitizeHtml($content) . '</div>';
		$preview['overflowY'] = true;
		$preview['style']     = 'word';
		$preview['msg']       = Text::_('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_PREVIEW_INCOMPLETE_MSG');

		return $preview;
	}

	/**
	 * Build the HTML preview envelope for a spreadsheet document (xls, xlsx, ods, csv).
	 * Public so legacy consumers (e.g. models/application.php) share this single implementation.
	 *
	 * @param   string  $absolutePath  Absolute path to the stored file.
	 *
	 * @return  array{status: bool, content: string, style: string, overflowX: bool, overflowY: bool, msg: string, error: string}
	 */
	public function buildSheetPreview(string $absolutePath): array
	{
		$preview = $this->baseEnvelope();
		$this->ensureAutoload();

		$phpSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($absolutePath);
		$htmlWriter     = new \PhpOffice\PhpSpreadsheet\Writer\Html($phpSpreadsheet);
		$htmlWriter->setGenerateSheetNavigationBlock(true);
		$htmlWriter->setSheetIndex(0);

		$preview['content']   = $htmlWriter->generateHtmlAll();
		$preview['overflowY'] = true;
		$preview['overflowX'] = true;
		$preview['style']     = 'sheet';
		$preview['msg']       = Text::_('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_PREVIEW_INCOMPLETE_MSG');

		return $preview;
	}

	/**
	 * The empty preview envelope shared by every build path.
	 *
	 * @return  array{status: bool, content: string, style: string, overflowX: bool, overflowY: bool, msg: string, error: string}
	 */
	private function baseEnvelope(): array
	{
		return [
			'status'    => true,
			'content'   => '',
			'style'     => '',
			'overflowX' => false,
			'overflowY' => false,
			'msg'       => '',
			'error'     => '',
		];
	}

	private function ensureAutoload(): void
	{
		require_once JPATH_LIBRARIES . '/emundus/vendor/autoload.php';
	}

	/**
	 * Sanitize document-derived HTML before it is rendered client-side.
	 * Removes script/iframe/object tags, on* event handlers and dangerous URL schemes while
	 * keeping the layout intact (tags, inline styles and the writer's <style> block).
	 *
	 * @param   string  $html  Raw HTML produced by a document writer.
	 *
	 * @return  string
	 */
	private function sanitizeHtml(string $html): string
	{
		// Blacklist mode: keep every tag EXCEPT the dangerous ones and strip on*/javascript:
		// attributes. The default getInstance() filter whitelists an empty tag set, which would
		// erase the whole document (plain text) — hence a dedicated instance here.
		$filter = new InputFilter(
			[],
			[],
			InputFilter::ONLY_BLOCK_DEFINED_TAGS,
			InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES,
			1
		);

		// Keep <style>: its CSS is generated by the document writer (not user text, which is
		// already escaped at the source) and the preview renders inside a shadow root, so the
		// styles stay scoped. Any <script> the writer might emit is still stripped everywhere.
		$filter->blockedTags = array_values(array_diff($filter->blockedTags, ['style']));

		return $filter->clean($html, 'html');
	}
}
