<?php

namespace Tchooz\Entities\Poll;

use Joomla\CMS\User\User;
use Tchooz\Attributes\ORM\Column;
use Tchooz\Attributes\ORM\Table;
use Tchooz\Attributes\ORM\Types;

#[Table(name: '#__emundus_setup_polls_participants')]
class PollParticipantsEntity
{
	private int $id;

	#[Column(name: 'poll', type: Types::INTEGER)]
	private ?PollEntity $poll;

	#[Column(type: Types::STRING)]
	private string $email;

	#[Column(type: Types::STRING)]
	private string $firstname;

	#[Column(type: Types::STRING)]
	private string $lastname;

	#[Column(type: Types::INTEGER)]
	private ?User $user;

	/**
	 * @param   int         $id
	 * @param   PollEntity  $poll
	 * @param   string      $email
	 * @param   string      $firstname
	 * @param   string      $lastname
	 * @param   User|null   $user
	 */
	public function __construct(int $id, ?PollEntity $poll, string $email, string $firstname, string $lastname, ?User $user)
	{
		$this->id        = $id;
		$this->poll      = $poll;
		$this->email     = $email;
		$this->firstname = $firstname;
		$this->lastname  = $lastname;
		$this->user      = $user;
	}


	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): PollParticipantsEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getPoll(): ?PollEntity
	{
		return $this->poll;
	}

	public function setPoll(?PollEntity $poll): PollParticipantsEntity
	{
		$this->poll = $poll;

		return $this;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): PollParticipantsEntity
	{
		$this->email = $email;

		return $this;
	}

	public function getFirstname(): string
	{
		return $this->firstname;
	}

	public function setFirstname(string $firstname): PollParticipantsEntity
	{
		$this->firstname = $firstname;

		return $this;
	}

	public function getLastname(): string
	{
		return $this->lastname;
	}

	public function setLastname(string $lastname): PollParticipantsEntity
	{
		$this->lastname = $lastname;

		return $this;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): PollParticipantsEntity
	{
		$this->user = $user;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id'        => $this->id,
			'email'     => $this->email,
			'firstname' => $this->firstname,
			'lastname'  => $this->lastname,
			'user'      => $this->user?->id,
		];
	}


}