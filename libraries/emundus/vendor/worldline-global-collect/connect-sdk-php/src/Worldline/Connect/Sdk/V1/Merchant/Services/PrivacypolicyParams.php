<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Services;

use Worldline\Connect\Sdk\Communication\RequestObject;

/**
 * Query parameters for Get privacy policy
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Services
 * @link    https://apireference.connect.worldline-solutions.com/s2sapi/v1/en_US/php/services/privacypolicy.html Get privacy policy
 */
class PrivacypolicyParams extends RequestObject
{
    /**
     * @var string|null
     */
    public ?string $locale;

    /**
     * @var int|null
     */
    public ?int $paymentProductId;
}
