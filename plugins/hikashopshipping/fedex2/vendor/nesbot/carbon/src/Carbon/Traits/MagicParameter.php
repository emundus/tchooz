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


namespace Carbon\Traits;

trait MagicParameter
{
    private function getMagicParameter(array $parameters, int $index, string $key, $default)
    {
        if (\array_key_exists($index, $parameters)) {
            return $parameters[$index];
        }

        if (\array_key_exists($key, $parameters)) {
            return $parameters[$key];
        }

        return $default;
    }
}
