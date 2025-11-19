<?php

namespace Tchooz\Repositories\Workflow;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Workflow\StepEntity;
use Joomla\Database\DatabaseDriver;
use Tchooz\Factories\Workflow\StepFactory;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: 'jos_emundus_setup_workflows_steps', alias: 'steps')]
class StepRepository
{
	use TraitTable;

	private DatabaseDriver $db;


	private string $tableName;
	private string $alias;

	public function __construct(?DatabaseDriver $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
		Log::addLogger(['text_file' => 'com_emundus.repository.step.php'], Log::ALL, ['com_emundus.repository.step']);
		$this->tableName = $this->getTableName(self::class);
		$this->alias = $this->getTableAlias(self::class);
	}


	/**
	 * @param   int  $id
	 *
	 * @return StepEntity|null
	 */
	public function getStepById(int $id): ?StepEntity
	{
		$step = null;

		if (!empty($id))
		{
			$query = $this->db->createQuery();
			$query->select('s.*, GROUP_CONCAT(es.status) AS entry_status, f.db_table_name AS ' . $this->db->quoteName('table') . ', f.id AS table_id')
				->from($this->db->quoteName($this->tableName, 's'))
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'es') . ' ON ' . $this->db->quoteName('s.id') . ' = ' . $this->db->quoteName('es.step_id'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'f') . ' ON ' . $this->db->quoteName('s.form_id') . ' = ' . $this->db->quoteName('f.form_id'))
				->where($this->db->quoteName('s.id') . ' = ' . $id)
				->group('s.id');

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			$steps = StepFactory::fromDbObjects([$result]);
			$step = $steps[0] ?? null;
		}

		return $step;
	}

	/**
	 * @param   int  $workflowId
	 *
	 * @return array<StepEntity>
	 */
	public function getStepsByWorkflowId(int $workflowId): array
	{
		$steps = [];

		if (!empty($workflowId))
		{
			$query = $this->db->createQuery();

			try {
				$query->select('s.*, GROUP_CONCAT(es.status) AS entry_status, f.db_table_name AS ' . $this->db->quoteName('table') . ', f.id AS table_id')
					->from($this->db->quoteName($this->tableName, 's'))
					->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'es') . ' ON ' . $this->db->quoteName('s.id') . ' = ' . $this->db->quoteName('es.step_id'))
					->leftJoin($this->db->quoteName('#__fabrik_lists', 'f') . ' ON ' . $this->db->quoteName('s.form_id') . ' = ' . $this->db->quoteName('f.form_id'))
					->where($this->db->quoteName('s.workflow_id') . ' = ' . $workflowId)
					->order($this->db->quoteName('s.ordering') . ' ASC')
					->group('s.id');

				$this->db->setQuery($query);
				$results = $this->db->loadObjectList();
				$steps = StepFactory::fromDbObjects($results);
			} catch (\Exception $e) {
				Log::add('Error while fetching steps for workflow ID ' . $workflowId . ': ' . $e->getMessage() .  ' ' . $query->__toString(), Log::ERROR, 'com_emundus.repository.step');
				throw new \Exception(Text::_('COM_EMUNDUS_ERROR_FETCHING_STEPS'));
			}
		}

		return $steps;
	}

	/**
	 * @param   array  $filters
	 * @param   int    $limit
	 * @param   int    $page
	 *
	 * @return array<StepEntity>
	 * @throws \Exception
	 */
	public function getAll(array $filters = [], int $limit = 10, int $page = 1): array
	{
		$steps = [];

		$query = $this->db->createQuery();
		$query->select($this->alias . '.*, GROUP_CONCAT(es.status) AS entry_status, f.db_table_name AS ' . $this->db->quoteName('table') . ', f.id AS table_id')
			->from($this->db->quoteName($this->tableName, $this->alias))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'es') . ' ON ' . $this->db->quoteName($this->alias . '.id') . ' = ' . $this->db->quoteName('es.step_id'))
			->leftJoin($this->db->quoteName('#__fabrik_lists', 'f') . ' ON ' . $this->db->quoteName($this->alias . '.form_id') . ' = ' . $this->db->quoteName('f.form_id'))
			->group($this->alias . '.id');

		// Apply filters
		$this->applyFilters($filters, $query);

		// Apply pagination
		$offset = ($page - 1) * $limit;
		$query->setLimit($limit, $offset);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if (!empty($results)) {
				$steps = StepFactory::fromDbObjects($results);
			}
		} catch (\Exception $e)
		{
			Log::add('Error while fetching steps: ' . $e->getMessage() .  ' ' . $query->__toString(), Log::ERROR, 'com_emundus.repository.step');
			throw new \Exception(Text::_('COM_EMUNDUS_ERROR_FETCHING_STEPS'));
		}

		return $steps;
	}

	public function applyFilters(array $filters, object $query): void
	{
		foreach ($filters as $field => $value)
		{
			if(!in_array($field, [$this->alias . '.id', $this->alias . '.workflow_id', $this->alias . '.type', $this->alias . '.state']))
			{
				continue;
			}

			if (is_array($value))
			{
				$query->where($this->db->quoteName($field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
			}
			else
			{
				$query->where($this->db->quoteName($field) . ' = ' . $this->db->quote($value));
			}
		}
	}

	/**
	 * @param   StepEntity  $step
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException
	 */
	public function save(StepEntity $step): bool
	{
		$saved = false;

		if (empty($step->getWorkflowId()))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_STEP_WORKFLOW_ID_NOT_SET'));
		}

		$query = $this->db->createQuery();
		if (empty($step->getId()))
		{
			$query->insert($this->db->quoteName($this->getTableName(self::class)))
				->columns([
					$this->db->quoteName('workflow_id'),
					$this->db->quoteName('label'),
					$this->db->quoteName('type'),
					$this->db->quoteName('profile_id'),
					$this->db->quoteName('form_id'),
					$this->db->quoteName('output_status'),
					$this->db->quoteName('multiple'),
					$this->db->quoteName('state'),
					$this->db->quoteName('ordering')
				])
				->values(
					implode(',', [
						$step->getWorkflowId(),
						$this->db->quote($step->getLabel()),
						$step->getType()->getId(),
						!empty($step->getProfileId()) ? $step->getProfileId() : 'NULL',
						!empty($step->getFormId()) ? $step->getFormId() : 'NULL',
						$this->db->quote($step->getOutputStatus()),
						$step->getMultiple(),
						$step->getState(),
						$step->getOrdering()
					])
				);

			try {
				$this->db->setQuery($query);
				$saved = $this->db->execute();

				if ($saved)
				{
					$stepId = (int)$this->db->insertid();
					$step->setId($stepId);
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error while inserting step: ' . $query->__toString() . $e->getMessage(), Log::ERROR, 'com_emundus.repository.step');
				throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SAVING_STEP'));
			}
		}
		else
		{
			$query->update($this->db->quoteName($this->getTableName(self::class)))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($step->getLabel()))
				->set($this->db->quoteName('type') . ' = ' . $step->getType()->getId())
				->set($this->db->quoteName('profile_id') . ' = ' . (!empty($step->getProfileId()) ? $step->getProfileId() : 'NULL'))
				->set($this->db->quoteName('form_id') . ' = ' . (!empty($step->getFormId()) ? $step->getFormId() : 'NULL'))
				->set($this->db->quoteName('output_status') . ' = ' . $this->db->quote($step->getOutputStatus()))
				->set($this->db->quoteName('multiple') . ' = ' . $step->getMultiple())
				->set($this->db->quoteName('state') . ' = ' . $step->getState())
				->set($this->db->quoteName('ordering') . ' = ' . $step->getOrdering())
				->where($this->db->quoteName('id') . ' = ' . $step->getId());

			try {
				$this->db->setQuery($query);
				$saved = $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error while updating step: ' . $query->__toString() . $e->getMessage(), Log::ERROR, 'com_emundus.repository.step');
				throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SAVING_STEP'));
			}
		}

		// update entry statuses
		if (!empty($step->getId()))
		{
			$query->clear()
				->delete($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status'))
				->where($this->db->quoteName('step_id') . ' = ' . $step->getId());

			try {
				$this->db->setQuery($query);
				$this->db->execute();
			} catch (\Exception $e) {
				Log::add('Error while deleting entry statuses for step ID ' . $step->getId() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.step');
				throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SAVING_STEP'));
			}

			$entryStatuses = $step->getEntryStatus();
			if (!empty($entryStatuses))
			{
				foreach ($entryStatuses as $status) {
					$query->clear()
						->insert($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status'))
						->columns([
							$this->db->quoteName('step_id'),
							$this->db->quoteName('status')
						])
						->values($step->getId() . ', ' . $this->db->quote($status));

					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (\Exception $e) {
						Log::add('Error while inserting entry statuses for step ID ' . $step->getId() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.step');
						throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SAVING_STEP'));
					}
				}
			}

			/**
			 * Update campaign step dates only if they are set in the step entity
			 */
			if (!is_null($step->getCampaignsDates()))
			{
				$campaignStepDateRepository = new CampaignStepDateRepository();
				$existingDates = $campaignStepDateRepository->getCampaignsDatesByStepId($step->getId());
				foreach ($existingDates as $existingDate)
				{
					$found = false;
					foreach ($step->getCampaignsDates() as $date)
					{
						if ($existingDate->getCampaignId() === $date->getCampaignId())
						{
							$found = true;
							break;
						}
					}

					if (!$found)
					{
						$campaignStepDateRepository->delete($existingDate);
					}
				}

				foreach ($step->getCampaignsDates() as $date)
				{
					if (empty($date->getStepId()))
					{
						$date->setStepId($step->getId());
					}

					$campaignStepDateRepository->save($date);
				}
			}
		}

		return $saved;
	}

	/**
	 * @param   StepEntity  $step
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function delete(StepEntity $step): bool
	{
		$deleted = false;

		if (!empty($step->getId()))
		{
			$query = $this->db->createQuery()
				->delete($this->db->quoteName($this->getTableName(self::class)))
				->where($this->db->quoteName('id') . ' = ' . $step->getId());

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (\Exception $e) {
				Log::add('Error while deleting step ID ' . $step->getId() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.step');
				throw new \Exception(Text::_('COM_EMUNDUS_ERROR_DELETING_STEP'));
			}
		}

		return $deleted;
	}
}