<?php
/**
 * @package     Tchooz\Entities\Attachments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Attachments;

class AttachmentType
{
	private int $id;

	private string $lbl;

	private string $name;

	private ?string $description;

	private string $allowedTypes;

	private int $nbMax;

	private ?int $ordering;

	private bool $published = true;

	private ?string $category;

	private ?bool $isRequired;

	private AttachmentTypeProperty $properties;

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getLbl(): string
	{
		return $this->lbl;
	}

	public function setLbl(string $lbl): void
	{
		$this->lbl = $lbl;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): void
	{
		$this->description = $description;
	}

	public function getAllowedTypes(): string
	{
		return $this->allowedTypes;
	}

	public function setAllowedTypes(string $allowedTypes): void
	{
		$this->allowedTypes = $allowedTypes;
	}

	public function getNbMax(): int
	{
		return $this->nbMax;
	}

	public function setNbMax(int $nbMax): void
	{
		$this->nbMax = $nbMax;
	}

	public function getOrdering(): ?int
	{
		return $this->ordering;
	}

	public function setOrdering(?int $ordering): void
	{
		$this->ordering = $ordering;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getCategory(): ?string
	{
		return $this->category;
	}

	public function setCategory(?string $category): void
	{
		$this->category = $category;
	}

	public function isRequired(): ?bool
	{
		return $this->isRequired;
	}

	public function setIsRequired(?bool $isRequired): void
	{
		$this->isRequired = $isRequired;
	}

	public function getProperties(): AttachmentTypeProperty
	{
		return $this->properties;
	}

	public function setProperties(AttachmentTypeProperty $properties): void
	{
		$this->properties = $properties;
	}
}