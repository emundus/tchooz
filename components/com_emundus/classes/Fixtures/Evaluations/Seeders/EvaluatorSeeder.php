<?php
/**
 * @package     Tchooz\Fixtures\Evaluations\Seeders
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\Evaluations\Seeders;

use Joomla\Database\DatabaseInterface;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Fixtures\SampleData\Seeders\UserSeeder;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Actions\GroupAccessRepository;
use Tchooz\Repositories\Groups\GroupRepository;

\defined('_JEXEC') or die;

/**
 * Provides evaluator users able to evaluate the sample steps: one eMundus group linked to the
 * programs, granted read on files and create/read/update on the evaluation actions, and $count
 * users in that group. Idempotent: re-running only fills the gap up to $count.
 */
final class EvaluatorSeeder
{
	public const GROUP_LABEL = '[TEST] Évaluateurs';

	private const EVALUATOR_PROFILE = 6;
	private const FILE_ACTION       = 1;
	private const JOOMLA_REGISTERED = 2;

	private DatabaseInterface $db;
	private UserSeeder        $userSeeder;
	private GroupRepository   $groupRepository;

	public function __construct(DatabaseInterface $db, UserSeeder $userSeeder)
	{
		$this->db              = $db;
		$this->userSeeder      = $userSeeder;
		$this->groupRepository = new GroupRepository();
	}

	/**
	 * @param   int       $count         Evaluators to reach in the sample group.
	 * @param   string[]  $programCodes  Programs the evaluators must reach.
	 * @param   int[]     $actionIds     Evaluation step actions to grant.
	 *
	 * @return  array{ids: int[], created: int}  Every evaluator of the sample group.
	 */
	public function seed(int $count, array $programCodes, array $actionIds): array
	{
		$groupId = $this->ensureGroup($programCodes);
		$this->grantAccess($groupId, $actionIds);

		$existing = $this->loadGroupUserIds($groupId);
		$missing  = $count - count($existing);
		$created  = [];

		if ($missing > 0)
		{
			$created = $this->userSeeder->seed(
				$missing,
				self::EVALUATOR_PROFILE,
				[self::JOOMLA_REGISTERED, $this->evaluatorJoomlaGroup()],
				$this->userSeeder->countExisting()
			);

			$this->attachToGroup($created, $groupId);
		}

		return ['ids' => array_merge($existing, $created), 'created' => count($created)];
	}

	/**
	 * @param   string[]  $programCodes
	 */
	private function ensureGroup(array $programCodes): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__emundus_setup_groups'))
			->where($this->db->quoteName('label') . ' = ' . $this->db->quote(self::GROUP_LABEL));
		$this->db->setQuery($query);
		$groupId = (int) $this->db->loadResult();

		if (empty($groupId))
		{
			$group = new GroupEntity(0, self::GROUP_LABEL, '', true, [], false, false, []);
			$this->groupRepository->flush($group);
			$groupId = $group->getId();
		}

		foreach ($programCodes as $code)
		{
			if (!$this->groupRepository->checkGroupAssociated($groupId, $code))
			{
				$this->groupRepository->addProgram($groupId, $code);
			}
		}

		return $groupId;
	}

	/**
	 * @param   int[]  $actionIds
	 */
	private function grantAccess(int $groupId, array $actionIds): void
	{
		$group                 = $this->groupRepository->getById($groupId);
		$actionRepository      = new ActionRepository();
		$groupAccessRepository = new GroupAccessRepository();

		$rights = [self::FILE_ACTION => new CrudEntity(0, 0, 1, 0, 0)];
		foreach ($actionIds as $actionId)
		{
			$rights[$actionId] = new CrudEntity(0, 1, 1, 1, 0);
		}

		foreach ($rights as $actionId => $crud)
		{
			$action = $actionRepository->getById($actionId);
			if ($action === null)
			{
				throw new \RuntimeException('Action ' . $actionId . ' not found.');
			}

			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_acl'))
				->where($this->db->quoteName('group_id') . ' = ' . $groupId)
				->where($this->db->quoteName('action_id') . ' = ' . (int) $actionId);
			$this->db->setQuery($query);

			$groupAccessRepository->flush(new GroupAccessEntity((int) $this->db->loadResult(), $group, $action, $crud));
		}
	}

	/**
	 * @return int[]
	 */
	private function loadGroupUserIds(int $groupId): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('user_id'))
			->from($this->db->quoteName('#__emundus_groups'))
			->where($this->db->quoteName('group_id') . ' = ' . $groupId);
		$this->db->setQuery($query);

		return array_map('intval', $this->db->loadColumn() ?: []);
	}

	private function evaluatorJoomlaGroup(): int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('acl_aro_groups'))
			->from($this->db->quoteName('#__emundus_setup_profiles'))
			->where($this->db->quoteName('id') . ' = ' . self::EVALUATOR_PROFILE);
		$this->db->setQuery($query);
		$joomlaGroup = (int) $this->db->loadResult();

		if (empty($joomlaGroup))
		{
			throw new \RuntimeException('Evaluator profile ' . self::EVALUATOR_PROFILE . ' not found.');
		}

		return $joomlaGroup;
	}

	/**
	 * @param   int[]  $userIds
	 */
	private function attachToGroup(array $userIds, int $groupId): void
	{
		$now = date('Y-m-d H:i:s');

		foreach ($userIds as $userId)
		{
			$membership = (object) ['user_id' => $userId, 'group_id' => $groupId];
			$profile    = (object) [
				'date_time'  => $now,
				'user_id'    => $userId,
				'profile_id' => self::EVALUATOR_PROFILE,
			];

			if (!$this->db->insertObject('#__emundus_groups', $membership) || !$this->db->insertObject('#__emundus_users_profiles', $profile))
			{
				throw new \RuntimeException('Failed to attach evaluator ' . $userId . ' to group ' . $groupId);
			}
		}
	}
}
