<?php

namespace FedexRest\Services\Rates;

use Exception;
use FedexRest\Entity\Item;
use FedexRest\Entity\Person;
use FedexRest\Exceptions\MissingAccessTokenException;
use FedexRest\Exceptions\MissingAccountNumberException;
use FedexRest\Exceptions\MissingLineItemException;
use FedexRest\Services\AbstractRequest;
use FedexRest\Services\Ship\Entity\Label;
use FedexRest\Services\Ship\Entity\ShipmentSpecialServices;
use FedexRest\Services\Ship\Entity\ShippingChargesPayment;
use GuzzleHttp\Exception\GuzzleException;

class CreateRatesRequest extends AbstractRequest
{
    protected Person $shipper;
    protected Person $recipient;
    protected Label $label;
    protected ?string $shipDateStamp;
    protected ?string $serviceType;
    protected array $rateRequestTypes;
    protected string $packagingType = '';
    protected string $pickupType = '';
    protected int $accountNumber;
    protected array $lineItems = [];
    protected ShipmentSpecialServices $shipmentSpecialServices;
    protected ShippingChargesPayment $shippingChargesPayment;
    protected int $totalWeight;
    protected string $preferredCurrency = '';
    protected int $totalPackageCount;
    protected bool $returnTransitTimes = false;
    protected bool $servicesNeededOnRateFailure = false;
    protected ?string $variableOptions = null;
    protected ?string $rateSortOrder = null;
    protected array $carrierCodes = [];

    public function setApiEndpoint()
    {
        return '/rate/v1/rates/quotes';
    }

    public function setShipper(Person $shipper): CreateRatesRequest
    {
        $this->shipper = $shipper;
        return $this;
    }

    public function getShipper(): Person
    {
        return $this->shipper;
    }

    public function setRecipient(Person $recipient): CreateRatesRequest
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getRecipient(): Person
    {
        return $this->recipient;
    }

    public function setShipDateStamp(string $shipDateStamp): CreateRatesRequest
    {
        $this->shipDateStamp = $shipDateStamp;
        return $this;
    }

    public function getShipDateStamp(): string
    {
        return $this->shipDateStamp;
    }

    public function setServiceType(string $serviceType): CreateRatesRequest
    {
        $this->serviceType = $serviceType;
        return $this;
    }

    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    public function setPackagingType(string $packagingType): CreateRatesRequest
    {
        $this->packagingType = $packagingType;
        return $this;
    }

    public function getPackagingType(): string
    {
        return $this->packagingType;
    }

    public function setPickupType(string $pickupType): CreateRatesRequest
    {
        $this->pickupType = $pickupType;
        return $this;
    }

    public function getPickupType(): string
    {
        return $this->pickupType;
    }

    public function setAccountNumber(int $accountNumber): CreateRatesRequest
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function setRateRequestTypes(string ...$rateRequestTypes): CreateRatesRequest
    {
        $this->rateRequestTypes = $rateRequestTypes;
        return $this;
    }

    public function getRateRequestTypes(array $rateRequestTypes): array
    {
        return $this->rateRequestTypes;
    }

    public function setLineItems(Item ...$lineItems): CreateRatesRequest
    {
        $this->lineItems = $lineItems;
        return $this;
    }

    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function setShipmentSpecialServices(ShipmentSpecialServices $shipmentSpecialServices): CreateRatesRequest
    {
        $this->shipmentSpecialServices = $shipmentSpecialServices;
        return $this;
    }


    public function getShipmentSpecialServices(): ShipmentSpecialServices
    {
        return $this->shipmentSpecialServices;
    }

    public function setShippingChargesPayment(ShippingChargesPayment $shippingChargesPayment): CreateRatesRequest
    {
        $this->shippingChargesPayment = $shippingChargesPayment;
        return $this;
    }

    public function getShippingChargesPayment(): ShippingChargesPayment
    {
        return $this->shippingChargesPayment;
    }

    public function setTotalWeight(int $totalWeight): CreateRatesRequest
    {
        $this->totalWeight = $totalWeight;
        return $this;
    }

    public function getTotalWeight(): int
    {
        return $this->totalWeight;
    }

    public function setPreferredCurrency(string $preferredCurrency): CreateRatesRequest
    {
        $this->preferredCurrency = $preferredCurrency;
        return $this;
    }

    public function getPreferredCurrency(): string
    {
        return $this->preferredCurrency;
    }

