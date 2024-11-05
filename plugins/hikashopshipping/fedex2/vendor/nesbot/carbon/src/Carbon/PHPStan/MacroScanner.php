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


namespace Carbon\PHPStan;

use Carbon\CarbonInterface;
use PHPStan\Reflection\ReflectionProvider;
use ReflectionClass;
use ReflectionException;

final class MacroScanner
{
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function hasMethod(string $className, string $methodName): bool
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        if (
            $classReflection->getName() !== CarbonInterface::class &&
            !$classReflection->isSubclassOf(CarbonInterface::class)
        ) {
            return false;
        }

        return \is_callable([$className, 'hasMacro']) &&
            $className::hasMacro($methodName);
    }

    public function getMethod(string $className, string $methodName): Macro
    {
        $reflectionClass = new ReflectionClass($className);
        $property = $reflectionClass->getProperty('globalMacros');

        $property->setAccessible(true);
        $macro = $property->getValue()[$methodName];

        return new Macro(
            $className,
            $methodName,
            $macro
        );
    }
}
