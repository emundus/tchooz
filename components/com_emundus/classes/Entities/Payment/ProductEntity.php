<?php

namespace Tchooz\Entities\Payment;

use Joomla\CMS\Log\Log;
use Tchooz\Repositories\Payment\ProductCategoryRepository;

class ProductEntity
{
	private int $id;
	public string $label = '';
	public string $description = '';
	public float $price = 0.0;
	public ?CurrencyEntity $currency = null;
	public bool $illimited = true;
	public int $quantity = 0;
	public ?ProductCategoryEntity $category = null;
	public \DateTime|null $available_from = null;
	public \DateTime|null $available_to = null;
	public array $campaigns = [];
	public bool $published = true;
	private ?int $mandatory = null;

	public function __construct(int $id = 0, string $label = '', string $description = '', float $price = 0.0, ?CurrencyEntity $currency = null, bool $illimited = true, int $quantity = 0, ?ProductCategoryEntity $category = null, ?\DateTime $available_from = null, ?\DateTime $available_to = null, array $campaigns = [], bool $published = true, ?int $mandatory = null)
	{
		$this->id = $id;
		$this->label = $label;
		$this->description = $description;
		$this->price = $price;
		$this->currency = $currency;
		$this->illimited = $illimited;
		$this->quantity = $quantity;
		$this->category = $category;
		$this->available_from = $available_from;
		$this->available_to = $available_to;
		$this->campaigns = $campaigns;
		$this->published = $published;
		$this->mandatory = $mandatory;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setPrice(float $price): void
	{
		$this->price = $price;
	}

	public function getPrice(): float
	{
		return $this->price;
	}

	public function getMandatory(): ?int
	{
		return $this->mandatory;
	}

	public function setMandatory(int $mandatory): void
	{
		$this->mandatory = $mandatory;
	}

	public function setCurrency(CurrencyEntity $currency): void
	{
		$this->currency = $currency;
	}

	public function getCurrency(): ?CurrencyEntity
	{
		return $this->currency;
	}

	public function setCategory(ProductCategoryEntity $category): void
	{
		$this->category = $category;
	}

	public function getDisplayedPrice(): string
	{
		return number_format($this->price, 2, '.', ' ') . ' ' . $this->currency->symbol;
	}

	public function setCampaigns(array $campaigns): void
	{
		$this->campaigns = $campaigns;
	}

	public function getCampaigns(): array
	{
		return $this->campaigns;
	}

	public function setIllimited(bool $illimited): void
	{
		$this->illimited = $illimited;
	}

	public function setQuantity(int $quantity): void
	{
		$this->quantity = $quantity;
	}

	public function getQuantity(): int
	{
		return $this->quantity;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'label' => $this->label,
			'description' => $this->description,
			'price' => $this->getPrice(),
			'displayed_price' => $this->getDisplayedPrice(),
			'currency' => $this->currency?->serialize(),
			'illimited' => $this->illimited ? 1 : 0,
			'quantity' => $this->quantity,
			'category' => $this->category?->serialize(),
			'available_from' => !empty($this->available_from) ? $this->available_from->format('Y-m-d H:i:s') : '',
			'available_to' => !empty($this->available_to) ? $this->available_to->format('Y-m-d H:i:s') : '',
			'campaigns' => $this->campaigns,
			'published' => $this->published ? 1 : 0,
			'mandatory' => $this->mandatory,
		];
	}
}