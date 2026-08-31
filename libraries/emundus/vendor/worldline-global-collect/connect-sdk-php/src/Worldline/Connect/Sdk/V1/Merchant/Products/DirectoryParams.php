<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Products;

use Worldline\Connect\Sdk\Communication\RequestObject;

/**
 * Query parameters for Get payment product directory
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Products
 * @link    https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/products/directory.html Get payment product directory
 */
class DirectoryParams extends RequestObject
{
    /**
     * @var string|null
     */
    public ?string $countryCode;

    /**
     * @var string|null
     */
    public ?string $currencyCode;
}
