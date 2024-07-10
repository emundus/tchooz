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

namespace FedexRest\Entity;

class Weight
{
    public string $unit = '';
    public float $value = 0;

    public function setUnit(string $unit): Weight
    {
        $this->unit = $unit;
        return $this;
    }

    public function setValue(float $value): Weight
    {
        $this->value = $value;
        return $this;
    }

    public function prepare(): array {
        $data = [];
        if (!empty($this->value)) {
            $data['value'] = $this->value;
        }
        if (!empty($this->unit)) {
            $data['units'] = $this->unit;
        }
        return $data;
    }


}
