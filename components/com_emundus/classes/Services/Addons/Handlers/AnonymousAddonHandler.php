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

class AnonymousAddonHandler extends AbstractAddonHandler
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
				->where('alias IN (' . $db->quote('connect-from-token') . ', ' . $db->quote('anonym-registration') . ')');
			$db->setQuery($query);
			$db->execute();

			$query->clear()
				->update($db->quoteName('#__emundus_setup_emails'))
				->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
				->where($db->quoteName('lbl') . ' = ' . $db->quote('anonym_token_email'));
			$db->setQuery($query);
			$db->execute();
		}
		catch (\Exception $e)
		{
			$tasks[] = false;
		}

		return !in_array(false, $tasks, true);
	}
}

