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


if (\PHP_VERSION_ID < 80000 && extension_loaded('tokenizer')) {
    class PhpToken extends Symfony\Polyfill\Php80\PhpToken
    {
    }
}
