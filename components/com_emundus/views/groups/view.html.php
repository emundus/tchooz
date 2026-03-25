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
use Joomla\CMS\MVC\View\HtmlView;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class EmundusViewGroups extends HtmlView
{
	function display($tpl = null): void
	{
		$app    = Factory::getApplication();

		$layout = $app->input->getString('layout', '');
		if ($layout == 'form')
		{
			$this->id = $app->input->getString('id', 0);
		}

		parent::display($tpl);
	}
}
