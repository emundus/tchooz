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


use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;

class EmundusHelperMenu
{
	public static function buildMenuQuery($profile, $formids = null, $checklevel = true, int $userId = 0): array|false
	{
		if (empty($profile))
		{
			return false;
		}

		if (!class_exists('EmundusHelperCache'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		}
		$hCache = new EmundusHelperCache('com_emundus.menus');

		// Try to get all menus for this profile from cache (without contextual filters)
		$allMenus = $hCache->get('menus_' . $profile);

		if (empty($allMenus))
		{
			$allMenus = self::fetchMenusForProfile($profile);

			if (!empty($allMenus))
			{
				$hCache->set('menus_' . $profile, $allMenus);
			}
		}

		if (empty($allMenus))
		{
			return [];
		}

		// Apply contextual filters in PHP
		$list = $allMenus;

		// Filter by access levels
		if ($checklevel)
		{
			$app = Factory::getApplication();

			if (empty($userId))
			{
				$user = $app->getIdentity();
			}
			else
			{
				$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
			}

			$levels = Access::getAuthorisedViewLevels($user->id);

			if (!empty($levels))
			{
				$list = array_filter($list, function ($item) use ($levels) {
					return in_array($item->access, $levels);
				});
			}
		}

		// Filter by formids (match on table_id OR form_id)
		if (!empty($formids) && $formids[0] != "")
		{
			$formids = array_map('intval', $formids);
			$list    = array_filter($list, function ($item) use ($formids) {
				return in_array((int) $item->table_id, $formids) || in_array((int) $item->form_id, $formids);
			});
		}

		return array_values($list);
	}

	/**
	 * Fetch all menus for a given profile from the database (no contextual filters).
	 * A single query is used, and results are deduplicated by form_id.
	 */
	private static function fetchMenusForProfile(int $profile): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('fbtables.id AS table_id, fbtables.form_id, fbforms.label, fbtables.db_table_name, CONCAT(menu.link,"&Itemid=",menu.id) AS link, menu.id, menu.title, menu.access, profile.menutype, fbforms.params, menu.params as menu_params, menu.lft')
			->from($db->quoteName('#__menu', 'menu'))
			->innerJoin($db->quoteName('#__emundus_setup_profiles', 'profile') . ' ON ' . $db->quoteName('profile.menutype') . ' = ' . $db->quoteName('menu.menutype') . ' AND ' . $db->quoteName('profile.id') . ' = ' . $db->quote($profile))
			->innerJoin($db->quoteName('#__fabrik_forms', 'fbforms') . ' ON ' . $db->quoteName('fbforms.id') . ' = SUBSTRING_INDEX(SUBSTRING(menu.link, LOCATE("formid=",menu.link)+7, 4), "&", 1)')
			->innerJoin($db->quoteName('#__fabrik_lists', 'fbtables') . ' ON ' . $db->quoteName('fbtables.form_id') . ' = ' . $db->quoteName('fbforms.id'))
			->where($db->quoteName('menu.published') . ' = 1')
			->andWhere($db->quoteName('menu.parent_id') . ' != 1')
			->order('menu.lft');

		try
		{
			$db->setQuery($query);
			$rows = $db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus.menu');

			return [];
		}

		// Deduplicate by form_id
		$seen = [];
		$list = [];
		foreach ($rows as $item)
		{
			if (!in_array($item->form_id, $seen))
			{
				$seen[] = $item->form_id;
				$list[] = $item;
			}
		}

		return $list;
	}

	/**
	 * @deprecated Use buildMenuQuery() instead.
	 */
	public static function getUserApplicationMenu($profile, $formids = null): false|array
	{
		return self::buildMenuQuery($profile, $formids, true);
	}


	public static function getApplicantFormsInMenus(?string $menutype = null): array
	{
		$formIds = [];

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$fabrik_component_id = ComponentHelper::getComponent('com_fabrik')->id;

		$query->clear()
			->select('link')
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('published') . ' = 1')
			->andWhere($db->quoteName('type') . ' = ' . $db->quote('component'))
			->andWhere($db->quoteName('component_id') . ' = ' . $db->quote($fabrik_component_id))
			->order('lft');
		if(!empty($menutype))
		{
			$query->andWhere($db->quoteName('menutype') . ' LIKE ' . $db->quote($menutype));
		}
		else {
			$query->andWhere($db->quoteName('menutype') . ' LIKE ' . $db->quote('menu-profile%'));
		}

		try
		{
			$db->setQuery($query);
			$links = $db->loadColumn();
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');

			return $formIds;
		}

