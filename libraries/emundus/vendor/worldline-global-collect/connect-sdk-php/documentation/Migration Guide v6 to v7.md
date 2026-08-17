# Migrating from version 6.x.x to 7.0.0

## Dependency

The Composer project name has changed to `worldline-global-collect/connect-sdk-php`. You need to update the dependencies in your `composer.json` file:

```json
    "require": {
        "worldline-global-collect/connect-sdk-php": "~7.0.0"
    }
```

## Use statements

All namespaces have been renamed, and some classes and interfaces have moved to different namespaces. Each API version now has its own namespace structure that contains all classes specific for that version, including classes like `APIError`, exceptions and webhooks classes.

You need to change your use statements as follows:

| Previous namespace                   | Class / interface            | New namespace                        | Notes |
|--------------------------------------|------------------------------|--------------------------------------|-------|
| Ingenico\Connect\Sdk                 | ApiException                 | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | AuthorizationException       | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | BodyObfuscator               | Worldline\Connect\Sdk\Logging        |
| Ingenico\Connect\Sdk                 | CommunicatorLogger           | Worldline\Connect\Sdk\Logging        |
| Ingenico\Connect\Sdk                 | CommunicatorLoggerHelper     | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | Connection                   | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | ConnectionResponse           | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | DataObject                   | Worldline\Connect\Sdk\Domain         |
| Ingenico\Connect\Sdk                 | DeclinedPaymentException     | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | DeclinedPayoutException      | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | DeclinedRefundException      | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | DefaultConnection            | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | GlobalCollectException       | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | HeaderObfuscator             | Worldline\Connect\Sdk\Logging        |
| Ingenico\Connect\Sdk                 | HttpHeaderHelper             | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | HttpObfuscator               | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | IdempotenceException         | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | InvalidResponseException     | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | MultipartDataObject          | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | MultipartFormDataObject      | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | ReferenceException           | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | RequestObject                | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | ResourceLogger               | Worldline\Connect\Sdk\Logging        |
| Ingenico\Connect\Sdk                 | ResponseBuilder              | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | ResponseClassMap             | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | ResponseException            | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | ResponseExceptionFactory     | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | ResponseFactory              | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | ResponseHeaderBuilder        | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | SplFileObjectLogger          | Worldline\Connect\Sdk\Logging        |
| Ingenico\Connect\Sdk                 | UploadableFile               | Worldline\Connect\Sdk\Domain         |
| Ingenico\Connect\Sdk                 | UuidGenerator                | Worldline\Connect\Sdk\Communication  |
| Ingenico\Connect\Sdk                 | ValidationException          | Worldline\Connect\Sdk\V1             |
| Ingenico\Connect\Sdk                 | ValueObfuscator              | Worldline\Connect\Sdk\Logging        |
| Ingenico\Connect\Sdk                 | All other classes            | Worldline\Connect\Sdk                |
| Ingenico\Connect\Sdk\Domain\MetaData | ShoppingCartExtension        | Worldline\Connect\Sdk\Domain         |
| Ingenico\Connect\Sdk\Domain\*        | All other domain classes     | Worldline\Connect\Sdk\V1\Domain      | All domain classes for version 1 of the API are now in the same namespace |
| Ingenico\Connect\Sdk\Merchant\*      | All classes                  | Worldline\Connect\Sdk\V1\Merchant\*  | The same namespace structure is used |
| Ingenico\Connect\Sdk\Webhooks        | WebhooksHelper               | Worldline\Connect\Sdk\V1\Webhooks    |
| Ingenico\Connect\Sdk\Webhooks        | All other classes            | Worldline\Connect\Sdk\Webhooks       |

## API calls

Method `merchant` of class `Client` has moved to new class `V1Client`. Instances of this class are available through method `v1` of class `Client`. You need to replace all occurrences of `->merchant` with `->v1()->merchant` in your code. For instance:

```php
$response = $client->v1()->merchant($merchantId)->services()->testconnection();
```

## API version

Constant `API_VERSION` of class `Client` has been removed. You need to replace all occurrences in your code with string literal `'v1'`.

## Communicator

The constructor of class `Communicator` now takes a required `CommunicatorConfiguration` and an optional `Authenticator` and `Connection`. If the `Authenticator` or `Connection` is not provided a new instance will be created using the given `CommunicatorConfiguration`. That means you only need to provide an `Authenticator` or `Connection` if the defaults aren't sufficient. You need to change all calls to the constructor in your code with one of the following:

* To use a `DefaultConnection` instance, provide only the `CommunicatorConfiguration`:
    ```php
    $communicator = new Communicator($communicatorConfiguration);
    ```

* To use a custom `Connection` implementation, provide the `CommunicatorConfiguration`, `null` for the `Authenticator`, and the `Connection`:
    ```php
    $communicator = new Communicator($connection, null, $communicatorConfiguration);
    ```

### Accessing fields

The `getConnection`, `setConnection`, `getCommunicatorConfiguration` and `setCommunicatorConfiguration` methods have been removed. You need to capture the constructor arguments in your code instead of calling the `getConnection` and `getCommunicatorConfiguration` methods, and you need to create a new `Communicator` instance in your code instead of calling the `setConnection` and `setCommunicatorConfiguration` methods.

### HTTP methods

