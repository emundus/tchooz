<?php

namespace Tchooz\Repositories\Automation;

use Gantry\Framework\Exception;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionsAndorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;


class ConditionRepository
{
	private DatabaseDriver $db;

	public function __construct(?DatabaseDriver $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
	}

	public function saveGroupCondition(ConditionGroupEntity $group): bool
	{
		$saved = false;

		try {
			$query = $this->db->createQuery();

			if ($group->getId() > 0)
			{
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__emundus_group_condition'))
					->where($this->db->quoteName('id') . ' = ' . $group->getId());

				$this->db->setQuery($query);
				$exists = $this->db->loadResult();

				if (!$exists) {
					$group->setId(0);
				}
			}

			if ($group->getId() > 0)
			{
				$query->clear()
					->update($this->db->quoteName('#__emundus_group_condition'))
					->set($this->db->quoteName('operator') . ' = ' . $this->db->quote($group->getOperator()->value));

				if ($group->getParentId() > 0)
				{
					$query->set($this->db->quoteName('parent_id') . ' = ' . $group->getParentId());
				}
				else
				{
					$query->set($this->db->quoteName('parent_id') . ' = NULL');
				}

				$query->where($this->db->quoteName('id') . ' = ' . $group->getId());

				$this->db->setQuery($query);
				$saved = $this->db->execute();
			}
			else
			{
				$query->clear()
					->insert($this->db->quoteName('#__emundus_group_condition'))
					->columns(['operator', 'parent_id'])
					->values($this->db->quote($group->getOperator()->value) . ', ' . (!empty($group->getParentId()) ? $group->getParentId() : 'NULL'));

				$saved = $this->db->setQuery($query)->execute();
				if ($saved)
				{
					$group->setId((int) $this->db->insertid());
					$saved = true;
				}
			}
		} catch (Exception $e) {
		}

		return $saved;
	}

	public function saveCondition(ConditionEntity $condition): bool
	{
		$saved = false;

		$query = $this->db->createQuery();

		if ($condition->getId() > 0)
		{
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_condition'))
				->where($this->db->quoteName('id') . ' = ' . $condition->getId());

			$this->db->setQuery($query);
			$exists = $this->db->loadResult();

			if (!$exists) {
				$condition->setId(0); // Reset ID to 0 if it doesn't exist in the database
			}
		}

		if ($condition->getId() > 0)
		{
			$query->clear()
				->update($this->db->quoteName('#__emundus_condition'))
				->set($this->db->quoteName('type') . ' = ' . $this->db->quote($condition->getTargetType()->value))
				->set($this->db->quoteName('target') . ' = ' . $this->db->quote($condition->getField()))
				->set($this->db->quoteName('operator') . ' = ' . $this->db->quote($condition->getOperator()->value))
				->set($this->db->quoteName('value') . ' = ' . $this->db->quote(json_encode($condition->getValue())));

			if ($condition->getGroupId() > 0)
			{
				$query->set($this->db->quoteName('group_id') . ' = ' . $condition->getGroupId());
			}
			else
			{
				$query->set($this->db->quoteName('group_id') . ' = NULL');
			}

			$query->where($this->db->quoteName('id') . ' = ' . $condition->getId());

			$this->db->setQuery($query);
			$saved = $this->db->execute();
		}
		else
		{
			$query->clear()
				->insert($this->db->quoteName('#__emundus_condition'))
				->columns(['group_id', 'type', 'target', 'operator', 'value'])
				->values((!empty($condition->getGroupId()) ? $condition->getGroupId()  : ' null ')  . ', ' . $this->db->quote($condition->getTargetType()->value) . ', ' . $this->db->quote($condition->getField()) . ', ' . $this->db->quote($condition->getOperator()->value) . ', ' . $this->db->quote(json_encode($condition->getValue())));

			$saved = $this->db->setQuery($query)->execute();
			if ($saved)
			{
				$condition->setId((int) $this->db->insertid());
				$saved = true;
			}
		}

		return $saved;
	}

