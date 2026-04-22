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
class AddonsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $contentType = 'addons';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  3.0
     */
    protected $default_view = 'addons';

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
        $filter        = InputFilter::getInstance();

        if (\array_key_exists('activated', $apiFilterInfo)) {
            $this->modelState->set('filter.activated', $filter->clean($apiFilterInfo['activated'], 'INT'));
        }

	    if (\array_key_exists('subscribed', $apiFilterInfo)) {
		    $this->modelState->set('filter.subscribed', $filter->clean($apiFilterInfo['subscribed'], 'INT'));
	    }

	    if (\array_key_exists('name', $apiFilterInfo)) {
		    $this->modelState->set('filter.name', $filter->clean($apiFilterInfo['name'], 'STRING'));
	    }

        return parent::displayList();
    }
}
