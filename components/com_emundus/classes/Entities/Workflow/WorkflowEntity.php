<?php

namespace Tchooz\Entities\Workflow;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;

class WorkflowEntity
{
	private int $id;

	public string $label;

	public int $published;

	/**
	 * @var array<StepEntity>
	 */
	public array $steps = [];

	public array $program_ids = [];

	private array $childWorkflows = [];

	private ?WorkflowEntity $parentWorkflow;

	/**
	 * @param   int                $id
	 * @param   string             $label
	 * @param   int                $published
	 * @param   array<StepEntity>  $steps
	 * @param   array<int>         $program_ids
	 */
	public function __construct(int $id, string $label = '', int $published = 1, array $steps = [], array $program_ids = [], array $childWorkflows = [], WorkflowEntity $parentWorkflow = null)
	{
		$this->id             = $id;
		$this->label          = $label;
		$this->published      = $published;
		$this->steps          = $steps;
		$this->program_ids    = $program_ids;
		$this->childWorkflows = $childWorkflows;
		$this->parentWorkflow = $parentWorkflow;

		Log::addLogger(['text_file' => 'com_emundus.entity.workflow.php'], Log::ALL, ['com_emundus.entity.workflow']);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function isPublished(): bool
	{
		return $this->published === 1;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published ? 1 : 0;
	}

	/**
	 * @return array<StepEntity>
	 */
	public function getSteps(): array
	{
		return $this->steps;
	}

	/**
	 * @param   array<StepEntity>  $steps
	 *
	 * @return void
	 */
	public function setSteps(array $steps): void
	{
		$this->steps = $steps;
	}

	/**
	 * @return array<int>
	 */
	public function getProgramIds(): array
	{
		return $this->program_ids;
	}

	/**
	 * @param   array<int>  $program_ids
	 *
	 * @return void
	 */
	public function setProgramIds(array $program_ids): void
	{
		$this->program_ids = $program_ids;
	}

	/**
	 * @param   StepEntity  $step
	 *
	 * @return bool
	 */
	public function addStep(StepEntity $step): bool
	{
		$added = false;

		if ($step->isApplicantStep())
		{
			// two steps of same type cannot be on same entry_status, if not evaluation type
			// check if step on same entry_status already exists
			$already_used_entry_status = [];
			foreach ($this->getApplicantSteps() as $existingApplicantStep)
			{
				$already_used_entry_status = array_merge($already_used_entry_status, $existingApplicantStep->entry_status);
			}

			$intersect = array_intersect($already_used_entry_status, $step->entry_status);

			if (empty($intersect))
			{
				$step->setWorkflowId($this->getId());
				$this->steps[] = $step;
				$added         = true;
			}
			else
			{
				$alreadyUsingSteps = [];

				foreach ($this->getApplicantSteps() as $step)
				{
					if (array_intersect($step->getEntryStatus(), $intersect))
					{
						$alreadyUsingSteps[] = $step->getLabel() . '[' . $step->getId() . ']';
					}
				}

				Log::add('Can not add step on entry status ' . implode(', ', $intersect) . ' already have steps using it : ' . implode(', ', $alreadyUsingSteps), Log::WARNING, 'com_emundus.entity.workflow');
			}
		}
		else
		{
			$step->setWorkflowId($this->getId());
			$this->steps[] = $step;
			$added         = true;
		}

		return $added;
	}

	/**
	 * @param   int  $stepId
	 *
	 * @return void
	 */
	public function removeStep(int $stepId): void
	{
		$this->steps = array_filter($this->steps, function ($step) use ($stepId) {
			return $step->getId() !== $stepId;
		});
	}

	/**
	 * @param   array  $state
	 *
	 * @return array<StepEntity>
	 */
	public function getApplicantSteps(array $state = [1]): array
	{
		return array_values(array_filter($this->steps, function ($step) use ($state) {
			return in_array($step->state, $state, true) && $step->isApplicantStep();
		}));
	}

	/**
	 *
	 * @return array<WorkflowEntity>
	 *
	 * @since version
	 */
	public function getChildWorkflows(): array
	{
		return $this->childWorkflows;
	}

	public function setChildWorkflows(array $childWorkflows): void
	{
		$this->childWorkflows = $childWorkflows;
	}

	public function getParentWorkflow(): ?WorkflowEntity
	{
		return $this->parentWorkflow;
	}

	public function setParentWorkflow(?WorkflowEntity $parentWorkflow): void
	{
		$this->parentWorkflow = $parentWorkflow;
	}
}