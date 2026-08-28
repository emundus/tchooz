<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Payouts;

use Worldline\Connect\Sdk\Communication\RequestObject;

/**
 * Query parameters for Find payouts
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Payouts
 * @link    https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/payouts/find.html Find payouts
 */
class FindPayoutsParams extends RequestObject
{
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
