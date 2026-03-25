<?php
/**
 * @package     Tchooz\Repositories\Groups
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Groups;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Factories\Groups\GroupFactory;
use Tchooz\Repositories\Actions\GroupAccessRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\Join;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: 'jos_emundus_setup_groups', alias: 'esg', columns: [
	'id',
	'label',
	'description',
	'published',
	'class',
	'anonymize',
	'filter_status',
	'GROUP_CONCAT(DISTINCT esgrc.course) as programs',
	'GROUP_CONCAT(DISTINCT esgrs.status) as statuses',
	'GROUP_CONCAT(DISTINCT esfrfgl.fabrik_group_link) as visible_groups',
	'GROUP_CONCAT(DISTINCT esgrail.attachment_id_link) as visible_attachments',
])]
class GroupRepository extends EmundusRepository implements RepositoryInterface
{
	private GroupFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'groups', self::class);

		$this->factory = new GroupFactory();

		$this->joins = [
			'esgrc'   => new Join(
				fromTable: $this->tableName,
				fromAlias: $this->alias,
				toTable: '#__emundus_setup_groups_repeat_course',
				toAlias: 'esgrc',
				fromKey: 'id',
				toKey: 'parent_id'
			),
			'esgrs'   => new Join(
				fromTable: $this->tableName,
				fromAlias: $this->alias,
				toTable: '#__emundus_setup_groups_repeat_status',
				toAlias: 'esgrs',
				fromKey: 'id',
				toKey: 'parent_id'
			),
			'esfrfgl' => new Join(
				fromTable: $this->tableName,
				fromAlias: $this->alias,
				toTable: '#__emundus_setup_groups_repeat_fabrik_group_link',
				toAlias: 'esfrfgl',
				fromKey: 'id',
				toKey: 'parent_id'
			),
			'esgrail' => new Join(
				fromTable: $this->tableName,
				fromAlias: $this->alias,
				toTable: '#__emundus_setup_groups_repeat_attachment_id_link',
				toAlias: 'esgrail',
				fromKey: 'id',
				toKey: 'parent_id'
			)
		];

		$this->searchableColumns = [
			'label',
			'description'
		];
	}

	public function flush(GroupEntity $group): bool
	{
		if (empty($group->getLabel()))
		{
			throw new \InvalidArgumentException('Label is required');
		}

		$data = (object) [
			'label'               => $group->getLabel(),
			'description'         => $group->getDescription(),
			'published'           => $group->isPublished() ? 1 : 0,
			'class'               => $group->getClass(),
			'anonymize'           => $group->isAnonymize() ? 1 : 0,
			'filter_status'       => $group->isFilterStatus() ? 1 : 0
		];

		if (empty($group->getId()))
		{
			// Insert
			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Error while inserting group: ' . $this->db->getErrorMsg());
			}

			$group->setId($this->db->insertid());
		}
		else
		{
			// Update
			$data->id = $group->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \RuntimeException('Error while updating group: ' . $this->db->getErrorMsg());
			}
		}

		$this->saveGroupPrograms($group);
		$this->saveGroupStatuses($group);
		$this->saveGroupVisibleGroups($group);
		$this->saveGroupVisibleAttachments($group);

		// Check if ACL exist for the group, if not create it with default values
		$query = $this->db->getQuery(true);
		$query->select('DISTINCT 1')
			->from('#__emundus_acl')
			->where('group_id = '.$group->getId());
		$this->db->setQuery($query);
		$result = $this->db->loadResult();
		if ($result != 1)
		{
			$groupAccessRepository = new GroupAccessRepository();
			try
			{
				$groupAccessRepository->syncAllActions($group->getId());
			}
			catch (\Exception $e)
			{
				throw new \RuntimeException('Error while syncing group ACL: ' . $e->getMessage());
			}
		}

		return true;
	}

	public function saveGroupStatuses(GroupEntity $group): void
	{
		// Get existing statuses
		$query = $this->db->getQuery(true)
			->select('status')
			->from($this->db->quoteName('#__emundus_setup_groups_repeat_status'))
			->where('parent_id = ' . $group->getId());
		$this->db->setQuery($query);
		$existingStatuses = $this->db->loadColumn();

		// Determine statuses to add and remove
		$newStatuses      = array_map(fn($status) => $status->getStep(), $group->getStatuses());
		$statusesToAdd    = array_diff($newStatuses, $existingStatuses);
		$statusesToRemove = array_diff($existingStatuses, $newStatuses);

		// Add new statuses
		foreach ($statusesToAdd as $status)
		{
			$data = (object) [
				'parent_id' => $group->getId(),
				'status'    => $status
			];
			if (!$this->db->insertObject('#__emundus_setup_groups_repeat_status', $data))
			{
				throw new \RuntimeException('Error while adding group status: ' . $this->db->getErrorMsg());
			}
		}

		// Remove old statuses
		if (!empty($statusesToRemove))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__emundus_setup_groups_repeat_status'))
				->where('parent_id = ' . $group->getId())
				->where('status IN (' . implode(',', $statusesToRemove) . ')');
			$this->db->setQuery($query);
			if (!$this->db->execute())
			{
				throw new \RuntimeException('Error while removing group statuses: ' . $this->db->getErrorMsg());
			}
		}
	}

	public function saveGroupPrograms(GroupEntity $group): void
	{
		// Get existing programs
		$query = $this->db->getQuery(true)
			->select('course')
			->from($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where('parent_id = ' . $group->getId());
		$this->db->setQuery($query);
		$existingPrograms = $this->db->loadColumn();

		// Determine programs to add and remove
		$newPrograms      = array_map(fn($program) => $program->getCode(), $group->getPrograms());
		$programsToAdd    = array_diff($newPrograms, $existingPrograms);
		$programsToRemove = array_diff($existingPrograms, $newPrograms);

		// Add new programs
		foreach ($programsToAdd as $program)
		{
			$data = (object) [
				'parent_id' => $group->getId(),
				'course'    => $program
			];
			if (!$this->db->insertObject('#__emundus_setup_groups_repeat_course', $data))
			{
				throw new \RuntimeException('Error while adding group program: ' . $this->db->getErrorMsg());
			}
		}

		// Remove old programs
		if (!empty($programsToRemove))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
				->where('parent_id = ' . $group->getId())
				->where('course IN (' . implode(',', array_map(fn($p) => $this->db->quote($p), $programsToRemove)) . ')');
			$this->db->setQuery($query);
			if (!$this->db->execute())
			{
				throw new \RuntimeException('Error while removing group programs: ' . $this->db->getErrorMsg());
			}
		}
	}

	public function saveGroupVisibleGroups(GroupEntity $group): void
	{
		// Get existing visible groups
		$query = $this->db->getQuery(true)
			->select('fabrik_group_link')
			->from($this->db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link'))
			->where('parent_id = ' . $group->getId());
		$this->db->setQuery($query);
		$existingVisibleGroups = $this->db->loadColumn();

		// Determine visible groups to add and remove
		$newVisibleGroups      = $group->getVisibleGroups();
		$visibleGroupsToAdd    = array_diff($newVisibleGroups, $existingVisibleGroups);
		$visibleGroupsToRemove = array_diff($existingVisibleGroups, $newVisibleGroups);

		// Add new visible groups
		foreach ($visibleGroupsToAdd as $visibleGroup)
		{
			$data = (object) [
				'parent_id'       => $group->getId(),
				'fabrik_group_link' => $visibleGroup
			];
			if (!$this->db->insertObject('#__emundus_setup_groups_repeat_fabrik_group_link', $data))
			{
				throw new \RuntimeException('Error while adding group visible group: ' . $this->db->getErrorMsg());
			}
		}

		// Remove old visible groups
		if (!empty($visibleGroupsToRemove))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link'))
				->where('parent_id = ' . $group->getId())
				->where('fabrik_group_link IN (' . implode(',', array_map(fn($vg) => $this->db->quote($vg), $visibleGroupsToRemove)) . ')');
			$this->db->setQuery($query);
			if (!$this->db->execute())
			{
				throw new \RuntimeException('Error while removing group visible groups: ' . $this->db->getErrorMsg());
			}
		}
	}

	public function saveGroupVisibleAttachments(GroupEntity $group): void
	{
		// Get existing visible attachments
		$query = $this->db->getQuery(true)
			->select('attachment_id_link')
			->from($this->db->quoteName('#__emundus_setup_groups_repeat_attachment_id_link'))
			->where('parent_id = ' . $group->getId());
		$this->db->setQuery($query);
		$existingVisibleAttachments = $this->db->loadColumn();

		// Determine visible attachments to add and remove
		$newVisibleAttachments      = $group->getVisibleAttachments();
		$visibleAttachmentsToAdd    = array_diff($newVisibleAttachments, $existingVisibleAttachments);
		$visibleAttachmentsToRemove = array_diff($existingVisibleAttachments, $newVisibleAttachments);

		// Add new visible attachments
		foreach ($visibleAttachmentsToAdd as $visibleAttachment)
		{
			$data = (object) [
				'parent_id'       => $group->getId(),
				'attachment_id_link' => $visibleAttachment
			];
			if (!$this->db->insertObject('#__emundus_setup_groups_repeat_attachment_id_link', $data))
			{
				throw new \RuntimeException('Error while adding group visible attachment: ' . $this->db->getErrorMsg());
			}
		}

		// Remove old visible attachments
		if (!empty($visibleAttachmentsToRemove))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__emundus_setup_groups_repeat_attachment_id_link'))
				->where('parent_id = ' . $group->getId())
				->where('attachment_id_link IN (' . implode(',', array_map(fn($va) => $this->db->quote($va), $visibleAttachmentsToRemove)) . ')');
			$this->db->setQuery($query);
			if (!$this->db->execute())
			{
				throw new \RuntimeException('Error while removing group visible attachments: ' . $this->db->getErrorMsg());
			}
		}
	}

	public function addProgram(int $groupId, string $programCode): void
	{
		$data = (object) [
			'parent_id' => $groupId,
			'course'    => $programCode
		];
		if (!$this->db->insertObject('#__emundus_setup_groups_repeat_course', $data))
		{
			throw new \RuntimeException('Error while adding group program: ' . $this->db->getErrorMsg());
		}
	}

	public function checkGroupAssociated(int $groupId, string $programCode): bool
	{
		$query = $this->db->getQuery(true);

		$query->clear()
			->select('COUNT(id)')
			->from($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($groupId))
			->andWhere($this->db->quoteName('course') . ' LIKE ' . $this->db->quote($programCode));
		$this->db->setQuery($query);

		return $this->db->loadResult() > 0;
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true);

		// 1. Remove users associated
		$query->clear()
			->delete($this->db->quoteName('#__emundus_groups'))
			->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group associations: ' . $this->db->getErrorMsg());
		}

		// 2. Remove fnums associated to groups
		$query->clear()
			->delete($this->db->quoteName('#__emundus_group_assoc'))
			->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group associations: ' . $this->db->getErrorMsg());
		}

		// 3. Remove ACL associated to groups
		$query->clear()
			->delete($this->db->quoteName('#__emundus_acl'))
			->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group ACL: ' . $this->db->getErrorMsg());
		}

		// 4. Remove attachments, programs and statuses associated to groups
		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_groups_repeat_attachment_id_link'))
			->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group attachments: ' . $this->db->getErrorMsg());
		}

		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group programs: ' . $this->db->getErrorMsg());
		}

		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link'))
			->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group visible groups: ' . $this->db->getErrorMsg());
		}

		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_groups_repeat_status'))
			->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group statuses: ' . $this->db->getErrorMsg());
		}

		// 5. Remove email triggers associated to group
		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_emails_trigger_repeat_group_id'))
			->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group email triggers: ' . $this->db->getErrorMsg());
		}

		// 6. Dissociate group from profiles
		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_profiles_repeat_emundus_groups'))
			->where($this->db->quoteName('emundus_groups') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group from profiles: ' . $this->db->getErrorMsg());
		}

		// 7. Remove group
		$query->clear()
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		if (!$this->db->execute())
		{
			throw new \RuntimeException('Error while removing group: ' . $this->db->getErrorMsg());
		}

		return true;
	}

	public function getById(int $id): ?GroupEntity
	{
		return $this->getItemByField('id', $id, true, []);
	}

	public function getFactory(): GroupFactory
	{
		return $this->factory;
	}
}