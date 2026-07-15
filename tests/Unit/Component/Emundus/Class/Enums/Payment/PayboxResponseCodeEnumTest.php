<?php

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Enums\Payment\PayboxResponseCodeEnum;

/**
 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum
 */
class PayboxResponseCodeEnumTest extends TestCase
{

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::fromCode
	 * @return void
	 */
	public function testFromCodeWhenSuccessCodeReturnsSuccess(): void
	{
		$this->assertSame(
			PayboxResponseCodeEnum::SUCCESS,
			PayboxResponseCodeEnum::fromCode('00000'),
			'Code 00000 must resolve to SUCCESS'
		);
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::fromCode
	 * @return void
	 */
	public function testFromCodeWhenBankRefusalTwoDigitsReturnsAuthorizationRefused(): void
	{
		$this->assertSame(
			PayboxResponseCodeEnum::AUTHORIZATION_REFUSED,
			PayboxResponseCodeEnum::fromCode('00133'),
			'A 001xx refusal (2-digit bank suffix) must resolve to AUTHORIZATION_REFUSED'
		);
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::fromCode
	 * @return void
	 */
	public function testFromCodeWhenBankRefusalThreeDigitsReturnsAuthorizationRefused(): void
	{
		$this->assertSame(
			PayboxResponseCodeEnum::AUTHORIZATION_REFUSED,
			PayboxResponseCodeEnum::fromCode('001123'),
			'A 001xxx refusal (3-digit bank suffix, e.g. Finaref) must also resolve to AUTHORIZATION_REFUSED'
		);
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::fromCode
	 * @return void
	 */
	public function testFromCodeWhenKnownErrorCodeReturnsMatchingCase(): void
	{
		$this->assertSame(
			PayboxResponseCodeEnum::PAYBOX_ERROR,
			PayboxResponseCodeEnum::fromCode('00003'),
			'Code 00003 must resolve to PAYBOX_ERROR'
		);
		$this->assertSame(
			PayboxResponseCodeEnum::INVALID_CARD,
			PayboxResponseCodeEnum::fromCode('00004'),
			'Code 00004 must resolve to INVALID_CARD'
		);
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::fromCode
	 * @return void
	 */
	public function testFromCodeWhenPendingValidationCodeReturnsPendingValidation(): void
	{
		$this->assertSame(
			PayboxResponseCodeEnum::PENDING_VALIDATION,
			PayboxResponseCodeEnum::fromCode('99999'),
			'Code 99999 must resolve to PENDING_VALIDATION'
		);
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::fromCode
	 * @return void
	 */
	public function testFromCodeWhenUnknownCodeReturnsUnknown(): void
	{
		$this->assertSame(
			PayboxResponseCodeEnum::UNKNOWN,
			PayboxResponseCodeEnum::fromCode('ZZZZ'),
			'An unlisted code must resolve to UNKNOWN'
		);
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::getTransactionStatus
	 * @return void
	 */
	public function testGetTransactionStatusWhenSuccessReturnsConfirmed(): void
	{
		$this->assertSame(
			TransactionStatus::CONFIRMED,
			PayboxResponseCodeEnum::SUCCESS->getTransactionStatus(),
			'SUCCESS must map to the CONFIRMED status'
		);
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::getTransactionStatus
	 * @return void
	 */
	public function testGetTransactionStatusWhenPendingValidationReturnsWaiting(): void
	{
		$this->assertSame(
			TransactionStatus::WAITING,
			PayboxResponseCodeEnum::PENDING_VALIDATION->getTransactionStatus(),
			'PENDING_VALIDATION must map to the WAITING status'
		);
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PayboxResponseCodeEnum::getTransactionStatus
	 * @return void
	 */
	public function testGetTransactionStatusWhenFailureCaseReturnsFailed(): void
	{
		$this->assertSame(
			TransactionStatus::FAILED,
			PayboxResponseCodeEnum::AUTHORIZATION_REFUSED->getTransactionStatus(),
			'A refusal (AUTHORIZATION_REFUSED) must map to the FAILED status'
		);
		$this->assertSame(
			TransactionStatus::FAILED,
			PayboxResponseCodeEnum::UNKNOWN->getTransactionStatus(),
			'UNKNOWN must map to the FAILED status'
		);
	}
}