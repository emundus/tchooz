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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;


$view = $displayData['view'];

$microDataForCurrentProduct = false;

$rowPrices     = array_values($view->row->prices);
$rowPriceCount = count($rowPrices);

$app    = Factory::getApplication();
$active = $app->getMenu()->getActive();

if (!$active) {
    $active = $app->getMenu()->getDefault();
}

$priceDisplayOptions = array_reduce(array_filter(
    array_values((array)$active->getParams()->get('hk_custom_price_display_options', [])),
    fn(object $item) => (int)$item->enabled
), function (array $carry, object $o) {
    $carry[$o->key] = $o;

    return $carry;
}, []);

echo Text::_('PRICE_BEGINNING');

if (!empty($show_msrp)) :
    echo '<span class="hikashop_product_our_price_title">';
    echo Text::_('PRODUCT_MSRP_AFTER');
    echo '</span> ';
endif;

for ($i = 0; $i < $rowPriceCount; $i++) :
    $price = $rowPrices[$i];

    if (!empty($view->unit) && isset($price->unit_price)) :
        $price = $price->unit_price;
    endif;

    if (empty($price->price_currency_id)) :
        continue;
    endif;

    if (!empty($view->element->product_id) && !$microDataForCurrentProduct) :
        $round                      = $view->currencyHelper->getRounding($price->price_currency_id, true);
        $microDataForCurrentProduct = true;

        if (empty($view->displayed_price_microdata)) :
            $view->displayed_price_microdata = true;
        endif;

        if ($view->params->get('price_with_tax')) :
            $price_attributes = str_replace(
                ',',
                '.',
                $view->currencyHelper->round($price->price_value_with_tax, $round, 0, true)
            );
        else :
            $price_attributes = str_replace(
                ',',
                '.',
                $view->currencyHelper->round($price->price_value, $round, 0, true)
            );
        endif;

        $view->itemprop_price = $price_attributes . '';
    endif;

    $displayData = [
        'view'                => $view,
        'price'               => $price,
        'index'               => $i,
        'priceDisplayOptions' => $priceDisplayOptions,
    ];

    echo LayoutHelper::render('price', $displayData, __DIR__);
endfor;

echo Text::_('PRICE_END');

