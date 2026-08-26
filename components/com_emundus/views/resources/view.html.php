<?php
/**
 * @package     Joomla
 * @subpackage  com_emunudus_onboard
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Factories\Resource\ResourceFactory;

defined('_JEXEC') or die('Restricted access');

class EmundusViewResources extends HtmlView
{
	function display($tpl = null): void
	{
		$app    = Factory::getApplication();

		parent::display($tpl);
	}
}