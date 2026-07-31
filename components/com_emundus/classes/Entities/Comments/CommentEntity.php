<?php
/**
 * @package     Tchooz\Entities\Comments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Comments;

use DateTime;
use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;

class CommentEntity
{
	private int $id;
	private CommentTargetTypeEnum $targetType;
	private int $targetId;
	private string $content;
	private int $createdBy;
	private ?int $updatedBy;
	private DateTime $createdAt;
	private ?DateTime $updatedAt;
	private ?int $pinned;
	private ?int $opened;
	private ?string $fnum;
	private ?int $ccid;
	private ?int $parentId;
	private bool $isPublic;

	/**
	 * Display-only author name, resolved via JOIN on read. Not a persisted column.
	 */
	private ?string $authorName = null;

	public function __construct(int $id, CommentTargetTypeEnum|string $targetType, int $targetId, string $content, int $createdBy, DateTime $createdAt, bool $isPublic = true, ?int $pinned = 0, ?int $updatedBy = 0, DateTime $updatedAt = null, ?string $fnum = '', ?int $parentId = 0, ?int $ccid = 0, ?int $opened = 0)
	{
		if ($targetType instanceof CommentTargetTypeEnum)
		{
			$this->targetType = $targetType;
		}
		else
		{
			$resolvedTargetType = CommentTargetTypeEnum::tryFrom($targetType);
			if ($resolvedTargetType === null)
			{
				throw new InvalidArgumentException('Invalid comment target type: ' . $targetType);
			}
			$this->targetType = $resolvedTargetType;
		}
		$this->targetId  = $targetId;
		$this->content   = $content;
		$this->createdBy = $createdBy;
		$this->createdAt = $createdAt;
		$this->id        = $id;
		$this->pinned    = $pinned;
		$this->isPublic  = $isPublic;
		$this->updatedBy = $updatedBy;
		$this->updatedAt = $updatedAt;
		$this->fnum      = $fnum;
		$this->parentId  = $parentId;
		$this->ccid      = $ccid;
		$this->opened    = $opened;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function isPublic(): bool
	{
		return $this->isPublic;
	}

	public function setIsPublic(bool $isPublic): void
	{
		$this->isPublic = $isPublic;
	}

	public function getTargetType(): CommentTargetTypeEnum
	{
		return $this->targetType;
	}

	public function setTargetType(CommentTargetTypeEnum $targetType): void
	{
		$this->targetType = $targetType;
	}

	public function getTargetId(): int
	{
		return $this->targetId;
	}

	public function setTargetId(int $targetId): void
	{
		$this->targetId = $targetId;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function setContent(string $content): void
	{
		$this->content = $content;
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public function setCreatedBy(int $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getUpdatedBy(): ?int
	{
		return $this->updatedBy;
	}

	public function setUpdatedBy(?int $updatedBy): void
	{
		$this->updatedBy = $updatedBy;
	}

	public function getCreatedAt(): DateTime
	{
		return $this->createdAt;
	}

	public function setCreatedAt(DateTime $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	public function getUpdatedAt(): ?DateTime
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(?DateTime $updatedAt): void
	{
		$this->updatedAt = $updatedAt;
	}

	public function getPinned(): ?int
	{
		return $this->pinned;
	}

	public function setPinned(?int $pinned): void
	{
		$this->pinned = $pinned;
	}

	public function getOpened(): ?int
	{
		return $this->opened;
	}

	public function setOpened(?int $opened): void
	{
		$this->opened = $opened;
	}

	public function getFnum(): ?string
	{
		return $this->fnum;
	}

	public function setFnum(?string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function getCcid(): ?int
	{
		return $this->ccid;
	}

	public function setCcid(?int $ccid): void
	{
		$this->ccid = $ccid;
	}

	public function getParentId(): ?int
	{
		return $this->parentId;
	}

	public function setParentId(?int $parentId): void
	{
		$this->parentId = $parentId;
	}

	public function getAuthorName(): ?string
	{
		return $this->authorName;
	}

	public function setAuthorName(?string $authorName): void
	{
		$this->authorName = $authorName;
	}

	public function __serialize(): array
	{
		return [
			'id'           => $this->getId(),
			'user_id'      => $this->getCreatedBy(),
			'date'         => $this->getCreatedAt()->format('Y-m-d H:i:s'),
			'comment_body' => $this->getContent(),
			'ccid'         => $this->getCcid(),
			'parent_id'    => $this->getParentId(),
			'opened'       => $this->getOpened(),
			'updated'      => $this->getUpdatedAt()?->format('Y-m-d H:i:s'),
			'updated_by'   => $this->getUpdatedBy(),
			'target_type'  => $this->getTargetType()->value,
			'target_id'    => $this->getTargetId(),
			'pinned'       => $this->getPinned() ?? 0,
			'is_public'    => $this->isPublic(),
			'name'         => $this->getAuthorName() ?? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->getCreatedBy())->name
		];
	}
}
