<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1;

use Worldline\Connect\Sdk\Domain\DataObject;
use Worldline\Connect\Sdk\V1\Domain\CreatePaymentResult;
use Worldline\Connect\Sdk\V1\Domain\PaymentErrorResponse;

/**
 * Class DeclinedPaymentException
 *
 * @package Worldline\Connect\Sdk\V1
 */
class DeclinedPaymentException extends ResponseException
{
    /**
     * @param int         $httpStatusCode
     * @param DataObject  $response
     * @param string|null $message
     */
    public function __construct(int $httpStatusCode, DataObject $response, ?string $message = null)
    {
        if (is_null($message)) {
            $message = DeclinedPaymentException::buildMessage($response);
        }
        parent::__construct($httpStatusCode, $response, $message);
    }

    private static function buildMessage(DataObject $response): string
    {
        if ($response instanceof PaymentErrorResponse && $response->paymentResult != null && $response->paymentResult->payment != null) {
            $payment = $response->paymentResult->payment;
            return "declined payment '$payment->id' with status '$payment->status'";
        }
        return 'the Worldline Global Collect platform returned a declined payment response';
    }

    /**
     * @return CreatePaymentResult
     */
    public function getCreatePaymentResult(): CreatePaymentResult
    {
        $responseVariables = get_object_vars($this->getResponse());
        if (!array_key_exists('paymentResult', $responseVariables)) {
            return new CreatePaymentResult();
        }
        $paymentResult = $responseVariables['paymentResult'];
        if (!($paymentResult instanceof CreatePaymentResult)) {
            return new CreatePaymentResult();
        }
        return $paymentResult;
    }
}
