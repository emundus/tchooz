<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.1
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$config = hikashop_config();
if ($this->params->get('price_with_tax', 3) == 3) :
    $this->params->set('price_with_tax', (int)$config->get('price_with_tax'));
endif;

$class = (!empty($this->row->prices) && count($this->row->prices) > 1) ? ' hikashop_product_several_prices' : '';

if (!empty($this->row->has_options)) :
    $class .= ' hikashop_product_has_options';
endif;

if (isset($this->element->main->product_msrp) && !(@$this->row->product_msrp > 0.0)) :
    $this->row->product_msrp = $this->element->main->product_msrp;
endif;

if (isset($this->row->product_msrp) && $this->row->product_msrp > 0.0
    && hikaInput::get()->getCmd('layout') == 'show'
    && $this->params->get('from_module', '') == '') :

    $mainCurr     = $this->currencyHelper->mainCurrency();
    $currCurrency = hikashop_getCurrency();

    if ($currCurrency == $mainCurr && !empty($this->row->prices)) :
        $price = reset($this->row->prices);

        if (!empty($this->unit) && isset($price->unit_price)) :
            $price = $price->unit_price;
        endif;

        unset($price);
    endif;
endif;
?>

<span class="hikashop_product_price_full<?php
echo $class; ?>">
    <?php
    if (empty($this->row->prices)) :
        echo Text::_('FREE_PRICE');
    else :
        echo LayoutHelper::render('prices', ['view' => $this], __DIR__);
    endif;
    ?>
</span>
