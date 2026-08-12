<?php

namespace Tchooz\Enums\Payment;

use Tchooz\Synchronizers\Payment\Paybox;
use Tchooz\Synchronizers\Payment\PaymentSynchronizerInterface;
use Tchooz\Synchronizers\Payment\Payzen;
use Tchooz\Synchronizers\Payment\Sogecommerce;
use Tchooz\Synchronizers\Payment\Stripe;

enum PaymentGatewayEnum: string
{
	case STRIPE       = 'stripe';
	case SOGECOMMERCE = 'sogecommerce';
	case PAYBOX       = 'paybox';
	case PAYZEN       = 'payzen';

	public function getSynchronizer(): PaymentSynchronizerInterface
	{
		return match ($this)
		{
			self::STRIPE       => new Stripe(),
			self::SOGECOMMERCE => new Sogecommerce(),
			self::PAYBOX       => new Paybox(),
			self::PAYZEN       => new Payzen(),
		};
	}
}