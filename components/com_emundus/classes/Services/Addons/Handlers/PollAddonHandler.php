<?php
/**
 * @package     Tchooz\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Actions\GroupAccessRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class PollAddonHandler extends AbstractAddonHandler
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
			$tasks[] = $this->switchMenus($state_integer, $query, $db);
			$tasks[] = $this->switchPollAction($state);
			$tasks[] = $this->switchSchedulerTask($state_integer, $query, $db);
		}
		catch (\Exception $e)
		{
			Log::add(
				'Error while switching poll addon state: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine(),
				Log::ERROR,
				'com_emundus.poll'
			);
			$tasks[] = false;
		}

		return !in_array(false, $tasks, true);
	}

	private function switchMenus(int $state, QueryInterface $query, DatabaseInterface $db): bool
	{
		$links = [
			'index.php?option=com_emundus&view=polls&layout=edit',
			'index.php?option=com_emundus&view=polls&layout=add',
			'index.php?option=com_emundus&view=polls',
			'index.php?option=com_emundus&view=polls&layout=reply'
		];

		$query->clear()
			->update($db->quoteName('#__menu'))
			->set('published = ' . $db->quote($state))
			->where('link IN (' . implode(',', $db->quote($links)) . ')');
		$db->setQuery($query);
		return $db->execute();
	}

	private function switchSchedulerTask(int $state, QueryInterface $query, DatabaseInterface $db): bool
	{
		$query->clear()
			->update($db->quoteName('#__scheduler_tasks'))
			->set($db->quoteName('state') . ' = ' . $db->quote($state))
			->where($db->quoteName('type') . ' = ' . $db->quote('plg_task_managepolls'));
		$db->setQuery($query);

		return $db->execute();
	}

	private function switchPollAction(bool $state): bool
	{
		$results = [];

		$actionRepository = new ActionRepository();
		$pollAction = $actionRepository->getByName('poll');
		$pollAction->setStatus($state);
		$results[] = $actionRepository->flush($pollAction);

		$emundusCmptConfig = ComponentHelper::getParams('com_emundus');
		$allRightsGrp = $emundusCmptConfig->get('all_rights_group', 1);
		$group = (new GroupRepository())->getById($allRightsGrp);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
			->from($db->quoteName('jos_emundus_acl'))
			->where($db->quoteName('group_id') . ' = ' . $db->quote($group->getId()))
			->where($db->quoteName('action_id') . ' = ' . $db->quote($pollAction->getId()));
		$db->setQuery($query);
		$existingId = (int) ($db->loadResult() ?? 0);

		$groupAccessEntity = new GroupAccessEntity(
			$existingId,
			$group,
			$pollAction,
			new CrudEntity(0, 1, 1, 1, 1)
		);

		$results[] = (new GroupAccessRepository())->flush($groupAccessEntity);

		return !in_array(false, $results, true);
	}
}

