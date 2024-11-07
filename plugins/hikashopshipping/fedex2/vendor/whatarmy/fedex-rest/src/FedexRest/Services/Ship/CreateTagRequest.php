<?php

namespace FedexRest\Services\Ship;

use FedexRest\Entity\Item;
use FedexRest\Entity\Person;
use FedexRest\Exceptions\MissingAccountNumberException;
use FedexRest\Exceptions\MissingLineItemException;
use FedexRest\Services\AbstractRequest;

class CreateTagRequest extends AbstractRequest
{
    protected int $account_number;
    protected Person $shipper;
    protected array $recipients;
    protected ?Item $line_items;
    protected string $service_type;
    protected string $packaging_type;
    protected string $pickup_type;
    protected string $ship_datestamp = '';

    public function setApiEndpoint(): string
    {
        return '/ship/v1/shipments';
    }

    public function getPickupType(): string
    {
        return $this->pickup_type;
    }

    public function setPickupType(string $pickup_type): CreateTagRequest
    {
        $this->pickup_type = $pickup_type;
        return $this;
    }

    public function getPackagingType(): string
    {
        return $this->packaging_type;
    }

    public function setPackagingType(string $packaging_type): CreateTagRequest
    {
        $this->packaging_type = $packaging_type;
        return $this;
    }

    public function getLineItems(): Item
    {
        return $this->line_items;
    }

    public function setLineItems(Item $line_items): CreateTagRequest
    {
        $this->line_items = $line_items;
        return $this;
    }

    public function setShipDatestamp(string $ship_datestamp): CreateTagRequest
    {
        $this->ship_datestamp = $ship_datestamp;
        return $this;
    }

    public function setServiceType(string $service_type): CreateTagRequest
    {
        $this->service_type = $service_type;
        return $this;
    }

    public function getServiceType(): string
    {
        return $this->service_type;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getShipper(): Person
    {
        return $this->shipper;
    }

    public function setShipper(Person $shipper): CreateTagRequest
    {
        $this->shipper = $shipper;
        return $this;
    }

    public function setRecipients(Person ...$recipients): CreateTagRequest
    {
        $this->recipients = $recipients;
        return $this;
    }

    public function setAccountNumber(int $account_number): CreateTagRequest
    {
        $this->account_number = $account_number;
        return $this;
    }

    public function prepare(): array
    {
        return [
            'json' => [
                'labelResponseOptions' => 'LABEL',
                'requestedShipment' => [
                    'shipper' => $this->shipper->prepare(),
                    'recipients' => array_map(fn(Person $person) => $person->prepare(), $this->recipients),
                    'shipDatestamp' => $this->ship_datestamp,
                    'serviceType' => $this->getServiceType(),
                    'packagingType' => $this->getPackagingType(),
                    'pickupType' => $this->getPickupType(),
                    'blockInsightVisibility' => false,
                    'shippingChargesPayment' => [
                        'paymentType' => 'SENDER',
                        'payor' => [
                            'responsibleParty' => [
                                'accountNumber' => [
                                    'value' => $this->account_number
                                ]
                            ]
                        ]
                    ],
                    'shipmentSpecialServices' => [
                        'specialServiceTypes' => [
                            'RETURN_SHIPMENT',
                        ],
                        'returnShipmentDetail' => [
                            'returnType' => 'PRINT_RETURN_LABEL',
                        ],
                    ],
                    'labelSpecification' => [
                        'labelFormatType' => 'COMMON2D',
                        'imageType' => 'PNG',
                        'labelStockType' => 'PAPER_7X475',
                    ],
                    'requestedPackageLineItems' => [$this->getLineItems()->prepare()],
                ],
                'accountNumber' => [
                    'value' => $this->account_number,
                ],
            ],
        ];
    }

    public function request()
    {
        parent::request();
        if (empty($this->account_number)) {
            throw new MissingAccountNumberException('The account number is required');
        }
        if (empty($this->getLineItems())) {
            throw new MissingLineItemException('Line items are required');
        }

        $query = $this->http_client->post($this->getApiUri($this->api_endpoint), $this->prepare());
        return ($this->raw === true) ? $query : json_decode($query->getBody()->getContents());
    }

}
