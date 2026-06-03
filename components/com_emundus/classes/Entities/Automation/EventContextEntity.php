<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\User\User;

class EventContextEntity
{
	private ?User $user;

	private array $files = [];

	private array $users = [];

	private array $parameters = [];

	public function __construct(?User $user, array $files = [], array $users = [], array $parameters = [])
	{
		$this->user = $user;
		$this->files = $files;
		$this->users = $users;
		$this->parameters = $parameters;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function getFiles(): array
	{
		return $this->files;
	}

	public function setFiles(array $files): self
	{
		$this->files = $files;

		return $this;
	}

	public function getUsers(): array
	{
		return $this->users;
	}

	public function setUsers(array $users): self
	{
		$this->users = $users;

		return $this;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function setParameters(array $parameters): self
	{
		$this->parameters = $parameters;

		return $this;
	}

	public function serialize()
	{
		return [
			'user' => $this->user?->id,
			'files' => $this->files,
			'users' => $this->users,
			'parameters' => $this->parameters
		];
	}
}