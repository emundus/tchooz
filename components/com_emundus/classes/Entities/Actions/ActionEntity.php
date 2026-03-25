<?php
/**
 * @package     Tchooz\Entities\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Actions;

use Tchooz\Enums\Actions\ActionTypeEnum;

class ActionEntity
{
	private int $id;

	private string $name;

	private string $label;

	private CrudEntity $crud;

	private int $ordering;

	private bool $status;

	private ?string $description;

	private ActionTypeEnum $type;

	public function __construct(int $id, string $name, string $label, CrudEntity $crud, int $ordering = 0, bool $status = true, ?string $description = null, ActionTypeEnum $type = ActionTypeEnum::FILE)
	{
		$this->id          = $id ?: 0;
		$this->name        = $name;
		$this->label       = $label;
		$this->crud        = $crud;
		$this->ordering    = $ordering;
		$this->status      = $status;
		$this->description = $description;
		$this->type        = $type;
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

	public function getType(): ActionTypeEnum
	{
		return $this->type;
	}

	public function setType(ActionTypeEnum $type): ActionEntity
	{
		$this->type = $type;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id'          => $this->id,
			'name'        => $this->name,
			'label'       => $this->label,
			'crud'        => $this->crud->__serialize(),
			'ordering'    => $this->ordering,
			'status'      => $this->status,
			'description' => $this->description,
			'type'        => $this->type->value,
		];
	}
}