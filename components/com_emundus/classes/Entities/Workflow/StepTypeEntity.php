<?php

namespace Tchooz\Entities\Workflow;

class StepTypeEntity
{
	public int $id;
	public int $parent_id = 0;

	public string $label;

	public ?string $code;

	public int $action_id;

	public bool $system;

	public bool $published;

	private ?string $class;

	public function __construct(int $id, int $parent_id = 0, string $label = '', ?string $code = null,  int $action_id = 0, bool $system = false, bool $published = true, ?string $class = null)
	{
		$this->id = $id;
		$this->parent_id = $parent_id;
		$this->label = $label;
		$this->code = $code;
		$this->action_id = $action_id;
		$this->system = $system;
		$this->published = $published;
		$this->class = $class;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getParentId(): int
	{
		return $this->parent_id;
	}

	public function setParentId(int $parent_id): void
	{
		$this->parent_id = $parent_id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function isSystem(): bool
	{
		return $this->system;
	}

	public function setSystem(bool $system): void
	{
		$this->system = $system;
	}

	public function setCode(string $code): void
	{
		$this->code = $code;
	}

	public function getCode(): ?string
	{
		return $this->code;
	}

	public function getActionId(): int
	{
		return $this->action_id;
	}

	public function setActionId(int $action_id): void
	{
		$this->action_id = $action_id;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getClass(): ?string
	{
		return $this->class;
	}

	public function setClass(?string $class): void
	{
		$this->class = $class;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'parent_id' => $this->parent_id,
			'label' => $this->label,
			'action_id' => $this->action_id,
			'code' => $this->code,
			'system' => $this->system,
			'published' => $this->published,
			'class' => $this->class,
		];
	}
}