The HTTP methods of class `Communicator` no longer use a `ResponseExceptionFactory` but instead throw instances of new class `ErrorResponseException`. Mapping these to other exceptions is now the response of the calling class. The SDK's own client classes will take care of this for you, but if you use these methods directly you need to change all occurrences in your code. For instance:

```php
try {
    return $communicator->get($responseClassMap, $uri, $clientMetaInfo, null, $callContext);
} catch (ErrorResponseException $e) {
    throw $responseExceptionFactory->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
}
```

The `getResponseExceptionFactory` method has been removed, as it is no longer necessary.

### HTTP methods with binary responses

The `BodyHandler` parameter of methods `getWithBinaryResponse`, `deleteWithBinaryResponse`, `postWithBinaryResponse` and `putWithBinaryResponse` is required and is therefore now the first parameter. You need to move the argument from the end to the start of the argument list in all occurrences in your code.

## CommunicatorConfiguration

The integrator is now required. You need to make sure a non-empty integrator is set on any `CommunicatorConfiguration` instance you create.

## DeclinedPaymentException

Method `getPaymentResult` of class `DeclinedPaymentException` has been renamed to `getCreatePaymentResult` to match the return type. You need to change all occurrences in your code with the new name.

## GlobalCollectException

Class `GlobalCollectException` has been renamed to `PlatformException`. You need to replace all occurrences in your code with the new name.

## Resource

Class `Resource` has been renamed to `ApiResource`, to avoid IDEs confusing it with the built-in `resource` type. You need to replace all occurrences in your code with the new name.

## Client classes

All client classes have been renamed to end with `Client`. For instance, class `Merchant` is renamed to `MerchantClient`, and class `Payments` is renamed to `PaymentsClient`. You need to replace all occurrences in your code with the new names.

## Communication

### Connection

The `ProxyConfiguration` parameter has been removed from methods of interface `Connection`. This should instead be provided when creating instances of implementing classes. You need to change all custom implementations to remove the parameter from the implemented methods and store an optional `ProxyConfiguration` in a field instead.

### ConnectionResponse and DefaultConnectionResponse

Interface `ConnectionResponse` and class `DefaultConnectionResponse` have been combined into class `ConnectionResponse`. You need to replace all occurrences of class `DefaultConnectionResponse` in your code with `ConnectionResponse`. You need also need to change all custom implementations of interface `ConnectionResponse` to extend the combined class instead of implement it.

### DefaultConnection

The constructor of class `DefaultConnection` now takes an optional `CommunicatorConfiguration` instead of optional connect and read timeouts. You need to replace all occurrences in your code that set a connect or read timeout to use the `CommunicatorConfiguration` instead. For instance:

```php
$communicatorConfiguration->setConnectTimeout($connectTimeout);
$communicatorConfiguration->setReadTimeout($readTimeout);
$connection = new DefaultConnection($communicatorConfiguration);
```

Alternatively, you can omit the `DefaultConnection` and let the `Communicator` create it:

```php
$communicatorConfiguration->setConnectTimeout($connectTimeout);
$communicatorConfiguration->setReadTimeout($readTimeout);
$communicator = new Communicator($communicatorConfiguration);
```

### HttpHeaderHelper

Class `HttpHeaderHelper` has been turned into a utility class. Its methods are now static and it can no longer be instantiated. You need to replace all occurrences in your code with static method calls, and remove any instance.

### InvalidResponseException

Field `response` of class `InvalidResponseException` has been made private. You need to replace all occurrences in your code with calls to the `getResponse` method.

### RequestHeaderGenerator

Class `RequestHeaderGenerator` has been replaced with new interface `Authenticator`, new classes `V1HMACAuthenticator` and `MetadataProvider`, and new methods in class `Communicator`.

* To get values for the `X-GCS-ServerMetaInfo` header you need to change your code to use instances of class `MetaDataProvider` and its `getServerMetaInfoValue` method.
* To get values for the `X-GCS-Idempotence-Key` header you need to change your code to use the result of calling `getIdempotenceKey` on the `CallContext` instance.
* To get values for the `Authorization` header you need to change your code to use instances of class `V1HMACAuthenticator` and its `getAuthorization` method.
* To get values for the `Date` header you need to change your code to use `gmdate('D, d M Y H:i:s T')`.

### ResponseClassMap

Field `responseClassNamesByHttpStatusCode` of class `ResponseClassMap` has been made private. You need to replace all occurrences in your code with the `addResponseClassName` and `getResponseClassName` methods.

### ResponseExceptionFactory

Class `ResponseExceptionFactory` has been renamed to `ExceptionFactory`. You need to replace all occurrences in your code with the new name.

### ResponseFactory

Since class `ErrorResponse` is specific for version 1 of the API, class `ResponseFactory` no longer defaults to it. The `ResponseClassMap` argument must have a value for its `defaultErrorResponseClassName` field instead.

## JSON marshalling

### DataObject

Method `getLastJsonDecodeErrorString` of class `DataObject` has moved to new class `JSONUtil`. You need to change all occurrences in custom `DataObject` sub classes to call the method on `JSONUtil` instead.

## Webhooks

### WebhooksHelper

Method `validate` of class `WebhooksHelper` has been removed. You need to replace all occurrences in your code with a `SignatureValidator`.
