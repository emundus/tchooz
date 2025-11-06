<?php
/**
 * @package     Tchooz\Entities\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Contacts;

use Tchooz\Enums\Contacts\VerifiedStatusEnum;

class OrganizationEntity
{
	private int $id;

	private string $name;

	private ?string $description;

	private ?string $url_website;

	private bool $published;

	private ?AddressEntity $address;

	private ?array $referent_contacts;
	private ?array $other_contacts;

	private ?string $identifier_code;

	private ?string $logo;

	private ?VerifiedStatusEnum $status;

	public function __construct(int $id, string $name, ?string $description = null, ?string $url_website = null, ?AddressEntity $address = null, ?string $identifier_code = null, ?string $logo = null, ?array $referent_contacts = [],  ?array $other_contacts = [], bool $published = true, ?VerifiedStatusEnum $status = VerifiedStatusEnum::VERIFIED)
	{
		$this->name            = $name;
		$this->description     = $description;
		$this->url_website     = $url_website;
		$this->id              = $id ?: 0;
		$this->address         = $address;
		$this->identifier_code = $identifier_code;
		$this->logo            = $logo;
		$this->referent_contacts = [];
		$this->other_contacts = [];
		$this->status = $status;

		if(!empty($referent_contacts))
		{
			foreach ($referent_contacts as $contact)
			{
				if ($contact instanceof ContactEntity)
				{
					$this->referent_contacts[] = $contact;
				}
			}
		}
		if(!empty($other_contacts))
		{
			foreach ($other_contacts as $contact)
			{
				if ($contact instanceof ContactEntity)
				{
					$this->other_contacts[] = $contact;
				}
			}
		}
		$this->published       = $published;
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

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): void
	{
		$this->description = $description;
	}

	public function getUrlWebsite(): ?string
	{
		return $this->url_website;
	}

	public function setUrlWebsite(?string $url_website): void
	{
		$this->url_website = $url_website;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setIsPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getAddress(): ?AddressEntity
	{
		return $this->address;
	}

	public function setAddress(?AddressEntity $address): void
	{
		$this->address = $address;
	}

	/**
	 * @return ContactEntity[]
	 */
	public function getReferentContacts(): ?array
	{
		return $this->referent_contacts;
	}

	public function setReferentContacts(?array $contacts): void
	{
		$this->referent_contacts = $contacts;
	}

	/**
	 * @return ContactEntity[]
	 */
	public function getOtherContacts(): ?array
	{
		return $this->other_contacts;
	}

	public function setOtherContacts(?array $contacts): void
	{
		$this->other_contacts = $contacts;
	}

	public function getIdentifierCode(): ?string
	{
		return $this->identifier_code;
	}

	public function setIdentifierCode(?string $identifier_code): void
	{
		$this->identifier_code = $identifier_code;
	}

	public function getLogo(): ?string
	{
		return $this->logo;
	}

	public function setLogo(?string $logo): void
	{
		$this->logo = $logo;
	}

	public function getStatus(): ?VerifiedStatusEnum
	{
		return $this->status;
	}

	public function setStatus(?VerifiedStatusEnum $status): void
	{
		$this->status = $status;
	}

	public function __serialize(): array
	{
		return get_object_vars($this);
	}


}