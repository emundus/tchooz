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

use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Closure;
use Generator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

trait Mixin
{
    protected static $macroContextStack = [];

    public static function mixin($mixin)
    {
        \is_string($mixin) && trait_exists($mixin)
            ? self::loadMixinTrait($mixin)
            : self::loadMixinClass($mixin);
    }

    private static function loadMixinClass($mixin)
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            $method->setAccessible(true);

            static::macro($method->name, $method->invoke($mixin));
        }
    }

    private static function loadMixinTrait($trait)
    {
        $context = eval(self::getAnonymousClassCodeForTrait($trait));
        $className = \get_class($context);
        $baseClass = static::class;

        foreach (self::getMixableMethods($context) as $name) {
            $closureBase = Closure::fromCallable([$context, $name]);

            static::macro($name, function (...$parameters) use ($closureBase, $className, $baseClass) {
                $downContext = isset($this) ? ($this) : new $baseClass();
                $context = isset($this) ? $this->cast($className) : new $className();

                try {
                    $closure = @$closureBase->bindTo($context);
                } catch (Throwable $throwable) { // @codeCoverageIgnore
                    $closure = $closureBase; // @codeCoverageIgnore
                }

                $closure = $closure ?: $closureBase;

                $result = $closure(...$parameters);

                if (!($result instanceof $className)) {
                    return $result;
                }

                if ($downContext instanceof CarbonInterface && $result instanceof CarbonInterface) {
                    if ($context !== $result) {
                        $downContext = $downContext->copy();
                    }

                    return $downContext
                        ->setTimezone($result->getTimezone())
                        ->modify($result->format('Y-m-d H:i:s.u'))
                        ->settings($result->getSettings());
                }

                if ($downContext instanceof CarbonInterval && $result instanceof CarbonInterval) {
                    if ($context !== $result) {
                        $downContext = $downContext->copy();
                    }

                    $downContext->copyProperties($result);
                    self::copyStep($downContext, $result);
                    self::copyNegativeUnits($downContext, $result);

                    return $downContext->settings($result->getSettings());
                }

                if ($downContext instanceof CarbonPeriod && $result instanceof CarbonPeriod) {
                    if ($context !== $result) {
                        $downContext = $downContext->copy();
                    }

                    return $downContext
                        ->setDates($result->getStartDate(), $result->getEndDate())
                        ->setRecurrences($result->getRecurrences())
                        ->setOptions($result->getOptions())
                        ->settings($result->getSettings());
                }

                return $result;
            });
        }
    }

    private static function getAnonymousClassCodeForTrait(string $trait)
    {
        return 'return new class() extends '.static::class.' {use '.$trait.';};';
    }

    private static function getMixableMethods(self $context): Generator
    {
        foreach (get_class_methods($context) as $name) {
            if (method_exists(static::class, $name)) {
                continue;
            }

            yield $name;
        }
    }

    protected static function bindMacroContext($context, callable $callable)
    {
        static::$macroContextStack[] = $context;

        try {
            return $callable();
        } finally {
            array_pop(static::$macroContextStack);
        }
    }

    protected static function context()
    {
        return end(static::$macroContextStack) ?: null;
    }

    protected static function this()
    {
        return end(static::$macroContextStack) ?: new static();
    }
}
