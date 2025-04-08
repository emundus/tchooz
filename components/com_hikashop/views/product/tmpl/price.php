<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;


$view                = $displayData['view'];
$price               = $displayData['price'];
$index               = $displayData['index'];
$hasDiscount         = property_exists($price, 'price_value_without_discount') && $view->row->discount ?? null;
$priceDisplayOptions = $displayData['priceDisplayOptions'] ?? [];

$currencyHelper = $view->currencyHelper;
$priceHelper    = new HikaShopPriceHelper($view->row, $view->currencyHelper);

$formattedPrice = fn(float $value, int $currencyId) => ($value ?? 0) ? $currencyHelper->format($value, $currencyId) : '';

$priceVatDisplay = (int)$view->params->get('price_with_tax', 0);
$showWithVAT     = $priceVatDisplay === 1 || $priceVatDisplay === 2;
$showWithoutVAT  = $priceVatDisplay === 0 || $priceVatDisplay === 2;

$priceDiscountDisplay = (int)$view->params->get('show_discount', 0);
$showDiscountedPrice  = !empty($view->row->discount)
    && ($priceDiscountDisplay === 2 || $priceDiscountDisplay === 4);
$showDiscount         = (int)$view->params->get('show_discount', 0)
    && ($priceDiscountDisplay === 1 || $priceDiscountDisplay === 4);

$discountAmount = 0;

if ($hasDiscount && $showDiscount) {
    if (bccomp($view->row->discount->discount_flat_amount, 0, 5) !== 0) {
        $discountAmount = abs(-1 * $view->row->discount->discount_flat_amount);
    } elseif (bccomp($view->row->discount->discount_percent_amount, 0, 5) !== 0) {
        $discountAmount = abs(-1 * $view->row->discount->discount_percent_amount) . '%';
    }
}

$priceExVat = $hasDiscount ? $price->price_value_without_discount : $price->price_value;
$priceInVat = $hasDiscount ? $price->price_value_without_discount_with_tax : $price->price_value_with_tax;

$showOriginalPrice = $view->params->get('show_original_price');

$priceDisplayDataItems = [];

if ($priceHelper->getRetailPrice()->exTax > 0) {
    $priceDisplayDataItems['msrp_ex_vat'] = [
        'prefix'       => 'HS_MSRP_EX_VAT_PREFIX',
        'value'        => $priceHelper->getFormattedRetailPrice(),
        'suffix'       => 'HS_MSRP_EX_VAT_SUFFIX',
        'wrapperClass' => 'hs_msrp_ex_vat',
    ];
}

if ($priceHelper->getRetailPrice()->incTax > 0) {
    $priceDisplayDataItems['msrp_in_vat'] = [
        'prefix'       => 'HS_MSRP_IN_VAT_PREFIX',
        'value'        => $priceHelper->getFormattedRetailPrice(true),
        'suffix'       => 'HS_MSRP_IN_VAT_SUFFIX',
        'wrapperClass' => 'hs_msrp_in_vat',
    ];
}

if ($showWithoutVAT && !empty($priceExVat)) {
    $priceDisplayDataItems['ex_vat'] = [
        'prefix'       => 'HS_PRICE_EX_VAT_PREFIX',
        'value'        => $formattedPrice($priceExVat, $price->price_currency_id),
        'suffix'       => 'HS_PRICE_EX_VAT_SUFFIX',
        'wrapperClass' => 'hs_price_ex_vat',

    ];
}

if ($showWithVAT && !empty($priceInVat)) {
    $priceDisplayDataItems['in_vat'] = [
        'prefix'       => 'HS_PRICE_IN_VAT_PREFIX',
        'value'        => $formattedPrice($priceInVat, $price->price_currency_id),
        'suffix'       => 'HS_PRICE_IN_VAT_SUFFIX',
        'wrapperClass' => 'hs_price_in_vat',
    ];
}

