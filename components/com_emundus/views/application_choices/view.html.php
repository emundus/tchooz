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
use Joomla\CMS\User\User;

class EmundusViewApplication_choices extends HtmlView
{
	protected ?User $user = null;

	protected ?string $fnum = '';

	public int $item_id = 0;

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->user = $this->getCurrentUser();

		$this->fnum = $app->input->getString('fnum', '');
		if(!empty($this->fnum))
		{
			if(!class_exists('EmundusModelProfile'))
			{
				require_once JPATH_ROOT . '/components/com_emundus/models/profile.php';
			}
			$m_profile = new EmundusModelProfile();
			$m_profile->initEmundusSession($this->fnum);
		}

		$layout = $app->input->getString('layout', 'default');

		parent::display($tpl);
	}
}