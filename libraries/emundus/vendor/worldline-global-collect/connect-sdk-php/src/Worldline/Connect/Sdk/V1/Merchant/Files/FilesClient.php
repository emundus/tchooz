<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Files;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\CallContext;
use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\InvalidResponseException;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\AuthorizationException;
use Worldline\Connect\Sdk\V1\ExceptionFactory;
use Worldline\Connect\Sdk\V1\IdempotenceException;
use Worldline\Connect\Sdk\V1\PlatformException;
use Worldline\Connect\Sdk\V1\ReferenceException;
use Worldline\Connect\Sdk\V1\ValidationException;

/**
 * Files client.
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Files
 */
class FilesClient extends ApiResource
{
    /**
     * @var ExceptionFactory|null
     */
    private ?ExceptionFactory $responseExceptionFactory = null;

    /**
     * Resource /{merchantId}/files/{fileId} - Retrieve File
     *
     * @param string           $fileId
     * @param callable         $bodyHandler Callable accepting a response body chunk and the response headers
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
     * @link   https://apireference.connect.worldline-solutions.com/fileserviceapi/v1/en_US/php/files/getFile.html Retrieve File
     */
    public function getFile(string $fileId, callable $bodyHandler, ?CallContext $callContext = null): void
    {
        $this->context['fileId'] = $fileId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            $this->getCommunicator()->getWithBinaryResponse(
                $bodyHandler,
                $responseClassMap,
                $this->instantiateUri('/files/v1/{merchantId}/files/{fileId}'),
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
