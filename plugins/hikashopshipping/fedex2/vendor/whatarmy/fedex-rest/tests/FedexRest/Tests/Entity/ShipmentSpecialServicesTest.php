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

namespace FedexRest\Tests\Entity;

use FedexRest\Services\Ship\Entity\ShipmentSpecialServices;
use FedexRest\Services\Ship\Type\ShipmentSpecialServiceType;
use PHPUnit\Framework\TestCase;

class ShipmentSpecialServicesTest extends TestCase
{
    public function testItemHasAttributes()
    {
        $ShipmentSpecialServices = (new ShipmentSpecialServices())
            ->setSpecialServiceTypes([ShipmentSpecialServiceType::_THIRD_PARTY_CONSIGNEE])
            ->setReturnShipmentDetails(['returnType' => 'PRINT_RETURN_LABEL']);
        $this->assertObjectHasProperty('returnShipmentDetails', $ShipmentSpecialServices);
        $this->assertObjectHasProperty('specialServiceTypes', $ShipmentSpecialServices);
    }
}
