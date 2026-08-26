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

#[Table(name: '#__emundus_resources')]
class ResourceEntity implements \JsonSerializable
{
	private int $id;

	#[Column(length: 255)]
	private string $name;

	#[Column(length: 20)]
	private string $format;

	#[Column(length: 255)]
	private string $filename;

	#[Column(type: Types::INTEGER)]
	private int $size;

	#[Column(type: Types::INTEGER, options: ['default' => 0])]
	private int $downloadCount;

	#[Column(type: Types::INTEGER)]
	private ?int $folderId;

	#[Column(type: Types::INTEGER)]
	private int $createdBy;

	#[Column(type: Types::DATETIME_MUTABLE)]
	private \DateTimeImmutable $createdAt;

	/**
	 * Relations hydrated by the factory — not persisted columns.
	 *
	 * @var array<ResourceAccessEntity>
	 */
	private array $access = [];

	/**
	 * @var array<ResourceDisplaySpaceEntity>
	 */
	private array $displaySpaces = [];

	private ?ResourceShareEntity $share = null;

	public function __construct(
		int $id = 0,
		string $name = '',
		string $format = '',
		string $filename = '',
		int $size = 0,
		int $downloadCount = 0,
		?int $folderId = null,
		int $createdBy = 0,
		\DateTimeImmutable $createdAt = new \DateTimeImmutable()
	) {
		$this->id            = $id;
		$this->name          = $name;
		$this->format        = $format;
		$this->filename      = $filename;
		$this->size          = $size;
		$this->downloadCount = $downloadCount;
		$this->folderId      = $folderId;
		$this->createdBy     = $createdBy;
		$this->createdAt     = $createdAt;
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

	public function getFormat(): string
	{
		return $this->format;
	}

	public function setFormat(string $format): void
	{
		$this->format = $format;
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function setFilename(string $filename): void
	{
		$this->filename = $filename;
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function setSize(int $size): void
	{
		$this->size = $size;
	}

	public function getDownloadCount(): int
	{
		return $this->downloadCount;
	}

	public function setDownloadCount(int $downloadCount): void
	{
		$this->downloadCount = $downloadCount;
	}

	public function getFolderId(): ?int
	{
		return $this->folderId;
	}

	public function setFolderId(?int $folderId): void
	{
		$this->folderId = $folderId;
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

	/**
	 * @return array<ResourceAccessEntity>
	 */
	public function getAccess(): array
	{
		return $this->access;
	}

	/**
	 * @param array<ResourceAccessEntity> $access
	 */
	public function setAccess(array $access): void
	{
		$this->access = $access;
	}

	/**
	 * @return array<ResourceDisplaySpaceEntity>
	 */
	public function getDisplaySpaces(): array
	{
		return $this->displaySpaces;
	}

	/**
	 * @param array<ResourceDisplaySpaceEntity> $displaySpaces
	 */
	public function setDisplaySpaces(array $displaySpaces): void
	{
		$this->displaySpaces = $displaySpaces;
	}

	public function getShare(): ?ResourceShareEntity
	{
		return $this->share;
	}

	public function setShare(?ResourceShareEntity $share): void
	{
		$this->share = $share;
	}

	public function jsonSerialize(): array
	{
		return [
			'id'            => $this->id,
			'name'          => $this->name,
			'format'        => $this->format,
			'size'          => $this->size,
			'downloadCount' => $this->downloadCount,
			'folderId'      => $this->folderId,
			'createdBy'     => $this->createdBy,
			'createdAt'     => $this->createdAt->format('Y-m-d H:i:s'),
			'access'        => $this->access,
			'displaySpaces' => $this->displaySpaces,
			'share'         => $this->share,
		];
	}
}
