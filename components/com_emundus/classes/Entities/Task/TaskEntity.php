<?php

namespace Tchooz\Entities\Task;

use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Task\TaskPriorityEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Factories\Automation\ActionFactory;
use Tchooz\Factories\Automation\ActionTargetFactory;

class TaskEntity
{
	private int $id;

	private TaskStatusEnum $status;

	private ?ActionEntity $action;

	private ?int $userId = null;

	private array $metadata = [];

	private ?\DateTimeImmutable $createdAt = null;

	private ?\DateTimeImmutable $updatedAt = null;

	private ?\DateTimeImmutable $startedAt = null;

	private ?\DateTimeImmutable $finishedAt = null;

	private int $attempts = 0;

	private TaskPriorityEnum $priority;

	public function __construct(int $id, TaskStatusEnum $status, ?ActionEntity $action = null, int $userId = null, array $metadata = [], ?\DateTimeImmutable $createdAt = null, ?\DateTimeImmutable $updatedAt = null, ?\DateTimeImmutable $startedAt = null, ?\DateTimeImmutable $finishedAt = null, int $attempts = 0, TaskPriorityEnum $priority = TaskPriorityEnum::MEDIUM)
	{
		$this->id = $id;
		$this->status = $status;
		$this->action = $action;
		$this->userId = $userId;
		$this->metadata = $metadata;
		$this->createdAt = $createdAt;
		$this->updatedAt = $updatedAt;
		$this->startedAt = $startedAt;
		$this->finishedAt = $finishedAt;
		$this->attempts = $attempts;
		$this->priority = $priority;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): static
	{
		$this->id = $id;

		return $this;
	}

	public function getStatus(): TaskStatusEnum
	{
		return $this->status;
	}

	public function setStatus(TaskStatusEnum $status): static
	{
		$this->status = $status;

		return $this;
	}

	public function getAction(): ?ActionEntity
	{
		return $this->action;
	}

	public function setAction(ActionEntity $action): static
	{
		$this->action = $action;

		return $this;
	}

	public function getUserId(): ?int
	{
		return $this->userId;
	}

	public function setUserId(int $userId): static
	{
		$this->userId = $userId;

		return $this;
	}

	public function getMetadata(): array
	{
		return $this->metadata;
	}

	public function setMetadata(array $metadata): static
	{
		$this->metadata = $metadata;

		return $this;
	}

	public function getCreatedAt(): ?\DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function getUpdatedAt(): ?\DateTimeImmutable
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	public function getStartedAt(): ?\DateTimeImmutable
	{
		return $this->startedAt;
	}

	public function setStartedAt(\DateTimeImmutable $startedAt): static
	{
		$this->startedAt = $startedAt;

		return $this;
	}


	public function getFinishedAt(): ?\DateTimeImmutable
	{
		return $this->finishedAt;
	}

	public function setFinishedAt(\DateTimeImmutable $finishedAt): static
	{
		$this->finishedAt = $finishedAt;

		return $this;
	}

	public function getAttempts(): int
	{
		return $this->attempts;
	}

	public function setAttempts(int $attempts): static
	{
		$this->attempts = $attempts;

		return $this;
	}

	public function getPriority(): TaskPriorityEnum
	{
		return $this->priority;
	}

	public function setPriority(TaskPriorityEnum $priority): static
	{
		$this->priority = $priority;

		return $this;
	}

	public function execute(): void
	{
		$this->setAttempts($this->getAttempts() + 1);

		try
		{
			$actionTargetEntityData = $this->metadata['actionTargetEntity'] ?? null;
			$actionTargetEntitiesData = $this->metadata['actionTargetEntities'] ?? null;

			if (empty($actionTargetEntitiesData) && !empty($this->metadata['fnums']))
			{
				$actionTargetEntitiesData = array_map(function ($fnum) {
					return [
						'triggeredBy' => $this->getUserId(),
						'file' => $fnum,
						'user' => null,
						'parameters' => [],
						'custom' => null,
						'originalContext' => null,
					];
				}, $this->metadata['fnums']);
			}

			if (empty($actionTargetEntityData) && empty($actionTargetEntitiesData)) {
				throw new \Exception('Action target entity data is missing in task metadata.');
			}

			if (!empty($actionTargetEntityData)) {
				$actionTargetEntity = ActionTargetFactory::fromSerialized($actionTargetEntityData);
				if (empty($actionTargetEntity)) {
					throw new \Exception('Failed to create ActionTargetEntity from metadata.');
				}
			}

			if (!empty($actionTargetEntitiesData) && is_array($actionTargetEntitiesData)) {
				$actionTargetEntities = [];
				foreach ($actionTargetEntitiesData as $entityData) {
					$entity = ActionTargetFactory::fromSerialized($entityData);
					if (empty($entity)) {
						throw new \Exception('Failed to create one of the ActionTargetEntities from metadata.');
					}
					$actionTargetEntities[] = $entity;
				}
			}
			
			$action = $this->getAction() ?: ActionFactory::fromSerialized($this->metadata['actionEntity'] ?? []);
			if (empty($action))
			{
				throw new \Exception('Action not found for task id ' . $this->getId());
			}

			if (isset($actionTargetEntity)) {
				$actionResult = $action->with($this)->execute($actionTargetEntity, null);
			} elseif (isset($actionTargetEntities)) {
				$actionResult = $action->with($this)->execute($actionTargetEntities, null);
			} else {
				throw new \Exception('No valid action target entity/entities found for task id ' . $this->getId());
			}

			if ($actionResult === ActionExecutionStatusEnum::COMPLETED)
			{
				$this->setStatus(TaskStatusEnum::COMPLETED);
			}
			elseif ($actionResult === ActionExecutionStatusEnum::PENDING)
			{
				$this->setStatus(TaskStatusEnum::PENDING);
			}
			else
			{
				$this->setStatus(TaskStatusEnum::FAILED);
			}
		} catch(\Exception $e) {
			$this->setStatus(TaskStatusEnum::FAILED);
		}
	}

	public function serialize(): array
	{
		if (!class_exists('EmundusHelperDate'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/date.php');
		}

		return [
			'id' => $this->id,
			'status' => $this->status->value,
			'action' => !empty($this->action) ? $this->action->serialize() : null,
			'userId' => $this->userId,
			'metadata' => $this->metadata,
			'createdAt' => $this->createdAt?->format('d/m/Y H:i:s'),
			'updatedAt' => $this->updatedAt?->format('d/m/Y H:i:s'),
			'startedAt' => $this->startedAt?->format('d/m/Y H:i:s'),
			'finishedAt' => $this->finishedAt?->format('d/m/Y H:i:s'),
			'attempts' => $this->attempts,
			'priority' => $this->priority->value,
		];
	}
}