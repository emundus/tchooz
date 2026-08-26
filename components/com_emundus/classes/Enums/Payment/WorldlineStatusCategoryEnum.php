<?php

namespace Tchooz\Enums\Payment;

use Tchooz\Entities\Payment\TransactionStatus;

/**
 * Worldline exposes both a payment `status` and a coarser `statusOutput.statusCategory`.
 * Worldline regularly adds new statuses but keeps the categories stable, so the category
 * drives the mapping and only the statuses that must not follow their category are special-cased.
 */
enum WorldlineStatusCategoryEnum: string
{
	case CREATED                      = 'CREATED';
	case PENDING_PAYMENT              = 'PENDING_PAYMENT';
	case PENDING_MERCHANT             = 'PENDING_MERCHANT';
	case PENDING_CONNECT_OR_3RD_PARTY = 'PENDING_CONNECT_OR_3RD_PARTY';
	case ACCOUNT_VERIFIED             = 'ACCOUNT_VERIFIED';
	case COMPLETED                    = 'COMPLETED';
	case UNSUCCESSFUL                 = 'UNSUCCESSFUL';
	case REVERSED                     = 'REVERSED';
	case REFUNDED                     = 'REFUNDED';

	/**
	 * Authorised: the bank has reserved the funds and the capture is on its way. Card capture
	 * runs in batches, so waiting for CAPTURED or PAID would leave the applicant blocked for
	 * hours on money that is already guaranteed. Treated as acquired from here on.
	 */
	private const CONFIRMED_STATUSES = ['CAPTURE_REQUESTED', 'CAPTURE_IN_PROGRESS'];

	/** Consumer or merchant cancellation, as opposed to a refusal. */
	private const CANCELLED_STATUSES = ['CANCELLED'];

	/** Refused by the acquirer: distinct from a cancellation for reporting. */
	private const FAILED_STATUSES = ['REJECTED', 'REJECTED_CAPTURE'];

	/**
	 * Money taken back after the fact. These sit in the COMPLETED category, so mapping them
	 * by category alone would wrongly keep the transaction CONFIRMED.
	 */
	private const CLAWBACK_STATUSES = ['CHARGEBACK_NOTIFICATION', 'CHARGEBACKED'];

	public function getTransactionStatus(): ?TransactionStatus
	{
		return match ($this)
		{
			self::CREATED,
			self::PENDING_PAYMENT,
			self::PENDING_MERCHANT,
			self::PENDING_CONNECT_OR_3RD_PARTY,
			self::ACCOUNT_VERIFIED => TransactionStatus::WAITING,

			self::COMPLETED    => TransactionStatus::CONFIRMED,
			self::UNSUCCESSFUL => TransactionStatus::FAILED,

			// No equivalent in TransactionStatus: handled by the caller, never silently confirmed.
			self::REVERSED, self::REFUNDED => null,
		};
	}

	/**
	 * @param   string|null  $status    Worldline payment status (e.g. CAPTURED, REJECTED)
	 * @param   string|null  $category  Worldline statusOutput.statusCategory
	 *
	 * @return TransactionStatus|null  null when the event carries no meaning for our own workflow
	 */
	public static function resolve(?string $status, ?string $category): ?TransactionStatus
	{
		$status = strtoupper(trim((string) $status));

		if (in_array($status, self::CLAWBACK_STATUSES, true))
		{
			return null;
		}

		if (in_array($status, self::CONFIRMED_STATUSES, true))
		{
			return TransactionStatus::CONFIRMED;
		}

		if (in_array($status, self::CANCELLED_STATUSES, true))
		{
			return TransactionStatus::CANCELLED;
		}

		if (in_array($status, self::FAILED_STATUSES, true))
		{
			return TransactionStatus::FAILED;
		}

		return self::tryFrom(strtoupper(trim((string) $category)))?->getTransactionStatus();
	}
}
