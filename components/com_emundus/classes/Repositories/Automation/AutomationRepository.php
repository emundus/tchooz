<?php

namespace Tchooz\Repositories\Automation;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\AutomationEntity;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Factories\Automation\AutomationFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;

#[TableAttribute(
	table: '#__emundus_automation',
	alias: 'a'
)]
class AutomationRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'automation', self::class);
	}

	/**
	 * @param   array  $filters
	 *
	 * @return int
	 */
	public function getAutomationsCount(array $filters = []): int
	{
		$count = 0;

		try {
			$query = $this->db->createQuery();
			$query->select('COUNT(DISTINCT a.id)')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName('#__emundus_plugin_events', 'e') . ' ON ' . $this->db->quoteName($this->alias . '.event_id') . ' = ' . $this->db->quoteName('e.id'))
				->where('1=1');

			$this->applyFilters($query, $filters);

			$this->db->setQuery($query);
			$count = (int) $this->db->loadResult();
		} catch (\Exception $e) {
			Log::add('Error fetching automations count: ' . $e->getMessage(), Log::ERROR, 'com_emundus.automation');
		}

		return $count;
	}

	/**
	 * @param   array  $filters
	 * @param   int    $limit
	 * @param   int    $page
	 *
	 * @return array
	 */
	public function getAutomations(array $filters = [], int $limit = 10, int $page = 1): array
	{
		$automations = [];

		$query = $this->db->createQuery();
		$query->select($this->alias . '.*, e.label as event_label, e.description as event_description')
			->from($this->db->quoteName($this->tableName, $this->alias))
			->leftJoin($this->db->quoteName('#__emundus_plugin_events', 'e') . ' ON ' . $this->db->quoteName($this->alias . '.event_id') . ' = ' . $this->db->quoteName('e.id'))
			->where('1=1');

		$this->applyFilters($query, $filters);

		$query->group($this->alias . '.id')
			->setLimit($limit, ($page - 1) * $limit);

		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();

		if (!empty($results))
		{
			$automations = AutomationFactory::fromDbObjects($results, $this->db);
		}

		return $automations;
	}

	/**
	 * @param   array   $filters
	 * @param   object  $query
	 *
	 * @return void
	 */
	public function applyFilters(object $query, array $filters): void
	{
		if (empty($filters)) {
			$filters = ['published' => 1];
		}

		if (in_array('action.name', array_keys($filters)))
		{
			$query->leftJoin($this->db->quoteName('#__emundus_action', 'action') . ' ON ' . $this->db->quoteName('action.automation_id') . ' = ' . $this->db->quoteName('a.id'));
		}

		foreach ($filters as $field => $value)
		{
			if (!in_array($field, [$this->alias . '.published', $this->alias . '.event_id', 'search', 'action.name']) || $value === 'all' || $value === '')
			{
				continue;
			}

			if ($field === 'search')
			{
				$query->andWhere($this->db->quoteName('a.name') . ' LIKE ' . $this->db->quote('%' . $value . '%') . ' OR ' . $this->db->quoteName('a.description') . ' LIKE ' . $this->db->quote('%' . $value . '%'));
			}
			else
			{
				if (str_contains($value, ','))
				{
					$values = explode(',', $value);
					$query->andWhere($this->db->quoteName($field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $values)) . ')');
				}
				else
				{
					$query->andWhere($this->db->quoteName($field) . ' = ' . $this->db->quote($value));
				}
			}
		}
	}

	/**
	 * @param   int  $id
	 *
	 * @return AutomationEntity|null
	 */
	public function getById(int $id): ?AutomationEntity
	{
		$automation = null;

		if (!empty($id) && $id > 0)
		{
			$query = $this->db->createQuery();
			$query->select('a.*, e.label as event_label, e.description as event_description')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName('#__emundus_plugin_events', 'e') . ' ON ' . $this->db->quoteName($this->alias . '.event_id') . ' = ' . $this->db->quoteName('e.id'))
				->where($this->db->quoteName($this->alias . '.id') . ' = ' . $id);

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if (!empty($result)) {
				$automations = AutomationFactory::fromDbObjects([$result], $this->db);
				$automation = $automations[0] ?? null;
			}
		}

		return $automation;
	}

	/**
	 * @param   string  $eventName
	 *
	 * @return AutomationEntity[]
	 */
	public function getAutomationsByEventName(string $eventName): array
	{
		$automations = [];

		if (!empty($eventName))
		{
			$query = $this->db->createQuery();
			$query->select($this->alias . '.*, event.label as event_label, event.description as event_description')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->leftJoin($this->db->quoteName('#__emundus_plugin_events', 'event') . ' ON ' . $this->db->quoteName($this->alias . '.event_id') . ' = ' . $this->db->quoteName('event.id'))
				->where($this->db->quoteName('event.label') . ' = ' . $this->db->quote($eventName))
				->andWhere($this->db->quoteName($this->alias . '.published') . ' = 1');

			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if (!empty($results)) {
				$automations = AutomationFactory::fromDbObjects($results, $this->db);
			}
		}

		return $automations;
	}

	/**
	 * @param   AutomationEntity  $automation
	 *
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public function flush(AutomationEntity $automation): bool
	{
		$saved = false;

		$errors = $this->validateAutomation($automation);
		if (!empty($errors))
		{
			throw new \InvalidArgumentException(implode(' ', $errors));
		}

		$query = $this->db->createQuery();
		if ($automation->getId() > 0)
		{
			$query->update($this->db->quoteName($this->tableName))
				->set($this->db->quoteName('event_id') . ' = ' . $automation->getEvent()->getId())
				->set($this->db->quoteName('name') . ' = ' . $this->db->quote($automation->getName()))
				->set($this->db->quoteName('description') . ' = ' . $this->db->quote($automation->getDescription()))
				->set($this->db->quoteName('published') . ' = ' . ($automation->isPublished() ? 1 : 0))
				->where($this->db->quoteName('id') . ' = ' . $automation->getId());

			$updated = $this->db->setQuery($query)->execute();
			if ($updated)
			{
				$actionRepository = new ActionRepository($this->db);
				$existingActions = $actionRepository->getActionsByAutomationId($automation->getId());

				$allActionsSaved = true;
				foreach ($automation->getActions() as $action)
				{
					if (!$actionRepository->flush($action, $automation->getId()))
					{
						$allActionsSaved = false;
					}
				}

				// delete actions that are not in the current automation anymore
				foreach ($existingActions as $existingAction) {
					$found = false;
					foreach ($automation->getActions() as $action) {
						if ($action->getId() === $existingAction->getId()) {
							$found = true;
							break;
						}
					}

					if (!$found) {
						$actionRepository->deleteAction($existingAction->getId());
					}
				}

				$conditionRepository = new ConditionRepository($this->db);
				$existingConditionsGroups = $conditionRepository->getConditionsGroupsByAutomationId($automation->getId());

				$allConditionsSaved = true;
				foreach ($automation->getConditionsGroups() as $conditionGroup)
				{
					if (!$conditionRepository->saveGroupCondition($conditionGroup, $automation->getId())) {
						$allConditionsSaved = false;
					}
				}

				// delete condition groups and conditions that are not in the current automation anymore
				$this->deleteNonExistingConditions($existingConditionsGroups, $automation->getConditionsGroups());

				$saved = $allActionsSaved && $allConditionsSaved;
			}
		}
		else
		{
			$automationObj = (object)[
				'name'        => $automation->getName(),
				'description' => $automation->getDescription(),
				'event_id'    => $automation->getEvent()->getId(),
				'published'   => $automation->isPublished() ? 1 : 0,
			];
			$inserted = $this->db->insertObject($this->tableName, $automationObj);

			if ($inserted)
			{
				$automation->setId($this->db->insertid());

				$allConditionsSaved  = true;
				$conditionRepository = new ConditionRepository($this->db);
				foreach ($automation->getConditionsGroups() as $conditionGroup)
				{
					$conditionRepository->saveGroupCondition($conditionGroup);

					foreach ($conditionGroup->getConditions() as $condition)
					{
						$condition->setGroupId($conditionGroup->getId());

						if (!$conditionRepository->saveCondition($condition))
						{
							$allConditionsSaved = false;
						} else {
							$query->clear()
								->insert($this->db->quoteName('#__emundus_automation_condition'))
								->columns(['automation_id', 'condition_id'])
								->values($automation->getId() . ', ' . $condition->getId());

							$inserted = $this->db->setQuery($query)->execute();

							if (!$inserted) {
								$allConditionsSaved = false;
							}
						}
					}
				}

				$allActionsSaved  = true;
				$actionRepository = new ActionRepository($this->db);
				foreach ($automation->getActions() as $action)
				{
					if (!$actionRepository->flush($action, $automation->getId()))
					{
						$allActionsSaved = false;
					}
				}

				$saved = $allConditionsSaved && $allActionsSaved;
			}
		}


		return $saved;
	}

	/**
	 * @param   array<ConditionGroupEntity>  $existingConditionsGroups
	 * @param   array<ConditionGroupEntity>  $actualConditionsGroups
	 *
	 * @return void
	 */
	public function deleteNonExistingConditions(array $existingConditionsGroups, array $actualConditionsGroups): void
	{
		$conditionRepository = new ConditionRepository($this->db);

		foreach ($existingConditionsGroups as $existingGroup) {
			if (!$this->findConditionGroupInConditionGroups($existingGroup, $actualConditionsGroups)) {
				foreach ($existingGroup->getConditions() as $condition) {
					$conditionRepository->deleteCondition($condition->getId());
				}
				$conditionRepository->deleteGroupCondition($existingGroup->getId());
			} else {
				// delete also the conditions in groups that are not in the current automation anymore
				foreach ($existingGroup->getConditions() as $existingCondition) {
					if (!$this->findConditionInConditionGroups($existingCondition, $actualConditionsGroups))
					{
						$conditionRepository->deleteCondition($existingCondition->getId());
					}
				}
			}

			if (!empty($existingGroup->getSubGroups())) {
				$this->deleteNonExistingConditions($existingGroup->getSubGroups(), $actualConditionsGroups);
			}
		}
	}

	/**
	 * @param   ConditionGroupEntity $groupToFind
	 * @param   array<ConditionGroupEntity>  $groups
	 *
	 * @return bool|null
	 */
	public function findConditionGroupInConditionGroups(ConditionGroupEntity $groupToFind, array $groups): ?bool
	{
		foreach ($groups as $conditionGroup) {
			if ($conditionGroup->getId() === $groupToFind->getId()) {
				return true;
			}

			if (!empty($conditionGroup->getSubGroups())) {
				$foundInSubGroup = $this->findConditionGroupInConditionGroups($groupToFind, $conditionGroup->getSubGroups());

				if ($foundInSubGroup) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param   ConditionEntity $conditionEntity
	 * @param   array<ConditionGroupEntity>  $groups
	 *
	 * @return bool|null
	 */
	public function findConditionInConditionGroups(ConditionEntity $conditionEntity, array $groups): ?bool
	{
		foreach ($groups as $conditionGroup) {
			foreach ($conditionGroup->getConditions() as $condition) {
				if ($condition->getId() === $conditionEntity->getId()) {
					return true;
				}
			}

			if (!empty($conditionGroup->getSubGroups())) {
				$foundInSubGroup = $this->findConditionInConditionGroups($conditionEntity, $conditionGroup->getSubGroups());

				if ($foundInSubGroup) {
					return true;
				}
			}
		}

		return false;
	}


	public function togglePublishedAutomations(array $ids, bool $published): bool
	{
		$toggled = false;

		if (!empty($ids))
		{
			$query = $this->db->createQuery();
			$query->update($this->db->quoteName($this->tableName))
				->set($this->db->quoteName('published') . ' = ' . ($published ? 1 : 0))
				->where($this->db->quoteName('id') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $ids)) . ')');

			$toggled = (bool) $this->db->setQuery($query)->execute();
		}

		return $toggled;
	}

	/**
	 * @param   AutomationEntity  $automation
	 *
	 * @return AutomationEntity|null
	 */
	public function duplicateAutomation(AutomationEntity $automation): ?AutomationEntity
	{
		$duplicatedAutomation = null;

		if ($automation->getId() > 0)
		{
			$clonedAutomation = clone $automation;
			$clonedAutomation->setId(0);
			$clonedAutomation->setName($automation->getName() . ' (Copy)');
			$clonedAutomation->setPublished(false);

			// Clone actions
			$clonedActions = [];
			foreach ($automation->getActions() as $action) {
				assert($action instanceof ActionEntity);
				$clonedAction = clone $action;
				$clonedAction->setId(0); // Reset ID for new insertion

				$targets = [];
				foreach ($action->getTargets() as $target) {
					$clonedTarget = clone $target;
					$clonedTarget->setId(0); // Reset ID for new insertion
					$targets[] = $clonedTarget;
				}
				$clonedAction->setTargets($targets);
				$clonedActions[] = $clonedAction;
			}
			$clonedAutomation->setActions($clonedActions);

			// Clone condition groups and conditions
			$clonedConditionGroups = [];
			foreach ($automation->getConditionsGroups() as $conditionGroup) {
				$clonedGroup = clone $conditionGroup;
				$clonedGroup->setId(0); // Reset ID for new insertion

				$clonedConditions = [];
				foreach ($conditionGroup->getConditions() as $condition) {
					$clonedCondition = clone $condition;
					$clonedCondition->setId(0); // Reset ID for new insertion
					$clonedConditions[] = $clonedCondition;
				}
				$clonedGroup->setConditions($clonedConditions);
				$clonedConditionGroups[] = $clonedGroup;
			}
			$clonedAutomation->setConditionsGroups($clonedConditionGroups);

			if ($this->flush($clonedAutomation)) {
				$duplicatedAutomation = $this->getById($clonedAutomation->getId());
			}
		}

		return $duplicatedAutomation;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id) && $id > 0)
		{
			$automation = $this->getById($id);
			if ($automation)
			{
				$conditionRepository = new ConditionRepository($this->db);
				foreach ($automation->getConditionsGroups() as $conditionGroup)
				{
					foreach ($conditionGroup->getConditions() as $condition)
					{
						$conditionRepository->deleteCondition($condition->getId());
					}
					$conditionRepository->deleteGroupCondition($conditionGroup->getId());
				}

				$query = $this->db->createQuery();
				$query->delete($this->db->quoteName($this->tableName))
					->where($this->db->quoteName('id') . ' = ' . $id);

				$deleted = (bool) $this->db->setQuery($query)->execute();
			}
		}

		return $deleted;
	}

	private function validateAutomation(AutomationEntity $automation): array
	{
		$errors = [];

		if (empty($automation->getName()))
		{
			$errors[] = Text::_('COM_EMUNDUS_AUTOMATION_ERROR_NAME_REQUIRED');
		} else if (strlen($automation->getName()) > 255)
		{
			$errors[] = Text::_('COM_EMUNDUS_AUTOMATION_ERROR_NAME_TOO_LONG');
		}

		if (empty($automation->getEvent()))
		{
			$errors[] = Text::_('COM_EMUNDUS_AUTOMATION_ERROR_EVENT_REQUIRED');
		}

		// Additional validation rules can be added here
		if (empty($automation->getActions()))
		{
			$errors[] = Text::_('COM_EMUNDUS_AUTOMATION_ERROR_AT_LEAST_ONE_ACTION_REQUIRED');
		}

		foreach ($automation->getActions() as $action)
		{
			assert($action instanceof ActionEntity);

			try {
				$action->verifyRequiredParameters();
			} catch (\RuntimeException $e) {
				$errors[] = Text::sprintf('COM_EMUNDUS_AUTOMATION_ERROR_ACTION_INVALID_PARAMETERS', $action::getLabel(), $e->getMessage());
			}

			if (!empty($action::supportTargetTypes()) && empty($action->getTargets()))
			{
				$errors[] = Text::sprintf('COM_EMUNDUS_AUTOMATION_ERROR_ACTION_REQUIRES_TARGETS', $action::getLabel());
			}
		}

		return $errors;
	}
}