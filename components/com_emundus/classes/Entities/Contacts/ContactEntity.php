<?php
/**
 * @package     Tchooz\Entities\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Contacts;

use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Country;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Enums\Contacts\GenderEnum;

class ContactEntity
{
	private int $id;

	private string $lastname;

	private string $firstname;

	private string $email;

	private ?string $phone_1;

	private int $user_id;

	private ?string $birthdate;

	private ?GenderEnum $gender;

	private ?VerifiedStatusEnum $status;

	private bool $published;

	private ?array $addresses = [];

	private ?array $countries = [];

	private ?array $organizations = [];

	private ?array $application_files = [];

	private ?string $profile_picture;

	private ?string $fonction;

	private ?string $service;

	public function __construct(string $email, string $lastname, string $firstname, ?string $phone_1 = null, ?int $id = 0, ?int $user_id = 0, ?array $addresses = null, ?string $birth = null, GenderEnum|string|null $gender = null, ?string $fonction = null, ?string $service = null, ?array $countries = null, ?array $organizations = null, ?array $application_files = null, ?string $profile_picture = null, bool $published = true, ?VerifiedStatusEnum $status = VerifiedStatusEnum::VERIFIED)
	{
		$this->email     = $email;
		$this->lastname  = $lastname;
		$this->firstname = $firstname;
		$this->phone_1   = $phone_1;
		$this->id        = $id ?: 0;
		$this->user_id   = !empty($user_id) ? $user_id : 0;
		$this->birthdate = $birth;
		$this->fonction = $fonction;
		$this->service  = $service;
		$this->status = $status;

		if($gender instanceof GenderEnum || is_null($gender)) {
			$this->gender = $gender;
		}
		else {
			$this->gender = GenderEnum::from($gender) ?? null;
		}

		if(!empty($countries))
		{
			foreach ($countries as $country)
			{
				if ($country instanceof Country)
				{
					$this->countries[] = $country;
				}
			}
		}

		if(!empty($organizations))
		{
			foreach ($organizations as $organization)
			{
				if ($organization instanceof OrganizationEntity)
				{
					$this->organizations[] = $organization;
				}
			}
		}

		if(!empty($application_files))
		{
			foreach ($application_files as $application_file)
			{
				if ($application_file instanceof ApplicationFileEntity)
				{
					$this->application_files[] = $application_file;
				}
			}
		}

		if(!empty($addresses))
		{
			foreach ($addresses as $address)
			{
				if ($address instanceof AddressEntity)
				{
					$this->addresses[] = $address;
				}
			}
		}

		$this->profile_picture = $profile_picture;
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

	public function getLastname(): string
	{
		return $this->lastname;
	}

	public function setLastname(string $lastname): void
	{
		$this->lastname = $lastname;
	}

	public function getFirstname(): string
	{
		return $this->firstname;
	}

	public function setFirstname(string $firstname): void
	{
		$this->firstname = $firstname;
	}

	public function getFullName(): string
	{
		return $this->firstname . ' ' . $this->lastname;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): void
	{
		$this->email = $email;
	}

	public function getPhone1(): ?string
	{
		return $this->phone_1;
	}

	public function setPhone1(?string $phone_1): void
	{
		$this->phone_1 = $phone_1;
	}

	public function getUserId(): int
	{
		return $this->user_id;
	}

	public function setUserId(int $user_id): void
	{
		$this->user_id = $user_id;
	}

	/**
	 * @return AddressEntity[]
	 */
	public function getAddresses(): ?array
	{
		return $this->addresses;
	}

	public function setAddresses(?array $addresses): void
	{
		$this->addresses = $addresses;
	}

	public function getBirthdate(): ?string
	{
		return $this->birthdate;
	}

	public function setBirthdate(?string $birthdate): void
	{
		$this->birthdate = $birthdate;
	}

	public function getGender(): ?GenderEnum
	{
		return $this->gender;
	}

	public function setGender(?GenderEnum $gender): void
	{
		$this->gender = $gender;
	}

	public function getStatus(): ?VerifiedStatusEnum
	{
		return $this->status;
	}

	public function setStatus(?VerifiedStatusEnum $status): void
	{
		$this->status = $status;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	/**
	 * @return Country[]
	 */
	public function getCountries(): ?array
	{
		return $this->countries;
	}

	public function setCountries(?array $countries): void
	{
		$this->countries = $countries;
	}

	/**
	 * @return OrganizationEntity[]
	 */
	public function getOrganizations(): ?array
	{
		return $this->organizations;
	}

	public function setOrganizations(?array $organizations): void
	{
		$this->organizations = $organizations;
	}

	public function getApplicationFiles(): ?array
	{
		return $this->application_files;
	}

	public function setApplicationFiles(?array $application_files): void
	{
		$this->application_files = $application_files;
	}

	public function getProfilePicture(): ?string
	{
		return $this->profile_picture;
	}

	public function setProfilePicture(?string $profile_picture): void
	{
		$this->profile_picture = $profile_picture;
	}

	public function getFonction(): ?string
	{
		return $this->fonction;
	}

	public function setFonction(?string $fonction): void
	{
		$this->fonction = $fonction;
	}

	public function getService(): ?string
	{
		return $this->service;
	}

	public function setService(?string $service): void
	{
		$this->service = $service;
	}

	public function __serialize(): array
	{
		return get_object_vars($this);
	}
}