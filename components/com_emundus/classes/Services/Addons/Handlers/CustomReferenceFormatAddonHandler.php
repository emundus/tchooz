<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class CustomReferenceFormatAddonHandler extends AbstractAddonHandler
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

		$intState = $state ? 1 : 0;

		try {
			// ACL
			$actionRepository = new ActionRepository();
			$referenceAction = $actionRepository->getByName('custom_reference');
			$referenceAction->setStatus($state);
			$tasks[] = $actionRepository->flush($referenceAction);

			// toggle menu items
			$menuLinks = [
				'index.php?option=com_emundus&view=files&layout=generatereference&format=raw',
				'index.php?option=com_emundus&view=references&layout=history&format=raw'
			];
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $db->quote($intState))
				->where($db->quoteName('link') . ' IN (' . implode(',', $db->quote($menuLinks)) . ')');

			$db->setQuery($query);
			$tasks[] = $db->execute();
		} catch (\Exception $e) {
			$tasks[] = false;
		}

		return !in_array(false, $tasks);
	}
}