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

class Value
{
    public ?string $currency;
    public ?float $amount;

    public function setCurrency(string $currency): Value
    {
        $this->currency = $currency;
        return $this;
    }

    public function setAmount(float $amount): Value
    {
        $this->amount = $amount;
        return $this;
    }

    public function prepare(): array {
        $data = [];
        if (!empty($this->amount)) {
            $data['amount'] = $this->amount;
        }
        if (!empty($this->currency)) {
            $data['currency'] = $this->currency;
        }
        return $data;
    }


}
