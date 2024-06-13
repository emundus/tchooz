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
}

?>
