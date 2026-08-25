<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

class EmundusViewLanguages extends HtmlView
{
	function display($tpl = null): void
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
		}

		if (!EmundusHelperAccess::asAdministratorAccessLevel($user->id))
		{
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/');

			return;
		}

		parent::display($tpl);
	}
}
