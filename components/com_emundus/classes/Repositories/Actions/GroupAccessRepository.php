<?php
/**
 * @package     Tchooz\Repositories\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Factories\Actions\GroupAccessFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: 'jos_emundus_acl', alias: 'ea', columns: [
	'id',
	'group_id',
	'action_id',
	'c',
	'r',
	'u',
	'd'
])]
class GroupAccessRepository extends EmundusRepository implements RepositoryInterface
{
	private GroupAccessFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'group_access', self::class);

		$this->factory = new GroupAccessFactory();
	}

	public function flush(GroupAccessEntity $entity): bool
	{
		$data = (object)[
			'c'         => $entity->getCrud()->getCreate(),
			'r'         => $entity->getCrud()->getRead(),
			'u'         => $entity->getCrud()->getUpdate(),
			'd'         => $entity->getCrud()->getDelete()
		];

		if(empty($entity->getId()))
		{
			if(empty($entity->getGroup()) || empty($entity->getAction()))
			{
				throw new \Exception('Group access entity must have a group and action');
			}

			$data->group_id = $entity->getGroup()->getId();
			$data->action_id = $entity->getAction()->getId();

			if(!$this->db->insertObject($this->tableName, $data))
			{
				throw new \Exception('Failed to create group access');
			}

			$entity->setId($this->db->insertid());
		}
		else {
			$data->id = $entity->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id')) {
				throw new \Exception('Failed to update group access');
			}
		}

		return true;
	}

	public function delete(int $id): bool
	{
		// TODO: Implement delete() method.
	}

	public function getById(int $id): mixed
	{
		// TODO: Implement getById() method.
	}

	public function syncAllActions(int $gid = 0): bool
	{
		try {
			$subQuery = $this->db->getQuery(true);
			$query = $this->db->getQuery(true);

			/* Get the missing groups */
			$subQuery->select($this->db->quoteName('group_id'))
				->from($this->db->quoteName('#__emundus_acl'));

			$query->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_setup_groups'))
				->where($this->db->quoteName('id') . ' NOT IN (' . $subQuery .')');
			$this->db->setQuery($query);
			$missingGroups = $this->db->loadColumn();


			/* Get action IDs*/
			$query->clear()
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_setup_actions'))
				->where($this->db->quoteName('status') . ' >= 1');
			$this->db->setQuery($query);
			$actionsId = $this->db->loadColumn();

			/** Get all group assoc
			 *  When using the $gid param, we only get the files linked to the group we are looking at
			 */
			$query->clear()
				->select([$this->db->quoteName('jega.fnum'), $this->db->quoteName('jega.group_id'), $this->db->quoteName('jega.action_id')])
				->from($this->db->quoteName('#__emundus_group_assoc', 'jega'))
				->leftJoin($this->db->quoteName('#__emundus_setup_actions','jesa').' ON '.$this->db->quoteName('jesa.id').' = '.$this->db->quoteName('jega.action_id'))
				->where($this->db->quoteName('jesa.status') . ' = 1');

			if (!empty($gid)) {
				$query->andWhere($this->db->quoteName('jega.group_id') . ' = ' . $gid);
			}

			$this->db->setQuery($query);
			$arrayGroupAssoc = $this->db->loadAssocList();

			/** Get all user assoc
			 *  When using the $gid param, we only get the files linked to the group we are looking at
			 */
			if (empty($gid)) {
				$query->clear()
					->select([$this->db->quoteName('jeua.fnum'), $this->db->quoteName('jeua.user_id'), $this->db->quoteName('jeua.action_id')])
					->from($this->db->quoteName('#__emundus_users_assoc', 'jeua'))
					->leftJoin($this->db->quoteName('#__emundus_setup_actions','jesa').' ON '.$this->db->quoteName('jesa.id').' = '.$this->db->quoteName('jeua.action_id'))
					->where($this->db->quoteName('jesa.status') . ' = 1');

				$this->db->setQuery($query);
				$arrayUserAssoc = $this->db->loadAssocList();
			} else {
				$arrayUserAssoc = [];
			}

			/* Get all actions in acl table */
			$query->clear()
				->select([$this->db->quoteName('action_id'), $this->db->quoteName('group_id')])
				->from($this->db->quoteName('#__emundus_acl'));
			$this->db->setQuery($query);
			$aclAction = $this->db->loadAssocList();

			/* Insert missing groups*/
			if (!empty($missingGroups)) {
				$columns = ['group_id', 'action_id', 'c', 'r', 'u', 'd'];

				$query->clear()
					->insert($this->db->quoteName('#__emundus_acl'))
					->columns($this->db->quoteName($columns));
				foreach ($missingGroups as $missingGroup) {
					$query->values($missingGroup.',1,0,1,0,0');
				}

				$this->db->setQuery($query);
				$this->db->execute();
			}


			$acl = array();
			$aclGroupAssoc = array();
			$aclUserAssoc = array();
			foreach ($aclAction as $action) {
				$acl[$action['group_id']][] = $action['action_id'];
			}
			foreach ($arrayGroupAssoc as $aga) {
				$aclGroupAssoc[$aga['fnum']][$aga['group_id']][] = $aga['action_id'];
			}
			foreach ($arrayUserAssoc as $aua) {
				$aclUserAssoc[$aua['fnum']][$aua['user_id']][] = $aua['action_id'];
			}
			foreach ($acl as $gId => $groupAction) {
				$acl[$gId] = array_diff($actionsId, $groupAction);
			}
			$queryActionID = "SELECT id FROM jos_emundus_setup_actions WHERE status = 1";
			$this->db->setQuery($queryActionID);
			$actionsId = $this->db->loadColumn();

			foreach ($aclGroupAssoc as $fnum => $groups) {
				foreach ($groups as $gid => $action) {
					$aclGroupAssoc[$fnum][$gid] = array_diff($actionsId, $action);
				}
			}
			foreach ($aclUserAssoc as $fnum => $users) {
				foreach ($users as $uid => $action) {
					$aclUserAssoc[$fnum][$uid] = array_diff($actionsId, $action);
				}
			}

			$canInsert = false;
			$insert = "INSERT INTO jos_emundus_acl (action_id, group_id, c, r, u, d) values ";
			$overload = array();
			foreach ($acl as $gid => $actions) {
				if (!empty($actions)) {
					if (count($actions) > count($overload)) {
						$overload = $actions;
					}
					$canInsert = true;
					foreach ($actions as $aid) {
						$insert .= "({$aid}, {$gid}, 0, 0, 0, 0),";
					}
				}
			}

			if ($canInsert) {
				$insert = rtrim($insert, ",");
				$this->db->setQuery($insert);
				$this->db->execute();
			}
			$canInsert = false;
			$insert = "INSERT INTO jos_emundus_group_assoc (fnum, action_id, group_id, c, r, u, d) values ";

			foreach ($aclGroupAssoc as $fnum => $groups) {
				foreach ($groups as $gid => $assocActions) {
					if (!empty($assocActions)) {
						$canInsert = true;
						foreach ($assocActions as $aid) {
							$insert .= "({$fnum}, {$aid}, {$gid}, 0, 0, 0, 0),";
						}
					}
				}
			}
			if ($canInsert) {
				$insert = rtrim($insert, ",");
				$this->db->setQuery($insert);
				$this->db->execute();
			}
			$canInsert = false;
			$insert = "INSERT INTO jos_emundus_users_assoc (fnum, action_id, user_id, c, r, u, d) values ";

			foreach ($aclUserAssoc as $fnum => $users) {
				foreach ($users as $uid => $assocActions) {
					if (!empty($assocActions)) {
						foreach ($assocActions as $aid) {
							$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($uid);
							if ($user->id > 0) {
								$canInsert = true;
								$insert .= "({$fnum}, {$aid}, {$uid}, 0, 0, 0, 0),";
							}
						}
					}
				}
			}

			if ($canInsert) {
				$insert = rtrim($insert, ",");
				$this->db->setQuery($insert);
				$this->db->execute();
			}

		} catch (\Exception $e) {
			throw $e;
		}

		return true;
	}

	public function getFactory(): GroupAccessFactory
	{
		return $this->factory;
	}
}