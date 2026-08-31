<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Productgroups;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\CallContext;
use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\InvalidResponseException;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\AuthorizationException;
use Worldline\Connect\Sdk\V1\Domain\DeviceFingerprintRequest;
use Worldline\Connect\Sdk\V1\Domain\DeviceFingerprintResponse;
use Worldline\Connect\Sdk\V1\Domain\PaymentProductGroupResponse;
use Worldline\Connect\Sdk\V1\Domain\PaymentProductGroups;
use Worldline\Connect\Sdk\V1\ExceptionFactory;
use Worldline\Connect\Sdk\V1\IdempotenceException;
use Worldline\Connect\Sdk\V1\PlatformException;
use Worldline\Connect\Sdk\V1\ReferenceException;
use Worldline\Connect\Sdk\V1\ValidationException;

/**
 * Product Groups client.
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Productgroups
 */
class ProductgroupsClient extends ApiResource
{
    /**
     * @var ExceptionFactory|null
     */
    private ?ExceptionFactory $responseExceptionFactory = null;

    /**
     * Resource /{merchantId}/productgroups - Get payment product groups
     *
     * @param FindProductgroupsParams $query
     * @param CallContext|null        $callContext
     *
     * @return PaymentProductGroups
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/productgroups/find.html Get payment product groups
     */
    public function find(FindProductgroupsParams $query, ?CallContext $callContext = null): PaymentProductGroups
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\PaymentProductGroups';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/productgroups'),
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
     * Resource /{merchantId}/productgroups/{paymentProductGroupId} - Get payment product group
     *
     * @param string                $paymentProductGroupId
     * @param GetProductgroupParams $query
     * @param CallContext|null      $callContext
     *
     * @return PaymentProductGroupResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/productgroups/get.html Get payment product group
     */
    public function get(string $paymentProductGroupId, GetProductgroupParams $query, ?CallContext $callContext = null): PaymentProductGroupResponse
    {
        $this->context['paymentProductGroupId'] = $paymentProductGroupId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\PaymentProductGroupResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/productgroups/{paymentProductGroupId}'),
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
     * Resource /{merchantId}/productgroups/{paymentProductGroupId}/deviceFingerprint - Get device fingerprint
     *
     * @param string                   $paymentProductGroupId
     * @param DeviceFingerprintRequest $body
     * @param CallContext|null         $callContext
     *
     * @return DeviceFingerprintResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/productgroups/deviceFingerprint.html Get device fingerprint
     */
    public function deviceFingerprint(string $paymentProductGroupId, DeviceFingerprintRequest $body, ?CallContext $callContext = null): DeviceFingerprintResponse
    {
        $this->context['paymentProductGroupId'] = $paymentProductGroupId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\DeviceFingerprintResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/productgroups/{paymentProductGroupId}/deviceFingerprint'),
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
