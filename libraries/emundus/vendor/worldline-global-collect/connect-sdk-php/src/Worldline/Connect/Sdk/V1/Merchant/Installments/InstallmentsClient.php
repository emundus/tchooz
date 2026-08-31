<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Installments;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\CallContext;
use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\InvalidResponseException;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\AuthorizationException;
use Worldline\Connect\Sdk\V1\Domain\GetInstallmentRequest;
use Worldline\Connect\Sdk\V1\Domain\InstallmentOptionsResponse;
use Worldline\Connect\Sdk\V1\ExceptionFactory;
use Worldline\Connect\Sdk\V1\IdempotenceException;
use Worldline\Connect\Sdk\V1\PlatformException;
use Worldline\Connect\Sdk\V1\ReferenceException;
use Worldline\Connect\Sdk\V1\ValidationException;

/**
 * Installments client.
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Installments
 */
class InstallmentsClient extends ApiResource
{
    /**
     * @var ExceptionFactory|null
     */
    private ?ExceptionFactory $responseExceptionFactory = null;

    /**
     * Resource /{merchantId}/installments/getInstallmentsInfo - Get installment information
     *
     * @param GetInstallmentRequest $body
     * @param CallContext|null      $callContext
     *
     * @return InstallmentOptionsResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/installments/getInstallmentsInfo.html Get installment information
     */
    public function getInstallmentsInfo(GetInstallmentRequest $body, ?CallContext $callContext = null): InstallmentOptionsResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\InstallmentOptionsResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/installments/getInstallmentsInfo'),
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
