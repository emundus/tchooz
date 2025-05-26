<?php

namespace Tchooz\Entities\Payment;

use Joomla\CMS\Component\ComponentHelper;

class AlterationEntity
{
	private int $id;
	private int $cart_id = 0;
	private ?ProductEntity $product = null;

	private ?DiscountEntity $discount = null;
	private string $description = '';
	private float $amount = 0.0;
	private AlterationType $type = AlterationType::FIXED;
	private int $created_by = 0;
	private \DateTime $created_at;
	private int $updated_by = 0;
	private \DateTime $updated_at;

	private int $published = 1;
	private int $automated_task_user = 0;

	public function __construct(int $id, int $cart_id, ?ProductEntity $product, ?DiscountEntity $discount, string $description, float $amount, AlterationType $type = AlterationType::FIXED, $created_by = 0, $created_at = null, $updated_by = 0, $updated_at = null, int $published = 1)
	{
		$this->id = $id;
		$this->cart_id = $cart_id;
		$this->product = $product;
		$this->discount = $discount;

		if (!empty($discount)) {
			$this->description = $discount->getDescription();
			$this->amount = -$discount->getValue(); // a discount is always a negative value
			$this->type = AlterationType::from($discount->getType()->value);
		} else {
			$this->description = $description;
			$this->amount = $amount;
			$this->type = $type;
		}

		$config = ComponentHelper::getParams('com_emundus');
		$this->automated_task_user = (int)$config->get('automated_task_user', 0);

		$this->created_by = $created_by;
		$this->created_at = $created_at ?? new \DateTime();
		$this->updated_by = $updated_by;
		$this->updated_at = $updated_at ?? new \DateTime();
		$this->published = $published;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getCartId(): int
	{
		return $this->cart_id;
	}

	public function setCartId(int $cart_id): void
	{
		$this->cart_id = $cart_id;
	}

	public function getProduct(): ?ProductEntity
	{
		return $this->product;
	}

	public function setProduct(?ProductEntity $product): void
	{
		$this->product = $product;
	}

	public function getDiscount(): ?DiscountEntity
	{
		return $this->discount;
	}

	public function setDiscount(?DiscountEntity $discount): void
	{
		$this->discount = $discount;

		if (!empty($discount)) {
			$this->description = $discount->getDescription();
			$this->amount = -$discount->getValue();
			$this->type = AlterationType::from($discount->getType()->value);
		}
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setAmount(float $amount): void
	{
		$this->amount = $amount;
	}

	public function getAmount(): float
	{
		return $this->amount;
	}

	public function setType(AlterationType $type): void
	{
		$this->type = $type;
	}

	public function getType(): AlterationType
	{
		return $this->type;
	}

	public function getCreatedBy(): int
	{
		return $this->created_by;
	}

	public function setCreatedBy(int $user_id): void
	{
		$this->created_by = $user_id;
	}

	public function getCreatedAt(): \DateTime
	{
		return $this->created_at;
	}



	public function getUpdatedBy(): int
	{
		return $this->updated_by;
	}

	public function setUpdatedBy(int $user_id): void
	{
		$this->updated_by = $user_id;
	}

	public function getUpdatedAt(): \DateTime
	{
		return $this->updated_at;
	}

	public function setUpdatedAt(\DateTime $updated_at): void
	{
		$this->updated_at = $updated_at;
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	public function setPublished(int $published): void
	{
		if ($published != 1 && $published != 0) {
			throw new \InvalidArgumentException('Published must be 1 or 0');
		}
		$this->published = $published;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'cart_id' => $this->cart_id,
			'product_id' => !empty($this->product) ? $this->product->getId() : null,
			'discount_id' => !empty($this->discount) ? $this->discount->getId() : null,
			'description' => $this->description,
			'amount' => $this->amount,
			'displayed_amount' => $this->amount,
			'type' => $this->type->value,
			'created_by' => $this->created_by,
			'created_at' => $this->created_at->format('Y-m-d H:i:s'),
			'updated_by' => $this->updated_by,
			'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
			'automation' => $this->created_by == $this->automated_task_user ? 1 : 0,
		];
	}
}