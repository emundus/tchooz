<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Actions\GroupAccessRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class ResourcesAddonHandler extends AbstractAddonHandler
{
	private const MENU_LINK = 'index.php?option=com_emundus&view=resources';

	public function onActivate(): bool
	{
		$state = $this->applyState(true);
		$this->ensureAllRightsGroupAccess();

		return $state;
	}

	public function onDeactivate(): bool
	{
		return $this->applyState(false);
	}

	private function applyState(bool $state): bool
	{
		$tasks = [];

		$db       = Factory::getContainer()->get('DatabaseDriver');
		$query    = $db->createQuery();
		$intState = $state ? 1 : 0;

		try
		{
			// Toggle the Resources menu item
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $db->quote($intState))
				->where($db->quoteName('link') . ' = ' . $db->quote(self::MENU_LINK));
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// Toggle the Resources ACL action
			$query->clear()
				->update($db->quoteName('#__emundus_setup_actions'))
				->set($db->quoteName('status') . ' = ' . $db->quote($intState))
				->where($db->quoteName('name') . ' = ' . $db->quote(ActionEnum::RESOURCE->value));
			$db->setQuery($query);
			$tasks[] = $db->execute();
		}
		catch (\Exception $e)
		{
			Log::add(
				sprintf(
					'ResourcesAddonHandler failed to apply state %d: %s [%s] in %s:%d',
					$intState,
					$e->getMessage(),
					get_class($e),
					$e->getFile(),
					$e->getLine()
				),
				Log::ERROR,
				'com_emundus'
			);
			$tasks[] = false;
		}

		return !in_array(false, $tasks, true);
	}

	/**
	 * Make sure the "all rights" group holds full CRUD on the Resources action,
	 * so platform administrators always manage resources once the addon is enabled.
	 */
	private function ensureAllRightsGroupAccess(): void
	{
		$action = (new ActionRepository())->getByName(ActionEnum::RESOURCE->value);
		if (empty($action))
		{
			return;
		}

		$emundusCmptConfig = ComponentHelper::getParams('com_emundus');
		$allRightsGrp      = $emundusCmptConfig->get('all_rights_group', 1);
		$group             = (new GroupRepository())->getById($allRightsGrp);
		if (empty($group))
		{
			return;
		}

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
			->from($db->quoteName('#__emundus_acl'))
			->where($db->quoteName('group_id') . ' = ' . $db->quote($group->getId()))
			->where($db->quoteName('action_id') . ' = ' . $db->quote($action->getId()));
		$db->setQuery($query);
		$existingId = (int) ($db->loadResult() ?? 0);

		$groupAccessEntity = new GroupAccessEntity(
			$existingId,
			$group,
			$action,
			new CrudEntity(0, 1, 1, 1, 1)
		);

		(new GroupAccessRepository())->flush($groupAccessEntity);
	}

	public function getParameters(): array
	{
		// No configurable parameters for this addon.
		return [];
	}
}
