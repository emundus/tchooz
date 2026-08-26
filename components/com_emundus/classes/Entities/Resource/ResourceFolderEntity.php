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

#[Table(name: '#__emundus_resource_folders')]
class ResourceFolderEntity implements \JsonSerializable
{
	private int $id;

	#[Column(length: 255)]
	private string $name;

	#[Column(type: Types::INTEGER)]
	private ?int $parentId;

	#[Column(type: Types::INTEGER)]
	private int $createdBy;

	#[Column(type: Types::DATETIME_MUTABLE)]
	private \DateTimeImmutable $createdAt;

	public function __construct(
		int $id = 0,
		string $name = '',
		?int $parentId = null,
		int $createdBy = 0,
		\DateTimeImmutable $createdAt = new \DateTimeImmutable()
	) {
		$this->id        = $id;
		$this->name      = $name;
		$this->parentId  = $parentId;
		$this->createdBy = $createdBy;
		$this->createdAt = $createdAt;
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

	public function getParentId(): ?int
	{
		return $this->parentId;
	}

	public function setParentId(?int $parentId): void
	{
		$this->parentId = $parentId;
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public function setCreatedBy(int $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getCreatedAt(): \DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTimeImmutable $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	public function jsonSerialize(): array
	{
		return [
			'id'        => $this->id,
			'name'      => $this->name,
			'parentId'  => $this->parentId,
			'createdBy' => $this->createdBy,
			'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
		];
	}
}
