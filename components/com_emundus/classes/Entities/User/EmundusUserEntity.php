<?php
/**
 * @package     Tchooz\Entities\User
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\User;

use Joomla\CMS\User\User;

class EmundusUserEntity
{
	private int $id;

	private User $user;

	private string $firstname;

	private string $lastname;

	private ?string $profile_picture;

	private ?UserCategoryEntity $user_category;

	public function __construct(
		int $id,
		User $user,
		string $firstname,
		string $lastname,
		string $profile_picture = null,
		UserCategoryEntity $user_category = null
	) {
		$this->id = $id;
		$this->user = $user;
		$this->firstname = $firstname;
		$this->lastname = $lastname;
		$this->profile_picture = $profile_picture;
		$this->user_category = $user_category;
	}

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
		return $this->firstname;
	}

	public function setFirstname(string $firstname): void
	{
		$this->firstname = $firstname;
	}

	public function getLastname(): string
	{
		return $this->lastname;
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
}