    public function setTotalPackageCount(int $totalPackageCount): CreateRatesRequest
    {
        $this->totalPackageCount = $totalPackageCount;
        return $this;
    }

    public function getTotalPackageCount(): int
    {
        return $this->totalPackageCount;
    }

    public function setReturnTransitTimes(bool $returnTransitTimes=true): CreateRatesRequest
    {
        $this->returnTransitTimes = $returnTransitTimes;
        return $this;
    }

    public function getReturnTransitTimes(): bool
    {
        return $this->returnTransitTimes;
    }

    public function setServicesNeededOnRateFailure(bool $servicesNeededOnRateFailure=true): CreateRatesRequest
    {
        $this->servicesNeededOnRateFailure = $servicesNeededOnRateFailure;
        return $this;
    }

    public function getServicesNeededOnRateFailure(): bool
    {
        return $this->servicesNeededOnRateFailure;
    }

    public function setVariableOptions(?string $variableOptions): CreateRatesRequest
    {
        $this->variableOptions = $variableOptions;
        return $this;
    }

    public function getVariableOptions(): ?string
    {
        return $this->variableOptions;
    }

    public function setRateSortOrder(?string $rateSortOrder): CreateRatesRequest
    {
        $this->rateSortOrder = $rateSortOrder;
        return $this;
    }

    public function getRateSortOrder(): ?string
    {
        return $this->rateSortOrder;
    }

    public function setCarrierCodes(array $carrierCodes): CreateRatesRequest
    {
        $this->carrierCodes = $carrierCodes;
        return $this;
    }

    public function getCarrierCodes(): array
    {
        return $this->carrierCodes;
    }

    public function getControlParameters(): array
    {
        $data = [
            'returnTransitTimes' => $this->returnTransitTimes,
            'servicesNeededOnRateFailure' => $this->servicesNeededOnRateFailure,
        ];

        if ($this->variableOptions) {
            $data['variableOptions'] = $this->variableOptions;
        }

        if ($this->rateSortOrder) {
            $data['rateSortOrder'] = $this->rateSortOrder;
        }

        return $data;
    }

    public function getRequestedShipment(): array
    {
        $line_items = [];
        foreach ($this->lineItems as $line_item) {

            $line_items[] = $line_item->prepare();
        }

        $data = [
            'shipper' => $this->shipper->prepare(),
            'recipient' => $this->recipient->prepare(),
            'pickupType' => $this->pickupType,
            'requestedPackageLineItems' => $line_items,
        ];

        if (!empty($this->shipmentSpecialServices)) {
            $data['shipmentSpecialServices'] = $this->shipmentSpecialServices->prepare();
        }

        if (!empty($this->serviceType)) {
            $data['serviceType'] = $this->serviceType;
        }

        if (!empty($this->rateRequestTypes)) {
            $data['rateRequestType'] = $this->rateRequestTypes;
        }

        if (!empty($this->packagingType)) {
            $data['packagingType'] = $this->packagingType;
        }

        if (!empty($this->shipDateStamp)) {
            $data['shipDateStamp'] = $this->shipDateStamp;
        }

        if (!empty($this->totalWeight)) {
            $data['totalWeight'] = $this->totalWeight;
        }

        if (!empty($this->preferredCurrency)) {
            $data['preferredCurrency'] = $this->preferredCurrency;
        }

        if (!empty($this->totalPackageCount)) {
            $data['totalPackageCount'] = $this->totalPackageCount;
        }

        return $data;
    }

    public function prepare(): array
    {
        return [
            'accountNumber' => [
                'value' => $this->accountNumber,
            ],
            'rateRequestControlParameters' => $this->getControlParameters(),
            'requestedShipment' => $this->getRequestedShipment(),
            'carrierCodes' => $this->getCarrierCodes(),
        ];
    }

    public function request()
    {
        parent::request();
        if (empty($this->accountNumber)) {
            throw new MissingAccountNumberException('The account number is required');
        }
        if (empty($this->lineItems)) {
            throw new MissingLineItemException('Line items are required');
        }

        try {
            $prepare = $this->prepare();
            $query = $this->http_client->post($this->getApiUri($this->api_endpoint), [
                'json' => $prepare,
                'http_errors' => false,
            ]);
            return ($this->raw === true) ? $query : json_decode($query->getBody()->getContents());
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
