<?php

namespace Tchooz\Factories\Payment;

use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Repositories\Payment\CurrencyRepository;

class TransactionFactory
{
	public static function fromDbObjects(array $dbObjects): array
	{
		$transactions = [];

		if (!empty($dbObjects))
		{
			$currency_repository = new CurrencyRepository();

			foreach ($dbObjects as $dbObject)
			{
				$transaction = new TransactionEntity($dbObject->id);

				$transaction->setStatus(TransactionStatus::from($dbObject->status));
				$transaction->setAmount($dbObject->amount);
				$transaction->setCreatedAt($dbObject->created_at);
				$transaction->setCreatedBy($dbObject->created_by);
				$transaction->setFnum($dbObject->fnum);

				if (!empty($dbObject->updated_at)) {
					$transaction->setUpdatedAt($dbObject->updated_at);
					$transaction->setUpdatedBy($dbObject->updated_by);
				}

				if (!empty($cart)) {
					$transaction->setCartId($cart->getId());
				} else {
					$transaction->setCartId($dbObject->cart_id);
				}

				$currency = $currency_repository->getCurrencyById($dbObject->currency_id);
				$transaction->setCurrency($currency);
				$transaction->setPaymentMethod(new PaymentMethodEntity($dbObject->payment_method_id));
				$transaction->setSynchronizerId($dbObject->synchronizer_id);
				$transaction->setStepId($dbObject->step_id);

				if (!empty($dbObject->reference)) {
					$transaction->setExternalReference($dbObject->reference);
				} else {
					$transaction->setExternalReference('');
				}

				if (!empty($dbObject->data)) {
					$transaction->setData($dbObject->data);

					$data = json_decode($dbObject->data);
					if (!empty($data->installment) && !empty($data->installment->number_installment_debit))
					{
						$transaction->setNumberInstallmentDebit($data->installment->number_installment_debit);
					}
				}

				$transactions[] = $transaction;
			}
		}

		return $transactions;
	}

	/**
	 * @param   TransactionEntity  $transaction
	 * Build a title for the transaction, using products labels
	 * @return string
	 */
	public static function getTransactionTitle(TransactionEntity $transaction): string
	{
		$title = '';

		if (!empty($transaction->getData()))
		{
			$data = json_decode($transaction->getData(), true);

			if (!empty($data['products']) && is_array($data['products']))
			{
				$product_labels = array_map(function ($product) {
					return $product['label'] ?? '';
				}, $data['products']);

				$title = implode(', ', $product_labels);
			}
			else
			{
				$title = 'Transaction #' . $transaction->getId();
			}
		}

		return $title;
	}
}