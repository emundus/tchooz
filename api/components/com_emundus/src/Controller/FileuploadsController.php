<?php

namespace Joomla\Component\Emundus\Api\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Controller\ApiController;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class FileuploadsController extends ApiController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $contentType = 'fileuploads';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $default_view = 'fileuploads';

	/**
	 * Article list view amended to add filtering of data
	 *
	 * @return  static  A BaseController object to support chaining.
	 *
	 * @since   4.0.0
	 */
	public function displayList()
	{
		$filters = $this->input->get('filter', [], 'array');

		if (\array_key_exists('fnum', $filters)) {
			$this->modelState->set('filter.fnum', InputFilter::getInstance()->clean($filters['fnum'], 'STRING'));
		}

		return parent::displayList();
	}

	public function displayItem($id = null)
	{
		$item = $this->getModel('fileupload')->getItem($id);

		return parent::displayItem($item);
	}

	/**
	 * Streams the physical upload file as a download.
	 *
	 * @param   int|null  $id  The upload id.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 * @throws  InvalidParameterException
	 */
	public function download($id = null): void
	{
		$id = (int) ($id ?? $this->input->getInt('id', 0));

		if ($id <= 0) {
			throw new InvalidParameterException('A valid upload id is required.', 400);
		}

		$item = $this->getModel('fileupload')->getItem($id);

		if (empty($item) || empty($item->filename) || empty($item->applicant_id)) {
			throw new InvalidParameterException('The requested upload does not exist.', 404);
		}

		$baseDir = JPATH_SITE . '/images/emundus/files/' . (int) $item->applicant_id;
		$file    = $baseDir . '/' . basename($item->filename);

		// Guard against path traversal: resolved path must stay inside the applicant folder.
		$realBase = realpath($baseDir);
		$realFile = realpath($file);

		if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase . '/')) {
			throw new InvalidParameterException('The requested file does not exist.', 404);
		}

		$downloadName = !empty($item->local_filename) ? $item->local_filename : basename($item->filename);

		$app = Factory::getApplication();

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
		header('Content-Length: ' . filesize($realFile));
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');

		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		readfile($realFile);

		$app->close();
	}
}