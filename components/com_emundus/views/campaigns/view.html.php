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

/**
 * eMundus Onboard Campaign View
 *
 * @since  0.0.1
 */
class EmundusViewCampaigns extends JViewLegacy
{

	protected $tabs_to_display = 'global,more,form,emails,history';

	function display($tpl = null)
	{
		$jinput = Factory::getApplication()->input;

		$layout = $jinput->getString('layout', null);
		if ($layout == 'add') {
			$this->id = $jinput->getString('cid', null);
		}

		$menu                         = Factory::getApplication()->getMenu();
		$current_menu                 = $menu->getActive();
		$menu_params                  = $menu->getParams($current_menu->id);
		if($menu_params->get('tabs_to_display')) {
			if(!empty($menu_params->get('tabs_to_display'))) {
				$this->tabs_to_display = implode(',', $menu_params->get('tabs_to_display'));
			}
		}

		parent::display($tpl);
	}
}
