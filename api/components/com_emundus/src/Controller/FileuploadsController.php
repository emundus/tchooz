<?php

namespace Joomla\Component\Emundus\Api\Controller;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Controller\ApiController;

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
}