<?php
/**
 * @package     Tchooz\Entities\Emails\Providers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails\Providers;

use Tchooz\Entities\Emails\TagContext;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Interfaces\TagProviderInterface;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\TransactionRepository;

/**
 * Resolves transaction-related constant email tags for an application file.
 */
class TransactionTagProvider implements TagProviderInterface
{
	/**
	 * Repositories are injectable so the provider can be unit-tested without a database.
	 * When not injected, they are created lazily on first use (default runtime behaviour).
	 */
	public function __construct(
		private ?PaymentRepository $paymentRepository = null,
		private ?TransactionRepository $transactionRepository = null
	) {}

	public function getName(): string
	{
		return 'transaction';
	}

	public function getProvidedTags(): array
	{
		return ['LAST_CONFIRMED_TRANSACTION_AMOUNT'];
	}

	public function supports(TagContext $context): bool
	{
		if (empty($context->getFnum()))
		{
			return false;
		}

		// Transaction tags are only meaningful when the payment addon is active.
		return $this->getPaymentRepository()->isActivated();
	}

	public function provide(TagContext $context): array
	{
		$amount = '';

		$last_confirmed_transactions = $this->getTransactionRepository()->getTransactions(1, 1, [
			'fnum'   => $context->getFnum(),
			'status' => TransactionStatus::CONFIRMED->value,
		]);

		if (!empty($last_confirmed_transactions[0]))
		{
			$last_confirmed_transaction = $last_confirmed_transactions[0];
			$amount = $last_confirmed_transaction->getAmount() . ' ' . $last_confirmed_transaction->getCurrency()->getSymbol();
		}

		return ['LAST_CONFIRMED_TRANSACTION_AMOUNT' => $amount];
	}

	private function getPaymentRepository(): PaymentRepository
	{
		return $this->paymentRepository ??= new PaymentRepository();
	}

	private function getTransactionRepository(): TransactionRepository
	{
		return $this->transactionRepository ??= new TransactionRepository();
	}
}
