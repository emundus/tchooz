<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2023 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class hikashopJoomlaPlugin extends Joomla\CMS\Plugin\CMSPlugin implements Joomla\Event\SubscriberInterface{
    public static function getSubscribedEvents(): array
    {
        $class = static::class;
        $dispatcher = new Joomla\Event\Dispatcher();
        $options = array();
        $reflectedObject = new \ReflectionObject(new $class($dispatcher, $options));
        $methods = $reflectedObject->getMethods(\ReflectionMethod::IS_PUBLIC);
        $events = array();
        foreach ($methods as $method) {
            if (substr($method->name, 0, 2) !== 'on') {
                continue;
            }
            $events[$method->name] = $method->name.'Handler';
        }
        return $events;
    }
    public function __call($name, $arguments) {
        if(substr($name,0, 2) == 'on' && substr($name,-7) == 'Handler') {
            $handler = substr($name, 0, -7);
            if(method_exists($this, $handler)) {
                if(count($arguments) == 1 && get_class($arguments[0]) == 'Joomla\Event\Event') {
                    $reflectedMethod = new \ReflectionMethod($this, $handler);
                    $reflectedParameters = $reflectedMethod->getParameters();
                    if(count($reflectedParameters)) {
                        $params = array();
                        foreach($reflectedParameters as $reflectedParameter) {
                            $parameterPosition = $reflectedParameter->getPosition();
                            if($reflectedParameter->isOptional() && !$arguments[0]->hasArgument($parameterPosition)) {
                                $params[] = $reflectedParameter->getDefaultValue();
                            } else {
                                $params[] = $arguments[0]->getArgument($parameterPosition);
                            }
                        }

                        $result = $this->$handler(...$params);

                        foreach($reflectedParameters as $reflectedParameter) {
                            if($reflectedParameter->isPassedByReference()) {
                                $parameterPosition = $reflectedParameter->getPosition();
                                $arguments[0]->setArgument($parameterPosition, $params[$parameterPosition]);
                            }
                        }
                        return $result;
                    }
                    return $this->$handler();
                }
                return $this->$handler(...$arguments);
            }
        }
        throw new Exception('Method '.$name.' does not exist in class '.get_class($this));
    }
}



