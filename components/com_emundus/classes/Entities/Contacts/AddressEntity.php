<?php

namespace Tchooz\Entities\Contacts;

class AddressEntity
{
	private int $id;

	private ?string $locality;

	private ?string $region;

	private ?string $street_address;

	private ?string $extended_address;

	private ?string $postal_code;

	private ?string $description;

	private ?int $country;

	public function __construct(int $id, ?string $locality = null, ?string $region = null, ?string $street_address = null, ?string $extended_address = null, ?string $postal_code = null, ?string $description = null, ?int $country = 0)
	{
		$this->locality         = $locality;
		$this->region           = $region;
		$this->street_address   = $street_address;
		$this->extended_address = $extended_address;
		$this->postal_code      = $postal_code;
		$this->description      = $description;
		$this->country          = $country;
		$this->id               = $id ?: 0;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getLocality(): ?string
	{
		return $this->locality;
	}

	public function setLocality(?string $locality): void
	{
		$this->locality = $locality;
	}

	public function getRegion(): ?string
	{
		return $this->region;
	}

	public function setRegion(?string $region): void
	{
		$this->region = $region;
	}

	public function getStreetAddress(): ?string
	{
		return $this->street_address;
	}

	public function setStreetAddress(?string $street_address): void
	{
		$this->street_address = $street_address;
	}

	public function getExtendedAddress(): ?string
	{
		return $this->extended_address;
	}

	public function setExtendedAddress(?string $extended_address): void
	{
		$this->extended_address = $extended_address;
	}

	public function getPostalCode(): ?string
	{
		return $this->postal_code;
	}

	public function setPostalCode(?string $postal_code): void
	{
		$this->postal_code = $postal_code;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): void
	{
		$this->description = $description;
	}

	public function getCountry(): ?int
	{
		return $this->country;
	}

	public function setCountry(?int $country): void
	{
		$this->country = $country;
	}

	public function __serialize()
	{
		return get_object_vars($this);
	}
}
