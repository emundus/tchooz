<?php
/**
 * @package     Tchooz\Entities\User
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\User;

class UserCategoryEntity
{
	private int $id;

	private ?string $created_at;

	private string $label;

	private bool $published;

	private ?int $created_by;

	public function __construct(int $id, string $label, ?int $created_by = 0, ?string $created_at = '', bool $published = true)
	{
		$this->id         = $id;
		$this->created_at = $created_at;
		$this->label      = $label;
		$this->created_by = $created_by;
		$this->published  = $published;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getCreatedAt(): string
	{
		return $this->created_at;
	}

	public function setCreatedAt(?string $created_at): void
	{
		$this->created_at = $created_at;
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
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getCreatedBy(): ?int
	{
		return $this->created_by;
	}

	public function setCreatedBy(?int $created_by): void
	{
		$this->created_by = $created_by;
	}

	public function __serialize(): array
	{
		return [
			'id'    => $this->id,
			'label' => $this->label
		];
	}
}