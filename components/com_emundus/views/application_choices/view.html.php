<?php

/**
 * @package     Joomla
 * @subpackage  com_emunudus_onboard
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductRepository;
use Tchooz\Entities\Payment\PaymentStepEntity;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Plugin\PluginHelper;

require_once(JPATH_ROOT . '/components/com_emundus/models/payment.php');

/**
 * eMundus Onboard Campaign View
 *
 * @since  0.0.1
 */
class EmundusViewApplication_choices extends JViewLegacy
{

	public $hash = '';
	public $user = null;

	public int $item_id = 0;

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->user = $app->getIdentity();

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		$this->hash = EmundusHelperCache::getCurrentGitHash();
		$jinput = $app->input;
		$layout = $jinput->getString('layout', 'default');

		parent::display($tpl);
	}
}