<?php

namespace Tchooz\Synchronizers\Payment;

use Tchooz\Enums\Payment\PaymentGatewayEnum;

class Sogecommerce extends Lyra
{
	protected function getPaymentGateway(): PaymentGatewayEnum
	{
		return PaymentGatewayEnum::SOGECOMMERCE;
	}
}