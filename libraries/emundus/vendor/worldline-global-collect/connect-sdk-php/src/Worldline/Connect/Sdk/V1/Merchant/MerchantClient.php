<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\V1\Merchant\Captures\CapturesClient;
use Worldline\Connect\Sdk\V1\Merchant\Disputes\DisputesClient;
use Worldline\Connect\Sdk\V1\Merchant\Files\FilesClient;
use Worldline\Connect\Sdk\V1\Merchant\Hostedcheckouts\HostedcheckoutsClient;
use Worldline\Connect\Sdk\V1\Merchant\Hostedmandatemanagements\HostedmandatemanagementsClient;
use Worldline\Connect\Sdk\V1\Merchant\Installments\InstallmentsClient;
use Worldline\Connect\Sdk\V1\Merchant\Mandates\MandatesClient;
use Worldline\Connect\Sdk\V1\Merchant\Payments\PaymentsClient;
use Worldline\Connect\Sdk\V1\Merchant\Payouts\PayoutsClient;
use Worldline\Connect\Sdk\V1\Merchant\Productgroups\ProductgroupsClient;
use Worldline\Connect\Sdk\V1\Merchant\Products\ProductsClient;
use Worldline\Connect\Sdk\V1\Merchant\Refunds\RefundsClient;
use Worldline\Connect\Sdk\V1\Merchant\Riskassessments\RiskassessmentsClient;
use Worldline\Connect\Sdk\V1\Merchant\Services\ServicesClient;
use Worldline\Connect\Sdk\V1\Merchant\Sessions\SessionsClient;
use Worldline\Connect\Sdk\V1\Merchant\Tokens\TokensClient;

/**
 * @package Worldline\Connect\Sdk\V1\Merchant
 */
class MerchantClient extends ApiResource
{
    /**
     * Resource /{merchantId}/hostedcheckouts
     *
     * @return HostedcheckoutsClient
     */
    public function hostedcheckouts(): HostedcheckoutsClient
    {
        return new HostedcheckoutsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/hostedmandatemanagements
     *
     * @return HostedmandatemanagementsClient
     */
    public function hostedmandatemanagements(): HostedmandatemanagementsClient
    {
        return new HostedmandatemanagementsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/payments
     *
     * @return PaymentsClient
     */
    public function payments(): PaymentsClient
    {
        return new PaymentsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/captures
     *
     * @return CapturesClient
     */
    public function captures(): CapturesClient
    {
        return new CapturesClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/refunds
     *
     * @return RefundsClient
     */
    public function refunds(): RefundsClient
    {
        return new RefundsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/disputes
     *
     * @return DisputesClient
     */
    public function disputes(): DisputesClient
    {
        return new DisputesClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/payouts
     *
     * @return PayoutsClient
     */
    public function payouts(): PayoutsClient
    {
        return new PayoutsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/productgroups
     *
     * @return ProductgroupsClient
     */
    public function productgroups(): ProductgroupsClient
    {
        return new ProductgroupsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/products
     *
     * @return ProductsClient
     */
    public function products(): ProductsClient
    {
        return new ProductsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/riskassessments
     *
     * @return RiskassessmentsClient
     */
    public function riskassessments(): RiskassessmentsClient
    {
        return new RiskassessmentsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/services
     *
     * @return ServicesClient
     */
    public function services(): ServicesClient
    {
        return new ServicesClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/tokens
     *
     * @return TokensClient
     */
    public function tokens(): TokensClient
    {
        return new TokensClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/mandates
     *
     * @return MandatesClient
     */
    public function mandates(): MandatesClient
    {
        return new MandatesClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/sessions
     *
     * @return SessionsClient
     */
    public function sessions(): SessionsClient
    {
        return new SessionsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/installments
     *
     * @return InstallmentsClient
     */
    public function installments(): InstallmentsClient
    {
        return new InstallmentsClient($this, $this->context);
    }

    /**
     * Resource /{merchantId}/files
     *
     * @return FilesClient
     */
    public function files(): FilesClient
    {
        return new FilesClient($this, $this->context);
    }
}
