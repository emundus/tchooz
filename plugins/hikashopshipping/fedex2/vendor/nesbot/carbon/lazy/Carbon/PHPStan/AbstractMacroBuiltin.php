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

use PHPStan\BetterReflection\Reflection;
use ReflectionMethod;

if (!class_exists(AbstractReflectionMacro::class, false)) {
    abstract class AbstractReflectionMacro extends AbstractMacro
    {
        public function getReflection(): ?ReflectionMethod
        {
            if ($this->reflectionFunction instanceof Reflection\ReflectionMethod) {
                return new Reflection\Adapter\ReflectionMethod($this->reflectionFunction);
            }

            return $this->reflectionFunction instanceof ReflectionMethod
                ? $this->reflectionFunction
                : null;
        }
    }
}
