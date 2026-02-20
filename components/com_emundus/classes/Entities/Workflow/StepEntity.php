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

	/**
	 * @var array<CampaignStepDateEntity>|null $campaignsDates
	 */
	public ?array $campaignsDates = null;

	private PaymentRepository $paymentRepository;

	/**
	 * @param   int                  $id
	 * @param   int                  $workflow_id
	 * @param   string               $label
	 * @param   StepTypeEntity       $type
	 * @param   ?int                 $profile_id
	 * @param   ?int                 $form_id
	 * @param   array                $entry_status
	 * @param   int|null             $output_status
	 * @param   int                  $multiple
	 * @param   int                  $state
	 * @param   int                  $ordering
	 * @param   string               $table
	 * @param   int                  $table_id
	 * @param   array<CampaignStepDateEntity>|null  $campaignsDates (if you don't provide it, campaign dates won't be set, nor altered on updates, if you want to set or update them, you must provide them)
	 */
	public function __construct(int $id, int $workflow_id, string $label, StepTypeEntity $type, ?int $profile_id = 0, ?int $form_id = 0, array $entry_status = [], ?int $output_status = null, int $multiple = 0, int $state = 1, int $ordering = 0, string $table = '', int $table_id = 0, $campaignsDates = null)
	{
		$this->id = $id;
		$this->workflow_id = $workflow_id;
		$this->label = $label;
		$this->type = $type;
		$this->profile_id = $profile_id;
		$this->form_id = $form_id;
		$this->entry_status = $entry_status;
		$this->output_status = $output_status;
		$this->multiple = $multiple;
		$this->state = $state;
		$this->ordering = $ordering;
		$this->table = $table;
		$this->table_id = $table_id;
		$this->campaignsDates = $campaignsDates;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getWorkflowId(): int
	{
		return $this->workflow_id;
	}

	public function setWorkflowId(int $workflow_id): void
	{
		$this->workflow_id = $workflow_id;
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
		return $this->getType()->getCode() === 'evaluator' || (!$this->isApplicantStep() && !$this->isPaymentStep() && $this->getType()->getCode() !== 'choices');
	}

	public function isPaymentStep(): bool
	{
		if (empty($this->paymentRepository))
		{
			$this->paymentRepository = new PaymentRepository();
		}

		return in_array($this->getType()->getId(), $this->paymentRepository->getPaymentStepTypeIds());
	}

	public function setOrdering(int $ordering): void
	{
		$this->ordering = $ordering;
	}

	public function getTable(): string
	{
		return $this->table;
	}

	public function getTableId(): int
	{
		return $this->table_id;
	}

	public function getCampaignsDates(): ?array
	{
		return $this->campaignsDates;
	}

	/**
	 * @param   array<CampaignStepDateEntity>|null  $campaignsDates
	 *
	 * @return void
	 */
	public function setCampaignsDates(?array $campaignsDates): void
	{
		$this->campaignsDates = $campaignsDates;
	}

	public function getDatesForCampaign(int $campaignId): ?CampaignStepDateEntity
	{
		if (empty($this->campaignsDates))
		{
			return null;
		}

		foreach ($this->campaignsDates as $campaignStepDate)
		{
			if ($campaignStepDate->campaign_id === $campaignId)
			{
				return $campaignStepDate;
			}
		}

		return null;
	}

	public function serialize()
	{
		return [
			'id' => $this->getId(),
			'workflow_id' => $this->workflow_id,
			'label' => $this->label,
			'type' => $this->type?->serialize(),
			'profile_id' => $this->profile_id,
			'form_id' => $this->form_id,
			'entry_status' => $this->entry_status,
			'output_status' => $this->output_status,
			'multiple' => $this->multiple,
			'state' => $this->state,
			'ordering' => $this->ordering,
			'table' => $this->table,
			'table_id' => $this->table_id,
		];
	}
}