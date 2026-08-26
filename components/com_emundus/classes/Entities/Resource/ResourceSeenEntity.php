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

/**
 * Marks that a user has acknowledged (seen) a resource shared with them. The absence of a row
 * for a (user, resource) pair is what makes an accessible resource "new" in the user's list.
 */
#[Table(name: '#__emundus_resource_seen')]
class ResourceSeenEntity implements \JsonSerializable
{
	private int $id;

	#[Column(type: Types::INTEGER)]
	private int $userId;

	#[Column(type: Types::INTEGER)]
	private int $resourceId;

	#[Column(type: Types::DATETIME_MUTABLE)]
	private \DateTimeImmutable $seenAt;

	public function __construct(
		int $id = 0,
		int $userId = 0,
		int $resourceId = 0,
		\DateTimeImmutable $seenAt = new \DateTimeImmutable()
	) {
		$this->id         = $id;
		$this->userId     = $userId;
		$this->resourceId = $resourceId;
		$this->seenAt     = $seenAt;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function setUserId(int $userId): void
	{
		$this->userId = $userId;
	}

	public function getResourceId(): int
	{
		return $this->resourceId;
	}

	public function setResourceId(int $resourceId): void
	{
		$this->resourceId = $resourceId;
	}

	public function getSeenAt(): \DateTimeImmutable
	{
		return $this->seenAt;
	}

	public function setSeenAt(\DateTimeImmutable $seenAt): void
	{
		$this->seenAt = $seenAt;
	}

	public function jsonSerialize(): array
	{
		return [
			'id'         => $this->id,
			'userId'     => $this->userId,
			'resourceId' => $this->resourceId,
			'seenAt'     => $this->seenAt->format('Y-m-d H:i:s'),
		];
	}
}
