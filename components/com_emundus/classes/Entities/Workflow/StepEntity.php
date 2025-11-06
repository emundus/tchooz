<?php

namespace Tchooz\Entities\Workflow;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Repositories\Payment\PaymentRepository;

class StepEntity {
	private int $id;

	public int $workflow_id;

	public string $label;

	public StepTypeEntity $type;

	public ?int $profile_id = 0;

	public ?int $form_id = 0;

	public array $entry_status = [];

	public ?int $output_status = null;

	public int $multiple = 0;

	public int $state = 0;

	public int $ordering = 0;

	public string $table = '';

	public int $table_id = 0;

	private DatabaseDriver $db;

	/**
	 * @param   int                  $id
	 * @param   int                  $workflow_id
	 * @param   string               $label
	 * @param   StepTypeEntity|null  $type
	 * @param   ?int                  $profile_id
	 * @param   ?int                  $form_id
	 * @param   array                $entry_status
	 * @param   int|null             $output_status
	 * @param   int                  $multiple
	 * @param   int                  $state
	 * @param   int                  $ordering
	 */
	public function __construct(int $id, int $workflow_id = 0, string $label = '', ?StepTypeEntity $type = null, ?int $profile_id = 0, ?int $form_id = 0, array $entry_status = [], ?int $output_status = null, int $multiple = 0, int $state = 1, int $ordering = 0) {
		$this->id = $id;

		$this->workflow_id = $workflow_id;
		$this->label = $label;
		$this->type = $type ?? new StepTypeEntity(1);
		$this->profile_id = $profile_id;
		$this->form_id = $form_id;
		$this->entry_status = $entry_status;
		$this->output_status = $output_status;
		$this->multiple = $multiple;
		$this->state = $state;
		$this->ordering = $ordering;

		$this->db = Factory::getContainer()->get('DatabaseDriver');
		if (!empty($this->id) && empty($this->workflow_id) && empty($this->label) && empty($this->entry_status)) {
			$this->load();
		}
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

	public function getWorkflowId(): int
	{
		return $this->workflow_id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getType(): StepTypeEntity
	{
		return $this->type;
	}

	public function setType(StepTypeEntity $type): void
	{
		$this->type = $type;
	}

	public function getProfileId(): ?int
	{
		return $this->profile_id;
	}

	public function setProfileId(int $profile_id): void
	{
		$this->profile_id = $profile_id;
	}

	public function getFormId(): ?int
	{
		return $this->form_id;
	}

	public function setFormId(int $form_id): void
	{
		$this->form_id = $form_id;
	}

	public function getEntryStatus(): array
	{
		return $this->entry_status;
	}

	public function setEntryStatus(array $entry_status): void
	{
		$this->entry_status = $entry_status;
	}

	public function getOutputStatus(): ?int
	{
		return $this->output_status;
	}

	public function setOutputStatus(?int $output_status): void
	{
		$this->output_status = $output_status;
	}

	public function getMultiple(): int
	{
		return $this->multiple;
	}

	public function setMultiple(int $multiple): void
	{
		$this->multiple = $multiple;
	}

	public function getState(): int
	{
		return $this->state;
	}

	public function setState(int $state): void
	{
		$this->state = $state;
	}

	public function getOrdering(): int
	{
		return $this->ordering;
	}

	public function isApplicantStep(): bool
	{
		return $this->type->action_id === 1;
	}

	public function isEvaluationStep(): bool
	{
		return !$this->isApplicantStep() && !$this->isPaymentStep();
	}

	public function isPaymentStep(): bool
	{
		$paymentRepository = new PaymentRepository();

		return $this->type->id === $paymentRepository->getPaymentStepTypeId();
	}

	public function setOrdering(int $ordering): void
	{
		$this->ordering = $ordering;
	}

	// TODO: those functions should be in a repository class
	private function load(): void
	{
		$query = $this->db->createQuery();

		$query->select('esws.*, GROUP_CONCAT(eswses.status) AS entry_status')
			->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'eswses') . ' ON eswses.step_id = esws.id')
			->where('esws.id = ' . $this->id);

		$this->db->setQuery($query);
		$step = $this->db->loadObject();

		if (!empty($step)) {
			$this->workflow_id = $step->workflow_id;
			$this->label = $step->label;
			$this->type = new StepTypeEntity($step->type);
			$this->profile_id = $step->profile_id ?? 0;
			$this->form_id = $step->form_id ?? 0;
			$this->entry_status = explode(',', $step->entry_status);
			$this->output_status = $step->output_status;
			$this->multiple = $step->multiple ?? 0;
			$this->state = $step->state ?? 1;
			$this->ordering = $step->ordering ?? 0;

			if ($this->isEvaluationStep()) {
				$this->setEvaluationTable();
			}
		}
	}

