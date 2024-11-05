<?php

namespace FedexRest\Services\LocationSearch;

use FedexRest\Entity\Address;
use FedexRest\Services\AbstractRequest;
use FedexRest\Services\LocationSearch\Type\SearchCriterionType;

class FindLocations extends AbstractRequest
{
    protected ?Address $address;
    protected string $searchCriterion = SearchCriterionType::_ADDRESS;
    protected ?string $phoneNumber = null;
    protected bool $sameState = false;
    protected bool $sameCountry = false;
    protected ?float $long = null;
    protected ?float $lat = null;
    protected ?int $resultLimit = 20;

    public function setResultLimit(?int $resultLimit): FindLocations
    {
        $this->resultLimit = $resultLimit;
        return $this;
    }


    public function setApiEndpoint(): string
    {
        return '/location/v1/locations';
    }

    public function setAddress(?Address $address): FindLocations
    {
        $this->address = $address;
        return $this;
    }

    public function sameState(bool $sameState): FindLocations
    {
        $this->sameState = $sameState;
        return $this;
    }

    public function sameCountry(bool $sameCountry): FindLocations
    {
        $this->sameCountry = $sameCountry;
        return $this;
    }


    public function setPhoneNumber(?string $phoneNumber): FindLocations
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }


    public function setSearchCriterion(string $searchCriterion): FindLocations
    {
        $this->searchCriterion = $searchCriterion;
        return $this;
    }

    public function prepare(): array
    {
        return [
            'json' => [
                'locationSearchCriterion' => $this->searchCriterion,
                'locationsSummaryRequestControlParameters' => [
                    'maxResults' => $this->resultLimit
                ],
                'location' => [
                    'address' => $this->address->prepare(),
                    "longLat" => [
                        "latitude" => $this->lat,
                        "longitude" => $this->long
                    ]
                ],
                "phoneNumber" => $this->phoneNumber,
            ],
        ];
    }

    public function request()
    {
        parent::request();
        $query = $this->http_client->post($this->getApiUri($this->api_endpoint), $this->prepare());
        return ($this->raw === true) ? $query : json_decode($query->getBody()->getContents());
    }
}
