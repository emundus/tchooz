<?php
namespace Worldline\Connect\Sdk\It;

use Worldline\Connect\Sdk\ClientTestCase;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\Domain\PaymentProductGroupResponse;
use Worldline\Connect\Sdk\V1\Merchant\Productgroups\GetProductgroupParams;

/**
 * @group integration
 *
 */
class ProductGroupTest extends ClientTestCase
{
    /**
     * @return PaymentProductGroupResponse
     * @throws ApiException
     */
    public function testRetrievePaymentProductGroup()
    {
        $client = $this->getClient();
        $merchantId = $this->getMerchantId();

        $getParams = new GetProductgroupParams();

        $getParams->amount = 1000;
        $getParams->currencyCode = "USD";
        $getParams->locale = "en_US";
        $getParams->countryCode = "US";
        $getParams->isRecurring = true;

        $paymentProductResponse = $client->v1()->merchant($merchantId)->productgroups()->get("cards", $getParams);
        $this->assertEquals("cards", $paymentProductResponse->id);
        return $paymentProductResponse;
    }
}
