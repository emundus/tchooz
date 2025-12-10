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
class UsersController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $contentType = 'users';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  3.0
     */
    protected $default_view = 'users';

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

		if (\array_key_exists('id', $apiFilterInfo)) {
		    $this->modelState->set('filter.id', $filter->clean($apiFilterInfo['id'], 'INT'));
	    }
	    if (\array_key_exists('email', $apiFilterInfo)) {
		    $this->modelState->set('filter.email', $filter->clean($apiFilterInfo['email'], 'STRING'));
	    }

	    $apiListInfo = $this->input->get('list', [], 'array');

	    if (\array_key_exists('ordering', $apiListInfo)) {
		    $this->modelState->set('list.ordering', $filter->clean($apiListInfo['ordering'], 'STRING'));
	    }

	    if (\array_key_exists('direction', $apiListInfo)) {
		    $this->modelState->set('list.direction', $filter->clean($apiListInfo['direction'], 'STRING'));
	    }

        return parent::displayList();
    }
}
