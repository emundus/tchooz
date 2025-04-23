<?php
namespace classes\Entities\ApplicationFile;

use Joomla\CMS\User\User;

class ApplicationFileEntity
{
	private User $user;

	private string $fnum;

	private int $status = 0;

	private int $campaign_id;

	private int $published = 1;

	private array $data = [];

	public function __construct(User $user)
	{
		$this->user = $user;
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

	public function generateFnum(int $campaign_id): string
	{
		$this->fnum = date('YmdHis') . str_pad($campaign_id, 7, '0', STR_PAD_LEFT) . str_pad($this->user->id, 7, '0', STR_PAD_LEFT);
		return $this->fnum;
	}

	public function getStatus(): int
	{
		return $this->status;
	}

	public function setStatus(int $status): void
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
}