<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace FedexRest\Services\Track;


use FedexRest\Exceptions\MissingTrackingNumberException;
use FedexRest\Services\AbstractRequest;
use GuzzleHttp\Client;

class TrackByTrackingNumberRequest extends AbstractRequest
{
    private array $tracking_number;
    private bool $include_detailed_scans = false;


    public function setApiEndpoint()
    {
        return '/track/v1/trackingnumbers';
    }

    public function setTrackingNumber($tracking_number)
    {
        $this->tracking_number = (array) $tracking_number;
        return $this;
    }

    public function request()
    {
        parent::request();

        if (empty($this->tracking_number)) {
            throw new MissingTrackingNumberException('Please enter at least one tracking number');
        }

        try {
            $query = $this->http_client->post($this->getApiUri($this->api_endpoint), [
                'json' => [
                    'includeDetailedScans' => $this->include_detailed_scans,
                    'trackingInfo' => $this->preparedData(),
                ]
            ]);
            return ($this->raw === true) ? $query : json_decode($query->getBody()->getContents());
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function preparedData()
    {
        $data = [];
        foreach ($this->tracking_number as $token) {
            array_push($data, [
                'trackingNumberInfo' =>
                    [
                        'trackingNumber' => $token,
                    ],
            ]);
        }

        return $data;
    }

    public function includeDetailedScans()
    {
        $this->include_detailed_scans = true;
        return $this;
    }
}
