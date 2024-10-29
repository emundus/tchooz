<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_version
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\EmundusOauth2\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
use Joomla\CMS\Plugin\PluginHelper;

\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_emundus_oauth2
 *
 * @since  1.6
 */
class EmundusOauth2Helper
{
	/**
	 *
	 * @return array
	 *
	 * @since version 2.0.0
	 */
	public function getActiveDirectories()
	{
		$active_directories = array();
		require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'menu.php');

		$emundusOauth2 = PluginHelper::getPlugin('authentication','emundus_oauth2');
		if(!empty($emundusOauth2)) {
			$oauth2Config = json_decode($emundusOauth2->params);
			foreach ($oauth2Config->configurations as $key => $config) {
				if(in_array($config->display_on_login,[2,3]) && !empty($config->client_id)) {
					$active_directories[] = $config;
				}
			}
		}

		return $active_directories;
	}
}
