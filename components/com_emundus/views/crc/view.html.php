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

class EmundusViewCrc extends HtmlView
{

	function display($tpl = null)
	{
		$input = Factory::getApplication()->getInput();

		$layout = $input->getString('layout', 'default');
		if ($layout == 'contactform' || $layout == 'organizationform') {
			$this->id = $input->getInt('id', 0);
		}

		parent::display($tpl);
	}
}