	public function deleteCondition(int $conditionId): bool
	{
		$deleted = false;

		if ($conditionId > 0)
		{
			$query = $this->db->createQuery()
				->delete($this->db->quoteName('#__emundus_condition'))
				->where($this->db->quoteName('id') . ' = ' . $conditionId);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function deleteGroupCondition(int $groupId): bool
	{
		$deleted = false;

		if ($groupId > 0)
		{
			$query = $this->db->createQuery()
				->delete($this->db->quoteName('#__emundus_group_condition'))
				->where($this->db->quoteName('id') . ' = ' . $groupId);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function getConditionsGroupsByAutomationId(int $automationId): array
	{
		$groups = [];

		if ($automationId > 0)
		{
			$query = $this->db->createQuery()
				->select('DISTINCT gc.*')
				->from($this->db->quoteName('#__emundus_group_condition', 'gc'))
				->leftJoin($this->db->quoteName('#__emundus_condition', 'cond') . ' ON ' . $this->db->quoteName('cond.group_id') . ' = ' . $this->db->quoteName('gc.id'))
				->leftJoin($this->db->quoteName('#__emundus_automation_condition', 'ac') . ' ON ' . $this->db->quoteName('ac.condition_id') . ' = ' . $this->db->quoteName('cond.id'))
				->where($this->db->quoteName('ac.automation_id') . ' = ' . $automationId);

			$this->db->setQuery($query);
			$groupRows = $this->db->loadObjectList();

			foreach ($groupRows as $groupRow)
			{
				$conditions = $this->getConditionsByGroupId((int)$groupRow->id);

				$group = new ConditionGroupEntity((int)$groupRow->id, $conditions);
				$group->setOperator(ConditionsAndorEnum::from($groupRow->operator));

				if (!empty($groupRow->parent_id)) {
					$group->setParentId((int)$groupRow->parent_id);
				}

				$groups[] = $group;
			}
		}

		return $groups;
	}

	/**
	 * @return array<ConditionEntity>
	 */
	public function getConditionsByGroupId(int $groupId): array
	{
		$conditions = [];

		if ($groupId > 0)
		{
			$query = $this->db->createQuery()
				->select('*')
				->from($this->db->quoteName('#__emundus_condition'))
				->where($this->db->quoteName('group_id') . ' = ' . $groupId);

			$this->db->setQuery($query);
			$conditionRows = $this->db->loadObjectList();

			foreach ($conditionRows as $conditionRow)
			{
				$conditions[] = new ConditionEntity((int) $conditionRow->id, $groupId, ConditionTargetTypeEnum::from($conditionRow->type), $conditionRow->target, ConditionOperatorEnum::from($conditionRow->operator), json_decode($conditionRow->value, true));
			}
		}

		return $conditions;
	}

	public function getConditionsByTargetId(int $targetId): array
	{
		$conditions = [];

		if ($targetId > 0)
		{
			$query = $this->db->createQuery()
				->select('cond.*')
				->from($this->db->quoteName('#__emundus_condition', 'cond'))
				->leftJoin($this->db->quoteName('#__emundus_automation_target_condition', 'ac') . ' ON ' . $this->db->quoteName('ac.condition_id') . ' = ' . $this->db->quoteName('cond.id'))
				->where($this->db->quoteName('ac.target_id') . ' = ' . $targetId);

			$this->db->setQuery($query);
			$conditionRows = $this->db->loadObjectList();

			foreach ($conditionRows as $conditionRow)
			{
				$conditions[] = new ConditionEntity((int) $conditionRow->id, (int) $conditionRow->group_id, ConditionTargetTypeEnum::from($conditionRow->type), $conditionRow->target, ConditionOperatorEnum::from($conditionRow->operator), json_decode($conditionRow->value, true));
			}
		}

		return $conditions;
	}
}