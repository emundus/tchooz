<?php

namespace Tchooz\Entities\Payment;

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\TransactionStatus;

class TransactionEntity
{
	private int $id = 0;
	private TransactionStatus $status;
	private ?string $created_at = null;
	private int $created_by = 0;
	private ?string $updated_at = null;
	private int $updated_by = 0;

	//private CartEntity $cart;

	private int $cart_id = 0;
	private int $step_id = 0;

	private string $fnum = '';
	private float $amount = 0.00;
	private CurrencyEntity $currency;
	private PaymentMethodEntity $payment_method;
	private int $synchronizer_id = 0;

	private int $number_installment_debit = 1;

	private string $external_reference = '';

	/**
	 * @var string json of all products and alterations
	 */
	private string $data = '{}';

	public function __construct(int $id = 0)
	{
		$this->id = $id;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		if ($id < 0) {
			throw new \InvalidArgumentException('ID cannot be negative');
		}
		$this->id = $id;
	}

	public function setCreatedAt(string $created_at): void
	{
		$this->created_at = $created_at;
	}

	public function getCreatedAt(bool $formatted = false): ?string
	{
		$created_at = $this->created_at;

		if ($formatted) {
			$created_at = \EmundusHelperDate::displayDate($this->created_at, 'DATE_FORMAT_LC2', 0);
		}

		return $created_at;
	}

	public function setCreatedBy(int $created_by): void
	{
		if ($created_by < 0) {
			throw new \InvalidArgumentException('Created by cannot be negative');
		}
		$this->created_by = $created_by;
	}

	public function getCreatedBy(): int
	{
		return $this->created_by;
	}

	public function setUpdatedAt(string $updated_at): void
	{
		$this->updated_at = $updated_at;
	}

	public function getUpdatedAt(bool $formatted = false): ?string
	{
		$updated_at = $this->updated_at;

		if ($formatted) {
			$updated_at = \EmundusHelperDate::displayDate($this->updated_at, 'DATE_FORMAT_LC2', 0);
		}

		return $updated_at;
	}

	public function setUpdatedBy(int $updated_by): void
	{
		if ($updated_by < 0) {
			throw new \InvalidArgumentException('Updated by cannot be negative');
		}
		$this->updated_by = $updated_by;
	}

	public function getUpdatedBy(): int
	{
		return $this->updated_by;
	}

	public function setAmount(float $amount): void
	{
		if ($amount < 0) {
			throw new \InvalidArgumentException('Amount cannot be negative');
		}
		$this->amount = $amount;
	}

	public function getAmount(): float
	{
		return $this->amount;
	}

	public function setPaymentMethod(PaymentMethodEntity $payment_method): void
	{
		$this->payment_method = $payment_method;
	}

	public function getPaymentMethod(): PaymentMethodEntity
	{
		return $this->payment_method;
	}

	public function setCurrency(CurrencyEntity $currency): void
	{
		$this->currency = $currency;
	}

	public function getCurrency(): CurrencyEntity
	{
		return $this->currency;
	}

	public function getStatus(): TransactionStatus
	{
		return $this->status;
	}

	public function setStatus(TransactionStatus $status): void
	{
		$this->status = $status;
	}

	public function getCartId(): int
	{
		return $this->cart_id;
	}

	public function setCartId(int $cart_id): void
	{
		$this->cart_id = $cart_id;
	}

	public function setFnum(string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function getStepId(): int
	{
		return $this->step_id;
	}

	public function setStepId(int $step_id): void
	{
		$this->step_id = $step_id;
	}

	public function getSynchronizerId(): int
	{
		return $this->synchronizer_id;
	}

	public function setSynchronizerId(int $synchronizer_id): void
	{
		$this->synchronizer_id = $synchronizer_id;
	}

	public function generateExternalReference(int $length = 6): string
	{
		$reference = strtoupper(substr(bin2hex(random_bytes(ceil($length/2))), 0, $length));
		$this->setExternalReference($reference);

		return $this->getExternalReference();
	}

	public function setExternalReference(string $external_reference): void
	{
		$this->external_reference = $external_reference;
	}

	public function getExternalReference(): string
	{
		return $this->external_reference;
	}

	public function getNumberInstallmentDebit(): int
	{
		return $this->number_installment_debit;
	}

	public function setNumberInstallmentDebit(int $number_installment_debit): void
	{
		$this->number_installment_debit = $number_installment_debit;
	}

	public function setData(string $data): void
	{
		$this->data = $data;
	}

	public function getData(): string
	{
		return $this->data;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->getId(),
			'external_reference' => $this->getExternalReference(),
			'status' => $this->getStatus()->value,
			'created_at' => $this->getCreatedAt(true),
			'created_by' => $this->created_by,
			'updated_at' => $this->getUpdatedAt(true),
			'updated_by' => $this->updated_by,
			'fnum' => $this->getFnum(),
			'cart_id' => $this->getCartId(),
			'amount' => $this->getAmount(),
			'currency' => $this->getCurrency()->serialize(),
			'payment_method' => $this->getPaymentMethod()->serialize(),
			'synchronizer_id' => $this->synchronizer_id,
			'step_id' => $this->getStepId(),
			'data' => json_decode($this->getData(), true),
		];
	}
}