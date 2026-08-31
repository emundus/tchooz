<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1;

use Worldline\Connect\Sdk\Domain\DataObject;
use Worldline\Connect\Sdk\V1\Domain\PayoutErrorResponse;
use Worldline\Connect\Sdk\V1\Domain\PayoutResult;

/**
 * Class DeclinedPayoutException
 *
 * @package Worldline\Connect\Sdk\V1
 */
class DeclinedPayoutException extends ResponseException
{
    /**
     * @param int         $httpStatusCode
     * @param DataObject  $response
     * @param string|null $message
     */
    public function __construct(int $httpStatusCode, DataObject $response, ?string $message = null)
    {
        if (is_null($message)) {
            $message = DeclinedPayoutException::buildMessage($response);
        }
        parent::__construct($httpStatusCode, $response, $message);
    }

    private static function buildMessage(DataObject $response): string
    {
        if ($response instanceof PayoutErrorResponse && $response->payoutResult != null) {
            $payoutResult = $response->payoutResult;
            return "declined payout '$payoutResult->id' with status '$payoutResult->status'";
        }
        return 'the Worldline Global Collect platform returned a declined payout response';
    }

    /**
     * @return PayoutResult
     */
    public function getPayoutResult(): PayoutResult
    {
        $responseVariables = get_object_vars($this->getResponse());
        if (!array_key_exists('payoutResult', $responseVariables)) {
            return new PayoutResult();
        }
        $payoutResult = $responseVariables['payoutResult'];
        if (!($payoutResult instanceof PayoutResult)) {
            return new PayoutResult();
        }
        return $payoutResult;
    }
}
