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

use Closure;
use InvalidArgumentException;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter as AdapterReflectionParameter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionType as AdapterReflectionType;
use PHPStan\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use PHPStan\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use PHPStan\Reflection\Php\BuiltinMethodReflection;
use PHPStan\TrinaryLogic;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;
use stdClass;
use Throwable;

abstract class AbstractMacro implements BuiltinMethodReflection
{
    protected $reflectionFunction;

    private $className;

    private $methodName;

    private $parameters;

    private $static = false;

    public function __construct(string $className, string $methodName, $macro)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $rawReflectionFunction = \is_array($macro)
            ? new ReflectionMethod($macro[0], $macro[1])
            : new ReflectionFunction($macro);
        $this->reflectionFunction = self::hasModernParser()
            ? $this->getReflectionFunction($macro)
            : $rawReflectionFunction; // @codeCoverageIgnore
        $this->parameters = array_map(
            function ($parameter) {
                if ($parameter instanceof BetterReflectionParameter) {
                    return new AdapterReflectionParameter($parameter);
                }

                return $parameter; // @codeCoverageIgnore
            },
            $this->reflectionFunction->getParameters()
        );

        if ($rawReflectionFunction->isClosure()) {
            try {
                $closure = $rawReflectionFunction->getClosure();
                $boundClosure = Closure::bind($closure, new stdClass());
                $this->static = (!$boundClosure || (new ReflectionFunction($boundClosure))->getClosureThis() === null);
            } catch (Throwable $e) {
                $this->static = true;
            }
        }
    }

    private function getReflectionFunction($spec)
    {
        if (\is_array($spec) && \count($spec) === 2 && \is_string($spec[1])) {
            \assert($spec[1] !== '');

            if (\is_object($spec[0])) {
                return BetterReflectionClass::createFromInstance($spec[0])
                    ->getMethod($spec[1]);
            }

            return BetterReflectionClass::createFromName($spec[0])
                ->getMethod($spec[1]);
        }

        if (\is_string($spec)) {
            return BetterReflectionFunction::createFromName($spec);
        }

        if ($spec instanceof Closure) {
            return BetterReflectionFunction::createFromClosure($spec);
        }

        throw new InvalidArgumentException('Could not create reflection from the spec given'); // @codeCoverageIgnore
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->className);
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function isFinal(): bool
    {
        return false;
    }

    public function isInternal(): bool
    {
        return false;
    }

    public function isAbstract(): bool
    {
        return false;
    }

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function getDocComment(): ?string
    {
        return $this->reflectionFunction->getDocComment() ?: null;
    }

    public function getName(): string
    {
        return $this->methodName;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getReturnType(): ?ReflectionType
    {
        $type = $this->reflectionFunction->getReturnType();

        if ($type instanceof ReflectionType) {
            return $type; // @codeCoverageIgnore
        }

        return self::adaptType($type);
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean(
            $this->reflectionFunction->isDeprecated() ||
            preg_match('/@deprecated/i', $this->getDocComment() ?: '')
        );
    }

    public function isVariadic(): bool
    {
        return $this->reflectionFunction->isVariadic();
    }

    public function getPrototype(): BuiltinMethodReflection
    {
        return $this;
    }

    public function getTentativeReturnType(): ?ReflectionType
    {
        return null;
    }

    public function returnsByReference(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    private static function adaptType($type)
    {
        $method = method_exists(AdapterReflectionType::class, 'fromTypeOrNull')
            ? 'fromTypeOrNull'
            : 'fromReturnTypeOrNull'; // @codeCoverageIgnore

        return AdapterReflectionType::$method($type);
    }

    private static function hasModernParser(): bool
    {
        static $modernParser = null;

        if ($modernParser !== null) {
            return $modernParser;
        }

        $modernParser = method_exists(AdapterReflectionType::class, 'fromTypeOrNull');

        return $modernParser;
    }
}
