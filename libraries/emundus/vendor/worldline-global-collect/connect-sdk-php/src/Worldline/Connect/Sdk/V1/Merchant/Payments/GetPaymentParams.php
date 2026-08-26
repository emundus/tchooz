<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Payments;

use Worldline\Connect\Sdk\Communication\RequestObject;

/**
 * Query parameters for Get payment
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Payments
 * @link    https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/get.html Get payment
 */
class GetPaymentParams extends RequestObject
{
    /**
     * @var bool|null
     */
    public ?bool $returnOperations;
}
