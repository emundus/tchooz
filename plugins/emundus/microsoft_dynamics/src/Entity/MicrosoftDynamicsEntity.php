<?php
/**
 * @package     Joomla\Plugin\Emundus\MicrosoftDynamics\Entity
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\MicrosoftDynamics\Entity;

use Joomla\CMS\User\User;

class MicrosoftDynamicsEntity
{
	private User|null $user;

	private int $campaignId;

	private string $crmId;

	private string $fnum;

	private array $applicationFile;

	private array $lookupKeys;

	private array $config;

	public function __construct(int $campaignId, int $crmId, ?User $user = null, array $applicationFile = [], array $lookupKeys = [], array $config, string $fnum = '')
	{
		$this->campaignId      = $campaignId;
		$this->crmId           = $crmId;
		$this->user            = $user;
		$this->applicationFile = $applicationFile;
		$this->lookupKeys      = $lookupKeys;
		$this->config          = $config;
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

	public function setCrmId(string $crmId): void
	{
		$this->crmId = $crmId;
	}

	public function getCrmId(): string
	{
		return $this->crmId;
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

	public function setLookupKeys(array $lookupKeys): void
	{
		$this->lookupKeys = $lookupKeys;
	}

	public function getLookupKeys(): array
	{
		return $this->lookupKeys;
	}

	public function setConfig(array $config): void
	{
		$this->config = $config;
	}

	public function getConfig(): array
	{
		return $this->config;
	}

	public function getApplicationFileKey(string $key): mixed
	{
		return $this->applicationFile[$key] ?? null;
	}
}