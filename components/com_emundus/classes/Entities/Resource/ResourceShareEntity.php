<?php
/**
 * @package     Tchooz\Entities\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Resource;

use Tchooz\Attributes\ORM\Column;
use Tchooz\Attributes\ORM\Table;
use Tchooz\Attributes\ORM\Types;

#[Table(name: '#__emundus_resource_shares')]
class ResourceShareEntity implements \JsonSerializable
{
	private int $id;

	#[Column(type: Types::INTEGER)]
	private int $resourceId;

	#[Column(length: 64)]
	private string $code;

	#[Column(length: 255)]
	private ?string $passwordHash;

	#[Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeImmutable $expirationDate;

	#[Column(type: Types::DATETIME_MUTABLE)]
	private \DateTimeImmutable $createdAt;

	public function __construct(
		int $id = 0,
		int $resourceId = 0,
		string $code = '',
		?string $passwordHash = null,
		?\DateTimeImmutable $expirationDate = null,
		\DateTimeImmutable $createdAt = new \DateTimeImmutable()
	) {
		$this->id             = $id;
		$this->resourceId     = $resourceId;
		$this->code           = $code;
		$this->passwordHash   = $passwordHash;
		$this->expirationDate = $expirationDate;
		$this->createdAt      = $createdAt;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getResourceId(): int
	{
		return $this->resourceId;
	}

	public function setResourceId(int $resourceId): void
	{
		$this->resourceId = $resourceId;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function setCode(string $code): void
	{
		$this->code = $code;
	}

	public function getPasswordHash(): ?string
	{
		return $this->passwordHash;
	}

	public function setPasswordHash(?string $passwordHash): void
	{
		$this->passwordHash = $passwordHash;
	}

	public function hasPassword(): bool
	{
		return !empty($this->passwordHash);
	}

	public function getExpirationDate(): ?\DateTimeImmutable
	{
		return $this->expirationDate;
	}

	public function setExpirationDate(?\DateTimeImmutable $expirationDate): void
	{
		$this->expirationDate = $expirationDate;
	}

	public function getCreatedAt(): \DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTimeImmutable $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	public function isExpired(): bool
	{
		if ($this->expirationDate === null)
		{
			return false;
		}

		return $this->expirationDate < new \DateTimeImmutable();
	}

	/**
	 * The password hash is intentionally never exposed; only whether a password is set.
	 */
	public function jsonSerialize(): array
	{
		return [
			'id'             => $this->id,
			'resourceId'     => $this->resourceId,
			'code'           => $this->code,
			'hasPassword'    => $this->hasPassword(),
			'expirationDate' => $this->expirationDate?->format('Y-m-d H:i:s'),
			'isExpired'      => $this->isExpired(),
			'createdAt'      => $this->createdAt->format('Y-m-d H:i:s'),
		];
	}
}
