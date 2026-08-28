<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Payments;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\CallContext;
use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\InvalidResponseException;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\AuthorizationException;
use Worldline\Connect\Sdk\V1\DeclinedPaymentException;
use Worldline\Connect\Sdk\V1\DeclinedRefundException;
use Worldline\Connect\Sdk\V1\Domain\ApprovePaymentRequest;
use Worldline\Connect\Sdk\V1\Domain\CancelApprovalPaymentResponse;
use Worldline\Connect\Sdk\V1\Domain\CancelPaymentResponse;
use Worldline\Connect\Sdk\V1\Domain\CapturePaymentRequest;
use Worldline\Connect\Sdk\V1\Domain\CaptureResponse;
use Worldline\Connect\Sdk\V1\Domain\CapturesResponse;
use Worldline\Connect\Sdk\V1\Domain\CompletePaymentRequest;
use Worldline\Connect\Sdk\V1\Domain\CompletePaymentResponse;
use Worldline\Connect\Sdk\V1\Domain\CreateDisputeRequest;
use Worldline\Connect\Sdk\V1\Domain\CreatePaymentRequest;
use Worldline\Connect\Sdk\V1\Domain\CreatePaymentResponse;
use Worldline\Connect\Sdk\V1\Domain\CreateTokenResponse;
use Worldline\Connect\Sdk\V1\Domain\DeviceFingerprintDetails;
use Worldline\Connect\Sdk\V1\Domain\DisputeResponse;
use Worldline\Connect\Sdk\V1\Domain\DisputesResponse;
use Worldline\Connect\Sdk\V1\Domain\FindPaymentsResponse;
use Worldline\Connect\Sdk\V1\Domain\PaymentApprovalResponse;
use Worldline\Connect\Sdk\V1\Domain\PaymentResponse;
use Worldline\Connect\Sdk\V1\Domain\RefundRequest;
use Worldline\Connect\Sdk\V1\Domain\RefundResponse;
use Worldline\Connect\Sdk\V1\Domain\RefundsResponse;
use Worldline\Connect\Sdk\V1\Domain\ThirdPartyStatusResponse;
use Worldline\Connect\Sdk\V1\Domain\TokenizePaymentRequest;
use Worldline\Connect\Sdk\V1\ExceptionFactory;
use Worldline\Connect\Sdk\V1\IdempotenceException;
use Worldline\Connect\Sdk\V1\PlatformException;
use Worldline\Connect\Sdk\V1\ReferenceException;
use Worldline\Connect\Sdk\V1\ValidationException;

/**
 * Payments client.
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Payments
 */
class PaymentsClient extends ApiResource
{
    /**
     * @var ExceptionFactory|null
     */
    private ?ExceptionFactory $responseExceptionFactory = null;

