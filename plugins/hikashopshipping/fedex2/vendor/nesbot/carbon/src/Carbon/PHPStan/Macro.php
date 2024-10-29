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

declare(strict_types=1);


namespace Carbon\PHPStan;

use PHPStan\BetterReflection\Reflection\Adapter;
use PHPStan\Reflection\Php\BuiltinMethodReflection;
use ReflectionMethod;

$method = new ReflectionMethod(BuiltinMethodReflection::class, 'getReflection');

require $method->hasReturnType() && $method->getReturnType()->getName() === Adapter\ReflectionMethod::class
    ? __DIR__.'/../../../lazy/Carbon/PHPStan/AbstractMacroStatic.php'
    : __DIR__.'/../../../lazy/Carbon/PHPStan/AbstractMacroBuiltin.php';

$method = new ReflectionMethod(BuiltinMethodReflection::class, 'getFileName');

require $method->hasReturnType()
    ? __DIR__.'/../../../lazy/Carbon/PHPStan/MacroStrongType.php'
    : __DIR__.'/../../../lazy/Carbon/PHPStan/MacroWeakType.php';

final class Macro extends LazyMacro
{
}
