<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1;

use Worldline\Connect\Sdk\ApiResource;
use Worldline\Connect\Sdk\V1\Merchant\MerchantClient;

/**
 * V1 client
 *
 * @package Worldline\Connect\Sdk\V1
 */
class V1Client extends ApiResource
{
    /**
     * Resource /{merchantId}
     *
     * @param string $merchantId
     *
     * @return MerchantClient
     */
    public function merchant(string $merchantId): MerchantClient
    {
        $newContext = $this->context;
        $newContext['merchantId'] = $merchantId;
        return new MerchantClient($this, $newContext);
    }
}
