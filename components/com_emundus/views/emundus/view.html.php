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

defined('_JEXEC') or die('Restricted access');

/**
 * eMundus Onboard eMundus View
 *
 * @since  0.0.1
 */
class EmundusViewEmundus extends JViewLegacy
{
	protected $columns = 'title,message_language_key,log_date,user_id,diff';

	protected $more_data_columns = '';

	function display($tpl = null)
	{
		$jinput = Factory::getApplication()->input;

		// Display the template
		$layout = $jinput->getString('layout', null);

		$menu                         = Factory::getApplication()->getMenu();
		$current_menu                 = $menu->getActive();
		$menu_params                  = $menu->getParams($current_menu->id);
		if ($menu_params->get('columns')) {
			if(!empty($menu_params->get('columns'))) {
				$this->columns = implode(',', $menu_params->get('columns'));
			}
		}

		$more_data = $menu_params->get('more_data', '');
		if (!empty($more_data)) {
			$this->more_data_columns = $more_data;
			$this->more_data_columns = explode(',', $this->more_data_columns);
		}

		// Display the template
		parent::display($tpl);
	}
}
