<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1;

use Worldline\Connect\Sdk\Domain\DataObject;
use Worldline\Connect\Sdk\V1\Domain\RefundErrorResponse;
use Worldline\Connect\Sdk\V1\Domain\RefundResult;

/**
 * Class DeclinedRefundException
 *
 * @package Worldline\Connect\Sdk\V1
 */
class DeclinedRefundException extends ResponseException
{
    /**
     * @param int         $httpStatusCode
     * @param DataObject  $response
     * @param string|null $message
     */
    public function __construct(int $httpStatusCode, DataObject $response, ?string $message = null)
    {
        if (is_null($message)) {
            $message = DeclinedRefundException::buildMessage($response);
        }
        parent::__construct($httpStatusCode, $response, $message);
    }

    private static function buildMessage(DataObject $response): string
    {
        if ($response instanceof RefundErrorResponse && $response->refundResult != null) {
            $refundResult = $response->refundResult;
            return "declined refund '$refundResult->id' with status '$refundResult->status'";
        }
        return 'the Worldline Global Collect platform returned a declined refund response';
    }

    /**
     * @return RefundResult
     */
    public function getRefundResult(): RefundResult
    {
        $responseVariables = get_object_vars($this->getResponse());
        if (!array_key_exists('refundResult', $responseVariables)) {
            return new RefundResult();
        }
        $refundResult = $responseVariables['refundResult'];
        if (!($refundResult instanceof RefundResult)) {
            return new RefundResult();
        }
        return $refundResult;
    }
}
