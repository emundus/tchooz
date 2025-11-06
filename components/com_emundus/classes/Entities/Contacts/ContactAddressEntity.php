<?php

namespace Tchooz\Entities\Contacts;

class ContactAddressEntity
{
	private ContactEntity $contact;

	private AddressEntity $address;

	public function __construct(ContactEntity $contact, AddressEntity $address)
	{
		$this->contact = $contact;
		$this->address = $address;
	}

	public function getContact(): ContactEntity
	{
		return $this->contact;
	}

	public function setContact(ContactEntity $contact): void
	{
		$this->contact = $contact;
	}

	public function getAddress(): AddressEntity
	{
		return $this->address;
	}

	public function setAddress(AddressEntity $address): void
	{
		$this->address = $address;
	}
}
