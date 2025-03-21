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


$view = $displayData['view'];
?>

<span class="hikashop_product_msrp_price hikashop_product_price_full">
	<span class="hikashop_product_msrp_price_title">
        <?php // Obix: Basisprijs (label) ?>
        <?php echo Text::_('PRODUCT_MSRP_BEFORE'); ?>
    </span>

	<span class="hikashop_product_price">
        <?php
        $mainCurr = $view->currencyHelper->mainCurrency();
        $currCurrency = hikashop_getCurrency();

        $helper = new HikaShopPriceHelper($view->row, $view->currencyHelper);
        $retailPrices = $helper->getRetailPrice();

        echo $helper->getFormattedRetailPrice(true);

        if ($retailPrices->incTaxCurrencied != $retailPrices->incTax && $view->params->get('show_original_price')) :
	        echo ' (' . $view->currencyHelper->format($retailPrices->incTax, $mainCurr) . ')';
        endif;
        ?>
    </span>
</span>
