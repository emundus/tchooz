<?php

namespace Unit\Component\Emundus\Class\Entities\Emails\Providers;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Emails\Providers\TransactionTagProvider;
use Tchooz\Entities\Emails\TagContext;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\TransactionRepository;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Emails\Providers
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Emails\Providers\TransactionTagProvider
 */
class TransactionTagProviderTest extends TestCase
{
	// -------------------------------------------------------------------------
	// Identity — getName / getProvidedTags
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::getName
	 * @return void
	 */
	public function testGetNameReturnsTransaction(): void
	{
		$provider = new TransactionTagProvider();

		$this->assertSame('transaction', $provider->getName(), 'The provider name must be the stable registry key "transaction".');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::getProvidedTags
	 * @return void
	 */
	public function testGetProvidedTagsReturnsTheAmountAndReferenceTags(): void
	{
		$provider = new TransactionTagProvider();

		$this->assertSame(
			[
				'LAST_CONFIRMED_TRANSACTION_AMOUNT',
				'LAST_CONFIRMED_TRANSACTION_REFERENCE',
				'LAST_TRANSACTION_AMOUNT',
				'LAST_TRANSACTION_REFERENCE',
			],
			$provider->getProvidedTags(),
			'The provider must declare exactly the tags it resolves: the content guard skips any provider whose declared tags are absent from the email body.'
		);
	}

	// -------------------------------------------------------------------------
	// supports() — gating on fnum and payment addon activation
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::supports
	 * @return void
	 */
	public function testSupportsWhenFnumIsEmptyReturnsFalseWithoutTouchingPayment(): void
	{
		$paymentRepository = $this->createMock(PaymentRepository::class);
		$paymentRepository->expects($this->never())
			->method('isActivated');

		$provider = new TransactionTagProvider($paymentRepository);
		$context  = new TagContext(0, '');

		$this->assertFalse($provider->supports($context), 'Without a fnum the provider cannot resolve a transaction and must not be supported.');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::supports
	 * @return void
	 */
	public function testSupportsWhenPaymentAddonInactiveReturnsFalse(): void
	{
		$paymentRepository = $this->createMock(PaymentRepository::class);
		$paymentRepository->expects($this->once())
			->method('isActivated')
			->willReturn(false);

		$provider = new TransactionTagProvider($paymentRepository);
		$context  = new TagContext(0, 'abc1700000000applicant');

		$this->assertFalse($provider->supports($context), 'When the payment addon is inactive the transaction tag is meaningless and must not be supported.');
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::supports
	 * @return void
	 */
	public function testSupportsWhenFnumPresentAndPaymentActiveReturnsTrue(): void
	{
		$paymentRepository = $this->createMock(PaymentRepository::class);
		$paymentRepository->expects($this->once())
			->method('isActivated')
			->willReturn(true);

		$provider = new TransactionTagProvider($paymentRepository);
		$context  = new TagContext(0, 'abc1700000000applicant');

		$this->assertTrue($provider->supports($context), 'With a fnum and an active payment addon the provider must be supported.');
	}

	// -------------------------------------------------------------------------
	// provide() — value resolution
	// -------------------------------------------------------------------------

	/**
	 * Builds a repository whose two lookups — confirmed only, then unfiltered — are answered
	 * independently, so a test can make the last transaction differ from the last confirmed one.
	 *
	 * @param   array  $confirmed  Rows returned for the confirmed lookup
	 * @param   array  $any        Rows returned for the unfiltered lookup
	 *
	 * @return TransactionRepository
	 */
	private function repositoryReturning(string $fnum, array $confirmed, array $any): TransactionRepository
	{
		$repository = $this->createMock(TransactionRepository::class);
		$repository->expects($this->exactly(2))
			->method('getTransactions')
			->willReturnCallback(function (int $limit, int $page, array $filters) use ($fnum, $confirmed, $any) {
				$this->assertSame(1, $limit, 'Only the most recent transaction is needed.');
				$this->assertSame(1, $page, 'The lookup must read the first page.');
				$this->assertSame($fnum, $filters['fnum'] ?? null, 'Every lookup must be scoped to the context fnum.');

				if (($filters['status'] ?? null) === TransactionStatus::CONFIRMED->value)
				{
					return $confirmed;
				}

				$this->assertArrayNotHasKey('status', $filters, 'The unfiltered lookup must not constrain the status.');

				return $any;
			});

		return $repository;
	}

	private function transaction(float $amount, string $reference): TransactionEntity
	{
		$currency = $this->createMock(CurrencyEntity::class);
		$currency->method('getSymbol')->willReturn('€');

		$transaction = $this->createMock(TransactionEntity::class);
		$transaction->method('getAmount')->willReturn($amount);
		$transaction->method('getCurrency')->willReturn($currency);
		$transaction->method('getExternalReference')->willReturn($reference);

		return $transaction;
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::provide
	 * @return void
	 */
	public function testProvideResolvesAmountWithCurrencySymbolAndReferenceForBothLookups(): void
	{
		$fnum        = 'abc1700000000applicant';
		$transaction = $this->transaction(49.99, 'WL-2026-0042');

		$provider = new TransactionTagProvider(null, $this->repositoryReturning($fnum, [$transaction], [$transaction]));

		$this->assertSame(
			[
				'LAST_CONFIRMED_TRANSACTION_AMOUNT'    => '49.99 €',
				'LAST_CONFIRMED_TRANSACTION_REFERENCE' => 'WL-2026-0042',
				'LAST_TRANSACTION_AMOUNT'              => '49.99 €',
				'LAST_TRANSACTION_REFERENCE'           => 'WL-2026-0042',
			],
			$provider->provide(new TagContext(0, $fnum)),
			'Each tag must carry the amount followed by its currency symbol, and the external reference as stored.'
		);
	}

	/**
	 * The whole point of the unfiltered tags: a pending or refused attempt made after the last
	 * successful payment must be reported without overwriting the confirmed values.
	 *
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::provide
	 * @return void
	 */
	public function testProvideWhenLatestTransactionIsNotConfirmedReportsBothSeparately(): void
	{
		$fnum = 'abc1700000000applicant';

		$provider = new TransactionTagProvider(null, $this->repositoryReturning(
			$fnum,
			[$this->transaction(49.99, 'WL-2026-0042')],
			[$this->transaction(120.00, 'WL-2026-0099')]
		));

		$this->assertSame(
			[
				'LAST_CONFIRMED_TRANSACTION_AMOUNT'    => '49.99 €',
				'LAST_CONFIRMED_TRANSACTION_REFERENCE' => 'WL-2026-0042',
				'LAST_TRANSACTION_AMOUNT'              => '120 €',
				'LAST_TRANSACTION_REFERENCE'           => 'WL-2026-0099',
			],
			$provider->provide(new TagContext(0, $fnum)),
			'The unfiltered tags must describe the latest attempt, independently of the latest confirmed one.'
		);
	}

	/**
	 * The reference comes from the singular external_reference property, hydrated by the
	 * repository from the joined main reference row — not from the external_references list.
	 *
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::provide
	 * @return void
	 */
	public function testProvideWhenTransactionHasNoReferenceReturnsEmptyReference(): void
	{
		$fnum        = 'abc1700000000applicant';
		$transaction = $this->transaction(49.99, '');

		$provider = new TransactionTagProvider(null, $this->repositoryReturning($fnum, [$transaction], [$transaction]));

		$this->assertSame(
			[
				'LAST_CONFIRMED_TRANSACTION_AMOUNT'    => '49.99 €',
				'LAST_CONFIRMED_TRANSACTION_REFERENCE' => '',
				'LAST_TRANSACTION_AMOUNT'              => '49.99 €',
				'LAST_TRANSACTION_REFERENCE'           => '',
			],
			$provider->provide(new TagContext(0, $fnum)),
			'A transaction without a reference must leave that tag empty while the amount still resolves.'
		);
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::provide
	 * @return void
	 */
	public function testProvideWhenNoTransactionAtAllReturnsEmptyStrings(): void
	{
		$fnum = 'abc1700000000applicant';

		$provider = new TransactionTagProvider(null, $this->repositoryReturning($fnum, [], []));

		$this->assertSame(
			[
				'LAST_CONFIRMED_TRANSACTION_AMOUNT'    => '',
				'LAST_CONFIRMED_TRANSACTION_REFERENCE' => '',
				'LAST_TRANSACTION_AMOUNT'              => '',
				'LAST_TRANSACTION_REFERENCE'           => '',
			],
			$provider->provide(new TagContext(0, $fnum)),
			'With no transaction every tag must resolve to an empty string, never null, the tag name or an error.'
		);
	}
}
