<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Webhooks;

use Worldline\Connect\Sdk\Communication\ConnectionResponse;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\Communication\ResponseFactory;
use Worldline\Connect\Sdk\V1\Domain\WebhooksEvent;
use Worldline\Connect\Sdk\Webhooks\ApiVersionMismatchException;
use Worldline\Connect\Sdk\Webhooks\SecretKeyStore;
use Worldline\Connect\Sdk\Webhooks\SignatureValidationException;
use Worldline\Connect\Sdk\Webhooks\SignatureValidator;

/**
 * Class WebhooksHelper
 *
 * @package Worldline\Connect\Sdk\V1\Webhooks
 */
class WebhooksHelper
{
    /**
     * @var SignatureValidator
     */
    private SignatureValidator $signatureValidator;

    /**
     * @var ResponseFactory|null
     */
    private ?ResponseFactory $responseFactory = null;

    /**
     * @param SecretKeyStore $secretKeyStore
     */
    public function __construct(SecretKeyStore $secretKeyStore)
    {
        $this->signatureValidator = new SignatureValidator($secretKeyStore);
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

    /**
     * Unmarshals the given input stream that contains the body,
     * while also validating its contents using the given request headers.
     *
     * @param string $body
     * @param array  $requestHeaders
     *
     * @return WebhooksEvent
     * @throws SignatureValidationException
     * @throws ApiVersionMismatchException
     */
    public function unmarshal(string $body, array $requestHeaders): WebhooksEvent
    {
        $this->signatureValidator->validate($body, $requestHeaders);

        $response = new ConnectionResponse(200, array('Content-Type' => 'application/json'), $body);
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->addResponseClassName(200, '\Worldline\Connect\Sdk\V1\Domain\WebhooksEvent');

        /**
         * @var WebhooksEvent $event
         */
        $event = $this->getResponseFactory()->createResponse($response, $responseClassMap);
        $this->validateApiVersion($event);
        return $event;
    }

    private function validateApiVersion(WebhooksEvent $event): void
    {
        if ('v1' !== $event->apiVersion) {
            throw new ApiVersionMismatchException($event->apiVersion, 'v1');
        }
    }
}
