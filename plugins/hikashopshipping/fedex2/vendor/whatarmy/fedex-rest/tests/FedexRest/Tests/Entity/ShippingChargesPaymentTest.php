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

use FedexRest\Services\Ship\Entity\ShippingChargesPayment;
use PHPUnit\Framework\TestCase;

class ShippingChargesPaymentTest extends TestCase
{
    public function testItemHasAttributes()
    {
        $ShippingChargesPayment = (new ShippingChargesPayment())
            ->setPaymentType('SENDER');
        $this->assertObjectHasProperty('paymentType', $ShippingChargesPayment);
        $this->assertEquals('SENDER', $ShippingChargesPayment->prepare()['paymentType']);
    }
}
