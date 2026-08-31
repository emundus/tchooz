<?php
namespace Worldline\Connect\Sdk\It;

use Worldline\Connect\Sdk\Communication\MultipartDataObject;
use Worldline\Connect\Sdk\Communication\MultipartFormDataObject;
use Worldline\Connect\Sdk\Communication\ResponseBuilder;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\Communicator;
use Worldline\Connect\Sdk\Domain\DataObject;
use Worldline\Connect\Sdk\Domain\UploadableFile;
use Worldline\Connect\Sdk\TestCase;

/**
 * @group multipart/form-data
 *
 */
class MultipartFormDataTest extends TestCase
{
    public function testMultipartFormDataUploadPostWithMultipartFormDataObjectWithResponse()
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $communicator = new Communicator($communicatorConfiguration);

        $multipart = new MultipartFormDataObject();
        $multipart->addFile('file', new UploadableFile('file.txt', 'file-content', 'text/plain'));
        $multipart->addValue('value', 'Hello World');

        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\It\HttpBinResponse';

        /** @var HttpBinResponse $response */
        $response = $communicator->post($responseClassMap, '/post', '', $multipart);

        $this->assertEquals('Hello World', $response->form->value);
        $this->assertEquals('file-content', $response->files->file);
    }

    public function testMultipartFormDataUploadPostWithMultipartDataObjectWithResponse()
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $communicator = new Communicator($communicatorConfiguration);

        $multipart = new MultipartFormDataObject();
        $multipart->addFile('file', new UploadableFile('file.txt', 'file-content', 'text/plain'));
        $multipart->addValue('value', 'Hello World');

        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\It\HttpBinResponse';

        /** @var HttpBinResponse $response */
        $response = $communicator->post($responseClassMap, '/post', '', new MultipartFormDataWrapper($multipart));

        $this->assertEquals('Hello World', $response->form->value);
        $this->assertEquals('file-content', $response->files->file);
    }

    public function testMultipartFormDataUploadPostWithMultipartFormDataObjectWithCallable()
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $communicator = new Communicator($communicatorConfiguration);

        $multipart = new MultipartFormDataObject();
        $multipart->addFile('file', new UploadableFile('file.txt', 'file-content', 'text/plain'));
        $multipart->addValue('value', 'Hello World');

        $responseClassMap = new ResponseClassMap();

        $responseBuilder = new ResponseBuilder();
        $bodyHandler = function ($data, $headers) use ($responseBuilder) {
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };

        $communicator->postWithBinaryResponse($bodyHandler, $responseClassMap, '/post', '', $multipart);

        $response = new HttpBinResponse();
        $response->fromJson($responseBuilder->getResponse()->getBody());

        $this->assertEquals('Hello World', $response->form->value);
        $this->assertEquals('file-content', $response->files->file);
    }

    public function testMultipartFormDataUploadPostWithMultipartDataObjectWithCallable()
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $communicator = new Communicator($communicatorConfiguration);

        $multipart = new MultipartFormDataObject();
        $multipart->addFile('file', new UploadableFile('file.txt', 'file-content', 'text/plain'));
        $multipart->addValue('value', 'Hello World');

        $responseClassMap = new ResponseClassMap();

        $responseBuilder = new ResponseBuilder();
        $bodyHandler = function ($data, $headers) use ($responseBuilder) {
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };

        $communicator->postWithBinaryResponse($bodyHandler, $responseClassMap, '/post', '', new MultipartFormDataWrapper($multipart));

        $response = new HttpBinResponse();
        $response->fromJson($responseBuilder->getResponse()->getBody());

        $this->assertEquals('Hello World', $response->form->value);
        $this->assertEquals('file-content', $response->files->file);
    }

    public function testMultipartFormDataUploadPutWithMultipartFormDataObjectWithResponse()
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $communicator = new Communicator($communicatorConfiguration);

        $multipart = new MultipartFormDataObject();
        $multipart->addFile('file', new UploadableFile('file.txt', 'file-content', 'text/plain'));
        $multipart->addValue('value', 'Hello World');

        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\It\HttpBinResponse';

        /** @var HttpBinResponse $response */
        $response = $communicator->put($responseClassMap, '/put', '', $multipart);

        $this->assertEquals('Hello World', $response->form->value);
        $this->assertEquals('file-content', $response->files->file);
    }

    public function testMultipartFormDataUploadPutWithMultipartDataObjectWithResponse()
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $communicator = new Communicator($communicatorConfiguration);

        $multipart = new MultipartFormDataObject();
        $multipart->addFile('file', new UploadableFile('file.txt', 'file-content', 'text/plain'));
        $multipart->addValue('value', 'Hello World');

        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\It\HttpBinResponse';

        /** @var HttpBinResponse $response */
        $response = $communicator->put($responseClassMap,  '/put', '', new MultipartFormDataWrapper($multipart));

        $this->assertEquals('Hello World', $response->form->value);
        $this->assertEquals('file-content', $response->files->file);
    }

    public function testMultipartFormDataUploadPutWithMultipartFormDataObjectWithCallable()
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $communicator = new Communicator($communicatorConfiguration);

        $multipart = new MultipartFormDataObject();
        $multipart->addFile('file', new UploadableFile('file.txt', 'file-content', 'text/plain'));
        $multipart->addValue('value', 'Hello World');

        $responseClassMap = new ResponseClassMap();

        $responseBuilder = new ResponseBuilder();
        $bodyHandler = function ($data, $headers) use ($responseBuilder) {
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };

        $communicator->putWithBinaryResponse($bodyHandler, $responseClassMap, '/put', '', $multipart);

        $response = new HttpBinResponse();
        $response->fromJson($responseBuilder->getResponse()->getBody());

        $this->assertEquals('Hello World', $response->form->value);
        $this->assertEquals('file-content', $response->files->file);
    }

    public function testMultipartFormDataUploadPutWithMultipartDataObjectWithCallable()
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $communicator = new Communicator($communicatorConfiguration);

        $multipart = new MultipartFormDataObject();
        $multipart->addFile('file', new UploadableFile('file.txt', 'file-content', 'text/plain'));
        $multipart->addValue('value', 'Hello World');

        $responseClassMap = new ResponseClassMap();

        $responseBuilder = new ResponseBuilder();
        $bodyHandler = function ($data, $headers) use ($responseBuilder) {
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };

        $communicator->putWithBinaryResponse($bodyHandler, $responseClassMap, '/put', '', new MultipartFormDataWrapper($multipart));

        $response = new HttpBinResponse();
        $response->fromJson($responseBuilder->getResponse()->getBody());

        $this->assertEquals('Hello World', $response->form->value);
        $this->assertEquals('file-content', $response->files->file);
    }
}

class HttpBinResponse extends DataObject
{
    public ?object $form;
    public ?object $files;

    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->form)) {
            $object->form = $this->form;
        }
        if (!is_null($this->files)) {
            $object->files = $this->files;
        }
        return $object;
    }

    public function fromObject(object $object): HttpBinResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'form')) {
            $this->form = $object->form;
        }
        if (property_exists($object, 'files')) {
            $this->files = $object->files;
        }
        return $this;
    }
}

class MultipartFormDataWrapper extends MultipartDataObject
{
    private MultipartFormDataObject $multipart;

    public function __construct(MultipartFormDataObject $multipart)
    {
        $this->multipart = $multipart;
    }

    public function toMultipartFormDataObject(): MultipartFormDataObject
    {
        return $this->multipart;
    }
}
