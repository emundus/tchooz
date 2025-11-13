<?php

namespace Tchooz\Entities\Payment;
use Joomla\CMS\Log\Log;

class CurrencyEntity
{
	public int $id = 0;
	public string $name;
	public string $symbol;
	public string $iso3;
	private ?string $iso4217;
	public int $published;

	public function __construct(
		int $id,
		string $name,
		string $symbol,
		string $iso3,
		int $published = 1,
		?string $iso4217 = null,
	)
	{
		$this->id = $id;
		$this->name = $name;
		$this->symbol = $symbol;
		$this->iso3 = $iso3;
		$this->iso4217 = $iso4217;
		$this->published = $published;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getIso3(): string
	{
		return $this->iso3;
	}

	public function getIso4217(): ?string
	{
		return $this->iso4217;
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	public function getSymbol(): string
	{
		return $this->symbol;
	}

	public function serialize()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'symbol' => $this->symbol,
			'iso3' => $this->iso3,
			'iso4217' => $this->iso4217,
			'published' => $this->published
		];
	}
}