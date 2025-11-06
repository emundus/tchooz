<?php

namespace Tchooz\Repositories\Automation;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Factories\Automation\TargetFactory;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_automation_target')]
class TargetRepository
{
	use TraitTable;
	private DatabaseDriver $db;

	public function __construct(?DatabaseDriver $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * @param   int  $targetId
	 *
	 * @return TargetEntity|null
	 */
	public function getTargetById(int $targetId): ?TargetEntity
	{
		$target = null;

		if (!empty($targetId) && $targetId > 0)
		{
			$query = $this->db->getQuery(true);
			$query->select('*')
				->from($this->db->quoteName($this->getTableName(self::class)))
				->where($this->db->quoteName('id') . ' = ' . $targetId);

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if ($result)
			{
				$target = TargetFactory::fromDbObjects([$result], $this->db)[0];
			}
		}

		return $target;
	}

	/**
	 * @param   int  $actionId
	 *
	 * @return array
	 */
	public function getTargetsByActionId(int $actionId): array
	{
		$targets = [];

		if (!empty($actionId) && $actionId > 0)
		{
			$query = $this->db->getQuery(true);
			$query->select('*')
				->from($this->db->quoteName($this->getTableName(self::class)))
				->where($this->db->quoteName('action_id') . ' = ' . $actionId)
				->order('id ASC');

			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if ($results)
			{
				$targets = TargetFactory::fromDbObjects($results, $this->db);
			}
		}

		return $targets;
	}

	public function saveTarget(TargetEntity $target, int $actionId): bool
	{
		$saved = false;

		if (!empty($actionId)) {
			$columns = [
				'action_id' => $actionId,
				'type' => $target->getType()->value,
				'predefinition' => $target->getPredefinition()?->getName(),
			];
			$query = $this->db->createQuery();

			if (!empty($target->getId())) {
				$query->select('id')
					->from($this->db->quoteName($this->getTableName(self::class)))
					->where($this->db->quoteName('id') . ' = ' . $target->getId());

				$this->db->setQuery($query);
				$targetId = $this->db->loadResult();

				if (empty($targetId)) {
					$target->setId(0);
				}
			}

			if (!empty($target->getId()))
			{
				// Update existing target
				$query->clear()
					->update($this->db->quoteName($this->getTableName(self::class)))
					->set(
						array_map(
							fn($col, $val) => $this->db->quoteName($col) . ' = ' . $this->db->quote($val),
							array_keys($columns),
							array_values($columns)
						)
					)
					->where($this->db->quoteName('id') . ' = ' . $target->getId());
				$this->db->setQuery($query);

				$saved = $this->db->execute();
			}
			else
			{
				// Insert new target
				$query->clear()
					->insert($this->db->quoteName($this->getTableName(self::class)))
					->columns(array_map(fn($col) => $this->db->quoteName($col), array_keys($columns)))
					->values(implode(',', array_map(fn($val) => $this->db->quote($val), array_values($columns))));
				$this->db->setQuery($query);

				if ($this->db->execute())
				{
					$targetId = (int) $this->db->insertid();
					$target->setId($targetId);

					$saved = true;
				}
			}

			$query->clear()
				->delete($this->db->quoteName('#__emundus_automation_target_condition'))
				->where($this->db->quoteName('target_id') . ' = ' . (int) $target->getId());
			$this->db->setQuery($query);
			$this->db->execute();

			if (!empty($target->getConditions()))
			{
				$conditionRepo = new ConditionRepository($this->db);
				foreach ($target->getConditions() as $condition)
				{
					$conditionRepo->saveCondition($condition);

					if ($condition->getId())
					{
						$query->clear()
							->insert($this->db->quoteName('#__emundus_automation_target_condition'))
							->columns([$this->db->quoteName('target_id'), $this->db->quoteName('condition_id')])
							->values($target->getId() . ', ' . $condition->getId());
						$this->db->setQuery($query);
						$this->db->execute();
					}
				}
			}
		}

		return $saved;
	}

	public function deleteTargetsByActionId(int $actionId): bool
	{
		$deleted = false;

		if ($actionId > 0)
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->getTableName(self::class)))
				->where($this->db->quoteName('action_id') . ' = ' . (int) $actionId);
			$this->db->setQuery($query);

			$deleted = $this->db->execute();
		}

		return $deleted;
	}

}