		if (!empty($links))
		{
			foreach ($links as $link)
			{
				preg_match('/formid=([0-9]+)/', $link, $matches);
				if (isset($matches[1]) && is_numeric($matches[1]))
				{
					$formIds[] = (int) $matches[1];
				}
			}
		}


		return $formIds;
	}

	public static function getHomepageLink($default_link = null): string
	{
		$app  = Factory::getApplication();
		$menu = 'index.php';

		$live_site = $app->getConfig()->get('live_site');
		$parsedUrl = parse_url($live_site);
		$path      = ltrim($parsedUrl['path'], '/');

		$activeLanguage  = $app->getLanguage()->getTag();
		$languages       = LanguageHelper::getLanguages('lang_code');
		$defaultLanguage = ComponentHelper::getParams('com_languages')->get('site', 'fr-FR');
		$sef             = '';
		if (isset($languages[$activeLanguage]) && $activeLanguage !== $defaultLanguage)
		{
			$sef = $languages[$activeLanguage]->sef;
		}

		$homepage_itemId = ComponentHelper::getParams('com_emundus')->get('logged_homepage_link', '');

		if (!empty($homepage_itemId))
		{
			$menu = $app->getMenu()->getItem($homepage_itemId);
			if (!empty($menu))
			{
				$menu = $menu->alias;
			}
		}

		if (!in_array($default_link, ['/', 'index.php', '']) && $default_link !== $menu)
		{
			$menu = $default_link;
		}
		else
		{
			$menu = !empty($sef) ? '/' . $sef . '/' . $menu : '/' . $menu;
		}

		// Add potentially missing sub folder
		if (!empty($path) && strpos($menu, $path) === false)
		{
			$menu = $path . $menu;
		}

		return $menu;
	}

	public static function getBaseUriWithLang(): string
	{
		$app         = Factory::getApplication();
		$currentLang = $app->getLanguage()->getTag();

		$defaultLang = Multilanguage::isEnabled() ? ComponentHelper::getParams('com_languages')->get('site', 'fr-FR') : $currentLang;

		$base = rtrim(Uri::base(), '/');

		if ($currentLang !== $defaultLang)
		{
			$base .= '/' . substr($currentLang, 0, 2);
		}

		return $base;
	}

	public static function getLogoutRedirectLink(): string
	{
		$logout_page_item_id = ComponentHelper::getParams('com_emundus')->get('logout_page_link', '');

		if (empty($logout_page_item_id))
		{
			$menu = EmundusHelperMenu::getHomepageLink();
		}
		else
		{
			$menu = Factory::getApplication()->getMenu()->getItem($logout_page_item_id);

			if (!empty($menu))
			{
				$menu = $menu->alias;
			}
			else
			{
				$menu = EmundusHelperMenu::getHomepageLink();
			}
		}

		return $menu;
	}

	static function getAdminLink(): string
	{
		$menu = 'administrator/index.php';

		$htaccess = JPATH_BASE . '/.htaccess';
		if (file_exists($htaccess))
		{
			$htaccess = file_get_contents($htaccess);
			if (strpos($htaccess, 'RewriteCond %{HTTP_REFERER} !.*administrator/') !== false)
			{
				preg_match('/RewriteCond %{QUERY_STRING} !\^([a-zA-Z0-9]+)\$/', $htaccess, $matches);
				if (!empty($matches) && isset($matches[1]))
				{
					$menu = 'administrator/index.php?' . $matches[1];
				}
			}
		}

		return $menu;
	}

	static function getSefAliasByLink($link): string
	{
		$alias = '';

		$activeLanguage = Factory::getApplication()->getLanguage()->getTag();
		$languages      = LanguageHelper::getLanguages('lang_code');
		$sef            = '';
		if (isset($languages[$activeLanguage]))
		{
			$sef = $languages[$activeLanguage]->sef;
		}

		$menu = Factory::getApplication()->getMenu();
		$item = $menu->getItems('link', $link, true);

		if (!empty($item))
		{
			$alias = !empty($sef) ? $sef . '/' . $item->alias : $item->alias;
		}

		return $alias;
	}

	static function getNonce(): string
	{
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

	private static function hexToDec($hex): string
	{
		$dec = '0';
		$len = strlen($hex);

		for ($i = 0; $i < $len; $i++)
		{
			$dec = bcmul($dec, '16');
			$dec = bcadd($dec, hexdec($hex[$i]));
		}

		return $dec;
	}

	public static function routeViaLink($link): string
	{
		$app            = Factory::getApplication();
		$language       = $app->getLanguage()->getTag();
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

		$menu = $app->getMenu()->getItems('link', $link, true);

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

		if (!str_starts_with($link, '/'))
		{
			$link = '/' . $link;
		}

		return $link;
	}
}

?>
