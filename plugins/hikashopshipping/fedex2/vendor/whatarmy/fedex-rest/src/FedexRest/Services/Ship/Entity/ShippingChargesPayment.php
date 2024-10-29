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

class ShippingChargesPayment
{
    public ?string $paymentType;
    public function setPaymentType(string $paymentType): ShippingChargesPayment
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    public function prepare(): array
    {
        $data = [];
        if (!empty($this->paymentType)) {
            $data['paymentType'] = $this->paymentType;
        }
        return $data;
    }
}
