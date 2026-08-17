<?php
namespace Worldline\Connect\Sdk\It;

use Worldline\Connect\Sdk\ClientTestCase;
use Worldline\Connect\Sdk\V1\ApiException;
use Worldline\Connect\Sdk\V1\Domain\Directory;
use Worldline\Connect\Sdk\V1\Domain\PaymentProducts;
use Worldline\Connect\Sdk\V1\Merchant\Products\DirectoryParams;
use Worldline\Connect\Sdk\V1\Merchant\Products\FindProductsParams;

/**
 * @group integration
 *
 */
class ProductTest extends ClientTestCase
{
    /**
     * @return PaymentProducts
     * @throws ApiException
     */
    public function testRetrievePaymentProducts()
    {
        $client = $this->getClient();
        $merchantId = $this->getMerchantId();
        $findParams = new FindProductsParams();

        $findParams->currencyCode = "EUR";
        $findParams->countryCode = "NL";

        $paymentProducts = $client->v1()->merchant($merchantId)->products()->find($findParams);
        $this->assertNotEmpty($paymentProducts->paymentProducts);
        return $paymentProducts;
    }

    /**
     * @return Directory
     * @throws ApiException
     */
    public function testRetrievePaymentProductDirectory()
    {
        $client = $this->getClient();
        $merchantId = $this->getMerchantId();
        $directoryParams = new DirectoryParams();

        $directoryParams->currencyCode = "EUR";
        $directoryParams->countryCode = "NL";

        $productDirectory = $client->v1()->merchant($merchantId)->products()->directory(809, $directoryParams);
        $this->assertNotEmpty($productDirectory->entries);
        return $productDirectory;
    }
}
