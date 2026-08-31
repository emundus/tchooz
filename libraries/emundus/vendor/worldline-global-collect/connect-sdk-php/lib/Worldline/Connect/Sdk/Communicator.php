<?php
namespace Worldline\Connect\Sdk;

use Exception;
use UnexpectedValueException;
use Worldline\Connect\Sdk\Authentication\Authenticator;
use Worldline\Connect\Sdk\Authentication\V1HMACAuthenticator;
use Worldline\Connect\Sdk\Communication\Connection;
use Worldline\Connect\Sdk\Communication\ConnectionResponse;
use Worldline\Connect\Sdk\Communication\DefaultConnection;
use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\MetadataProvider;
use Worldline\Connect\Sdk\Communication\MultipartDataObject;
use Worldline\Connect\Sdk\Communication\MultipartFormDataObject;
use Worldline\Connect\Sdk\Communication\RequestObject;
use Worldline\Connect\Sdk\Communication\ResponseBuilder;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\Communication\ResponseFactory;
use Worldline\Connect\Sdk\Domain\DataObject;
use Worldline\Connect\Sdk\Logging\CommunicatorLogger;

/**
 * Class Communicator
 *
 * @package Worldline\Connect\Sdk
 */
class Communicator
{
    const MIME_APPLICATION_JSON = 'application/json';

    /**
     * @var string
     */
    private string $apiEndpoint;

    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var MetadataProvider
     */
    private MetadataProvider $metadataProvider;

    /**
     * @var ResponseFactory|null
     */
    private ?ResponseFactory $responseFactory = null;

    /**
     * @param CommunicatorConfiguration $communicatorConfiguration
     * @param Authenticator|null        $authenticator
     * @param Connection|null           $connection
     */
    public function __construct(
        CommunicatorConfiguration $communicatorConfiguration,
        ?Authenticator            $authenticator = null,
        ?Connection               $connection = null
    ) {
        $this->apiEndpoint = $communicatorConfiguration->getApiEndpoint();
        $this->authenticator = $authenticator != null ? $authenticator : new V1HMACAuthenticator($communicatorConfiguration);
        $this->connection = $connection != null ? $connection : new DefaultConnection($communicatorConfiguration);
        $this->metadataProvider = new MetadataProvider($communicatorConfiguration);
    }

    /**
     * @param CommunicatorLogger $communicatorLogger
     *
     * @return void
     */
    public function enableLogging(CommunicatorLogger $communicatorLogger): void
    {
        $this->connection->enableLogging($communicatorLogger);
    }

    /**
     * @return void
     */
    public function disableLogging(): void
    {
        $this->connection->disableLogging();
    }

    /**
     * @param ResponseClassMap   $responseClassMap
     * @param string             $relativeUriPath
     * @param string             $clientMetaInfo
     * @param RequestObject|null $requestParameters
     * @param CallContext|null   $callContext
     *
     * @return DataObject|null
     * @throws Exception
     */
    public function get(
        ResponseClassMap $responseClassMap,
        string           $relativeUriPath,
        string           $clientMetaInfo = '',
        ?RequestObject   $requestParameters = null,
        ?CallContext     $callContext = null
    ): ?DataObject {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        $requestHeaders = $this->getRequestHeaders('GET', $relativeUriPathWithRequestParameters, null, $clientMetaInfo, $callContext);

        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };

