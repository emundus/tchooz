<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Productgroups;

use Worldline\Connect\Sdk\Communication\RequestObject;

/**
 * Query parameters for Get payment product group
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Productgroups
 * @link    https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/productgroups/get.html Get payment product group
 */
class GetProductgroupParams extends RequestObject
{
    /**
     * @var string|null
     */
    public ?string $countryCode;

    /**
     * @var string|null
     */
    public ?string $currencyCode;

    /**
     * @var string|null
     */
    public ?string $locale;

    /**
     * @var int|null
     */
    public ?int $amount;

    /**
     * @var bool|null
     */
    public ?bool $isRecurring;

    /**
     * @var bool|null
     */
    public ?bool $isInstallments;

    /**
     * @var string[]|null
     */
    public ?array $hide;
}
