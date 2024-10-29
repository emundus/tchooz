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

if (!class_exists(LazyMacro::class, false)) {
    abstract class LazyMacro extends AbstractReflectionMacro
    {
        public function getFileName(): ?string
        {
            $file = $this->reflectionFunction->getFileName();

            return (($file ? realpath($file) : null) ?: $file) ?: null;
        }

        public function getStartLine(): ?int
        {
            return $this->reflectionFunction->getStartLine();
        }

        public function getEndLine(): ?int
        {
            return $this->reflectionFunction->getEndLine();
        }
    }
}
