<?php
/**
 * @package     Tchooz\Services\Export\Pdf
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Pdf;

use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;

/**
 * Concatenates multiple PDFs into a single output file.
 *
 * Picks the backend (FPDI ConcatPdf vs Gotenberg) from com_emundus configuration so that the choice
 * lives in exactly one place instead of being duplicated in every export service that needs PDF merging.
 */
class PdfMerger
{
	private Registry $config;

	public function __construct(?Registry $config = null)
	{
		$this->config = $config ?? ComponentHelper::getParams('com_emundus');
		Log::addLogger(['text_file' => 'com_emundus.export.pdf.merger.php'], Log::ALL, ['com_emundus.export.pdf.merger']);
	}

	/**
	 * Merge the given PDF files into $outputPath. Returns true when the merge succeeded and the file exists.
	 *
	 * @param   string[]  $files       Absolute paths of the PDFs to merge in order. Empty entries are ignored.
	 * @param   string    $outputPath  Absolute path of the output PDF.
	 */
	public function merge(array $files, string $outputPath): bool
	{
		$files = array_values(array_filter($files, fn($f) => is_string($f) && $f !== '' && file_exists($f)));

		if (empty($files))
		{
			return false;
		}

		try
		{
			if ($this->shouldUseGotenberg(count($files)))
			{
				return $this->mergeWithGotenberg($files, $outputPath);
			}

			return $this->mergeWithFpdi($files, $outputPath);
		}
		catch (\Throwable $e)
		{
			Log::add('PDF merge failed: ' . $e->getMessage(), Log::ERROR, 'com_emundus.export.pdf.merger');

			return false;
		}
	}

	private function shouldUseGotenberg(int $fileCount): bool
	{
		if ($fileCount <= 1)
		{
			return false;
		}

		if (!(int) $this->config->get('gotenberg_merge_activation', 0))
		{
			return false;
		}

		return !empty((string) $this->config->get('gotenberg_url', ''));
	}

	private function mergeWithFpdi(array $files, string $outputPath): bool
	{
		require_once JPATH_LIBRARIES . '/emundus/fpdi.php';

		$pdf = new \ConcatPdf();
		$pdf->setFiles($files);
		$pdf->concat();
		$pdf->Output($outputPath, 'F');

		return file_exists($outputPath);
	}

	private function mergeWithGotenberg(array $files, string $outputPath): bool
	{
		$streams = array_map(fn($file) => Stream::path($file), $files);

		$request  = Gotenberg::pdfEngines((string) $this->config->get('gotenberg_url'))->merge(...$streams);
		$response = Gotenberg::send($request);

		return file_put_contents($outputPath, $response->getBody()->getContents()) !== false;
	}
}
