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

class Label
{
    public ?string $imageType;
    public ?string $labelStockType;

    public function setImageType(string $imageType): Label
    {
        $this->imageType = $imageType;
        return $this;
    }

    public function setLabelStockType(string $labelStockType): Label
    {
        $this->labelStockType = $labelStockType;
        return $this;
    }

    public function prepare(): array
    {
        $data = [];
        if (!empty($this->labelStockType)) {
            $data['labelStockType'] = $this->labelStockType;
        }
        if (!empty($this->imageType)) {
            $data['imageType'] = $this->imageType;
        }
        return $data;
    }
}
