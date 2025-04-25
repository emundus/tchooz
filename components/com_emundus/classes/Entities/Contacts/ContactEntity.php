<?php
/**
 * @package     classes\Entities\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace classes\Entities\Contacts;

class ContactEntity
{
	private int $id = 0;

	private string $lastname;

	private string $firstname;

	private string $email;

	private ?string $phone_1 = null;

	private int $user_id = 0;

	public function __construct(string $email, string $lastname, string $firstname, ?string $phone_1 = null, ?int $id = 0)
	{
		$this->email     = $email;
		$this->lastname  = $lastname;
		$this->firstname = $firstname;
		$this->phone_1   = $phone_1;
		$this->id        = $id ?: 0;
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

	public function __serialize(): array
	{
		return [
			'id'        => $this->id,
			'lastname'  => $this->lastname,
			'firstname' => $this->firstname,
			'email'     => $this->email,
			'phone_1'   => $this->phone_1,
			'user_id'   => $this->user_id ?: null
		];
	}

}