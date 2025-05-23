<?php

namespace FedexRest\Tests\Rates;

use Carbon\Carbon;
use FedexRest\Authorization\Authorize;
use FedexRest\Entity\Address;
use FedexRest\Entity\Dimensions;
use FedexRest\Entity\Item;
use FedexRest\Entity\Person;
use FedexRest\Entity\Weight;
use FedexRest\Exceptions\MissingAccountNumberException;
use FedexRest\Services\Rates\CreateRatesRequest;
use FedexRest\Services\Ship\CreateShipment;
use FedexRest\Services\Ship\Entity\Label;
use FedexRest\Services\Ship\Entity\ShippingChargesPayment;
use FedexRest\Services\Ship\Type\ImageType;
use FedexRest\Services\Ship\Type\LabelDocOptionType;
use FedexRest\Services\Ship\Type\LabelResponseOptionsType;
use FedexRest\Services\Ship\Type\LabelStockType;
use FedexRest\Services\Ship\Type\LinearUnits;
use FedexRest\Services\Ship\Type\PackagingType;
use FedexRest\Services\Ship\Type\PickupType;
use FedexRest\Services\Ship\Type\ServiceType;
use FedexRest\Services\Ship\Type\WeightUnits;
use PHPUnit\Framework\TestCase;

class CreateRatesRequestTest extends TestCase
{
    protected Authorize $auth;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->auth = (new Authorize)
            ->setClientId('l7749d031872cf4b55a7889376f360d045')
            ->setClientSecret('bd59d91084e8482895d4ae2fb4fb79a3');
    }

    public function testHasAccountNumber()
    {
        $request = NULL;
        try {
            $request = (new CreateRatesRequest())
                ->setAccessToken((string)$this->auth->authorize()->access_token)
                ->request();

        } catch (MissingAccountNumberException $e) {
            $this->assertEquals('The account number is required', $e->getMessage());
        }
        $this->assertEmpty($request, 'The request did not fail as it should.');
    }

    public function testRequiredData()
    {
        $createRatesRequest = (new CreateRatesRequest())
            ->setAccessToken((string)$this->auth->authorize()->access_token)
            ->setAccountNumber(740561073)
            ->setServiceType(ServiceType::_FEDEX_GROUND)
            ->setRecipient(
                (new Person)
                    ->withAddress(
                        (new Address())
                            ->setPostalCode(93309)
                            ->setCountryCode('US')
                    )
            )
            ->setShipper(
                (new Person)
                    ->withAddress(
                        (new Address())
                            ->setPostalCode(60606)
                            ->setCountryCode('US')
                    )
            );

        $requested_rates = $createRatesRequest->getRequestedShipment();
        $this->assertCount(1, $requested_rates['recipient']);
        $this->assertNotEmpty($requested_rates['shipper']['address']['postalCode']);
        $this->assertEquals('FEDEX_GROUND', $requested_rates['serviceType']);
    }

    public function testPrepare(): void
    {
        $request = (new CreateRatesRequest)
            ->setAccessToken((string)$this->auth->authorize()->access_token)
            ->setAccountNumber(740561073)
            ->setServiceType(ServiceType::_FEDEX_GROUND)
            ->setPackagingType(PackagingType::_YOUR_PACKAGING)
            ->setPickupType(PickupType::_DROPOFF_AT_FEDEX_LOCATION)
            ->setShippingChargesPayment((new ShippingChargesPayment())
                ->setPaymentType('SENDER')
            )
            ->setRecipient(
                (new Person)
                    ->withAddress(
                        (new Address())
                            ->setPostalCode(55555)
                            ->setCountryCode('US')
                    )
            )
            ->setShipper(
                (new Person)
                    ->withAddress(
                        (new Address())
                            ->setPostalCode(60606)
                            ->setCountryCode('US')
                    )
            )
            ->setLineItems((new Item())
                ->setItemDescription('lorem Ipsum')
                ->setWeight(
                    (new Weight())
                        ->setValue(1)
                        ->setUnit(WeightUnits::_POUND)
                )
                ->setDimensions((new Dimensions())
                    ->setWidth(12.5)
                    ->setLength(12.5)
                    ->setHeight(12.5)
                    ->setUnits(LinearUnits::_INCH)
                ));

        $prepared = $request->prepare();

        $requestedShipment = $prepared['requestedShipment'];
        $this->assertCount(1, $requestedShipment['recipient']);
        $this->assertEquals(55555, $requestedShipment['recipient']['address']['postalCode']);
        $this->assertNotEmpty($requestedShipment['shipper']['address']);
        $this->assertNotEmpty($requestedShipment['recipient']['address']);
        $this->assertEquals(ServiceType::_FEDEX_GROUND, $requestedShipment['serviceType']);
        $this->assertEquals(PickupType::_DROPOFF_AT_FEDEX_LOCATION, $requestedShipment['pickupType']);
        $this->assertEquals(PackagingType::_YOUR_PACKAGING, $requestedShipment['packagingType']);
    }

    public function testRequest(): void
    {
        $request = (new CreateRatesRequest)
            ->setAccessToken((string)$this->auth->authorize()->access_token)
            ->setAccountNumber(740561073)
            ->setRateRequestTypes('ACCOUNT', 'LIST')
            ->setPickupType(PickupType::_DROPOFF_AT_FEDEX_LOCATION)
            ->setShipper(
                (new Person)
                    ->withAddress(
                        (new Address())
                            ->setPostalCode('38017')
                            ->setCountryCode('US')
                    )
            )
            ->setRecipient(
                (new Person)
                    ->withAddress(
                        (new Address())
                            ->setPostalCode('75063')
                            ->setCountryCode('US')
                    )
            )
            ->setLineItems((new Item())
                ->setWeight(
                    (new Weight())
                        ->setValue(1)
                        ->setUnit(WeightUnits::_POUND)
                )
            );

        $request = $request->request();

        $this->assertObjectHasProperty('transactionId', $request);
        $this->assertObjectNotHasProperty('errors', $request);
        $this->assertObjectHasProperty('output', $request);

        $output = $request->output;
        $this->assertNotEmpty($output->rateReplyDetails);
        $rate = $output->rateReplyDetails[0];
        $this->assertEquals('FIRST_OVERNIGHT', $rate->serviceType);
        $this->assertNotEmpty($rate->serviceName);
        $this->assertEquals('YOUR_PACKAGING', $rate->packagingType);
        $this->assertNotEmpty($rate->ratedShipmentDetails);
        $this->assertNotEmpty($rate->operationalDetail);
        $this->assertNotEmpty($rate->signatureOptionType);
        $this->assertNotEmpty($rate->serviceDescription);
    }
}
