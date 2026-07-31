<?php

/**
 * @package     Joomla.API
 * @subpackage  com_emundus
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Api\Controller;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Controller\ApiController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The attachments controller
 *
 * @since  4.0.0
 */
class AttachmentsController extends ApiController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $contentType = 'attachments';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $default_view = 'attachments';

	/**
	 * List view amended to add filtering of data
	 *
	 * @return  static  A BaseController object to support chaining.
	 *
	 * @since   4.0.0
	 */
	public function displayList()
	{
		$filters = $this->input->get('filter', [], 'array');

		if (\array_key_exists('published', $filters)) {
			$this->modelState->set('filter.published', InputFilter::getInstance()->clean($filters['published'], 'INT'));
		}

		if (\array_key_exists('category', $filters)) {
			$this->modelState->set('filter.category', InputFilter::getInstance()->clean($filters['category'], 'STRING'));
		}

		return parent::displayList();
	}
}
