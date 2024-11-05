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


$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Symfony\\Polyfill\\Php80\\' => array($vendorDir . '/symfony/polyfill-php80'),
    'Symfony\\Polyfill\\Mbstring\\' => array($vendorDir . '/symfony/polyfill-mbstring'),
    'Symfony\\Contracts\\Translation\\' => array($vendorDir . '/symfony/translation-contracts'),
    'Symfony\\Component\\Translation\\' => array($vendorDir . '/symfony/translation'),
    'Psr\\Http\\Message\\' => array($vendorDir . '/psr/http-message/src', $vendorDir . '/psr/http-factory/src'),
    'Psr\\Http\\Client\\' => array($vendorDir . '/psr/http-client/src'),
    'Psr\\Clock\\' => array($vendorDir . '/psr/clock/src'),
    'GuzzleHttp\\Psr7\\' => array($vendorDir . '/guzzlehttp/psr7/src'),
    'GuzzleHttp\\Promise\\' => array($vendorDir . '/guzzlehttp/promises/src'),
    'GuzzleHttp\\' => array($vendorDir . '/guzzlehttp/guzzle/src'),
    'FedexRest\\Tests\\' => array($vendorDir . '/whatarmy/fedex-rest/tests/FedexRest/Tests'),
    'FedexRest\\' => array($vendorDir . '/whatarmy/fedex-rest/src/FedexRest'),
    'Carbon\\Doctrine\\' => array($vendorDir . '/carbonphp/carbon-doctrine-types/src/Carbon/Doctrine'),
    'Carbon\\' => array($vendorDir . '/nesbot/carbon/src/Carbon'),
);
