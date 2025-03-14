<?php

namespace FedexRest\Tests\AddressValidation;

use FedexRest\Authorization\Authorize;
use FedexRest\Entity\Address;
use FedexRest\Exceptions\MissingAccessTokenException;
use FedexRest\Exceptions\MissingAuthCredentialsException;
use FedexRest\Services\AddressValidation\AddressValidation;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;

class AddressValidationTest extends TestCase
{
    protected Authorize $auth;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->auth = (new Authorize)
            ->setClientId('l7749d031872cf4b55a7889376f360d045')
            ->setClientSecret('bd59d91084e8482895d4ae2fb4fb79a3');
    }

    public function testValidateAddress()
    {
        $test = (new AddressValidation())
            ->setAddress(
                (new Address())
                    ->setCity('Irving')
                    ->setCountryCode('US')
                    ->setStateOrProvince('TX')
                    ->setPostalCode('75063-8659')
                    ->setStreetLines('7372 PARKRIDGE BLVD', 'APT 286', '2903 sprank')
                    ->setResidential(true)
            )
            ->setAccessToken($this->auth->authorize()->access_token)
            ->request();
        $this->assertObjectHasProperty('transactionId', $test);
    }
}
