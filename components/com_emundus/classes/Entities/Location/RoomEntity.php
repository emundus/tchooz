<?php

namespace Tchooz\Entities\Location;

class RoomEntity
{
	private int $id;

	private LocationEntity $location;

	private string $name;

	private int $capacity;

	/**
	 * @var array<SpecificationEntity>
	 */
	private array $specifications;

	/**
	 * @param   int                    $id
	 * @param   LocationEntity         $location
	 * @param   string                 $name
	 * @param   int                    $capacity
	 * @param   SpecificationEntity[]  $specifications
	 */
	public function __construct(int $id, LocationEntity $location, string $name, int $capacity, array $specifications = [])
	{
		$this->id             = $id;
		$this->location       = $location;
		$this->name           = $name;
		$this->capacity       = $capacity;
		$this->specifications = $specifications;
	}


	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): RoomEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getLocation(): LocationEntity
	{
		return $this->location;
	}

	public function setLocation(LocationEntity $location): RoomEntity
	{
		$this->location = $location;

		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): RoomEntity
	{
		$this->name = $name;

		return $this;
	}

	public function getCapacity(): int
	{
		return $this->capacity;
	}

	public function setCapacity(int $capacity): RoomEntity
	{
		$this->capacity = $capacity;

		return $this;
	}

	public function getSpecifications(): array
	{
		return $this->specifications;
	}

	public function setSpecifications(array $specifications): RoomEntity
	{
		$this->specifications = $specifications;

		return $this;
	}
}