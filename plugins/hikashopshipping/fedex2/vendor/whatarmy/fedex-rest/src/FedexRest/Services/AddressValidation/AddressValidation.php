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

namespace FedexRest\Services\AddressValidation;

use FedexRest\Entity\Address;
use FedexRest\Services\AbstractRequest;

class AddressValidation extends AbstractRequest
{
    protected ?Address $address;

    public function setApiEndpoint(): string
    {
        return '/address/v1/addresses/resolve';
    }

    public function setAddress(?Address $address): AddressValidation
    {
        $this->address = $address;
        return $this;
    }

    public function prepare(): array
    {
        return [
            'json' => [
                'addressesToValidate' => [
                    [
                        'address' => $this->address->prepare(),
                    ],
                ],
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
