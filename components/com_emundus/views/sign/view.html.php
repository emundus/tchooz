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
use Joomla\CMS\MVC\View\HtmlView;

class EmundusViewSign extends HtmlView
{

	function display($tpl = null)
	{
		$jinput = Factory::getApplication()->input;

		$layout = $jinput->getString('layout', null);
		if ($layout == 'add') {
			$this->id = $jinput->getString('rid', null);
		}

		parent::display($tpl);
	}
}
