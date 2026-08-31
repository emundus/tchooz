<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Products;

use Worldline\Connect\Sdk\Communication\RequestObject;

/**
 * Query parameters for Get payment products
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Products
 * @link    https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/products/find.html Get payment products
 */
class FindProductsParams extends RequestObject
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
