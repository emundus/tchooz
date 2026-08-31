<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Hostedmandatemanagements;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\CallContext;
use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\InvalidResponseException;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\AuthorizationException;
use Worldline\Connect\Sdk\V1\Domain\CreateHostedMandateManagementRequest;
use Worldline\Connect\Sdk\V1\Domain\CreateHostedMandateManagementResponse;
use Worldline\Connect\Sdk\V1\Domain\GetHostedMandateManagementResponse;
use Worldline\Connect\Sdk\V1\ExceptionFactory;
use Worldline\Connect\Sdk\V1\IdempotenceException;
use Worldline\Connect\Sdk\V1\PlatformException;
use Worldline\Connect\Sdk\V1\ReferenceException;
use Worldline\Connect\Sdk\V1\ValidationException;

/**
 * Hosted Mandate Management client.
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Hostedmandatemanagements
 */
class HostedmandatemanagementsClient extends ApiResource
{
    /**
     * @var ExceptionFactory|null
     */
    private ?ExceptionFactory $responseExceptionFactory = null;

    /**
     * Resource /{merchantId}/hostedmandatemanagements - Create hosted mandate management
     *
     * @param CreateHostedMandateManagementRequest $body
     * @param CallContext|null                     $callContext
     *
     * @return CreateHostedMandateManagementResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/hostedmandatemanagements/create.html Create hosted mandate management
     */
    public function create(CreateHostedMandateManagementRequest $body, ?CallContext $callContext = null): CreateHostedMandateManagementResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\CreateHostedMandateManagementResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/hostedmandatemanagements'),
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
     * Resource /{merchantId}/hostedmandatemanagements/{hostedMandateManagementId} - Get hosted mandate management status
     *
     * @param string           $hostedMandateManagementId
     * @param CallContext|null $callContext
     *
     * @return GetHostedMandateManagementResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/hostedmandatemanagements/get.html Get hosted mandate management status
     */
    public function get(string $hostedMandateManagementId, ?CallContext $callContext = null): GetHostedMandateManagementResponse
    {
        $this->context['hostedMandateManagementId'] = $hostedMandateManagementId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\GetHostedMandateManagementResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/hostedmandatemanagements/{hostedMandateManagementId}'),
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
