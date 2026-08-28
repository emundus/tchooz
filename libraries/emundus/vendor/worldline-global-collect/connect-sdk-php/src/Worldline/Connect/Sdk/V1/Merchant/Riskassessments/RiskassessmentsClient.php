<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Riskassessments;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\CallContext;
use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\InvalidResponseException;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\AuthorizationException;
use Worldline\Connect\Sdk\V1\Domain\RiskAssessmentBankAccount;
use Worldline\Connect\Sdk\V1\Domain\RiskAssessmentCard;
use Worldline\Connect\Sdk\V1\Domain\RiskAssessmentResponse;
use Worldline\Connect\Sdk\V1\ExceptionFactory;
use Worldline\Connect\Sdk\V1\IdempotenceException;
use Worldline\Connect\Sdk\V1\PlatformException;
use Worldline\Connect\Sdk\V1\ReferenceException;
use Worldline\Connect\Sdk\V1\ValidationException;

/**
 * Risk assessments client.
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Riskassessments
 */
class RiskassessmentsClient extends ApiResource
{
    /**
     * @var ExceptionFactory|null
     */
    private ?ExceptionFactory $responseExceptionFactory = null;

    /**
     * Resource /{merchantId}/riskassessments/bankaccounts - Risk-assess bankaccount
     *
     * @param RiskAssessmentBankAccount $body
     * @param CallContext|null          $callContext
     *
     * @return RiskAssessmentResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/riskassessments/bankaccounts.html Risk-assess bankaccount
     */
    public function bankaccounts(RiskAssessmentBankAccount $body, ?CallContext $callContext = null): RiskAssessmentResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\RiskAssessmentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/riskassessments/bankaccounts'),
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
     * Resource /{merchantId}/riskassessments/cards - Risk-assess card
     *
     * @param RiskAssessmentCard $body
     * @param CallContext|null   $callContext
     *
     * @return RiskAssessmentResponse
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     * @link   https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/riskassessments/cards.html Risk-assess card
     */
    public function cards(RiskAssessmentCard $body, ?CallContext $callContext = null): RiskAssessmentResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\RiskAssessmentResponse';
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        try {
            return $this->getCommunicator()->post(
                $responseClassMap,
                $this->instantiateUri('/v1/{merchantId}/riskassessments/cards'),
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
