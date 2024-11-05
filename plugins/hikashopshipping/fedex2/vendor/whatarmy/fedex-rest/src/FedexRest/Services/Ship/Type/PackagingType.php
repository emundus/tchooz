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


namespace FedexRest\Services\Ship\Type;

class PackagingType
{
    const _YOUR_PACKAGING = 'YOUR_PACKAGING'; // 150lbs/68KG or 70lbs/32KG
    const _FEDEX_ENVELOPE = 'FEDEX_ENVELOPE'; // 1lbs/0.5KG
    const _FEDEX_BOX = 'FEDEX_BOX'; // 20lbs/9KG
    const _FEDEX_SMALL_BOX = 'FEDEX_SMALL_BOX'; // 20lbs/9KG
    const _FEDEX_MEDIUM_BOX = 'FEDEX_MEDIUM_BOX'; // 20lbs/9KG
    const _FEDEX_LARGE_BOX = 'FEDEX_LARGE_BOX'; // 20lbs/9KG
    const _FEDEX_EXTRA_LARGE_BOX = 'FEDEX_EXTRA_LARGE_BOX'; // 20lbs/9KG
    const _FEDEX_10KG_BOX = 'FEDEX_10KG_BOX'; // 22lbs/10KG
    const _FEDEX_25KG_BOX = 'FEDEX_25KG_BOX'; // 55lbs/25KG
    const _FEDEX_PAK = 'FEDEX_PAK'; // 20lbs/9KG
    const _FEDEX_TUBE = 'FEDEX_TUBE'; //20lbs/9KG
}
