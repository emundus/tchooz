<?php
namespace Worldline\Connect\Sdk\It;

use Worldline\Connect\Sdk\ClientTestCase;
use Worldline\Connect\Sdk\V1\Domain\Address;
use Worldline\Connect\Sdk\V1\Domain\CardWithoutCvv;
use Worldline\Connect\Sdk\V1\Domain\CreateTokenRequest;
use Worldline\Connect\Sdk\V1\Domain\CustomerToken;
use Worldline\Connect\Sdk\V1\Domain\TokenCard;
use Worldline\Connect\Sdk\V1\Domain\TokenCardData;
use Worldline\Connect\Sdk\V1\Merchant\Tokens\DeleteTokenParams;

/**
 * @group integration
 *
 */
class TokenTest extends ClientTestCase
{
    /**
     * Smoke test for token calls.
     */
    public function test()
    {
        $expiryDate = date('my', strtotime('+6 months'));

        $billingAddress = new Address();
        $billingAddress->countryCode = "NL";

        $customer = new CustomerToken();
        $customer->billingAddress = $billingAddress;

        $cardWithoutCvv = new CardWithoutCvv();
        $cardWithoutCvv->cardholderName = "Jan";
        $cardWithoutCvv->issueNumber = "12";
        $cardWithoutCvv->cardNumber = "4567350000427977";
        $cardWithoutCvv->expiryDate = $expiryDate;

        $mandate = new TokenCardData();
        $mandate->cardWithoutCvv = $cardWithoutCvv;

        $tokenCard = new TokenCard();
        $tokenCard->customer = $customer;
        $tokenCard->data = $mandate;

        $createTokenRequest = new CreateTokenRequest();
        $createTokenRequest->paymentProductId = 1;
        $createTokenRequest->card = $tokenCard;

        $client = $this->getClient();
        $merchantId = $this->getMerchantId();
        $createTokenResponse = $client->v1()->merchant($merchantId)->tokens()->create($createTokenRequest);

        $this->assertNotNull($createTokenResponse->token);

        $deleteTokenRequest = new DeleteTokenParams();
        $client->v1()->merchant($merchantId)->tokens()->delete($createTokenResponse->token, $deleteTokenRequest);
    }
}