	private function setEvaluationTable(): void
	{
		$query = $this->db->createQuery();

		$query->select('db_table_name, id')
			->from('#__fabrik_lists')
			->where('form_id = ' . $this->form_id);

		try {
			$this->db->setQuery($query);
			$table_data = $this->db->loadAssoc();

			if (!empty($table_data) && !empty($table_data['db_table_name'])) {
				$this->table = $table_data['db_table_name'];
				$this->table_id = (int)$table_data['id'];
			} else {
				Log::add('No table found for form id: ' . $this->form_id, Log::ERROR, 'com_emundus.workflow');
				throw new \Exception('No table found for form id: ' . $this->form_id);
			}
		} catch (\Exception $e) {
			Log::add('Error while fetching form table name: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}
	}

	public function save(): bool
	{
		$saved = false;

		if (empty($this->id)) {
			$saved = $this->insert();
		} else {
			$saved = $this->update();
		}

		return $saved;
	}

	private function insert(): bool
	{
		$inserted = false;

		$query = $this->db->createQuery();

		$columns = ['workflow_id', 'label', 'type', 'profile_id', 'form_id', 'multiple', 'state', 'ordering'];
		$values = [$this->workflow_id, $this->db->quote($this->label), $this->type->id, $this->profile_id, $this->form_id, $this->multiple, $this->state, $this->ordering];

		if (isset($this->output_status)) {
			$columns[] = 'output_status';
			$values[] = $this->output_status;
		}

		$query->insert($this->db->quoteName('#__emundus_setup_workflows_steps'))
			->columns(implode(', ', $columns))
			->values(implode(', ', $values));

		try {
			$this->db->setQuery($query);
			$inserted = $this->db->execute();

			if ($inserted) {
				$this->id = (int)$this->db->insertid();

				if (!empty($this->entry_status)) {
					$query = $this->db->createQuery();
					$query->insert($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status'))
						->columns('step_id, status')
						->values($this->id . ', ' . implode('), (' . $this->id . ', ', $this->entry_status));

					$this->db->setQuery($query);
					$inserted = $this->db->execute();
				}
			}
		} catch (\Exception $e) {
			Log::add('Error while inserting step: ' . $query->__toString() . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $inserted;
	}

	private function update(): bool
	{
		$updated = false;

		try {
			$query = $this->db->createQuery();
			$query->update($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->set('workflow_id = ' . $this->workflow_id)
				->set('label = ' . $this->db->quote($this->label))
				->set('type = ' . $this->type->id)
				->set('profile_id = ' . $this->profile_id)
				->set('form_id = ' . $this->form_id)
				->set('multiple = ' . $this->multiple)
				->set('state = ' . $this->state)
				->set('ordering = ' . $this->ordering);

			if (isset($this->output_status)) {
				$query->set('output_status = ' . $this->output_status);
			} else {
				$query->set('output_status = NULL');
			}

			$query->where('id = ' . $this->id);

			$this->db->setQuery($query);
			$updated = $this->db->execute();

			// update entry status
			if ($updated) {
				$query = $this->db->createQuery();
				$query->delete($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status'))
					->where('step_id = ' . $this->id);

				$this->db->setQuery($query);
				$updated = $this->db->execute();

				if ($updated && !empty($this->entry_status)) {
					$query = $this->db->createQuery();
					$query->insert($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status'))
						->columns('step_id, status')
						->values($this->id . ', ' . implode('), (' . $this->id . ', ', $this->entry_status));

					$this->db->setQuery($query);
					$updated = $this->db->execute();
				}
			}
		} catch (\Exception $e) {
			Log::add('Error while updating step: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $updated;
	}
}