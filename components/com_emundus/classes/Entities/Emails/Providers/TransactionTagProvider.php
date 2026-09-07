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
use Tchooz\Entities\Payment\TransactionEntity;
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
		return [
			'LAST_CONFIRMED_TRANSACTION_AMOUNT',
			'LAST_CONFIRMED_TRANSACTION_REFERENCE',
			'LAST_TRANSACTION_AMOUNT',
			'LAST_TRANSACTION_REFERENCE',
		];
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
		$last_confirmed = $this->getLastTransaction($context->getFnum(), TransactionStatus::CONFIRMED);
		$last           = $this->getLastTransaction($context->getFnum());

		return [
			'LAST_CONFIRMED_TRANSACTION_AMOUNT'    => $this->formatAmount($last_confirmed),
			'LAST_CONFIRMED_TRANSACTION_REFERENCE' => $last_confirmed?->getExternalReference() ?? '',
			'LAST_TRANSACTION_AMOUNT'              => $this->formatAmount($last),
			'LAST_TRANSACTION_REFERENCE'           => $last?->getExternalReference() ?? '',
		];
	}

	/**
	 * The repository orders by creation date descending, so the first row of a single item page is
	 * the most recent transaction matching the filters.
	 *
	 * @param   string                  $fnum
	 * @param   TransactionStatus|null  $status  Omitted to consider every transaction, whatever its status.
	 *
	 * @return TransactionEntity|null
	 */
	private function getLastTransaction(string $fnum, ?TransactionStatus $status = null): ?TransactionEntity
	{
		$filters = ['fnum' => $fnum];

		if ($status !== null)
		{
			$filters['status'] = $status->value;
		}

		$transactions = $this->getTransactionRepository()->getTransactions(1, 1, $filters);

		return $transactions[0] ?? null;
	}

	private function formatAmount(?TransactionEntity $transaction): string
	{
		if ($transaction === null)
		{
			return '';
		}

		return $transaction->getAmount() . ' ' . $transaction->getCurrency()->getSymbol();
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
