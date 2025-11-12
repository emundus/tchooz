<?php

namespace Tchooz\Entities\Payment;

class ProductCategoryEntity
{
	private int $id = 0;
	private string $label;
	private int $published = 1;

	private ?bool $mandatory = null;

	public function __construct(int $id, string $label, int $published = 1, ?bool $mandatory = null)
	{
		$this->id = $id;
		$this->label = $label;
		$this->published = $published;
		$this->mandatory = $mandatory;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setPublished(int $published): void
	{
		$this->published = $published;
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->getId(),
			'label' => $this->getLabel(),
			'published' => $this->getPublished(),
			'mandatory' => $this->mandatory,
		];
	}
}