<?php

/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Api\Controller;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Controller\ApiController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The article controller
 *
 * @since  4.0.0
 */
class TransactionsController extends ApiController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $contentType = 'transactions';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $default_view = 'transactions';

	/**
	 * Article list view amended to add filtering of data
	 *
	 * @return  static  A BaseController object to support chaining.
	 *
	 * @since   4.0.0
	 */
	public function displayList()
	{
		$apiFilterInfo = $this->input->get('filter', [], 'array');

		if (array_key_exists('status', $apiFilterInfo)) {
			$this->modelState->set('filter.status', InputFilter::getInstance()->clean($apiFilterInfo['status'], 'STRING'));
		}

		// filter on method
		if (array_key_exists('payment_method_id', $apiFilterInfo)) {
			$this->modelState->set('filter.payment_method_id', InputFilter::getInstance()->clean($apiFilterInfo['payment_method_id'], 'STRING'));
		}

		// filter on synchronizer
		if (array_key_exists('synchronizer', $apiFilterInfo)) {
			$this->modelState->set('filter.synchronizer_id', InputFilter::getInstance()->clean($apiFilterInfo['synchronizer'], 'STRING'));
		}

		if (array_key_exists('external_reference', $apiFilterInfo)) {
			$this->modelState->set('filter.external_reference', InputFilter::getInstance()->clean($apiFilterInfo['external_reference'], 'STRING'));
		}

		if (array_key_exists('fnum', $apiFilterInfo)) {
			$this->modelState->set('filter.fnum', InputFilter::getInstance()->clean($apiFilterInfo['fnum'], 'STRING'));
		}

		if (array_key_exists('user_id', $apiFilterInfo)) {
			$this->modelState->set('filter.user_id', InputFilter::getInstance()->clean($apiFilterInfo['user_id'], 'INT'));
		}

		// filter on date range
		if (array_key_exists('date_from', $apiFilterInfo)) {
			$this->modelState->set('filter.date_from', InputFilter::getInstance()->clean($apiFilterInfo['date_from'], 'STRING'));
		}

		if (array_key_exists('date_to', $apiFilterInfo)) {
			$this->modelState->set('filter.date_to', InputFilter::getInstance()->clean($apiFilterInfo['date_to'], 'STRING'));
		}

		return parent::displayList();
	}

	public function displayItem($id = null)
	{
		$item = $this->getModel('transaction')->getItem($id);

		return parent::displayItem($item);
	}
}
