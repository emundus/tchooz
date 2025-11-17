<?php
/**
 * @package     Tchooz\Entities\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\NumericSign;

use Tchooz\Entities\Attachments\AttachmentType;
use Tchooz\Enums\NumericSign\SignConnectorsEnum;
use Tchooz\Enums\NumericSign\SignStatusEnum;

class Request
{
	private int $id = 0;

	private AttachmentType $attachment;

	private int $upload_id = 0;

	private int $signed_upload_id = 0;

	private SignStatusEnum $status = SignStatusEnum::TO_SIGN;

	private int $stepsCount = 0;

	private int $user_id = 0;

	private int $ccid = 0;

	private string $fnum = '';

	private SignConnectorsEnum $connector = SignConnectorsEnum::YOUSIGN;

	private string $createdAt;

	private int $createdBy;

	private string $cancelReason = '';

	private string $cancelAt = '';

	private int $sendReminder = 0;

	private string $lastReminderAt = '';

	private array $signers = [];

	private bool $ordered = false;

	public function __construct($createdBy)
	{
		$this->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
		$this->createdBy = $createdBy;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getAttachment(): AttachmentType
	{
		return $this->attachment;
	}

	public function setAttachment(AttachmentType $attachment): void
	{
		$this->attachment = $attachment;
	}

	public function getUploadId(): int
	{
		return $this->upload_id;
	}

	public function setUploadId(int $upload_id): void
	{
		$this->upload_id = $upload_id;
	}

	public function getSignedUploadId(): int
	{
		return $this->signed_upload_id;
	}

	public function setSignedUploadId(int $signed_upload_id): void
	{
		$this->signed_upload_id = $signed_upload_id;
	}

	public function getStatus(): SignStatusEnum
	{
		return $this->status;
	}

	public function setStatus(SignStatusEnum|string $status): self
	{
		try
		{
			if (is_string($status))
			{
				$status = SignStatusEnum::from($status);
			}
			elseif (!($status instanceof SignStatusEnum))
			{
				throw new \InvalidArgumentException('Invalid status type');
			}

			$this->status = $status;

			return $this;
		}
		catch (\ValueError)
		{
			throw new \InvalidArgumentException('Invalid status type');
		}
	}

	public function getStepsCount(): int
	{
		return $this->stepsCount;
	}

	public function setStepsCount(int $stepsCount): void
	{
		$this->stepsCount = $stepsCount;
	}

	public function getUserId(): int
	{
		return $this->user_id;
	}

	public function setUserId(int $user_id): void
	{
		$this->user_id = $user_id;
	}

	public function getCcid(): int
	{
		return $this->ccid;
	}

	public function setCcid(int $ccid): void
	{
		$this->ccid = $ccid;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function setFnum(string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function getCreatedAt(): string
	{
		return $this->createdAt;
	}

	public function setCreatedAt(string $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public function setCreatedBy(int $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getConnector(): SignConnectorsEnum
	{
		return $this->connector;
	}

	public function setConnector(SignConnectorsEnum|string $connector): self
	{
		try
		{
			if (is_string($connector))
			{
				$connector = SignConnectorsEnum::from($connector);
			}
			elseif (!($connector instanceof SignConnectorsEnum))
			{
				throw new \InvalidArgumentException('Invalid connector type');
			}

			$this->connector = $connector;

			return $this;
		}
		catch (\ValueError $e)
		{
			throw new \InvalidArgumentException('Invalid connector type', $e->getCode(), $e);
		}
	}

	public function getCancelReason(): string
	{
		return $this->cancelReason;
	}

	public function setCancelReason(string $cancelReason): void
	{
		$this->cancelReason = $cancelReason;
	}

	public function getCancelAt(): string
	{
		return $this->cancelAt;
	}

	public function setCancelAt(string $cancelAt): void
	{
		$this->cancelAt = $cancelAt;
	}

	public function getSendReminder(): int
	{
		return $this->sendReminder;
	}

	public function setSendReminder(int $sendReminder): void
	{
		$this->sendReminder = $sendReminder;
	}

	public function getLastReminderAt(): string
	{
		return $this->lastReminderAt;
	}

	public function setLastReminderAt(string $lastReminderAt): void
	{
		$this->lastReminderAt = $lastReminderAt;
	}

	public function getSigners(): array
	{
		return $this->signers;
	}

	public function setSigners(?array $signers): void
	{
		$this->signers = $signers;
	}

	public function addSigner(RequestSigners $signer): void
	{
		$this->signers[] = $signer;
	}

	public function isOrdered(): bool
	{
		return $this->ordered;
	}

	public function setOrdered(bool $ordered): void
	{
		$this->ordered = $ordered;
	}

	public function __serialize(): array
	{
		return [
			'id'               => $this->id,
			'attachment_id'    => $this->getAttachment()->getId(),
			'upload_id'        => $this->upload_id,
			'signed_upload_id' => $this->signed_upload_id,
			'status'           => $this->status->value,
			'steps_count'      => $this->stepsCount,
			'user_id'          => $this->user_id,
			'ccid'             => $this->ccid,
			'fnum'             => $this->fnum,
			'connector'        => $this->connector->value,
			'cancel_reason'    => $this->cancelReason,
			'cancel_at'        => $this->cancelAt,
			'send_reminder'    => $this->sendReminder,
			'last_reminder_at' => $this->lastReminderAt,
			'created_at'       => $this->createdAt,
			'created_by'       => $this->createdBy,
			'ordered'          => $this->ordered ? 1 : 0,
		];
	}
}