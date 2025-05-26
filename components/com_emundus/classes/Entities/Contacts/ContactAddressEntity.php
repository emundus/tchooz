<?php

namespace Tchooz\Entities\Contacts;

class ContactAddressEntity
{
	private int $id = 0;
	private int $contact_id = 0;
	private string $address1 = '';
	private string $address2 = '';
	private string $city = '';
	private string $state = '';
	private string $zip = '';
	private int $country = 0;

	public function __construct(int $contact_id, string $address1, string $address2, string $city, string $state, string $zip, int $country, ?int $id = 0)
	{
		$this->contact_id = $contact_id;
		$this->address1 = $address1;
		$this->address2 = $address2;
		$this->city = $city;
		$this->state = $state;
		$this->zip = $zip;
		$this->country = $country;
		$this->id = $id ?: 0;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getContactId(): int
	{
		return $this->contact_id;
	}

	public function setContactId(int $contact_id): void
	{
		$this->contact_id = $contact_id;
	}

	public function getAddress1(): string
	{
		return $this->address1;
	}

	public function setAddress1(string $address1): void
	{
		$this->address1 = $address1;
	}

	public function getAddress2(): string
	{
		return $this->address2;
	}

	public function setAddress2(string $address2): void
	{
		$this->address2 = $address2;
	}

	public function getCity(): string
	{
		return $this->city;
	}

	public function setCity(string $city): void
	{
		$this->city = $city;
	}

	public function getState(): string
	{
		return $this->state;
	}

	public function setState(string $state): void
	{
		$this->state = $state;
	}

	public function getZip(): string
	{
		return $this->zip;
	}

	public function setZip(string $zip): void
	{
		$this->zip = $zip;
	}

	public function getCountry(): int
	{
		return $this->country;
	}

	public function setCountry(int $country): void
	{
		$this->country = $country;
	}

	public function __serialize(): array
	{
		return [
			'id' => $this->getId(),
			'contact_id' => $this->getContactId(),
			'address1' => $this->getAddress1(),
			'address2' => $this->getAddress2(),
			'city' => $this->getCity(),
			'state' => $this->getState(),
			'zip' => $this->getZip(),
			'country' => $this->getCountry(),
		];
	}
}
