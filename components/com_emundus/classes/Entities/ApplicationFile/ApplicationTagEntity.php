<?php

namespace Tchooz\Entities\ApplicationFile;

class ApplicationTagEntity
{
	private int $id;
	private string $label;
	private string $color;
	private int $ordering;
	private string $category;

	public function __construct(int $id, string $label, string $color, int $ordering, string $category)
	{
		$this->id = $id;
		$this->label = $label;
		$this->color = $color;
		$this->ordering = $ordering;
		$this->category = $category;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): self
	{
		$this->label = $label;

		return $this;
	}

	public function getColor(): string
	{
		return $this->color;
	}

	public function setColor(string $color): self
	{
		$this->color = $color;

		return $this;
	}

	public function getOrdering(): int
	{
		return $this->ordering;
	}

	public function setOrdering(int $ordering): self
	{
		$this->ordering = $ordering;

		return $this;
	}

	public function getCategory(): string
	{
		return $this->category;
	}

	public function setCategory(string $category): self
	{
		$this->category = $category;

		return $this;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'label' => $this->label,
			'color' => $this->color,
			'ordering' => $this->ordering,
			'category' => $this->category,
		];
	}
}