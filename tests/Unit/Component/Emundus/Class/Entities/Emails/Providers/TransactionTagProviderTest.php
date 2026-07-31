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
	public function testGetProvidedTagsReturnsLastConfirmedTransactionAmount(): void
	{
		$provider = new TransactionTagProvider();

		$this->assertSame(
			['LAST_CONFIRMED_TRANSACTION_AMOUNT'],
			$provider->getProvidedTags(),
			'The provider must declare exactly the LAST_CONFIRMED_TRANSACTION_AMOUNT tag so the content guard can target it.'
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
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::provide
	 * @return void
	 */
	public function testProvideWhenConfirmedTransactionExistsReturnsAmountWithCurrencySymbol(): void
	{
		$currency = $this->createMock(CurrencyEntity::class);
		$currency->method('getSymbol')->willReturn('€');

		$transaction = $this->createMock(TransactionEntity::class);
		$transaction->method('getAmount')->willReturn(49.99);
		$transaction->method('getCurrency')->willReturn($currency);

		$fnum = 'abc1700000000applicant';

		$transactionRepository = $this->createMock(TransactionRepository::class);
		$transactionRepository->expects($this->once())
			->method('getTransactions')
			->with(1, 1, ['fnum' => $fnum, 'status' => TransactionStatus::CONFIRMED->value])
			->willReturn([$transaction]);

		$provider = new TransactionTagProvider(null, $transactionRepository);
		$context  = new TagContext(0, $fnum);

		$this->assertSame(
			['LAST_CONFIRMED_TRANSACTION_AMOUNT' => '49.99 €'],
			$provider->provide($context),
			'The tag value must be the last confirmed transaction amount followed by its currency symbol.'
		);
	}

	/**
	 * @covers \Tchooz\Entities\Emails\Providers\TransactionTagProvider::provide
	 * @return void
	 */
	public function testProvideWhenNoConfirmedTransactionReturnsEmptyString(): void
	{
		$fnum = 'abc1700000000applicant';

		$transactionRepository = $this->createMock(TransactionRepository::class);
		$transactionRepository->expects($this->once())
			->method('getTransactions')
			->with(1, 1, ['fnum' => $fnum, 'status' => TransactionStatus::CONFIRMED->value])
			->willReturn([]);

		$provider = new TransactionTagProvider(null, $transactionRepository);
		$context  = new TagContext(0, $fnum);

		$this->assertSame(
			['LAST_CONFIRMED_TRANSACTION_AMOUNT' => ''],
			$provider->provide($context),
			'With no confirmed transaction the tag must resolve to an empty string, never null or an error.'
		);
	}
}
