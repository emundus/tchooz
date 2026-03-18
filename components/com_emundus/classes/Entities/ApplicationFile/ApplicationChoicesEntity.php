<?php
/**
 * @package     Tchooz\Entities\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\ApplicationFile;

use Joomla\CMS\User\User;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;

class ApplicationChoicesEntity
{
	private int $id;

	private ?CampaignEntity $campaign;

	private string $fnum;

	private ?ApplicationFileEntity $application_file = null;

	private User $user;

	private ChoicesStateEnum $state;

	private int $order;

	// TODO: Refactor when Fabrik was moved into Entities
	private ?array $moreProperties;

	public function __construct(string $fnum, User $user, ?CampaignEntity $campaign, int $order = 0, ChoicesStateEnum $state = ChoicesStateEnum::DRAFT, int $id = 0, $moreProperties = [], ?ApplicationFileEntity $application_file = null)
	{
		$this->fnum             = $fnum;
		$this->user             = $user;
		$this->campaign         = $campaign;
		$this->order            = $order;
		$this->id               = $id;
		$this->moreProperties   = $moreProperties;
		$this->state            = $state;
		$this->application_file = $application_file;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getCampaign(): ?CampaignEntity
	{
		return $this->campaign;
	}

	public function setCampaign(?CampaignEntity $campaign): void
	{
		$this->campaign = $campaign;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function setFnum(string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function getApplicationFile(): ?ApplicationFileEntity
	{
		return $this->application_file;
	}

	public function setApplicationFile(?ApplicationFileEntity $application_file): void
	{
		$this->application_file = $application_file;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function setUser(User $user): void
	{
		$this->user = $user;
	}

	public function getState(): ChoicesStateEnum
	{
		return $this->state;
	}

	public function setState(ChoicesStateEnum $state): void
	{
		$this->state = $state;
	}

	public function getOrder(): int
	{
		return $this->order;
	}

	public function setOrder(int $order): void
	{
		$this->order = $order;
	}

	public function getMoreProperties(): ?array
	{
		return $this->moreProperties;
	}

	public function setMoreProperties(?array $moreProperties): void
	{
		$this->moreProperties = $moreProperties;
	}

	public function __serialize(): array
	{
		$serialize             = get_object_vars($this);
		$serialize['campaign'] = $this->campaign->__serialize();
		$serialize['state']    = ['name' => $this->state->name, 'value' => $this->state->value];
		$serialize['user']     = [
			'id'    => $this->user->id,
			'name'  => $this->user->name,
			'email' => $this->user->email,
		];

		return $serialize;
	}
}