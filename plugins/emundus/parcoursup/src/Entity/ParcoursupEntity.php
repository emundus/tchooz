<?php
/**
 * @package     Joomla\Plugin\Emundus\Parcoursup\Entity
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Parcoursup\Entity;

use Joomla\CMS\User\User;

class ParcoursupEntity
{
	private User|null $user;

	private int $campaignId;

	private string $parcoursupId;

	private string $fnum;

	private array $applicationFile;

	public function __construct(int $campaignId, int $parcoursupId, ?User $user = null, array $applicationFile = [], string $fnum = '')
	{
		$this->campaignId      = $campaignId;
		$this->parcoursupId    = $parcoursupId;
		$this->user            = $user;
		$this->applicationFile = $applicationFile;
		$this->fnum            = $fnum;
	}

	public function setCampaignId(int $campaignId): void
	{
		$this->campaignId = $campaignId;
	}

	public function getCampaignId(): int
	{
		return $this->campaignId;
	}

	public function setParcoursupId(string $parcoursupId): void
	{
		$this->parcoursupId = $parcoursupId;
	}

	public function getParcoursupId(): string
	{
		return $this->parcoursupId;
	}

	public function setUser(User $user): void
	{
		$this->user = $user;
	}

	public function setUserId(int $userId): void
	{
		$this->user->id = $userId;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setApplicationFile(array $applicationFile): void
	{
		$this->applicationFile = $applicationFile;
	}

	public function getApplicationFile(): array
	{
		return $this->applicationFile;
	}

	public function setFnum(string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function getApplicationFileKey(string $key): mixed
	{
		return $this->applicationFile[$key] ?? null;
	}

	public function addData(string $key, mixed $value, ?bool $initArray = false): void
	{
		if ($initArray && empty($this->applicationFile[$key]))
		{
			$this->applicationFile[$key] = [];
		}

		if (is_array($this->applicationFile[$key]))
		{
			$this->applicationFile[$key][] = $value;

			return;
		}

		$this->applicationFile[$key] = $value;
	}

	public function addSeparator(string $key, string $separator): void
	{
		$this->applicationFile[$key] = implode($separator, $this->applicationFile[$key]);
	}
}
