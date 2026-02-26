<?php

namespace Tchooz\Entities\ApplicationFile;

use Joomla\CMS\User\User;
use Tchooz\Entities\Campaigns\CampaignEntity;

class ApplicationFileEntity
{
	private int $id = 0;

	private User $user;

	private string $fnum;

	private StatusEntity|int|null $status;

	private int $campaign_id;

	private ?CampaignEntity $campaign;

	private ?\DateTime $date_submitted;

	private int $published;

	private int $formProgress;

	private int $attachmentProgress;

	private array $data;

	private ?\DateTime $updated_at;

	private ?User $updated_by;

	/**
	 * @var array<ApplicationChoicesEntity>
	 */
	private ?array $applicationChoices = null;

	public function __construct(
		User                  $user,
		string                $fnum = '',
		StatusEntity|int|null $status = 0,
		int                   $campaign_id = 0,
		int                   $published = 1,
		array                 $data = [],
		int                   $id = 0,
		CampaignEntity        $campaign = null,
		\DateTime             $date_submitted = null,
		int                   $formProgress = 0,
		int                   $attachmentProgress = 0,
		\DateTime             $updated_at = null,
		User                  $updated_by = null,
	)
	{
		$this->user               = $user;
		$this->fnum               = $fnum;
		$this->status             = $status;
		$this->campaign_id        = $campaign_id;
		$this->published          = $published;
		$this->data               = $data;
		$this->id                 = $id;
		$this->campaign           = $campaign;
		$this->date_submitted     = $date_submitted;
		$this->formProgress       = $formProgress;
		$this->attachmentProgress = $attachmentProgress;
		$this->updated_at         = $updated_at;
		$this->updated_by         = $updated_by;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function setUser(User $user): void
	{
		$this->user = $user;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function setFnum(string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function generateFnum(int $campaign_id = 0, int $user_id = 0): string
	{
		if (empty($campaign_id))
		{
			$campaign_id = $this->getCampaignId();
		}
		if (empty($user_id))
		{
			$user_id = $this->user->id;
		}
		$this->fnum = date('YmdHis') . str_pad($campaign_id, 7, '0', STR_PAD_LEFT) . str_pad($user_id, 7, '0', STR_PAD_LEFT);

		return $this->fnum;
	}

	public function getStatus(): StatusEntity|int|null
	{
		return $this->status;
	}

	public function setStatus(StatusEntity|int|null $status): void
	{
		$this->status = $status;
	}

	public function getCampaignId(): int
	{
		return $this->campaign_id;
	}

	public function setCampaignId(int $campaign_id): void
	{
		$this->campaign_id = $campaign_id;
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	public function setPublished(int $published): void
	{
		$this->published = $published;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function setData(array $data): void
	{
		$this->data = $data;
	}

	public function getCampaign(): ?CampaignEntity
	{
		return $this->campaign;
	}

	public function setCampaign(?CampaignEntity $campaign): void
	{
		$this->campaign = $campaign;
	}

	public function getDateSubmitted(): ?\DateTime
	{
		return $this->date_submitted;
	}

	public function setDateSubmitted(?\DateTime $date_submitted): void
	{
		$this->date_submitted = $date_submitted;
	}

	public function getFormProgress(): int
	{
		return $this->formProgress;
	}

	public function setFormProgress(int $formProgress): void
	{
		$this->formProgress = $formProgress;
	}

	public function getAttachmentProgress(): int
	{
		return $this->attachmentProgress;
	}

	public function setAttachmentProgress(int $attachmentProgress): void
	{
		$this->attachmentProgress = $attachmentProgress;
	}

	public function getUpdatedAt(): \DateTime
	{
		return $this->updated_at;
	}

	public function setUpdatedAt(\DateTime $updated_at): void
	{
		$this->updated_at = $updated_at;
	}

	public function getUpdatedBy(): User
	{
		return $this->updated_by;
	}

	public function setUpdatedBy(User $updated_by): void
	{
		$this->updated_by = $updated_by;
	}

	public function getApplicationChoices(): ?array
	{
		return $this->applicationChoices;
	}

	public function setApplicationChoices(array $applicationChoices): void
	{
		$this->applicationChoices = $applicationChoices;
	}

	public function __serialize(): array
	{
		return [
			'id'                 => $this->id,
			'user'               => $this->user->name,
			'fnum'               => $this->fnum,
			'status'             => $this->status,
			'campaign_id'        => $this->campaign_id,
			'campaign'           => $this->campaign,
			'date_submitted'     => $this->date_submitted,
			'published'          => $this->published,
			'data'               => $this->data,
			'formProgress'       => $this->formProgress,
			'attachmentProgress' => $this->attachmentProgress,
			'updated_at'         => $this->updated_at,
			'updated_by'         => $this->updated_by,
		];
	}
}