<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class NumericSignAddonHandler extends AbstractAddonHandler
{
	public function onActivate(): bool
	{
		return $this->applyState(true);
	}

	public function onDeactivate(): bool
	{
		return $this->applyState(false);
	}

	private function applyState(bool $state): bool
	{
		$tasks = [];

		try
		{
			// set available of numeric sign events
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->update('#__emundus_plugin_events')
				->set('available = ' . $db->quote((int)$state))
				->where('category = ' . $db->quote('Sign'))
				->andWhere('published = 1');
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// toggle publish state of menus
			$links = array_map(function($link) use ($db) {
				return $db->quote($link);
			}, ['index.php?option=com_emundus&view=sign', 'index.php?option=com_emundus&view=sign&layout=add']);


			$query->clear()
				->update('#__menu')
				->set('published = ' . $db->quote((int)$state))
				->where($db->quoteName('link') . ' IN (' . implode(',', $links) . ')');

			$db->setQuery($query);
			$tasks[] = $db->execute();
		}
		catch (\Exception $e)
		{
			$tasks[] = false;

			Log::add('Something went wrong while trying to activate/deactivate sign request module : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return !in_array(false, $tasks);
	}
}