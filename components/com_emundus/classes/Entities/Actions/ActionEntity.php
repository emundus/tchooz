<?php
/**
 * @package     Tchooz\Entities\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Actions;

class ActionEntity
{
	private int $id;

	private string $name;

	private string $label;

	private CrudEntity $crud;

	private int $ordering;

	private bool $status;

	private ?string $description;

	public function __construct(int $id, string $name, string $label, CrudEntity $crud, int $ordering = 0, bool $status = true, ?string $description = null)
	{
		$this->id          = $id ?: 0;
		$this->name        = $name;
		$this->label       = $label;
		$this->crud        = $crud;
		$this->ordering    = $ordering;
		$this->status      = $status;
		$this->description = $description;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getCrud(): CrudEntity
	{
		return $this->crud;
	}

	public function setCrud(CrudEntity $crud): void
	{
		$this->crud = $crud;
	}

	public function getOrdering(): int
	{
		return $this->ordering;
	}

	public function setOrdering(int $ordering): void
	{
		$this->ordering = $ordering;
	}

	public function isStatus(): bool
	{
		return $this->status;
	}

	public function setStatus(bool $status): void
	{
		$this->status = $status;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): void
	{
		$this->description = $description;
	}
}