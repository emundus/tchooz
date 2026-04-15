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
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class ImportAddonHandler extends AbstractAddonHandler
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
			// (Un)Publish emails
			$query->clear()
				->update($db->quoteName('#__emundus_setup_emails'))
				->set('published = ' . $db->quote($state_integer))
				->where('lbl IN (' . $db->quote('import_account_created') . ', ' . $db->quote('import_file_created') . ', ' . $db->quote('import_file_updated') . ')');
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// (Un)Publish import action
			$actionRepository = new ActionRepository();
			$choicesAction = $actionRepository->getByName('import');
			$choicesAction->setStatus($state);
			$tasks[] = $actionRepository->flush($choicesAction);
		}
		catch (\Exception $e)
		{
			$tasks[] = false;
		}

		return !in_array(false, $tasks, true);
	}
}

