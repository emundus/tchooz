<?php
/**
 * @package     Tchooz\Entities
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities;

class Country
{
	private int $id;

	private string $label;

	private string $iso2;

	private string $iso3;

	private int $country_nb;

	private ?string $continent;

	private bool $member;

	private ?string $flag;

	private ?string $flag_img;

	public function __construct(int $id, string $label, string $iso2, string $iso3, int $country_nb, ?string $continent = null, bool $member = false, ?string $flag = null, ?string $flag_img = null)
	{
		$this->id        = $id;
		$this->label     = $label;
		$this->iso2      = $iso2;
		$this->iso3      = $iso3;
		$this->country_nb= $country_nb;
		$this->continent = $continent;
		$this->member    = $member;
		$this->flag      = $flag;
		$this->flag_img  = $flag_img;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getIso2(): string
	{
		return $this->iso2;
	}

	public function setIso2(string $iso2): void
	{
		$this->iso2 = $iso2;
	}

	public function getIso3(): string
	{
		return $this->iso3;
	}

	public function setIso3(string $iso3): void
	{
		$this->iso3 = $iso3;
	}

	public function getCountryNb(): int
	{
		return $this->country_nb;
	}

	public function setCountryNb(int $country_nb): void
	{
		$this->country_nb = $country_nb;
	}

	public function getContinent(): ?string
	{
		return $this->continent;
	}

	public function setContinent(?string $continent): void
	{
		$this->continent = $continent;
	}

	public function isMember(): bool
	{
		return $this->member;
	}

	public function setMember(bool $member): void
	{
		$this->member = $member;
	}

	public function getFlag(): ?string
	{
		return $this->flag;
	}

	public function setFlag(?string $flag): void
	{
		$this->flag = $flag;
	}

	public function getFlagImg(): ?string
	{
		return $this->flag_img;
	}

	public function setFlagImg(?string $flag_img): void
	{
		$this->flag_img = $flag_img;
	}

	public function __serialize(): array
	{
		return get_object_vars($this);
	}
}