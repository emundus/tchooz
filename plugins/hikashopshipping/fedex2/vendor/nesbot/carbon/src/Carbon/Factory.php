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


namespace Carbon;

use Closure;
use DateTimeInterface;
use ReflectionMethod;

class Factory
{
    protected $className = Carbon::class;

    protected $settings = [];

    public function __construct(array $settings = [], ?string $className = null)
    {
        if ($className) {
            $this->className = $className;
        }

        $this->settings = $settings;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function setClassName(string $className)
    {
        $this->className = $className;

        return $this;
    }

    public function className(string $className = null)
    {
        return $className === null ? $this->getClassName() : $this->setClassName($className);
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    public function settings(array $settings = null)
    {
        return $settings === null ? $this->getSettings() : $this->setSettings($settings);
    }

    public function mergeSettings(array $settings)
    {
        $this->settings = array_merge($this->settings, $settings);

        return $this;
    }

    public function __call($name, $arguments)
    {
        $method = new ReflectionMethod($this->className, $name);
        $settings = $this->settings;

        if ($settings && isset($settings['timezone'])) {
            $tzParameters = array_filter($method->getParameters(), function ($parameter) {
                return \in_array($parameter->getName(), ['tz', 'timezone'], true);
            });

            if (isset($arguments[0]) && \in_array($name, ['instance', 'make', 'create', 'parse'], true)) {
                if ($arguments[0] instanceof DateTimeInterface) {
                    $settings['innerTimezone'] = $settings['timezone'];
                } elseif (\is_string($arguments[0]) && date_parse($arguments[0])['is_localtime']) {
                    unset($settings['timezone'], $settings['innerTimezone']);
                }
            } elseif (\count($tzParameters)) {
                array_splice($arguments, key($tzParameters), 0, [$settings['timezone']]);
                unset($settings['timezone']);
            }
        }

        $result = $this->className::$name(...$arguments);

        return $result instanceof CarbonInterface && !empty($settings)
            ? $result->settings($settings)
            : $result;
    }
}
