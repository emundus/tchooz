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

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\TypehintHelper;

final class MacroExtension implements MethodsClassReflectionExtension
{
    protected $methodReflectionFactory;

    protected $scanner;

    public function __construct(
        PhpMethodReflectionFactory $methodReflectionFactory,
        ReflectionProvider $reflectionProvider
    ) {
        $this->scanner = new MacroScanner($reflectionProvider);
        $this->methodReflectionFactory = $methodReflectionFactory;
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->scanner->hasMethod($classReflection->getName(), $methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $builtinMacro = $this->scanner->getMethod($classReflection->getName(), $methodName);
        $supportAssertions = class_exists(Assertions::class);

        return $this->methodReflectionFactory->create(
            $classReflection,
            null,
            $builtinMacro,
            $classReflection->getActiveTemplateTypeMap(),
            [],
            TypehintHelper::decideTypeFromReflection($builtinMacro->getReturnType()),
            null,
            null,
            $builtinMacro->isDeprecated()->yes(),
            $builtinMacro->isInternal(),
            $builtinMacro->isFinal(),
            $supportAssertions ? null : $builtinMacro->getDocComment(),
            $supportAssertions ? Assertions::createEmpty() : null,
            null,
            $builtinMacro->getDocComment(),
            []
        );
    }
}
