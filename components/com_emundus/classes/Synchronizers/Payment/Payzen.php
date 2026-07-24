<?php

namespace Tchooz\Synchronizers\Payment;

use Tchooz\Enums\Payment\PaymentGatewayEnum;

class Payzen extends Lyra
{
	protected function getPaymentGateway(): PaymentGatewayEnum
	{
		return PaymentGatewayEnum::PAYZEN;
	}
}