<?php

namespace Tchooz\Entities\ApplicationFile;

class ApplicationFileAccessEntity
{
	public const TOKEN_LENGTH = 24;
	public function __construct(
		private int $id,
		private int $applicationId,
		private string $token,
		private \DateTimeImmutable $expirationDate
	)
	{}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getApplicationId(): int
	{
		return $this->applicationId;
	}

	public function setApplicationId(int $applicationId): self
	{
		$this->applicationId = $applicationId;
		return $this;
	}

	public function getToken(): string
	{
		return $this->token;
	}

	public function setToken(string $token): self
	{
		$this->token = $token;
		return $this;
	}

	public function getExpirationDate(): \DateTimeImmutable
	{
		return $this->expirationDate;
	}

	public function setExpirationDate(\DateTimeImmutable $expirationDate): self
	{
		$this->expirationDate = $expirationDate;
		return $this;
	}
}