<?php

namespace Tchooz\Enums\Payment;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Payment\TransactionStatus;

/**
 * Codes returned by Paybox in the "Erreur" (E) variable of the payment response.
 *
 * Two levels of codes coexist:
 *  - The 5-digit Paybox "Erreur" code (overall operation result: 00000 accepted,
 *    00003 Paybox error, etc.). This is what determines the TransactionStatus.
 *    @see https://www.paybox.com/espace-integrateur-documentation/dictionnaire-des-donnees/paybox-system/
 *  - The 001 prefix means a refusal by the authorization center, followed by the issuing
 *    bank's response code (2 digits, or 3 for some networks such as Finaref): 00133, 001xxx...
 *    @see https://www.paybox.com/espace-integrateur-documentation/dictionnaire-des-donnees/codes-reponses/
 */
enum PayboxResponseCodeEnum
{
	case SUCCESS;
	case AUTHORIZATION_REFUSED;
	case CONNECTION_FAILED;
	case PAYBOX_ERROR;
	case INVALID_CARD;
	case ACCESS_DENIED;
	case INVALID_EXPIRY;
	case SUBSCRIPTION_ERROR;
	case UNKNOWN_CURRENCY;
	case INVALID_AMOUNT;
	case ALREADY_PAID;
	case EXISTING_SUBSCRIBER;
	case CARD_NOT_AUTHORIZED;
	case INVALID_CARD_NUMBER;
	case TIMEOUT;
	case COUNTRY_NOT_AUTHORIZED;
	case THREEDS_REFUSED;
	case PENDING_VALIDATION;
	case UNKNOWN;

	/**
	 * Resolve a Paybox "Erreur" code into a response case.
	 * The 001 prefix means a refusal by the authorization center, with a 2 or 3 digit
	 * bank code suffix (e.g. Finaref returns 3 digits) — matched by prefix, not length.
	 */
	public static function fromCode(string $code): self
	{
		if ($code === '00000')
		{
			return self::SUCCESS;
		}

		if (str_starts_with($code, '001'))
		{
			return self::AUTHORIZATION_REFUSED;
		}

		return match ($code)
		{
			'00001' => self::CONNECTION_FAILED,
			'00003' => self::PAYBOX_ERROR,
			'00004' => self::INVALID_CARD,
			'00006' => self::ACCESS_DENIED,
			'00008' => self::INVALID_EXPIRY,
			'00009' => self::SUBSCRIPTION_ERROR,
			'00010' => self::UNKNOWN_CURRENCY,
			'00011' => self::INVALID_AMOUNT,
			'00015' => self::ALREADY_PAID,
			'00016' => self::EXISTING_SUBSCRIBER,
			'00021' => self::CARD_NOT_AUTHORIZED,
			'00029' => self::INVALID_CARD_NUMBER,
			'00030' => self::TIMEOUT,
			'00033' => self::COUNTRY_NOT_AUTHORIZED,
			'00040' => self::THREEDS_REFUSED,
			'99999' => self::PENDING_VALIDATION,
			default => self::UNKNOWN,
		};
	}

	public function getTransactionStatus(): TransactionStatus
	{
		return match ($this)
		{
			self::SUCCESS            => TransactionStatus::CONFIRMED,
			self::PENDING_VALIDATION => TransactionStatus::WAITING,
			default                  => TransactionStatus::FAILED,
		};
	}

	public function getLabel(): string
	{
		return match ($this)
		{
			self::SUCCESS                => Text::_('COM_EMUNDUS_PAYBOX_CODE_SUCCESS'),
			self::AUTHORIZATION_REFUSED  => Text::_('COM_EMUNDUS_PAYBOX_CODE_AUTHORIZATION_REFUSED'),
			self::CONNECTION_FAILED      => Text::_('COM_EMUNDUS_PAYBOX_CODE_CONNECTION_FAILED'),
			self::PAYBOX_ERROR           => Text::_('COM_EMUNDUS_PAYBOX_CODE_PAYBOX_ERROR'),
			self::INVALID_CARD           => Text::_('COM_EMUNDUS_PAYBOX_CODE_INVALID_CARD'),
			self::ACCESS_DENIED          => Text::_('COM_EMUNDUS_PAYBOX_CODE_ACCESS_DENIED'),
			self::INVALID_EXPIRY         => Text::_('COM_EMUNDUS_PAYBOX_CODE_INVALID_EXPIRY'),
			self::SUBSCRIPTION_ERROR     => Text::_('COM_EMUNDUS_PAYBOX_CODE_SUBSCRIPTION_ERROR'),
			self::UNKNOWN_CURRENCY       => Text::_('COM_EMUNDUS_PAYBOX_CODE_UNKNOWN_CURRENCY'),
			self::INVALID_AMOUNT         => Text::_('COM_EMUNDUS_PAYBOX_CODE_INVALID_AMOUNT'),
			self::ALREADY_PAID           => Text::_('COM_EMUNDUS_PAYBOX_CODE_ALREADY_PAID'),
			self::EXISTING_SUBSCRIBER    => Text::_('COM_EMUNDUS_PAYBOX_CODE_EXISTING_SUBSCRIBER'),
			self::CARD_NOT_AUTHORIZED    => Text::_('COM_EMUNDUS_PAYBOX_CODE_CARD_NOT_AUTHORIZED'),
			self::INVALID_CARD_NUMBER    => Text::_('COM_EMUNDUS_PAYBOX_CODE_INVALID_CARD_NUMBER'),
			self::TIMEOUT                => Text::_('COM_EMUNDUS_PAYBOX_CODE_TIMEOUT'),
			self::COUNTRY_NOT_AUTHORIZED => Text::_('COM_EMUNDUS_PAYBOX_CODE_COUNTRY_NOT_AUTHORIZED'),
			self::THREEDS_REFUSED        => Text::_('COM_EMUNDUS_PAYBOX_CODE_THREEDS_REFUSED'),
			self::PENDING_VALIDATION     => Text::_('COM_EMUNDUS_PAYBOX_CODE_PENDING_VALIDATION'),
			self::UNKNOWN                => Text::_('COM_EMUNDUS_PAYBOX_CODE_UNKNOWN'),
		};
	}
}