<?php
/**
 * @package     Tchooz\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Tchooz\Services\Addons\AbstractAddonHandler;

class FavoriteAddonHandler extends AbstractAddonHandler
{
	public function onActivate(): bool
	{
		return $this->applyState(true);
	}

	public function onDeactivate(): bool
	{
		return $this->applyState(false);
	}

	/**
	 * Propagates the addon state into every place that renders favorites, so the display code has a
	 * single parameter to read instead of querying the addon on each row build.
	 *
	 * Favorites already marked are deliberately kept in database: deactivating hides the feature,
	 * it must not destroy what managers marked. Re-activating restores them as they were.
	 */
	private function applyState(bool $state): bool
	{
		$tasks = [];

		$db = Factory::getContainer()->get('DatabaseDriver');

		try
		{
			// Both lists carry the filter and the column, and both read the same two parameters.
			foreach (['view=files', 'view=evaluation'] as $view)
			{
				$tasks[] = $this->switchMenusParams(
					'index.php?option=com_emundus&' . $view,
					['filter_favorites', 'em_display_favorites'],
					$state,
					$db
				);
			}
		}
		catch (\Exception $e)
		{
			Log::add(
				'Failed to switch favorite addon state to ' . ($state ? 'activated' : 'deactivated') . ': '
				. $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine(),
				Log::ERROR,
				'com_emundus.addon'
			);
			$tasks[] = false;
		}

		return !in_array(false, $tasks, true);
	}

	/**
	 * @param   array<string>  $params_names
	 */
	private function switchMenusParams(string $link, array $params_names, bool $state, DatabaseInterface $db): bool
	{
		$query = $db->getQuery(true);

		// LIKE with wildcards, as EmundusFiltersFiles::setMenuParams() does: menu links carry
		// trailing parameters, an exact match would silently skip most of them.
		$query->select($db->quoteName(['id', 'params']))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('link') . ' LIKE ' . $db->quote('%' . $link . '%'));

		$db->setQuery($query);
		$menus = $db->loadObjectList();

		if (empty($menus))
		{
			return true;
		}

		$updates = [];

		foreach ($menus as $menu)
		{
			$params = json_decode($menu->params, true);
			$params = is_array($params) ? $params : [];

			foreach ($params_names as $param_name)
			{
				$params[$param_name] = $state ? 1 : 0;
			}

			$menu->params = json_encode($params);
			$updates[]    = $db->updateObject('#__menu', $menu, 'id');
		}

		return !in_array(false, $updates, true);
	}
}
