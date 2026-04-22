<?php
/**
 * @package     Tchooz\Entities\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Addons;

use Tchooz\Attributes\ORM\Column;
use Tchooz\Attributes\ORM\Table;
use Tchooz\Attributes\ORM\Types;
use Tchooz\Enums\Addons\AddonEnum;

#[Table(name: '#__emundus_setup_config')]
class AddonEntity
{
	#[Column(length: 50)]
	private string $namekey;

	private ?AddonEnum $addon;

	#[Column(type: Types::BOOLEAN, options:['default' => 0])]
	private bool $activated;

	#[Column(type: Types::BOOLEAN, options:['default' => 0])]
	private bool $displayed;

	#[Column(type: Types::BOOLEAN, options:['default' => 0])]
	private bool $suggested;

	#[Column(type: Types::TEXT)]
	private array $params;

	#[Column(type: Types::TEXT)]
	private array $default;

	#[Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeImmutable $activatedAt;

	public function __construct(
		string              $namekey,
		bool                $activated = false,
		bool                $displayed = false,
		bool                $suggested = false,
		array               $params = [],
		array               $default = [],
		?\DateTimeImmutable $activatedAt = null,
	)
	{
		$this->namekey     = $namekey;
		$this->addon       = AddonEnum::tryFrom($namekey);
		$this->activated   = $activated;
		$this->displayed   = $displayed;
		$this->suggested   = $suggested;
		$this->params      = $params;
		$this->default     = $default;
		$this->activatedAt = $activatedAt;
	}

	public function getNamekey(): string
	{
		return $this->namekey;
	}

	public function setNamekey(string $namekey): void
	{
		$this->namekey = $namekey;
	}

	public function getAddon(): ?AddonEnum
	{
		return $this->addon;
	}

	public function setAddon(?AddonEnum $addon): AddonEntity
	{
		$this->addon = $addon;

		return $this;
	}

	public function isActivated(): bool
	{
		return $this->activated;
	}

	public function setActivated(bool $activated): AddonEntity
	{
		$this->activated = $activated;

		return $this;
	}

	public function isDisplayed(): bool
	{
		return $this->displayed;
	}

	public function setDisplayed(bool $displayed): AddonEntity
	{
		$this->displayed = $displayed;

		return $this;
	}

	public function isSuggested(): bool
	{
		return $this->suggested;
	}

	public function setSuggested(bool $suggested): AddonEntity
	{
		$this->suggested = $suggested;

		return $this;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	public function getDefault(): array
	{
		return $this->default;
	}

	public function setDefault(array $default): AddonEntity
	{
		$this->default = $default;

		return $this;
	}

	public function getActivatedAt(): ?\DateTimeImmutable
	{
		return $this->activatedAt;
	}

	public function setActivatedAt(?\DateTimeImmutable $activatedAt): void
	{
		$this->activatedAt = $activatedAt;
	}

	public function __serialize(): array
	{
		return [
			'namekey'     => $this->namekey,
			'label'       => $this->addon?->getLabel(),
			'icon'        => $this->addon?->getIcon(),
			'description' => $this->addon?->getDescription(),
			'activated'   => $this->activated,
			'displayed'   => $this->displayed,
			'suggested'   => $this->suggested,
			'params'      => $this->params,
			'default'     => $this->default,
			'activatedAt' => $this->activatedAt?->format('Y-m-d H:i:s'),
		];
	}


}

