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

trait Macro
{
    use Mixin;

    protected static $globalMacros = [];

    protected static $globalGenericMacros = [];

    public static function macro($name, $macro)
    {
        static::$globalMacros[$name] = $macro;
    }

    public static function resetMacros()
    {
        static::$globalMacros = [];
        static::$globalGenericMacros = [];
    }

    public static function genericMacro($macro, $priority = 0)
    {
        if (!isset(static::$globalGenericMacros[$priority])) {
            static::$globalGenericMacros[$priority] = [];
            krsort(static::$globalGenericMacros, SORT_NUMERIC);
        }

        static::$globalGenericMacros[$priority][] = $macro;
    }

    public static function hasMacro($name)
    {
        return isset(static::$globalMacros[$name]);
    }

    public static function getMacro($name)
    {
        return static::$globalMacros[$name] ?? null;
    }

    public function hasLocalMacro($name)
    {
        return ($this->localMacros && isset($this->localMacros[$name])) || static::hasMacro($name);
    }

    public function getLocalMacro($name)
    {
        return ($this->localMacros ?? [])[$name] ?? static::getMacro($name);
    }
}