    /**
     * Resource /{merchantId}/payments - Create payment
     *
     * @param CreatePaymentRequest $body
     * @param CallContext|null     $callContext
     *
     * @return CreatePaymentResponse
     * @throws DeclinedPaymentException
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/create.html Create payment
     */
    public function create(CreatePaymentRequest $body, ?CallContext $callContext = null): CreatePaymentResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\CreatePaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\PaymentErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments'),
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
     * Resource /{merchantId}/payments - Find payments
     *
     * @param FindPaymentsParams $query
     * @param CallContext|null   $callContext
     *
     * @return FindPaymentsResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/find.html Find payments
     */
    public function find(FindPaymentsParams $query, ?CallContext $callContext = null): FindPaymentsResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\FindPaymentsResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments'),
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
     * Resource /{merchantId}/payments/{paymentId} - Get payment
     *
     * @param string           $paymentId
     * @param GetPaymentParams $query
     * @param CallContext|null $callContext
     *
     * @return PaymentResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/get.html Get payment
     */
    public function get(string $paymentId, GetPaymentParams $query, ?CallContext $callContext = null): PaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\PaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}'),
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
     * Resource /{merchantId}/payments/{paymentId}/complete - Complete payment
     *
     * @param string                 $paymentId
     * @param CompletePaymentRequest $body
     * @param CallContext|null       $callContext
     *
     * @return CompletePaymentResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/complete.html Complete payment
     */
    public function complete(string $paymentId, CompletePaymentRequest $body, ?CallContext $callContext = null): CompletePaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\CompletePaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/complete'),
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
     * Resource /{merchantId}/payments/{paymentId}/thirdpartystatus - Third party status poll
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return ThirdPartyStatusResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/thirdPartyStatus.html Third party status poll
     */
    public function thirdPartyStatus(string $paymentId, ?CallContext $callContext = null): ThirdPartyStatusResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ThirdPartyStatusResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/thirdpartystatus'),
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
     * Resource /{merchantId}/payments/{paymentId}/tokenize - Create a token from payment
     *
     * @param string                 $paymentId
     * @param TokenizePaymentRequest $body
     * @param CallContext|null       $callContext
     *
     * @return CreateTokenResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/tokenize.html Create a token from payment
     */
    public function tokenize(string $paymentId, TokenizePaymentRequest $body, ?CallContext $callContext = null): CreateTokenResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\CreateTokenResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/tokenize'),
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
     * Resource /{merchantId}/payments/{paymentId}/processchallenged - Approves challenged payment
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return PaymentResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/processchallenged.html Approves challenged payment
     */
    public function processchallenged(string $paymentId, ?CallContext $callContext = null): PaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\PaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/processchallenged'),
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
     * Resource /{merchantId}/payments/{paymentId}/approve - Approve payment
     *
     * @param string                $paymentId
     * @param ApprovePaymentRequest $body
     * @param CallContext|null      $callContext
     *
     * @return PaymentApprovalResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/approve.html Approve payment
     */
    public function approve(string $paymentId, ApprovePaymentRequest $body, ?CallContext $callContext = null): PaymentApprovalResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\PaymentApprovalResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/approve'),
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
     * Resource /{merchantId}/payments/{paymentId}/capture - Capture payment
     *
     * @param string                $paymentId
     * @param CapturePaymentRequest $body
     * @param CallContext|null      $callContext
     *
     * @return CaptureResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/capture.html Capture payment
     */
    public function capture(string $paymentId, CapturePaymentRequest $body, ?CallContext $callContext = null): CaptureResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\CaptureResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/capture'),
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
     * Resource /{merchantId}/payments/{paymentId}/finalizecapture - Finalize capture
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return PaymentResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/finalizecapture.html Finalize capture
     */
    public function finalizecapture(string $paymentId, ?CallContext $callContext = null): PaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\PaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/finalizecapture'),
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
     * Resource /{merchantId}/payments/{paymentId}/cancelapproval - Undo capture payment
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return CancelApprovalPaymentResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/cancelapproval.html Undo capture payment
     */
    public function cancelapproval(string $paymentId, ?CallContext $callContext = null): CancelApprovalPaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\CancelApprovalPaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/cancelapproval'),
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
     * Resource /{merchantId}/payments/{paymentId}/captures - Get captures of payment
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return CapturesResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/captures.html Get captures of payment
     */
    public function captures(string $paymentId, ?CallContext $callContext = null): CapturesResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\CapturesResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/captures'),
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
     * Resource /{merchantId}/payments/{paymentId}/refund - Create refund
     *
     * @param string           $paymentId
     * @param RefundRequest    $body
     * @param CallContext|null $callContext
     *
     * @return RefundResponse
     * @throws DeclinedRefundException
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/refund.html Create refund
     */
    public function refund(string $paymentId, RefundRequest $body, ?CallContext $callContext = null): RefundResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\RefundResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\RefundErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/refund'),
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
     * Resource /{merchantId}/payments/{paymentId}/refunds - Get refunds of payment
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return RefundsResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/refunds.html Get refunds of payment
     */
    public function refunds(string $paymentId, ?CallContext $callContext = null): RefundsResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\RefundsResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/refunds'),
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
     * Resource /{merchantId}/payments/{paymentId}/cancel - Cancel payment
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return CancelPaymentResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/cancel.html Cancel payment
     */
    public function cancel(string $paymentId, ?CallContext $callContext = null): CancelPaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\CancelPaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/cancel'),
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
     * Resource /{merchantId}/payments/{paymentId}/dispute - Create dispute
     *
     * @param string               $paymentId
     * @param CreateDisputeRequest $body
     * @param CallContext|null     $callContext
     *
     * @return DisputeResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/dispute.html Create dispute
     */
    public function dispute(string $paymentId, CreateDisputeRequest $body, ?CallContext $callContext = null): DisputeResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\DisputeResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/dispute'),
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
     * Resource /{merchantId}/payments/{paymentId}/disputes - Get disputes
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return DisputesResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/disputes.html Get disputes
     */
    public function disputes(string $paymentId, ?CallContext $callContext = null): DisputesResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\DisputesResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/disputes'),
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
     * Resource /{merchantId}/payments/{paymentId}/devicefingerprint - Get Device Fingerprint details
     *
     * @param string           $paymentId
     * @param CallContext|null $callContext
     *
     * @return DeviceFingerprintDetails
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/devicefingerprint.html Get Device Fingerprint details
     */
    public function devicefingerprint(string $paymentId, ?CallContext $callContext = null): DeviceFingerprintDetails
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\DeviceFingerprintDetails';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->get(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/payments/{paymentId}/devicefingerprint'),
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
