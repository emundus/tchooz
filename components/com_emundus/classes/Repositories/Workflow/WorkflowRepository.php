<?php

namespace Tchooz\Repositories\Workflow;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Factories\Workflow\WorkflowFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Traits\TraitTable;
use Joomla\Database\DatabaseDriver;

#[TableAttribute(table: 'jos_emundus_setup_workflows')]
class WorkflowRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct(?DatabaseDriver $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
		Log::addLogger(['text_file' => 'com_emundus.repository.workflow.php'], Log::ALL, ['com_emundus.repository.workflow']);
	}

	public function getWorkflowById(int $id): ?WorkflowEntity
	{
		$workflow = null;

		if (!empty($id))
		{
			$query = $this->db->createQuery();
			$query->select('w.*, GROUP_CONCAT(p.program_id) AS program_ids')
				->from($this->db->quoteName($this->getTableName(self::class), 'w'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_workflows_programs', 'p') . ' ON ' . $this->db->quoteName('w.id') . ' = ' . $this->db->quoteName('p.workflow_id'))
				->where($this->db->quoteName('w.id') . ' = ' . $id)
				->group('w.id');

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if ($result)
			{
				$workflow = WorkflowFactory::fromDbObjects([$result])[0];
			}
		}

		return $workflow;
	}

	/**
	 * TODO: give the applicationFileEntity directly instead of fnum ?
	 * @param   string  $fnum
	 * @param   bool    $loadChilds load children workflow if there are any
	 *
	 * @return WorkflowEntity|null
	 */
	public function getWorkflowByFnum(string $fnum, bool $loadChilds = false): ?WorkflowEntity
	{
		$workflow = null;

		if (!empty($fnum))
		{
			$query = $this->db->createQuery();
			$query->select('w.*, GROUP_CONCAT(p.program_id) AS program_ids')
				->from($this->db->quoteName($this->getTableName(self::class), 'w'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_workflows_programs', 'p') . ' ON ' . $this->db->quoteName('w.id') . ' = ' . $this->db->quoteName('p.workflow_id'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_programmes', 'pr') . ' ON ' . $this->db->quoteName('p.program_id') . ' = ' . $this->db->quoteName('pr.id'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('pr.code'))
				->leftJoin($this->db->quoteName('jos_emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quoteName('esc.id'))
				->where($this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quote($fnum))
				->group('w.id');

			try
			{
				$this->db->setQuery($query);
				$result = $this->db->loadObject();

				if ($result)
				{
					$applicationFileRepository = new ApplicationFileRepository();
					$applicationFile = $applicationFileRepository->getByFnum($fnum);
					$workflow = WorkflowFactory::fromDbObjects([$result], $loadChilds, $applicationFile)[0];
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error fetching workflow by fnum ' . $fnum . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.workflow');
			}
		}

		return $workflow;
	}

	/**
	 * @param int $programId
	 *
	 * @return WorkflowEntity|null
	 */
	public function getWorkflowByProgramId(int $programId): ?WorkflowEntity
	{
		$workflow = null;

		// TODO: Add caching here if performance becomes an issue
		if (!empty($programId))
		{
			$query = $this->db->createQuery();
			$query->select('w.*')
				->from($this->db->quoteName($this->getTableName(self::class), 'w'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_workflows_programs', 'p') . ' ON ' . $this->db->quoteName('w.id') . ' = ' . $this->db->quoteName('p.workflow_id'))
				->where($this->db->quoteName('p.program_id') . ' = ' . $programId)
				->group('w.id');

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if ($result)
			{
				$query->clear()
					->select('GROUP_CONCAT(p.program_id) AS program_ids')
					->from($this->db->quoteName('jos_emundus_setup_workflows_programs', 'p'))
					->where($this->db->quoteName('p.workflow_id') . ' = ' . $result->id);

				$this->db->setQuery($query);
				$result->program_ids = $this->db->loadResult();

				$workflow = WorkflowFactory::fromDbObjects([$result])[0];
			}
		}

		return $workflow;
	}

	/**
	 * @return array<WorkflowEntity>
	 */
	public function getWorkflows(): array
	{
		$workflows = [];

		try {
			$query = $this->db->createQuery();
			$query->select('w.*, GROUP_CONCAT(p.program_id) AS program_ids')
				->from($this->db->quoteName($this->getTableName(self::class), 'w'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_workflows_programs', 'p') . ' ON ' . $this->db->quoteName('w.id') . ' = ' . $this->db->quoteName('p.workflow_id'))
				->group('w.id');

			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if ($results)
			{
				$workflows = WorkflowFactory::fromDbObjects($results);
			}
		} catch (\Exception $e) {
			Log::add('Error fetching workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.workflow');
		}

		return $workflows;
	}

	/**
	 * @param   WorkflowEntity  $workflow
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function save(WorkflowEntity $workflow): bool
	{
		$query = $this->db->createQuery();

		// verify that two steps don't have same entry status if type is not evaluation
		// if they do, we could not determine which step to use when an entry reaches that status, thus not know which form or payment to show
		if (!empty($workflow->getSteps()))
		{
			$already_used_entry_status = [];
			foreach ($workflow->getSteps() as $step)
			{
				if (!$step->isEvaluationStep())
				{
					$intersect = array_intersect($already_used_entry_status, $step->getEntryStatus());
					if (!empty($intersect))
					{
						throw new \InvalidArgumentException('Two steps of the same type cannot have the same entry status: ' . implode(',', $intersect) . ' in workflow ' . $workflow->getLabel() . ' and step ' . $step->getLabel());
					} else
					{
						$already_used_entry_status = array_merge($already_used_entry_status, $step->getEntryStatus());
					}
				}
			}
		}

		try {
			if (empty($workflow->getId()))
			{
				$query->insert($this->db->quoteName($this->getTableName(self::class)))
					->columns([
						$this->db->quoteName('label'),
						$this->db->quoteName('published')
					])
					->values(
						$this->db->quote($workflow->getLabel()) . ', ' .
						(int) $workflow->isPublished()
					);

				$this->db->setQuery($query);
				$saved = $this->db->execute();

				if ($saved)
				{
					$workflowId = (int) $this->db->insertid();
					$workflow->setId($workflowId);
				}
			} else
			{
				$query->update($this->db->quoteName($this->getTableName(self::class)))
					->set([
						$this->db->quoteName('label') . ' = ' . $this->db->quote($workflow->getLabel()),
						$this->db->quoteName('published') . ' = ' . (int) $workflow->isPublished()
					])
					->where($this->db->quoteName('id') . ' = ' . $workflow->getId());

				$this->db->setQuery($query);
				$saved = $this->db->execute();
			}

			if ($saved && !empty($workflow->getId()))
			{
				// First, delete existing program associations
				$query->clear()
					->delete($this->db->quoteName('jos_emundus_setup_workflows_programs'))
					->where($this->db->quoteName('workflow_id') . ' = ' . $workflow->getId());


				if (!empty($workflow->getProgramIds())) {
					$query->orWhere($this->db->quoteName('program_id') . ' IN (' . implode(',', $workflow->getProgramIds()) . ')'); // program can only be associated with one workflow
				}

				$this->db->setQuery($query);
				$this->db->execute();

				if (!empty($workflow->getProgramIds()))
				{
					// Now, insert new program associations
					$insertedAll = [];
					foreach ($workflow->getProgramIds() as $programId)
					{
						$query->clear()
							->insert($this->db->quoteName('jos_emundus_setup_workflows_programs'))
							->columns([
								$this->db->quoteName('workflow_id'),
								$this->db->quoteName('program_id')
							])
							->values(
								$workflow->getId() . ', ' . $programId
							);

						$this->db->setQuery($query);
						$insertedAll[] = $this->db->execute();
					}

					if (in_array(false, $insertedAll, true))
					{
						$saved = false;
					}
				}

				if (!empty($workflow->getSteps()))
				{
					$stepRepository = new StepRepository();

					$existingSteps = $stepRepository->getStepsByWorkflowId($workflow->getId());
					foreach ($existingSteps as $existingStep)
					{
						$found = false;
						foreach ($workflow->getSteps() as $step){
							if ($existingStep->getId() === $step->getId()){
								$found = true;
								break;
							}
						}
						if (!$found)
						{
							$stepRepository->delete($existingStep);
						}
					}

					$savedAll = [];
					foreach ($workflow->getSteps() as $step)
					{
						$step->setWorkflowId($workflow->getId());
						$savedAll[] = $stepRepository->save($step);
					}

					if (empty($savedAll) || in_array(false, $savedAll, true))
					{
						$saved = false;
					}
				}
			} else {
				Log::add('Error saving workflow ' . $workflow->getLabel(), Log::ERROR, 'com_emundus.repository.workflow');
			}
		} catch (\Exception $e)
		{
			Log::add('Exception saving workflow ' . $workflow->getLabel() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.workflow');
			$saved = false;
		}

		return $saved;
	}

	public function delete(WorkflowEntity $workflow): bool
	{
		$deleted = false;

		if (!empty($workflow->getId()))
		{
			// First, delete associated steps
			$stepRepository = new StepRepository();
			$steps = $stepRepository->getStepsByWorkflowId($workflow->getId());
			foreach ($steps as $step)
			{
				$stepRepository->delete($step);
			}

			// Then, delete program associations
			$deleteProgramsQuery = $this->db->getQuery(true)
				->delete($this->db->quoteName('jos_emundus_setup_workflows_programs'))
				->where($this->db->quoteName('workflow_id') . ' = ' . $workflow->getId());

			$this->db->setQuery($deleteProgramsQuery);
			$this->db->execute();

			// Finally, delete the workflow itself
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->getTableName(self::class)))
				->where($this->db->quoteName('id') . ' = ' . $workflow->getId());

			$this->db->setQuery($query);
			$deleted = $this->db->execute();

			if (!$deleted)
			{
				Log::add('Error deleting workflow ID ' . $workflow->getId(), Log::ERROR, 'com_emundus.repository.workflow');
			}
		}

		return $deleted;
	}

	public function duplicate(WorkflowEntity $workflow, string $newLabel = '', array $newPrograms = []): ?WorkflowEntity
	{
		$duplicatedWorkflow = null;

		if (!empty($workflow->getId()))
		{
			$steps = $workflow->getSteps();
			foreach ($steps as $key => $step)
			{
				$step->setId(0); // Reset step ID for duplication
				$step->setWorkflowId(0); // Reset workflow ID for duplication

				// If the step has associated campaign step dates, reset it, no need to duplicate those
				$step->setCampaignsDates([]);

				$steps[$key] = $step;
			}
			$workflow->setSteps($steps);

			$newWorkflow = new WorkflowEntity(
				id: 0,
				label: !empty($newLabel) ? $newLabel : $workflow->getLabel() . ' (Copy)',
				published: $workflow->isPublished() ? 1 : 0,
				steps: $workflow->getSteps(),
				program_ids: $newPrograms // Do not duplicate program associations
			);

			$this->save($newWorkflow);
			if (!empty($newWorkflow->getId()))
			{
				$duplicatedWorkflow = $newWorkflow;
			}
		}

		return $duplicatedWorkflow;
	}
}