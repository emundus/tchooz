<?php

namespace Emundus\Workflow;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;

class WorkflowEntity {
	private int $id;

	public string $label;

	public array $steps = [];

	public array $program_ids = [];

	private DatabaseDriver $db;

	public function __construct(int $id) {
		$this->id = $id;
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		$this->load();
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
		$this->load();
	}

	private function load(): void
	{
		$query = $this->db->createQuery();

		$query->select('esw.*, GROUP_CONCAT(esws.id) AS steps, GROUP_CONCAT(eswp.program_id) AS program_ids')
			->from($this->db->quoteName('#__emundus_setup_workflows', 'esw'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws') . ' ON esws.workflow_id = esw.id')
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp') . ' ON eswp.workflow_id = esw.id')
			->where('esw.id = ' . $this->id)
			->group('esw.id');

		$this->db->setQuery($query);
		$workflow = $this->db->loadObject();

		if (!empty($workflow)) {
			$this->label = $workflow->label;
			$step_ids = explode(',', $workflow->steps);

			foreach ($step_ids as $step_id) {
				$this->steps[] = new StepEntity((int)$step_id);
			}

			$this->program_ids = explode(',', $workflow->program_ids);
		}
	}

	/**
	 * @param   StepEntity  $step
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function addStep(StepEntity $step): bool
	{
		$added = false;

		if (!$step->isEvaluationStep()) {
			// two steps of same type cannot be on same entry_status, if not evaluation type
			// check if step on same entry_status already exists
			$already_used_entry_status = [];
			foreach ($this->steps as $existing_step) {
				$already_used_entry_status = array_merge($already_used_entry_status, $existing_step->entry_status);
			}

			$intersect = array_intersect($already_used_entry_status, $step->entry_status);

			if (empty($intersect)) {

				$added = $step->save();

				if ($added) {
					$this->steps[] = $step;
				}
			} else {
				throw new \Exception('Step with same entry status already exists');
			}
		} else {
			$added = $step->save();

			if ($added) {
				$this->steps[] = $step;
			}
		}

		return $added;
	}
}