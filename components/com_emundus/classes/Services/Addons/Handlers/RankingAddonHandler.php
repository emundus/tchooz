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
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Services\Addons\AbstractAddonHandler;

class RankingAddonHandler extends AbstractAddonHandler
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

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$state_integer = $state ? 1 : 0;

		try
		{
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set('published = ' . $db->quote($state_integer))
				->where('link LIKE ' . $db->quote('index.php?option=com_emundus&view=ranking'));
			$db->setQuery($query);
			$tasks[] = $db->execute();

			$query->clear()
				->update($db->quoteName('#__emundus_setup_emails'))
				->set('published = ' . $db->quote($state_integer))
				->where('lbl LIKE ' . $db->quote('ranking_locked'))
				->orWhere('lbl LIKE ' . $db->quote('ask_lock_ranking'));
			$db->setQuery($query);
			$tasks[] = $db->execute();
		}
		catch (\Exception $e)
		{
			$tasks[] = false;
		}

		return !in_array(false, $tasks, true);
	}
}