        $this->connection->get(
            $this->apiEndpoint . $relativeUriPathWithRequestParameters,
            $requestHeaders,
            $responseHandler
        );
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            throw new ErrorResponseException($httpStatusCode, $response);
        }
        return $response;
    }

    /**
     * @param callable           $bodyHandler       Callable accepting a response body chunk and the response headers
     * @param ResponseClassMap   $responseClassMap  Used for error handling
     * @param string             $relativeUriPath
     * @param string             $clientMetaInfo
     * @param RequestObject|null $requestParameters
     * @param CallContext|null   $callContext
     *
     * @return void
     * @throws Exception
     */
    public function getWithBinaryResponse(
        callable         $bodyHandler,
        ResponseClassMap $responseClassMap,
        string           $relativeUriPath,
        string           $clientMetaInfo = '',
        ?RequestObject   $requestParameters = null,
        ?CallContext     $callContext = null
    ): void {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        $requestHeaders = $this->getRequestHeaders('GET', $relativeUriPathWithRequestParameters, null, $clientMetaInfo, $callContext);

        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder, $bodyHandler) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            if ($httpStatusCode >= 400) {
                $responseBuilder->appendBody($data);
            } else {
                call_user_func($bodyHandler, $data, $headers);
            }
        };

        $this->connection->get(
            $this->apiEndpoint . $relativeUriPathWithRequestParameters,
            $requestHeaders,
            $responseHandler
        );
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
            throw new ErrorResponseException($httpStatusCode, $response);
        }
    }

    /**
     * @param ResponseClassMap   $responseClassMap
     * @param string             $relativeUriPath
     * @param string             $clientMetaInfo
     * @param RequestObject|null $requestParameters
     * @param CallContext|null   $callContext
     *
     * @return DataObject|null
     * @throws Exception
     */
    public function delete(
        ResponseClassMap $responseClassMap,
        string           $relativeUriPath,
        string           $clientMetaInfo = '',
        ?RequestObject   $requestParameters = null,
        ?CallContext     $callContext = null
    ): ?DataObject {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        $requestHeaders = $this->getRequestHeaders('DELETE', $relativeUriPathWithRequestParameters, null, $clientMetaInfo, $callContext);

        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };

        $this->connection->delete(
            $this->apiEndpoint . $relativeUriPathWithRequestParameters,
            $requestHeaders,
            $responseHandler
        );
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            throw new ErrorResponseException($httpStatusCode, $response);
        }
        return $response;
    }

    /**
     * @param callable           $bodyHandler       Callable accepting a response body chunk and the response headers
     * @param ResponseClassMap   $responseClassMap  Used for error handling
     * @param string             $relativeUriPath
     * @param string             $clientMetaInfo
     * @param RequestObject|null $requestParameters
     * @param CallContext|null   $callContext
     *
     * @return void
     * @throws Exception
     */
    public function deleteWithBinaryResponse(
        callable         $bodyHandler,
        ResponseClassMap $responseClassMap,
        string           $relativeUriPath,
        string           $clientMetaInfo = '',
        ?RequestObject   $requestParameters = null,
        ?CallContext     $callContext = null
    ): void {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        $requestHeaders = $this->getRequestHeaders('DELETE', $relativeUriPathWithRequestParameters, null, $clientMetaInfo, $callContext);

        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder, $bodyHandler) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            if ($httpStatusCode >= 400) {
                $responseBuilder->appendBody($data);
            } else {
                call_user_func($bodyHandler, $data, $headers);
            }
        };

        $this->connection->delete(
            $this->apiEndpoint . $relativeUriPathWithRequestParameters,
            $requestHeaders,
            $responseHandler
        );
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
            throw new ErrorResponseException($httpStatusCode, $response);
        }
    }

    /**
     * @param ResponseClassMap                                            $responseClassMap
     * @param string                                                      $relativeUriPath
     * @param string                                                      $clientMetaInfo
     * @param DataObject|MultipartDataObject|MultipartFormDataObject|null $requestBodyObject
     * @param RequestObject|null                                          $requestParameters
     * @param CallContext|null                                            $callContext
     *
     * @return DataObject|null
     * @throws Exception
     */
    public function post(
        ResponseClassMap $responseClassMap,
        string           $relativeUriPath,
        string           $clientMetaInfo = '',
        $requestBodyObject = null,
        ?RequestObject   $requestParameters = null,
        ?CallContext     $callContext = null
    ): ?DataObject {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        if ($requestBodyObject instanceof MultipartFormDataObject) {
            $contentType = $requestBodyObject->getContentType();
            $requestBody = $requestBodyObject;
        } elseif ($requestBodyObject instanceof MultipartDataObject) {
            $multipart = $requestBodyObject->toMultipartFormDataObject();
            $contentType = $multipart->getContentType();
            $requestBody = $multipart;
        } elseif ($requestBodyObject instanceof DataObject || is_null($requestBodyObject)) {
            $contentType = static::MIME_APPLICATION_JSON;
            $requestBody = $requestBodyObject ? $requestBodyObject->toJson() : '';
        } else {
            throw new UnexpectedValueException('Unsupported request body');
        }
        $requestHeaders = $this->getRequestHeaders('POST', $relativeUriPathWithRequestParameters, $contentType, $clientMetaInfo, $callContext);

        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };

        $this->connection->post(
            $this->apiEndpoint . $relativeUriPathWithRequestParameters,
            $requestHeaders,
            $requestBody,
            $responseHandler
        );
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            throw new ErrorResponseException($httpStatusCode, $response);
        }
        return $response;
    }

    /**
     * @param callable                                                    $bodyHandler       Callable accepting a response body chunk
     *                                                                                       and the response headers
     * @param ResponseClassMap                                            $responseClassMap  Used for error handling
     * @param string                                                      $relativeUriPath
     * @param string                                                      $clientMetaInfo
     * @param DataObject|MultipartDataObject|MultipartFormDataObject|null $requestBodyObject
     * @param RequestObject|null                                          $requestParameters
     * @param CallContext|null                                            $callContext
     *
     * @return void
     * @throws Exception
     */
    public function postWithBinaryResponse(
        callable         $bodyHandler,
        ResponseClassMap $responseClassMap,
        string           $relativeUriPath,
        string           $clientMetaInfo = '',
        $requestBodyObject = null,
        ?RequestObject   $requestParameters = null,
        ?CallContext     $callContext = null
    ): void {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        if ($requestBodyObject instanceof MultipartFormDataObject) {
            $contentType = $requestBodyObject->getContentType();
            $requestBody = $requestBodyObject;
        } elseif ($requestBodyObject instanceof MultipartDataObject) {
            $multipart = $requestBodyObject->toMultipartFormDataObject();
            $contentType = $multipart->getContentType();
            $requestBody = $multipart;
        } elseif ($requestBodyObject instanceof DataObject || is_null($requestBodyObject)) {
            $contentType = static::MIME_APPLICATION_JSON;
            $requestBody = $requestBodyObject ? $requestBodyObject->toJson() : '';
        } else {
            throw new UnexpectedValueException('Unsupported request body');
        }
        $requestHeaders = $this->getRequestHeaders('POST', $relativeUriPathWithRequestParameters, $contentType, $clientMetaInfo, $callContext);

        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder, $bodyHandler) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            if ($httpStatusCode >= 400) {
                $responseBuilder->appendBody($data);
            } else {
                call_user_func($bodyHandler, $data, $headers);
            }
        };

        $this->connection->post(
            $this->apiEndpoint . $relativeUriPathWithRequestParameters,
            $requestHeaders,
            $requestBody,
            $responseHandler
        );
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
            throw new ErrorResponseException($httpStatusCode, $response);
        }
    }

    /**
     * @param ResponseClassMap                                            $responseClassMap
     * @param string                                                      $relativeUriPath
     * @param string                                                      $clientMetaInfo
     * @param DataObject|MultipartDataObject|MultipartFormDataObject|null $requestBodyObject
     * @param RequestObject|null                                          $requestParameters
     * @param CallContext|null                                            $callContext
     *
     * @return DataObject|null
     * @throws Exception
     */
    public function put(
        ResponseClassMap $responseClassMap,
        string           $relativeUriPath,
        string           $clientMetaInfo = '',
        $requestBodyObject = null,
        ?RequestObject   $requestParameters = null,
        ?CallContext     $callContext = null
    ): ?DataObject {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        if ($requestBodyObject instanceof MultipartFormDataObject) {
            $contentType = $requestBodyObject->getContentType();
            $requestBody = $requestBodyObject;
        } elseif ($requestBodyObject instanceof MultipartDataObject) {
            $multipart = $requestBodyObject->toMultipartFormDataObject();
            $contentType = $multipart->getContentType();
            $requestBody = $multipart;
        } elseif ($requestBodyObject instanceof DataObject || is_null($requestBodyObject)) {
            $contentType = static::MIME_APPLICATION_JSON;
            $requestBody = $requestBodyObject ? $requestBodyObject->toJson() : '';
        } else {
            throw new UnexpectedValueException('Unsupported request body');
        }
        $requestHeaders = $this->getRequestHeaders('PUT', $relativeUriPathWithRequestParameters, $contentType, $clientMetaInfo, $callContext);

        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };

        $this->connection->put(
            $this->apiEndpoint . $relativeUriPathWithRequestParameters,
            $requestHeaders,
            $requestBody,
            $responseHandler
        );
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            throw new ErrorResponseException($httpStatusCode, $response);
        }
        return $response;
    }

    /**
     * @param callable                                                    $bodyHandler       Callable accepting a response body chunk
     *                                                                                       and the response headers
     * @param ResponseClassMap                                            $responseClassMap  Used for error handling
     * @param string                                                      $relativeUriPath
     * @param string                                                      $clientMetaInfo
     * @param DataObject|MultipartDataObject|MultipartFormDataObject|null $requestBodyObject
     * @param RequestObject|null                                          $requestParameters
     * @param CallContext|null                                            $callContext
     *
     * @return void
     * @throws Exception
     */
    public function putWithBinaryResponse(
        callable         $bodyHandler,
        ResponseClassMap $responseClassMap,
        string           $relativeUriPath,
        string           $clientMetaInfo = '',
        $requestBodyObject = null,
        ?RequestObject   $requestParameters = null,
        ?CallContext     $callContext = null
    ): void {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        if ($requestBodyObject instanceof MultipartFormDataObject) {
            $contentType = $requestBodyObject->getContentType();
            $requestBody = $requestBodyObject;
        } elseif ($requestBodyObject instanceof MultipartDataObject) {
            $multipart = $requestBodyObject->toMultipartFormDataObject();
            $contentType = $multipart->getContentType();
            $requestBody = $multipart;
        } elseif ($requestBodyObject instanceof DataObject || is_null($requestBodyObject)) {
            $contentType = static::MIME_APPLICATION_JSON;
            $requestBody = $requestBodyObject ? $requestBodyObject->toJson() : '';
        } else {
            throw new UnexpectedValueException('Unsupported request body');
        }
        $requestHeaders = $this->getRequestHeaders('PUT', $relativeUriPathWithRequestParameters, $contentType, $clientMetaInfo, $callContext);

        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder, $bodyHandler) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            if ($httpStatusCode >= 400) {
                $responseBuilder->appendBody($data);
            } else {
                call_user_func($bodyHandler, $data, $headers);
            }
        };

        $this->connection->put(
            $this->apiEndpoint . $relativeUriPathWithRequestParameters,
            $requestHeaders,
            $requestBody,
            $responseHandler
        );
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
            throw new ErrorResponseException($httpStatusCode, $response);
        }
    }

    /**
     * @param ConnectionResponse $response
     * @param CallContext|null   $callContext
     *
     * @return void
     */
    protected function updateCallContext(ConnectionResponse $response, ?CallContext $callContext = null): void
    {
        if ($callContext) {
            $callContext->setIdempotenceRequestTimestamp(
                $response->getHeaderValue('X-GCS-Idempotence-Request-Timestamp')
            );
        }
    }

    /**
     * @param string             $relativeUriPath
     * @param RequestObject|null $requestParameters
     *
     * @return string
     * @throws Exception
     */
    protected function getRequestUri(string $relativeUriPath, ?RequestObject $requestParameters = null): string
    {
        return
            $this->apiEndpoint .
            $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
    }

    /**
     * @param string           $httpMethod
     * @param string           $relativeUriPathWithRequestParameters
     * @param string|null      $contentType
     * @param string           $clientMetaInfo
     * @param CallContext|null $callContext
     *
     * @return string[]
     */
    protected function getRequestHeaders(
        string       $httpMethod,
        string       $relativeUriPathWithRequestParameters,
        ?string      $contentType,
        string       $clientMetaInfo = '',
        ?CallContext $callContext = null
    ): array {
        $rfc2616Date = self::getRfc161Date();
        $requestHeaders = array();
        if ($contentType) {
            $requestHeaders['Content-Type'] = $contentType;
        }
        $requestHeaders['Date'] = $rfc2616Date;
        if ($clientMetaInfo) {
            $requestHeaders['X-GCS-ClientMetaInfo'] = $clientMetaInfo;
        }
        $requestHeaders['X-GCS-ServerMetaInfo'] = $this->metadataProvider->getServerMetaInfoValue();
        if ($callContext && strlen($callContext->getIdempotenceKey()) > 0) {
            $requestHeaders['X-GCS-Idempotence-Key'] = $callContext->getIdempotenceKey();
        }
        $requestHeaders['Authorization'] = $this->authenticator->getAuthorization(
            $httpMethod,
            $relativeUriPathWithRequestParameters,
            $requestHeaders
        );
        return $requestHeaders;
    }

    /**
     * @return string
     */
    protected static function getRfc161Date(): string
    {
        return gmdate('D, d M Y H:i:s T');
    }

    /**
     * @param string             $relativeUriPath
     * @param RequestObject|null $requestParameters
     *
     * @return string
     */
    protected function getRelativeUriPathWithRequestParameters(
        string         $relativeUriPath,
        ?RequestObject $requestParameters = null
    ): string {
        if (is_null($requestParameters)) {
            return $relativeUriPath;
        }
        $requestParameterObjectVars = get_object_vars($requestParameters);
        if (count($requestParameterObjectVars) == 0) {
            return $relativeUriPath;
        }
        $httpQuery = http_build_query($requestParameterObjectVars);
        if ($httpQuery === '') {
            return $relativeUriPath;
        }
        // remove [0], [1] etc. that are added if properties are arrays
        $httpQuery = preg_replace('/%5B\d+%5D/imU', '', $httpQuery);
        return $relativeUriPath . '?' . $httpQuery;
    }

    /**
     * @return ResponseFactory
     */
    protected function getResponseFactory(): ResponseFactory
    {
        if (is_null($this->responseFactory)) {
            $this->responseFactory = new ResponseFactory();
        }
        return $this->responseFactory;
    }
}
