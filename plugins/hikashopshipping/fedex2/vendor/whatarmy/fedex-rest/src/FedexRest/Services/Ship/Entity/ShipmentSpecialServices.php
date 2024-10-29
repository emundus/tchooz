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

namespace FedexRest\Services\Ship\Entity;

class ShipmentSpecialServices
{
    public ?array $specialServiceTypes;
    public ?array $returnShipmentDetails;

    public function setSpecialServiceTypes(array $specialServiceTypes): ShipmentSpecialServices
    {
        $this->specialServiceTypes = $specialServiceTypes;
        return $this;
    }

    public function setReturnShipmentDetails(array $returnShipmentDetails): ShipmentSpecialServices
    {
        $this->returnShipmentDetails = $returnShipmentDetails;
        return $this;
    }

    public function prepare(): array
    {
        $data = [];
        if (!empty($this->returnShipmentDetails)) {
            $data['returnShipmentDetail'] = $this->returnShipmentDetails;
        }
        if (!empty($this->specialServiceTypes)) {
            $data['specialServiceTypes'] = $this->specialServiceTypes;
        }
        return $data;
    }
}
