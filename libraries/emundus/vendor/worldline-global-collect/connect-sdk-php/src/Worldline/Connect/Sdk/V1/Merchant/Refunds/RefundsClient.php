<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Refunds;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\CallContext;
use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\InvalidResponseException;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\AuthorizationException;
use Worldline\Connect\Sdk\V1\Domain\ApproveRefundRequest;
use Worldline\Connect\Sdk\V1\Domain\FindRefundsResponse;
use Worldline\Connect\Sdk\V1\Domain\RefundResponse;
use Worldline\Connect\Sdk\V1\ExceptionFactory;
use Worldline\Connect\Sdk\V1\IdempotenceException;
use Worldline\Connect\Sdk\V1\PlatformException;
use Worldline\Connect\Sdk\V1\ReferenceException;
use Worldline\Connect\Sdk\V1\ValidationException;

/**
 * Refunds client.
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Refunds
 */
class RefundsClient extends ApiResource
{
    /**
     * @var ExceptionFactory|null
     */
    private ?ExceptionFactory $responseExceptionFactory = null;

    /**
     * Resource /{merchantId}/refunds - Find refunds
     *
     * @param FindRefundsParams $query
     * @param CallContext|null  $callContext
     *
     * @return FindRefundsResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/refunds/find.html Find refunds
     */
    public function find(FindRefundsParams $query, ?CallContext $callContext = null): FindRefundsResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\FindRefundsResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/refunds'),
                $this->getClientMetaInfo(),
                $query,
                $callContext
            );
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException(
                $e->getHttpStatusCode(),
                $e->getErrorResponse(),
                $callContext
            );
        }
    }

    /**
     * Resource /{merchantId}/refunds/{refundId} - Get refund
     *
     * @param string           $refundId
     * @param CallContext|null $callContext
     *
     * @return RefundResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/refunds/get.html Get refund
     */
    public function get(string $refundId, ?CallContext $callContext = null): RefundResponse
    {
        $this->context['refundId'] = $refundId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\RefundResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/refunds/{refundId}'),
                $this->getClientMetaInfo(),
                null,
                $callContext
            );
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException(
                $e->getHttpStatusCode(),
                $e->getErrorResponse(),
                $callContext
            );
        }
    }

    /**
     * Resource /{merchantId}/refunds/{refundId}/approve - Approve refund
     *
     * @param string               $refundId
     * @param ApproveRefundRequest $body
     * @param CallContext|null     $callContext
     *
     * @return void
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/refunds/approve.html Approve refund
     */
    public function approve(string $refundId, ApproveRefundRequest $body, ?CallContext $callContext = null): void
    {
        $this->context['refundId'] = $refundId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/refunds/{refundId}/approve'),
                $this->getClientMetaInfo(),
                $body,
                null,
                $callContext
            );
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException(
                $e->getHttpStatusCode(),
                $e->getErrorResponse(),
                $callContext
            );
        }
    }

    /**
     * Resource /{merchantId}/refunds/{refundId}/cancel - Cancel refund
     *
     * @param string           $refundId
     * @param CallContext|null $callContext
     *
     * @return void
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/refunds/cancel.html Cancel refund
     */
    public function cancel(string $refundId, ?CallContext $callContext = null): void
    {
        $this->context['refundId'] = $refundId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/refunds/{refundId}/cancel'),
                $this->getClientMetaInfo(),
                null,
                null,
                $callContext
            );
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException(
                $e->getHttpStatusCode(),
                $e->getErrorResponse(),
                $callContext
            );
        }
    }

    /**
     * Resource /{merchantId}/refunds/{refundId}/cancelapproval - Undo approve refund
     *
     * @param string           $refundId
     * @param CallContext|null $callContext
     *
     * @return void
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/refunds/cancelapproval.html Undo approve refund
     */
    public function cancelapproval(string $refundId, ?CallContext $callContext = null): void
    {
        $this->context['refundId'] = $refundId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/refunds/{refundId}/cancelapproval'),
                $this->getClientMetaInfo(),
                null,
                null,
                $callContext
            );
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException(
                $e->getHttpStatusCode(),
                $e->getErrorResponse(),
                $callContext
            );
        }
    }

    /**
     * @return ExceptionFactory
     */
    private function getResponseExceptionFactory(): ExceptionFactory
    {
        if (is_null($this->responseExceptionFactory)) {
            $this->responseExceptionFactory = new ExceptionFactory();
        }
        return $this->responseExceptionFactory;
    }
}
