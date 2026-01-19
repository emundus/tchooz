<?php

namespace Tchooz\Entities\Upload;

use DateTimeImmutable;
use Joomla\CMS\Uri\Uri;
use Tchooz\Enums\Upload\UploadValidationStatusEnum;

class UploadEntity
{
	private int $id;

	private DateTimeImmutable $timedate;

	private int $userId;

	private string $fnum;

	private ?int $campaignId = null;

	private int $attachmentId;

	private string $filename;

	private ?string $description;

	private UploadValidationStatusEnum $validationStatus = UploadValidationStatusEnum::TO_BE_VALIDATED;


	private ?string $localFilename;

	private ?int $size;

	private bool $isSigned = false;

	private ?string $thumbnail = null;

	private bool $canBeDeleted = true;

	private bool $canBeViewed = true;

	private ?DateTimeImmutable $modified = null;

	private ?int $modifiedBy = null;

	public function __construct(
		int $id,
		int $userId,
		string $fnum,
		int $attachmentId,
		string $filename,
		?string $description,
		?string $localFilename,
		?int $campaignId = null,
		?int $size = null,
		UploadValidationStatusEnum $validationStatus = UploadValidationStatusEnum::TO_BE_VALIDATED,
		bool $isSigned = false,
		?string $thumbnail = null,
		bool $canBeDeleted = true,
		bool $canBeViewed = true
	)
	{
		$this->id = $id;
		$this->userId = $userId;
		$this->fnum = $fnum;
		$this->attachmentId = $attachmentId;
		$this->filename = $filename;
		$this->description = $description;
		$this->localFilename = $localFilename;
		$this->campaignId = $campaignId;
		$this->size = $size;
		$this->validationStatus = $validationStatus;
		$this->isSigned = $isSigned;
		$this->thumbnail = $thumbnail;
		$this->canBeDeleted = $canBeDeleted;
		$this->canBeViewed = $canBeViewed;
	}

	// Getters and setters...
	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getTimedate(): DateTimeImmutable
	{
		return $this->timedate;
	}

	public function setTimedate(DateTimeImmutable $timedate): void
	{
		$this->timedate = $timedate;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function setUserId(int $userId): void
	{
		$this->userId = $userId;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function setFnum(string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function getCampaignId(): ?int
	{
		return $this->campaignId;
	}

	public function setCampaignId(?int $campaignId): void
	{
		$this->campaignId = $campaignId;
	}

	public function getAttachmentId(): int
	{
		return $this->attachmentId;
	}

	public function setAttachmentId(int $attachmentId): void
	{
		$this->attachmentId = $attachmentId;
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function setFilename(string $filename): void
	{
		$this->filename = $filename;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): void
	{
		$this->description = $description;
	}

	public function getValidationStatus(): UploadValidationStatusEnum
	{
		return $this->validationStatus;
	}

	public function setValidationStatus(UploadValidationStatusEnum $validationStatus): void
	{
		$this->validationStatus = $validationStatus;
	}

	public function getLocalFilename(): ?string
	{
		return $this->localFilename;
	}

	public function setLocalFilename(?string $localFilename): void
	{
		$this->localFilename = $localFilename;
	}

	public function getSize(): ?int
	{
		return $this->size;
	}

	public function setSize(int $size): void
	{
		$this->size = $size;
	}

	public function isSigned(): bool
	{
		return $this->isSigned;
	}

	public function setIsSigned(bool $isSigned): void
	{
		$this->isSigned = $isSigned;
	}

	public function getThumbnail(): ?string
	{
		return $this->thumbnail;
	}

	public function setThumbnail(?string $thumbnail): void
	{
		$this->thumbnail = $thumbnail;
	}

	public function canBeDeleted(): bool
	{
		return $this->canBeDeleted;
	}

	public function setCanBeDeleted(bool $canBeDeleted): void
	{
		$this->canBeDeleted = $canBeDeleted;
	}

	public function canBeViewed(): bool
	{
		return $this->canBeViewed;
	}

	public function setCanBeViewed(bool $canBeViewed): void
	{
		$this->canBeViewed = $canBeViewed;
	}

	public function getModified(): ?DateTimeImmutable
	{
		return $this->modified;
	}

	public function setModified(?DateTimeImmutable $modified): void
	{
		$this->modified = $modified;
	}

	public function getModifiedBy(): ?int
	{
		return $this->modifiedBy;
	}

	public function setModifiedBy(?int $modifiedBy): void
	{
		$this->modifiedBy = $modifiedBy;
	}

	public function getFileInternalPath(): string
	{
		return JPATH_SITE. '/images/emundus/files/' . $this->getUserId() . '/' . $this->filename;
	}

	public function getExtension(): string
	{
		return pathinfo($this->getFileInternalPath(), PATHINFO_EXTENSION);
	}

	public function getContent(): string
	{
		$content = '';

		if (file_exists($this->getFileInternalPath())) {
			$content = file_get_contents($this->getFileInternalPath());
		}

		return $content;
	}

	public function serialize(): array
	{
		if (!class_exists('EmundusHelperDate'))
		{
			require_once (JPATH_ROOT . '/components/com_emundus/helpers/date.php');
		}

		// never expose content or local path
		return [
			'id' => $this->id,
			'timedate' => \EmundusHelperDate::displayDate($this->timedate->format('Y-m-d H:i:s'), 'DATE_FORMAT_LC2', 0),
			'userId' => $this->userId,
			'fnum' => $this->fnum,
			'campaignId' => $this->campaignId,
			'attachmentId' => $this->attachmentId,
			'filename' => $this->filename,
			'description' => $this->description,
			'validationStatus' => $this->validationStatus->value,
			'localFilename' => $this->localFilename,
			'size' => $this->size,
			'isSigned' => $this->isSigned,
			'thumbnail' => $this->thumbnail,
			'canBeDeleted' => $this->canBeDeleted,
			'canBeViewed' => $this->canBeViewed,
			'modified' => $this->modified ? \EmundusHelperDate::displayDate($this->modified->format('Y-m-d H:i:s'), 'DATE_FORMAT_LC2', 0) : '',
			'modifiedBy' => $this->modifiedBy,
		];
	}
}