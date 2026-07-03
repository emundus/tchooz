<?php

namespace Tchooz\Synchronizers\Payment;

use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\TransactionEntity;

interface PaymentSynchronizerInterface
{
	public function prepareCheckout(TransactionEntity $transaction, CartEntity $cart): array;

	public function updateTransactionFromCallback(array $data, int $transaction_id, int $user_id): bool;
}