<?php

namespace Tchooz\Repositories\Automation;

use Joomla\CMS\Factory;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Exception\EmundusUnknownActionException;
use Tchooz\Services\Automation\ActionRegistry;
use Joomla\Database\DatabaseDriver;

class ActionRepository
{
	private DatabaseDriver $db;

	private TargetRepository $targetRepository;

	public function __construct(?DatabaseDriver $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
		$this->targetRepository = new TargetRepository($this->db);
	}

	/**
	 * @param   int  $actionId
	 *
	 * @return ActionEntity|null
	 */
	public function getActionById(int $actionId): ?ActionEntity
	{
		$action = null;

		if (!empty($actionId) && $actionId > 0)
		{
			$query = $this->db->getQuery(true);
			$query->select('*')
				->from($this->db->quoteName('#__emundus_action'))
				->where($this->db->quoteName('id') . ' = ' . $actionId);

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if ($result)
			{
				$parameters = json_decode($result->params, true) ?? [];
				$registry = new ActionRegistry();
				$action = $registry->getActionInstance($result->name);
				$action->setId($actionId);

				foreach ($parameters as $parameter => $value)
				{
					$action->setParameterValues($parameter, $value);
				}

				$action->setTargets($this->targetRepository->getTargetsByActionId($result->id));
			}
		}

		return $action;
	}

	/**
	 * Automations that would silently change behaviour if the referenced item disappeared: the choices of
	 * a parameter are rebuilt on load, and ActionEntity nulls a value that is no longer among them.
	 *
	 * The rows are filtered in PHP because params is a JSON column holding the value either as a string
	 * or as a number depending on where the action was saved from.
	 *
	 * @return string[] Names of the automations whose $actionName action carries $value in $parameter.
	 */
	public function getAutomationNamesByActionParameter(string $actionName, string $parameter, string|int $value): array
	{
		$query = $this->db->getQuery(true);
		$query->select([$this->db->quoteName('a.params'), $this->db->quoteName('au.name')])
			->from($this->db->quoteName('#__emundus_action', 'a'))
			->innerJoin(
				$this->db->quoteName('#__emundus_automation', 'au')
				. ' ON ' . $this->db->quoteName('au.id') . ' = ' . $this->db->quoteName('a.automation_id')
			)
			->where($this->db->quoteName('a.name') . ' = :name')
			->bind(':name', $actionName);

		$this->db->setQuery($query);
		$rows = $this->db->loadObjectList() ?: [];

		$names = [];
		foreach ($rows as $row)
		{
			$params = json_decode($row->params, true);
			if (!is_array($params) || !isset($params[$parameter]))
			{
				continue;
			}

			if ((string) $params[$parameter] === (string) $value)
			{
				$names[$row->name] = $row->name;
			}
		}

		return array_values($names);
	}

	/**
	 * @param   int  $automationId
	 *
	 * @return array<ActionEntity>
	 */
	public function getActionsByAutomationId(int $automationId): array
	{
		$actions = [];

		if (!empty($automationId) && $automationId > 0)
		{
			$query = $this->db->getQuery(true);
			$query->select('*')
				->from($this->db->quoteName('#__emundus_action'))
				->where($this->db->quoteName('automation_id') . ' = ' . $automationId)
				->order('id ASC');

			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if ($results)
			{
				$registry = new ActionRegistry();
				foreach ($results as $result)
				{
					$parameters = json_decode($result->params, true) ?? [];
					$action = $registry->getActionInstance($result->name);

					if (!$action) {
						throw new EmundusUnknownActionException('Action type "' . $result->name . '" not found in registry.');
					}

					$action->setId($result->id);
					$action->setParametersValuesFromArray($parameters);

					if (method_exists($action, 'setParametersOptionsWithValues')) {
						$action->setParametersOptionsWithValues();
					}
					$action->setTargets($this->targetRepository->getTargetsByActionId($result->id));

					$actions[] = $action;
				}
			}
		}

		return $actions;
	}

	/**
	 * @param   ActionEntity  $action
	 * @param   ?int           $automationId
	 *
	 * @return bool
	 */
	public function flush(ActionEntity $action, int $automationId): bool
	{
		$saved = false;

		$query = $this->db->createQuery();

		if ($action->getId() > 0) {
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_action'))
				->where($this->db->quoteName('id') . ' = ' . $action->getId());
			$this->db->setQuery($query);
			$exists = $this->db->loadResult();

			if (!$exists) {
				$action->setId(0); // Reset ID to 0 if it doesn't exist in the database
			}
		}

		if ($action->getId() > 0)
		{
			$update = (object)[
				'id' => $action->getId(),
				'name' => $action->getType(),
				'automation_id' => $automationId,
				'params' => json_encode($action->getParameterValues()),
			];

			$saved = $this->db->updateObject('#__emundus_action', $update, 'id');
		}
		else
		{
			$insert = (object)[
				'name' => $action->getType(),
				'params' => json_encode($action->getParameterValues()),
				'automation_id' => $automationId
			];
			if ($saved = $this->db->insertObject('#__emundus_action', $insert))
			{
				$action->setId((int) $this->db->insertid());
			}
		}

		if ($saved) {
			if (!empty($action->getTargets()))
			{
				// todo: improve this by checking existing targets and only updating/inserting/deleting as necessary
				// for now, we delete all existing targets and re-insert them
				$this->targetRepository->deleteTargetsByActionId($action->getId());

				$allTargetsSaved = true;
				foreach ($action->getTargets() as $target)
				{
					if (!$this->targetRepository->saveTarget($target, $action->getId()))
					{
						$allTargetsSaved = false;
					}
				}

				$saved = $allTargetsSaved;
			} else {
				// if there are no targets, ensure all previous targets are deleted
				$this->targetRepository->deleteTargetsByActionId($action->getId());
			}
		}

		return $saved;
	}

	/**
	 * @param   int  $actionId
	 *
	 * @return bool
	 */
	public function deleteAction(int $actionId): bool
	{
		$deleted = false;

		if ($actionId > 0)
		{
			$query = $this->db->createQuery();
			$query->delete($this->db->quoteName('#__emundus_action'))
				->where($this->db->quoteName('id') . ' = ' . $actionId);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();

			// delete associated targets, even if foreign key with cascade delete is set, to be sure
			if ($deleted) {
				$this->targetRepository->deleteTargetsByActionId($actionId);
			}
		}

		return $deleted;
	}
}