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

use FedexRest\Services\Ship\Entity\Value;
use PHPUnit\Framework\TestCase;

class ValueTest extends TestCase
{
    public function testItemHasAttributes()
    {
        $Value = (new Value())
            ->setAmount(12)
            ->setCurrency('USD');
        $this->assertObjectHasProperty('amount', $Value);
        $this->assertObjectHasProperty('currency', $Value);
        $this->assertEquals('USD', $Value->prepare()['currency']);
        $this->assertEquals(12, $Value->prepare()['amount']);
    }
}
