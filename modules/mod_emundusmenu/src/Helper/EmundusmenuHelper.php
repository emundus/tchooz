<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_languages
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\Emundusmenu\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_languages
 *
 * @since  1.6
 */
class EmundusmenuHelper
{
	/**
	 * Get a list of the menu items.
	 *
	 * @param   JRegistry  $params  The module options.
	 *
	 * @return    array
	 * @since    1.5
	 */
	public static function getList(&$params, $default_menutype = null)
	{
		$app    = Factory::getApplication();
		$user = $app->getIdentity();
		$config = $app->getConfig();
		$is_sef = (bool) $config->get('sef');
		$menu   = $app->getMenu();

		// If no active menu, use default
		$active = ($menu->getActive()) ? $menu->getActive() : $menu->getDefault();

		$levels = $user->getAuthorisedViewLevels();
		// $user is redefined using emundus user session here because getAuthorisedViewLevels is part of the JUser object
		$user = $app->getSession()->get('emundusUser');
		asort($levels);
		$key     = 'menu_items' . $params . implode(',', $levels) . '.' . $active->id;
		$items   = [];

		if ((isset($user->menutype) || !empty($default_menutype)) && empty($items)) {
			// Initialise variables.
			$list = array();
			$db   = Factory::getContainer()->get('DatabaseDriver');

			$path    = $active->tree;
			$start   = (int) $params->get('startLevel');
			$end     = (int) $params->get('endLevel');
			$showAll = $params->get('showAllChildren');
			if ($default_menutype != null) {
				$items = $menu->getItems('menutype', $default_menutype);
			}
			else {
				$items = $menu->getItems('menutype', $user->menutype);
			}

			$lastitem = 0;

			if ($items) {
				foreach ($items as $i => $item) {
					$params = $item->getParams();

					if (($start && $start > $item->level)
						|| ($end && $item->level > $end)
						|| (!$showAll && $item->level > 1 && !in_array($item->parent_id, $path))
						|| ($start > 1 && !in_array($item->tree[$start - 2], $path))
					) {
						unset($items[$i]);
						continue;
					}

					$item->deeper     = false;
					$item->shallower  = false;
					$item->level_diff = 0;

					if (isset($items[$lastitem])) {
						$items[$lastitem]->deeper     = ($item->level > $items[$lastitem]->level);
						$items[$lastitem]->shallower  = ($item->level < $items[$lastitem]->level);
						$items[$lastitem]->level_diff = ($items[$lastitem]->level - $item->level);
					}

					$item->parent = (boolean) $menu->getItems('parent_id', (int) $item->id, true);

					$lastitem     = $i;
					$item->active = false;
					$item->flink  = $item->link;

					// Reverted back for CMS version 2.5.6
					switch ($item->type) {
						case 'separator':
							// No further action needed.
							continue 2;

						case 'url':
							if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
								// If this is an internal Joomla link, ensure the Itemid is set.
								$item->flink = $item->link . '&Itemid=' . $item->id;
							}
							break;

						case 'alias':
							// If this is an alias use the item id stored in the parameters to make the link.
							$item->flink = 'index.php?Itemid=' . $params->get('aliasoptions');
							break;

						default:
							if ($is_sef) {
								$item->flink = 'index.php?Itemid=' . $item->id;
							}
							else {
								$item->flink .= '&Itemid=' . $item->id;
							}
							break;
					}

					if (strcasecmp(substr($item->flink, 0, 4), 'http') && (strpos($item->flink, 'index.php?') !== false)) {
						$item->flink = Route::_($item->flink, true, $params->get('secure'));
					}
					else {
						$item->flink = Route::_($item->flink);
					}

					$item->title        = htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false);
					$item->anchor_css   = htmlspecialchars($params->get('menu-anchor_css', ''), ENT_COMPAT, 'UTF-8', false);
					$item->anchor_title = htmlspecialchars($params->get('menu-anchor_title', ''), ENT_COMPAT, 'UTF-8', false);
					$item->anchor_rel   = htmlspecialchars($params->get('menu-anchor_rel', ''), ENT_COMPAT, 'UTF-8', false);
					$item->menu_image   = $params->get('menu_image', '') ?
						htmlspecialchars($params->get('menu_image', ''), ENT_COMPAT, 'UTF-8', false) : '';
				}

				if (isset($items[$lastitem])) {
					$items[$lastitem]->deeper     = (($start ? $start : 1) > $items[$lastitem]->level);
					$items[$lastitem]->shallower  = (($start ? $start : 1) < $items[$lastitem]->level);
					$items[$lastitem]->level_diff = ($items[$lastitem]->level - ($start ? $start : 1));
				}
			}

			if ($caching) {
				$cache->store($items, $key);
			}
		}

		return $items;
	}
}