if ($showDiscountedPrice && $showWithoutVAT && !empty($price->price_value)) {
    $priceDisplayDataItems['discounted_ex_vat'] = [
        'prefix'       => 'HS_PRICE_DISCOUNTED_EX_VAT_PREFIX',
        'value'        => $formattedPrice($price->price_value, $price->price_currency_id),
        'suffix'       => 'HS_PRICE_DISCOUNTED_EX_VAT_SUFFIX',
        'wrapperClass' => 'hs_price_discounted_ex_vat',
    ];
}

if ($showDiscountedPrice && $showWithVAT && !empty($price->price_value_with_tax)) {
    $priceDisplayDataItems['discounted_in_vat'] = [
        'prefix'       => 'HS_PRICE_DISCOUNTED_IN_VAT_PREFIX',
        'value'        => $formattedPrice($price->price_value_with_tax, $price->price_currency_id),
        'suffix'       => 'HS_PRICE_DISCOUNTED_IN_VAT_SUFFIX',
        'wrapperClass' => 'hs_price_discounted_in_vat',
    ];
}

if ($showDiscount && $discountAmount > 0) {
    if(is_float($discountAmount)) {
        $discountAmount = $formattedPrice($discountAmount, $price->price_currency_id);
    }
    $priceDisplayDataItems['discount_amount'] = [
        'prefix'       => 'HS_DISCOUNT_PREFIX',
        'value'        => $discountAmount,
        'suffix'       => 'HS_DISCOUNT_SUFFIX',
        'wrapperClass' => 'hs_price_discount',
    ];
}

if ($showOriginalPrice && $showWithoutVAT && !empty($price->price_orig_value_without_discount)) {
    $priceDisplayDataItems['orig_curr_ex_vat'] = [
        'prefix'       => 'HS_PRICE_ORG_CURR_EX_VAT_PREFIX',
        'value'        => $formattedPrice($price->price_orig_value_without_discount, $price->price_orig_currency_id),
        'suffix'       => 'HS_PRICE_ORG_CURR_EX_VAT_SUFFIX',
        'wrapperClass' => 'hs_price_org_curr_ex_vat',
    ];
}

if ($showOriginalPrice && $showWithVAT && !empty($price->price_orig_value_without_discount_with_tax)) {
    $priceDisplayDataItems['orig_curr_in_vat'] = [
        'prefix'       => 'HS_PRICE_ORG_CURR_IN_VAT_PREFIX',
        'value'        => $formattedPrice(
            $price->price_orig_value_without_discount_with_tax,
            $price->price_orig_currency_id
        ),
        'suffix'       => 'HS_PRICE_ORG_CURR_IN_VAT_SUFFIX',
        'wrapperClass' => 'hs_price_org_curr_in_vat',
    ];
}

if ($showOriginalPrice && $showWithoutVAT && !empty($price->price_orig_value)) {
    $priceDisplayDataItems['orig_curr_discounted_ex_vat'] = [
        'prefix'       => 'HS_PRICE_ORG_CURR_DISCOUNTED_EX_VAT_PREFIX',
        'value'        => $formattedPrice($price->price_orig_value, $price->price_orig_currency_id),
        'suffix'       => 'HS_PRICE_ORG_CURR_DISCOUNTED_EX_VAT_SUFFIX',
        'wrapperClass' => 'hs_price_org_curr_discounted_ex_vat',
    ];
}

if ($showOriginalPrice && $showWithoutVAT && !empty($price->price_orig_value_with_tax)) {
    $priceDisplayDataItems['orig_curr_discounted_in_vat'] = [
        'prefix'       => 'HS_PRICE_ORG_CURR_DISCOUNTED_IN_VAT_PREFIX',
        'value'        => $formattedPrice($price->price_orig_value_with_tax, $price->price_orig_currency_id),
        'suffix'       => 'HS_PRICE_ORG_CURR_DISCOUNTED_IN_VAT_SUFFIX',
        'wrapperClass' => 'hs_price_org_curr_discounted_in_vat',
    ];
}

$priceAdvantageDisplayValue = $priceHelper->getFormattedAdvantage($price);

