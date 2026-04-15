<?php
/**
 * @package     Tchooz\Entities\User
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\User;

use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

class EmundusUserEntity
{
	/**
	 * @var int Emundus User id, not to be confused with Joomla User id which is stored in $user property
	 */
	private int $id;

	private User $user;

	private string $firstname;

	private string $lastname;

	private ?string $profile_picture;

	private ?UserCategoryEntity $user_category;

	private bool $anonym;

	private ?\DateTimeImmutable $birthDate;

	private ?string $emailCc;

	public function __construct(
		int                $id,
		User               $user,
		string             $firstname,
		string             $lastname,
		string             $profile_picture = null,
		UserCategoryEntity $user_category = null,
		bool               $is_anonym = false,
		?\DateTimeImmutable $birthDate = null,
		?string             $emailCc = null
	)
	{
		$this->id              = $id;
		$this->user            = $user;
		$this->firstname       = $firstname;
		$this->lastname        = $lastname;
		$this->profile_picture = $profile_picture;
		$this->user_category   = $user_category;
		$this->anonym          = $is_anonym;
		$this->birthDate       = $birthDate;
		$this->emailCc         = $emailCc;
	}

	/**
	 * emundus_user id, be careful not to confuse with Joomla user id which is stored in $user property
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function setUser(User $user): void
	{
		$this->user = $user;
	}

	public function getFirstname(): string
	{
		return !$this->anonym ? $this->firstname : Text::_('COM_EMUNDUS_ANONYM_ACCOUNT');
	}

	public function setFirstname(string $firstname): void
	{
		$this->firstname = $firstname;
	}

	public function getLastname(): string
	{
		return !$this->anonym ? $this->lastname : Text::_('COM_EMUNDUS_ANONYM_ACCOUNT');
	}

	public function setLastname(string $lastname): void
	{
		$this->lastname = $lastname;
	}

	public function getProfilePicture(): ?string
	{
		return $this->profile_picture;
	}

	public function setProfilePicture(?string $profile_picture): void
	{
		$this->profile_picture = $profile_picture;
	}

	public function getUserCategory(): ?UserCategoryEntity
	{
		return $this->user_category;
	}

	public function setUserCategory(?UserCategoryEntity $user_category): void
	{
		$this->user_category = $user_category;
	}

	public function isAnonym(): bool
	{
		return $this->anonym;
	}

	public function setAnonym(bool $anonym): void
	{
		$this->anonym = $anonym;
	}

	public function getBirthDate(): ?\DateTimeImmutable
	{
		return $this->birthDate;
	}

	public function setBirthDate(?\DateTimeImmutable $birthDate): EmundusUserEntity
	{
		$this->birthDate = $birthDate;

		return $this;
	}

	public function getEmailCc(): ?string
	{
		return $this->emailCc;
	}

	public function setEmailCc(?string $emailCc): EmundusUserEntity
	{
		$this->emailCc = $emailCc;

		return $this;
	}

	public function getFullname(): string
	{
		return $this->getFirstname() . ' ' . $this->getLastname();
	}

	public function __serialize(): array
	{
		return [
			'id'              => $this->getId(),
			'user_id'         => $this->getUser()->id,
			'firstname'       => $this->getFirstname(),
			'lastname'        => $this->getLastname(),
			'profile_picture' => $this->getProfilePicture(),
			'user_category'   => $this->getUserCategory()?->__serialize(),
			'is_anonym'       => $this->isAnonym(),
			// Format birth date as ISO 8601 date (Y-m-d) for consistency
			'birth_date'      => $this->getBirthDate()?->format('Y-m-d'),
		];
	}
}