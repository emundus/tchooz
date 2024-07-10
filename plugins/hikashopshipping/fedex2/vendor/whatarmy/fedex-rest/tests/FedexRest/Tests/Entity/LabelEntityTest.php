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

use FedexRest\Services\Ship\Entity\Label;
use FedexRest\Services\Ship\Type\ImageType;
use FedexRest\Services\Ship\Type\LabelStockType;
use PHPUnit\Framework\TestCase;

class LabelEntityTest extends TestCase
{
    public function testItemHasAttributes()
    {
        $label = (new Label())
            ->setLabelStockType(LabelStockType::_STOCK_4X6)
            ->setImageType(ImageType::_PDF);
        $this->assertObjectHasProperty('imageType', $label);
        $this->assertObjectHasProperty('labelStockType', $label);
        $this->assertEquals(LabelStockType::_STOCK_4X6, $label->prepare()['labelStockType']);
        $this->assertEquals(ImageType::_PDF, $label->prepare()['imageType']);
    }
}