if ($priceHelper->getRetailPrice()->incTax > 0) {
    $priceDisplayDataItems['advantage'] = [
        'prefix'       => 'HS_ADVANTAGE_PREFIX',
        'value'        => $priceAdvantageDisplayValue,
        'suffix'       => 'HS_ADVANTAGE_SUFFIX',
        'wrapperClass' => 'hs_price_advantage',
    ];
}

if ($index === 0) :
    echo Text::_('PRICE_SEPARATOR');
endif;

$start = Text::_('PRICE_BEGINNING_' . $index);

if ($start != 'PRICE_BEGINNING_' . $index) :
    echo $start;
endif;

if (isset($price->price_min_quantity) && empty($view->cart_product_price) && $price->price_min_quantity > 1) :
    echo '<span class="hikashop_product_price_with_min_qty hikashop_product_price_for_at_least_'
        . $price->price_min_quantity . '">';
endif;

$classes = ['hikashop_product_price hikashop_product_price_' . $index];

if (!empty($view->row->discount)) :
    $classes[] = 'hikashop_product_price_with_discount';
endif;

echo '<span class="' . implode(' ', $classes) . '">';

if (count($priceDisplayOptions)) {
    $keys = array_keys($priceDisplayOptions);
} else {
    $keys = array_keys($priceDisplayDataItems);
}

foreach ($keys as $key) {
    if (!isset($priceDisplayDataItems[$key])) {
        continue;
    }

    $priceDisplayData = $priceDisplayDataItems[$key];

    if (isset($priceDisplayOptions[$key])) {
        $priceDisplayData['prefix'] = $priceDisplayOptions[$key]->prefix;
        $priceDisplayData['suffix'] = $priceDisplayOptions[$key]->suffix;
    }

    $layout = trim($priceDisplayData['layout'] ?? '') ? $priceDisplayData['layout'] : 'price-layout';

    echo LayoutHelper::render($layout, [
        'key'              => $key,
        'priceDisplayData' => $priceDisplayData,
    ], __DIR__);
}
$config = hikashop_config();
echo $config->get('advanced_price_display_separator', '<hr />');

echo '</span> ';

if (isset($price->price_min_quantity) && empty($view->cart_product_price) && $view->params->get('per_unit', 1)) :
    if ($price->price_min_quantity > 1) :
        echo '<span class="hikashop_product_price_per_unit_x">';
        echo Text::sprintf('PER_UNIT_AT_LEAST_X_BOUGHT', $price->price_min_quantity);
        echo '</span>';
    else :
        echo '<span class="hikashop_product_price_per_unit">';
        echo Text::_('PER_UNIT');
        echo '</span>';
    endif;
endif;

if ($view->params->get('show_price_weight')) :
    if (!empty($view->element->product_id)
        && isset($view->row->product_weight)
        && bccomp($view->row->product_weight, 0, 3)) :

        echo Text::_('PRICE_SEPARATOR') . '<span class="hikashop_product_price_per_weight_unit">';

        if ($view->params->get('price_with_tax')) :
            $weight_price = $price->price_value_with_tax / $view->row->product_weight;

            echo $view->currencyHelper->format(
                    $weight_price,
                    $price->price_currency_id
                ) . ' / ' . Text::_($view->row->product_weight_unit);
        endif;

        if ($view->params->get('price_with_tax') == 2) :
            echo Text::_('PRICE_BEFORE_TAX');
        endif;

        if ($view->params->get('price_with_tax') == 2 || !$view->params->get('price_with_tax')) :
            $weight_price = $price->price_value / $view->row->product_weight;

            echo $view->currencyHelper->format($weight_price, $price->price_currency_id)
                . ' / ' . Text::_($view->row->product_weight_unit);
        endif;

        if ($view->params->get('price_with_tax') == 2) :
            echo Text::_('PRICE_AFTER_TAX');
        endif;

        echo '</span>';
    endif;
endif;

if (isset($price->price_min_quantity) && empty($view->cart_product_price) && $price->price_min_quantity > 1) :
    echo '</span>';
endif;
?>

<?php
$end = Text::_('PRICE_ENDING_' . $index);

if ($end != 'PRICE_ENDING_' . $index) :
    echo $end;
endif;
