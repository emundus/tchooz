<?php

/**
 * @package         Joomla.API
 * @subpackage      com_files
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Api\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use libphonenumber\PhoneNumberUtil;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\ExternalReferenceEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\ExternalReferenceRepository;
use Tobscure\JsonApi\Exception\InvalidParameterException;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The article controller
 *
 * @since  4.0.0
 */
class StatisticsController extends ApiController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $contentType = 'statistics';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $default_view = 'statistics';

	public function displayList()
	{
		return parent::displayList();
	}
	

	public function displayItem($id = null)
	{
		$item = $this->getModel('statistic')->getItem($id);

		return parent::displayItem($item);
	}
}
