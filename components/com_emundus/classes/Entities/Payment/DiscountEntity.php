<?php

namespace Tchooz\Entities\Payment;

use Tchooz\Entities\Payment\DiscountType;
use Tchooz\Entities\Payment\CurrencyEntity;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class DiscountEntity
{
	private int $id = 0;
	private string $label = '';
	private string $description = '';
	private float $value = 0.0;
	private DiscountType $type = DiscountType::FIXED;
	private ?CurrencyEntity $currency = null;
	private \DateTime|null $available_from = null;
	private \DateTime|null $available_to = null;
	private ?int $quantity = 0;
	private int $published = 1;

	public function __construct(int $id = 0)
	{
		Log::addLogger(['text_file' => 'com_emundus.entity.discount.php'], Log::ALL, ['com_emundus.entity.discount']);
		$this->id = $id;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function setType(string|DiscountType $type): void
	{
		if (is_string($type)) {
			$type = DiscountType::from($type);

			if (empty($type)) {
				throw new \InvalidArgumentException('Invalid discount type');
			}
		}
		$this->type = $type;
	}

	public function getType(): DiscountType
	{
		return $this->type;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getValue(): float
	{
		return $this->value;
	}

	public function setValue(float $value): void
	{
		$this->value = $value;
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	public function getDisplayedValue(): string
	{
		$symbol = $this->type->getSymbol();

		if ($this->type == DiscountType::FIXED) {
			if ($this->currency) {
				$symbol = $this->currency->getSymbol();
			} else {
				$symbol = '€';
			}
		}

		return number_format($this->value, 2, '.', ' ') . ' ' . $this->type->getSymbol();
	}

	public function setPublished(int $published): void
	{
		$this->published = $published;
	}

	public function getAvailableFrom(): ?\DateTime
	{
		return $this->available_from;
	}

	public function setAvailableFrom(?\DateTime $available_from): void
	{
		$this->available_from = $available_from;
	}

	public function getAvailableTo(): ?\DateTime
	{
		return $this->available_to;
	}

	public function setAvailableTo(?\DateTime $available_to): void
	{
		$this->available_to = $available_to;
	}

	public function getQuantity(): ?int
	{
		return $this->quantity;
	}

	public function setQuantity(?int $quantity): void
	{
		$this->quantity = $quantity;
	}

	public function getCurrency(): ?CurrencyEntity
	{
		return $this->currency;
	}

	public function setCurrency(CurrencyEntity $currency): void
	{
		$this->currency = $currency;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'label' => $this->label,
			'description' => $this->description,
			'value' => $this->value,
			'type' => $this->type,
			'currency' => $this->currency?->serialize(),
			'available_from' => $this->available_from?->format('Y-m-d H:i:s'),
			'available_to' => $this->available_to?->format('Y-m-d H:i:s'),
			'quantity' => $this->quantity,
			'published' => $this->published
		];
	}
}