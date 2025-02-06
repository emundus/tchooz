<?php
/**
 * @package        Joomla
 * @subpackage     Emundus
 * @copyright      Copyright (C) 2015 emundus.fr. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');


use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Log\Log;

class EmundusHelperMenu
{

	public static function buildMenuQuery($profile, $formids = null, $checklevel = true)
	{
		if (empty($profile)) {
			return false;
		}

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
		$h_cache = new EmundusHelperCache();
		$list = $h_cache->get('menus_'.$profile);

		if (empty($list) || !empty($formids) || !$checklevel) {
			$app  = Factory::getApplication();
			$db   = Factory::getContainer()->get('DatabaseDriver');
			$user = $app->getIdentity();

			$query = $db->getQuery(true);

			$levels = [];
			if ($checklevel) {
				$levels = Access::getAuthorisedViewLevels($user->id);
			}

			$query->select('fbtables.id AS table_id, fbtables.form_id, fbforms.label, fbtables.db_table_name, CONCAT(menu.link,"&Itemid=",menu.id) AS link, menu.id, menu.title, profile.menutype, fbforms.params, menu.params as menu_params')
				->from($db->quoteName('#__menu', 'menu'))
				->innerJoin($db->quoteName('#__emundus_setup_profiles', 'profile') . ' ON ' . $db->quoteName('profile.menutype') . ' = ' . $db->quoteName('menu.menutype') . ' AND ' . $db->quoteName('profile.id') . ' = ' . $db->quote($profile))
				->innerJoin($db->quoteName('#__fabrik_forms', 'fbforms') . ' ON ' . $db->quoteName('fbforms.id') . ' = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("formid=",menu.link)+7, 4), "&", 1)')
				->innerJoin($db->quoteName('#__fabrik_lists', 'fbtables') . ' ON ' . $db->quoteName('fbtables.form_id') . ' = ' . $db->quoteName('fbforms.id'))
				->where($db->quoteName('menu.published') . ' = 1')
				->andWhere($db->quoteName('menu.parent_id') . ' != 1');
			if ($checklevel && !empty($levels)) {
				$query->andWhere($db->quoteName('menu.access') . ' IN (' . implode(',', $levels) . ')');
			}

			if (!empty($formids) && $formids[0] != "") {
				$query->andWhere($db->quoteName('fbtables.id') . ' IN (' . implode(',', $formids) . ')');
			}
			$query->order('menu.lft');

			try {
				$db->setQuery($query);
				$list = $db->loadObjectList();

				$query->clear()
					->select('fbtables.id AS table_id, fbtables.form_id, fbforms.label, fbtables.db_table_name, CONCAT(menu.link,"&Itemid=",menu.id) AS link, menu.id, menu.title, profile.menutype, fbforms.params, menu.params as menu_params')
					->from($db->quoteName('#__menu', 'menu'))
					->innerJoin($db->quoteName('#__emundus_setup_profiles', 'profile') . ' ON ' . $db->quoteName('profile.menutype') . ' = ' . $db->quoteName('menu.menutype') . ' AND ' . $db->quoteName('profile.id') . ' = ' . $db->quote($profile))
					->innerJoin($db->quoteName('#__fabrik_forms', 'fbforms') . ' ON ' . $db->quoteName('fbforms.id') . ' = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("formid=",menu.link)+7, 4), "&", 1)')
					->innerJoin($db->quoteName('#__fabrik_lists', 'fbtables') . ' ON ' . $db->quoteName('fbtables.form_id') . ' = ' . $db->quoteName('fbforms.id'))
					->where($db->quoteName('menu.published') . ' = 1')
					->andWhere($db->quoteName('menu.parent_id') . ' != 1');
				if ($checklevel && !empty($levels)) {
					$query->andWhere($db->quoteName('menu.access') . ' IN (' . implode(',', $levels) . ')');
				}

				if (!empty($formids) && $formids[0] != "") {
					$query->andWhere($db->quoteName('fbtables.form_id') . ' IN (' . implode(',', $formids) . ')');
				}
				$query->order('menu.lft');

				$db->setQuery($query);
				$forms = $db->loadObjectList();

				// merge forms and lists
				$list = array_merge($list, $forms);

				// remove duplicates
				$ids = [];
				foreach ($list as $key => $item) {
					if (in_array($item->form_id, $ids)) {
						unset($list[$key]);
					}
					else {
						$ids[] = $item->form_id;
					}
				}

				if(empty($formids) && $checklevel) {
					$h_cache->set('menus_' . $profile, $list);
				}
			}
			catch (Exception $e) {
				throw new $e->getMessage();
			}
		}

		return $list;
	}

	public static function getUserApplicationMenu($profile, $formids = null)
	{
		if (empty($profile)) {
			return false;
		}

		if(!class_exists('EmundusHelperCache')) {
			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
		}
		$h_cache = new EmundusHelperCache();
		$list = $h_cache->get('menus_'.$profile);

		$app  = Factory::getApplication();
		$db   = Factory::getDBO();
		$user = $app->getIdentity();

		$levels = Access::getAuthorisedViewLevels($user->id);

		$query = $db->getQuery(true);

		$query->select('fbtables.id AS table_id, fbtables.form_id, fbforms.label, fbtables.db_table_name, CONCAT(menu.link,"&Itemid=",menu.id) AS link, menu.id, menu.title, profile.menutype, fbforms.params, menu.params as menu_params')
			->from($db->quoteName('#__menu', 'menu'))
			->innerJoin($db->quoteName('#__emundus_setup_profiles', 'profile') . ' ON ' . $db->quoteName('profile.menutype') . ' = ' . $db->quoteName('menu.menutype') . ' AND ' . $db->quoteName('profile.id') . ' = ' . $db->quote($profile))
			->innerJoin($db->quoteName('#__fabrik_forms', 'fbforms') . ' ON ' . $db->quoteName('fbforms.id') . ' = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("formid=",menu.link)+7, 4), "&", 1)')
			->leftJoin($db->quoteName('#__fabrik_lists', 'fbtables') . ' ON ' . $db->quoteName('fbtables.form_id') . ' = ' . $db->quoteName('fbforms.id'))
			->where($db->quoteName('menu.published') . ' = 1')
			->where($db->quoteName('menu.parent_id') . ' != 1')
			->where($db->quoteName('menu.access') . ' IN (' . implode(',', $levels) . ')');
		if (!empty($formids) && $formids[0] != "") {
			$query->where($db->quote('fbtables.form_id') . ' IN (' . implode(',', $formids) . ')');
		}
		$query->order('menu.lft');

		try {
			$db->setQuery($query);

			$lists = $db->loadObjectList();

			$h_cache->set('menus_' . $profile, $list);
		}
		catch (Exception $e) {
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $lists;
	}

	function buildMenuListQuery($profile)
	{
		$menu_lists = array();

		if (version_compare(JVERSION, '4.0', '>')) {
			$db = Factory::getContainer()->get('DatabaseDriver');
		}
		else {
			$db = Factory::getDBO();
		}

		$query = $db->getQuery(true);

		$query->select('fbtables.db_table_name')
			->from($db->quoteName('#__menu', 'menu'))
			->innerJoin($db->quoteName('#__emundus_setup_profiles', 'profile') . ' ON ' . $db->quoteName('profile.menutype') . ' = ' . $db->quoteName('menu.menutype') . ' AND ' . $db->quoteName('profile.id') . ' = ' . $db->quote($profile))
			->innerJoin($db->quoteName('#__fabrik_forms', 'fbforms') . ' ON ' . $db->quoteName('fbforms.id') . ' = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("formid=",menu.link)+7, 4), "&", 1)')
			->leftJoin($db->quoteName('#__fabrik_lists', 'fbtables') . ' ON ' . $db->quoteName('fbtables.form_id') . ' = ' . $db->quoteName('fbforms.id'))
			->where($db->quoteName('menu.published') . ' = 1')
			->where($db->quoteName('menu.parent_id') . ' != 1')
			->order('menu.lft');

		try {
			$db->setQuery($query);
			$menu_lists = $db->loadResultArray();
		}
		catch (Exception $e) {
			throw new $e->getMessage();
		}

		return $menu_lists;
	}


	static function getHomepageLink($default_link = null)
	{
		$menu = 'index.php';

		$live_site = Factory::getConfig()->get('live_site');
		$parsedUrl = parse_url($live_site);
		$path = ltrim($parsedUrl['path'], '/');

		$activeLanguage = Factory::getApplication()->getLanguage()->getTag();
		$languages = LanguageHelper::getLanguages('lang_code');
		$defaultLanguage = ComponentHelper::getParams('com_languages')->get('site', 'fr-FR');
		$sef = '';
		if (isset($languages[$activeLanguage]) && $activeLanguage !== $defaultLanguage)
		{
			$sef = $languages[$activeLanguage]->sef;
		}

		$homepage_itemId = ComponentHelper::getParams('com_emundus')->get('logged_homepage_link', '');

		if(!empty($homepage_itemId)) {
			$menu = Factory::getApplication()->getMenu()->getItem($homepage_itemId);
			if(!empty($menu)) {
				$menu = $menu->alias;
			}
		}

		if(!in_array($default_link, ['/','index.php','']) && $default_link !== $menu) {
			$menu = $default_link;
		} else {
			$menu = !empty($sef) ? '/'.$sef.'/'.$menu : '/'.$menu;
		}

		// Add potentially missing sub folder
		if (!empty($path) && strpos($menu, $path) === false) {
			$menu = $path.$menu;
		}

		return $menu;
	}

	public static function getLogoutRedirectLink()
	{
		$logout_page_item_id = ComponentHelper::getParams('com_emundus')->get('logout_page_link', '');

		if (empty($logout_page_item_id)) {
			$menu = EmundusHelperMenu::getHomepageLink();
		} else {
			$menu = Factory::getApplication()->getMenu()->getItem($logout_page_item_id);

			if (!empty($menu)) {
				$menu = $menu->alias;
			} else {
				$menu = EmundusHelperMenu::getHomepageLink();
			}
		}

		return $menu;
	}

	static function getAdminLink() {
		$menu = 'administrator/index.php';

		$htaccess = JPATH_BASE . '/.htaccess';
		if (file_exists($htaccess)) {
			$htaccess = file_get_contents($htaccess);
			if (strpos($htaccess, 'RewriteCond %{HTTP_REFERER} !.*administrator/') !== false) {
				preg_match('/RewriteCond %{QUERY_STRING} !\^([a-zA-Z0-9]+)\$/', $htaccess, $matches);
				if(!empty($matches) && isset($matches[1])) {
					$menu = 'administrator/index.php?'.$matches[1];
				}
			}
		}

		return $menu;
	}

	static function getSefAliasByLink($link) {
		$alias = '';

		$activeLanguage = Factory::getApplication()->getLanguage()->getTag();
		$languages = LanguageHelper::getLanguages('lang_code');
		$sef = '';
		if (isset($languages[$activeLanguage]))
		{
			$sef = $languages[$activeLanguage]->sef;
		}

		$menu  = Factory::getApplication()->getMenu();
		$item = $menu->getItems('link', $link, true);

		if(!empty($item)) {
			$alias = !empty($sef) ? $sef.'/'.$item->alias : $item->alias;
		}

		return $alias;
	}

	static function getNonce() {
		$sitename = ComponentHelper::getParams('com_emundus')->get('sitename');

		// Step 1: Hash the input string
		$hash = md5($sitename);

		// Step 2: Convert the hash to a numeric value
		$numericHash = self::hexToDec($hash);

		// Step 3: Split the numeric value into parts of 7 digits each
		$part1 = substr($numericHash, 0, 7);
		$part2 = substr($numericHash, 7, 7);
		$part3 = substr($numericHash, 14, 7);

		// Step 4: Combine the parts with hyphens
		return sprintf('%s-%s-%s', $part1, $part2, $part3);
	}

	private static function hexToDec($hex) {
		$dec = '0';
		$len = strlen($hex);

		for ($i = 0; $i < $len; $i++) {
			$dec = bcmul($dec, '16');
			$dec = bcadd($dec, hexdec($hex[$i]));
		}

		return $dec;
	}
	
	public static function routeViaLink($link){
		$language = Factory::getApplication()->getLanguage()->getTag();
		$options_to_set = [];
		$segments       = explode('?', $link);
		$segments       = explode('&', $segments[1]);

		$exceptions = ['view', 'layout', 'option', 'format', 'formid'];
		foreach ($segments as $key => $segment)
		{
			$segment = explode('=', $segment);

			if (!in_array($segment[0], $exceptions))
			{
				$options_to_set[$segment[0]] = $segment[1];
				unset($segments[$key]);
			}
		}
		$link = 'index.php?' . implode('&', $segments);

		$menu = Factory::getApplication()->getMenu()->getItems('link', $link, true);

		if (!empty($menu))
		{
			$languages = LanguageHelper::getLanguages('lang_code');
			$sef       = '';
			if (isset($languages[$language]))
			{
				$sef = $languages[$language]->sef;
			}
			$link = !empty($sef) ? $sef . '/' . $menu->route : $menu->route;

			if (!empty($options_to_set))
			{
				$link .= '?' . http_build_query($options_to_set);
			}
		}

		return $link;
	}
}

?>
