<?php

namespace Tchooz\Repositories\Automation;

use Joomla\CMS\Factory;
use Tchooz\Entities\Automation\ActionEntity;
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
	 * @param   int           $automationId
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
			$query->clear()
				->update($this->db->quoteName('#__emundus_action'))
				->set($this->db->quoteName('automation_id') . ' = ' . $automationId)
				->set($this->db->quoteName('name') . ' = ' . $this->db->quote($action->getType()))
				->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($action->getParameterValues())))
				->where($this->db->quoteName('id') . ' = ' . $action->getId());

			$this->db->setQuery($query);
			$saved = $this->db->execute();
		}
		else
		{
			$query->clear()
				->insert($this->db->quoteName('#__emundus_action'))
				->columns(['automation_id', 'name', 'params'])
				->values($automationId . ', ' . $this->db->quote($action->getType()) . ', ' . $this->db->quote(json_encode($action->getParameterValues())));

			$this->db->setQuery($query);
			$saved = $this->db->execute();
			if ($saved)
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