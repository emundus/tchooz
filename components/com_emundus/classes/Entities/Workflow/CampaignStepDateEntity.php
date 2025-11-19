<?php

namespace Tchooz\Entities\Workflow;

use DateTimeImmutable;
use Tchooz\Enums\Workflow\WorkflowStepDateRelativeToEnum;
use Tchooz\Enums\Workflow\WorkflowStepDatesRelativeUnitsEnum;

class CampaignStepDateEntity
{
	private int $id;

	private int $campaignId;

	private int $stepId;

	private ?DateTimeImmutable $startDate;

	private ?DateTimeImmutable $endDate;

	private bool $infinite;

	private bool $relativeDate;

	private WorkflowStepDateRelativeToEnum $relativeTo;

	private int $relativeStartDateValue;

	private WorkflowStepDatesRelativeUnitsEnum $relativeStartDateUnit;

	private int $relativeEndDateValue;

	private WorkflowStepDatesRelativeUnitsEnum $relativeEndDateUnit;

	public function __construct(int $id, int $campaignId, int $stepId, ?DateTimeImmutable $startDate = null, ?DateTimeImmutable $endDate = null, bool $infinite = false, bool $relativeDate = false, WorkflowStepDateRelativeToEnum $relativeTo = WorkflowStepDateRelativeToEnum::STATUS, int $relativeStartDateValue = 0, WorkflowStepDatesRelativeUnitsEnum $relativeStartDateUnit = WorkflowStepDatesRelativeUnitsEnum::DAY, int $relativeEndDateValue = 0, WorkflowStepDatesRelativeUnitsEnum $relativeEndDateUnit = WorkflowStepDatesRelativeUnitsEnum::DAY)
	{
		$this->id = $id;
		$this->campaignId = $campaignId;
		$this->stepId = $stepId;
		$this->startDate = $startDate;
		$this->endDate = $endDate;
		$this->infinite = $infinite;
		$this->relativeDate = $relativeDate;
		$this->relativeTo = $relativeTo;
		$this->relativeStartDateValue = $relativeStartDateValue;
		$this->relativeStartDateUnit = $relativeStartDateUnit;
		$this->relativeEndDateValue = $relativeEndDateValue;
		$this->relativeEndDateUnit = $relativeEndDateUnit;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getCampaignId(): int
	{
		return $this->campaignId;
	}

	public function getStepId(): int
	{
		return $this->stepId;
	}

	public function getStartDate(): ?DateTimeImmutable
	{
		return $this->startDate;
	}

	public function getEndDate(): ?DateTimeImmutable
	{
		return $this->endDate;
	}

	public function isInfinite(): bool
	{
		return $this->infinite;
	}

	public function isRelativeDate(): bool
	{
		return $this->relativeDate;
	}

	public function getRelativeTo(): WorkflowStepDateRelativeToEnum
	{
		return $this->relativeTo;
	}

	public function getRelativeStartDateValue(): int
	{
		return $this->relativeStartDateValue;
	}

	public function getRelativeStartDateUnit(): WorkflowStepDatesRelativeUnitsEnum
	{
		return $this->relativeStartDateUnit;
	}

	public function getRelativeEndDateValue(): int
	{
		return $this->relativeEndDateValue;
	}

	public function getRelativeEndDateUnit(): WorkflowStepDatesRelativeUnitsEnum
	{
		return $this->relativeEndDateUnit;
	}

	// setters
	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function setCampaignId(int $campaignId): void
	{
		$this->campaignId = $campaignId;
	}

	public function setStepId(int $stepId): void
	{
		$this->stepId = $stepId;
	}

	public function setStartDate(?DateTimeImmutable $startDate): void
	{
		$this->startDate = $startDate;
	}

	public function setEndDate(?DateTimeImmutable $endDate): void
	{
		$this->endDate = $endDate;
	}

	public function setInfinite(bool $infinite): void
	{
		$this->infinite = $infinite;
	}

	public function setRelativeDate(bool $relativeDate): void
	{
		$this->relativeDate = $relativeDate;
	}

	public function setRelativeTo(WorkflowStepDateRelativeToEnum $relativeTo): void
	{
		$this->relativeTo = $relativeTo;
	}

	public function setRelativeStartDateValue(int $relativeStartDateValue): void
	{
		$this->relativeStartDateValue = $relativeStartDateValue;
	}

	public function setRelativeStartDateUnit(WorkflowStepDatesRelativeUnitsEnum $relativeStartDateUnit): void
	{
		$this->relativeStartDateUnit = $relativeStartDateUnit;
	}

	public function setRelativeEndDateValue(int $relativeEndDateValue): void
	{
		$this->relativeEndDateValue = $relativeEndDateValue;
	}

	public function setRelativeEndDateUnit(WorkflowStepDatesRelativeUnitsEnum $relativeEndDateUnit): void
	{
		$this->relativeEndDateUnit = $relativeEndDateUnit;
	}
}