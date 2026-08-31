<?php
namespace Worldline\Connect\Sdk\It;

use Worldline\Connect\Sdk\ClientTestCase;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\Domain\ConvertAmount;
use Worldline\Connect\Sdk\V1\Merchant\Services\ConvertAmountParams;

/**
 * @group integration
 *
 */
class ProxyTest extends ClientTestCase
{
    /**
     * @return ConvertAmount
     * @throws ApiException
     */
    public function testConvertAmount()
    {
        $client = $this->getProxyClient();
        $merchantId = $this->getMerchantId();
        $convertAmountParams = new ConvertAmountParams();

        $convertAmountParams->amount = 1000;
        $convertAmountParams->source = "USD";
        $convertAmountParams->target = "EUR";

        $response = $client->v1()->merchant($merchantId)->services()->convertAmount($convertAmountParams);
        $this->assertNotNull($response->convertedAmount);

        return $response;
    }
}
