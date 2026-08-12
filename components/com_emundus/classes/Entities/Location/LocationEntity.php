<?php

namespace Tchooz\Entities\Location;

use Joomla\CMS\User\User;

class LocationEntity
{
	private int $id;

	private \DateTime $createdAt;

	private string $name;

	private string $address;

	private string $description;

	private string $mapLocation;

	private bool $published;

	private ?User $createdBy;

	private ?\DateTime $updatedAt;

	private ?User $updatedBy;

	/**
	 * @param   int             $id
	 * @param   \DateTime       $createdAt
	 * @param   string          $name
	 * @param   string          $address
	 * @param   string          $description
	 * @param   string          $mapLocation
	 * @param   bool            $published
	 * @param   User|null       $createdBy
	 * @param   \DateTime|null  $updatedAt
	 * @param   User|null       $updatedBy
	 */
	public function __construct(int $id, \DateTime $createdAt, string $name, string $address, string $description, string $mapLocation, bool $published, ?User $createdBy, ?\DateTime $updatedAt, ?User $updatedBy)
	{
		$this->id          = $id;
		$this->createdAt   = $createdAt;
		$this->name        = $name;
		$this->address     = $address;
		$this->description = $description;
		$this->mapLocation = $mapLocation;
		$this->published   = $published;
		$this->createdBy   = $createdBy;
		$this->updatedAt   = $updatedAt;
		$this->updatedBy   = $updatedBy;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): LocationEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getCreatedAt(): \DateTime
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTime $createdAt): LocationEntity
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): LocationEntity
	{
		$this->name = $name;

		return $this;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	public function setAddress(string $address): LocationEntity
	{
		$this->address = $address;

		return $this;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): LocationEntity
	{
		$this->description = $description;

		return $this;
	}

	public function getMapLocation(): string
	{
		return $this->mapLocation;
	}

	public function setMapLocation(string $mapLocation): LocationEntity
	{
		$this->mapLocation = $mapLocation;

		return $this;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): LocationEntity
	{
		$this->published = $published;

		return $this;
	}

	public function getCreatedBy(): ?User
	{
		return $this->createdBy;
	}

	public function setCreatedBy(?User $createdBy): LocationEntity
	{
		$this->createdBy = $createdBy;

		return $this;
	}

	public function getUpdatedAt(): ?\DateTime
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(?\DateTime $updatedAt): LocationEntity
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	public function getUpdatedBy(): ?User
	{
		return $this->updatedBy;
	}

	public function setUpdatedBy(?User $updatedBy): LocationEntity
	{
		$this->updatedBy = $updatedBy;

		return $this;
	}
}