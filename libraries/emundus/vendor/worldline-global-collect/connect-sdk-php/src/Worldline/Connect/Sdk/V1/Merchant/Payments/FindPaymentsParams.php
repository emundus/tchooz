<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Payments;

use Worldline\Connect\Sdk\Communication\RequestObject;

/**
 * Query parameters for Find payments
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Payments
 * @link    https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payments/find.html Find payments
 */
class FindPaymentsParams extends RequestObject
{
    /**
     * @var string|null
     */
    public ?string $hostedCheckoutId;

    /**
     * @var string|null
     */
    public ?string $merchantReference;

    /**
     * @var int|null
     */
    public ?int $merchantOrderId;

    /**
     * @var int|null
     */
    public ?int $offset;

    /**
     * @var int|null
     */
    public ?int $limit;
}
